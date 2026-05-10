<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\ProductionLine;
use App\Models\DailyProgram;
use App\Models\Schedule;
use App\Models\Strike;
use App\Services\KPIService;
use App\Services\DailyProgramService;
use App\Services\ProductionLineService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OperadorController extends Controller
{
    protected $kpiService;
    protected $dailyProgramService;
    protected $productionLineService;

    public function __construct(
        KPIService $kpiService,
        DailyProgramService $dailyProgramService,
        ProductionLineService $productionLineService
    ) {
        $this->kpiService = $kpiService;
        $this->dailyProgramService = $dailyProgramService;
        $this->productionLineService = $productionLineService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        
        $productionLines = $this->productionLineService->getUserProductionLines($user);
        
        if ($productionLines->isEmpty()) {
            return Inertia::render('Operador/NoProductionLines');
        }
        
        $selectedLineId = $request->get('production_line_id');
        
        if ($selectedLineId) {
            session(['selected_production_line_id' => $selectedLineId]);
        } else {
            $selectedLineId = session('selected_production_line_id', $productionLines->first()->id);
        }
        
        $selectedLine = ProductionLine::with('workCenter')->findOrFail($selectedLineId);
        
        // Forzar fecha actual
        $selectedDate = $request->get('date', now()->format('Y-m-d'));
        $selectedShift = $request->get('shift', 'matutino');
        
        $dailyProgram = DailyProgram::with(['schedules', 'strikes'])
            ->where('id_work_center', $selectedLine->id_work_center)
            ->where('date', $selectedDate)
            ->where('shift', $selectedShift)
            ->first();
        
        $kpis = null;
        $schedules = collect();
        $strikes = collect();
        
        if ($dailyProgram) {
            $schedules = $dailyProgram->schedules()
                ->where('id_production_line', $selectedLineId)
                ->orderBy('start_time')
                ->get();
            
            $strikes = $dailyProgram->strikes()
                ->where('id_production_lines', $selectedLineId)
                ->orderBy('start_time')
                ->get();
            
            $kpis = $this->kpiService->calculateLineKPIs($dailyProgram, $selectedLine, $schedules, $strikes);
        }
        
        return Inertia::render('Operador/Dashboard', [
            'productionLines' => $productionLines,
            'selectedLine' => $selectedLine,
            'selectedDate' => $selectedDate,
            'selectedShift' => $selectedShift,
            'dailyProgram' => $dailyProgram,
            'schedules' => $schedules,
            'strikes' => $strikes,
            'kpis' => $kpis,
        ]);
    }
    
    public function updateScheduleProduction(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'produced' => 'required|integer|min:0',
        ]);
        
        $schedule = Schedule::findOrFail($request->schedule_id);
        $user = auth()->user();
        
        if (!$user->canEditProductionLine($schedule->id_production_line)) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para editar esta línea'], 403);
        }
        
        $schedule->update(['produced' => $request->produced]);
        
        $this->dailyProgramService->updateTotalProduced($schedule->id_daily_program);
        
        $dailyProgram = DailyProgram::with(['schedules', 'strikes', 'workCenter'])
            ->findOrFail($schedule->id_daily_program);
        
        $productionLine = ProductionLine::findOrFail($schedule->id_production_line);
        
        $schedules = $dailyProgram->schedules()
            ->where('id_production_line', $schedule->id_production_line)
            ->get();
        
        $strikes = $dailyProgram->strikes()
            ->where('id_production_lines', $schedule->id_production_line)
            ->get();
        
        $kpis = $this->kpiService->calculateLineKPIs($dailyProgram, $productionLine, $schedules, $strikes);
        
        return response()->json([
            'success' => true,
            'kpis' => $kpis,
            'total_produced' => $schedules->sum('produced')
        ]);
    }
    
    public function storeStrike(Request $request)
    {
        $request->validate([
            'id_production_line' => 'required|exists:production_lines,id',
            'id_daily_program' => 'required|exists:daily_programs,id',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'nullable',
            'description' => 'required|string',
        ]);
        
        $user = auth()->user();
        
        if (!$user->canEditProductionLine($request->id_production_line)) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para registrar paros en esta línea'], 403);
        }
        
        // Asegurar formato HH:MM:SS
        $startTime = $request->start_time;
        if (strlen($startTime) == 5) {
            $startTime = $startTime . ':00';
        }
        
        $endTime = $request->end_time;
        if ($endTime && strlen($endTime) == 5) {
            $endTime = $endTime . ':00';
        }
        
        $strike = Strike::create([
            'id_production_lines' => $request->id_production_line,
            'id_daily_program' => $request->id_daily_program,
            'date' => $request->date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'description' => $request->description,
            'minutes' => 0,
            'cost' => 0
        ]);
        
        return response()->json([
            'success' => true, 
            'strike' => $strike->load('productionLine'),
            'message' => 'Paro registrado correctamente'
        ]);
    }
    
    public function endStrike(Request $request, $id)
    {
        try {
            $strike = Strike::findOrFail($id);
            
            $user = auth()->user();
            
            if (!$user->canEditProductionLine($strike->id_production_lines)) {
                return response()->json(['success' => false, 'message' => 'No tienes permiso para finalizar este paro'], 403);
            }
            
            $endTime = $request->end_time;
            if (strlen($endTime) == 5) {
                $endTime = $endTime . ':00';
            }
            
            // Calcular minutos manualmente
            $startParts = explode(':', $strike->start_time);
            $endParts = explode(':', $endTime);
            
            $startMinutes = (int)$startParts[0] * 60 + (int)$startParts[1];
            $endMinutes = (int)$endParts[0] * 60 + (int)$endParts[1];
            
            $minutes = $endMinutes - $startMinutes;
            if ($minutes < 0) {
                $minutes += 1440;
            }
            
            // Calcular costo manualmente
            $productionLine = ProductionLine::find($strike->id_production_lines);
            $costoPorMinuto = $productionLine ? floatval($productionLine->cost) : 0;
            $cost = $costoPorMinuto * $minutes;
            
            // Actualizar el strike con minutos, costo y end_time
            $strike->update([
                'end_time' => $endTime,
                'minutes' => $minutes,
                'cost' => $cost
            ]);
            
            // Actualizar KPIs del programa diario
            $this->dailyProgramService->updateTotalProduced($strike->id_daily_program);
            
            return response()->json([
                'success' => true,
                'strike' => $strike,
                'minutes' => $minutes,
                'cost' => $cost,
                'message' => 'Paro finalizado correctamente'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function getStrikesByProgram($dailyProgramId)
    {
        $strikes = DB::table('strikes')
            ->join('production_lines', 'strikes.id_production_lines', '=', 'production_lines.id')
            ->select('strikes.*', 'production_lines.title as production_line_title')
            ->where('id_daily_program', $dailyProgramId)
            ->orderBy('start_time', 'desc')
            ->get();
        
        // Asegurar que start_time y end_time sean solo hora
        foreach ($strikes as $strike) {
            if ($strike->start_time && strlen($strike->start_time) > 8) {
                $strike->start_time = substr($strike->start_time, -8);
            }
            if ($strike->end_time && strlen($strike->end_time) > 8) {
                $strike->end_time = substr($strike->end_time, -8);
            }
            // Asegurar formato HH:MM para mostrar
            if ($strike->start_time && strlen($strike->start_time) >= 5) {
                $strike->start_time = substr($strike->start_time, 0, 5);
            }
            if ($strike->end_time && strlen($strike->end_time) >= 5) {
                $strike->end_time = substr($strike->end_time, 0, 5);
            }
        }
        
        return response()->json($strikes);
    }
    
    public function getProductionData(Request $request)
    {
        $lineId = $request->get('production_line_id');
        $date = $request->get('date', now()->format('Y-m-d'));
        $shift = $request->get('shift', 'matutino');
        
        $user = auth()->user();
        
        if (!$user->canViewProductionLine($lineId)) {
            return response()->json(['error' => 'No tienes acceso a esta línea'], 403);
        }
        
        $productionLine = ProductionLine::with('workCenter')->findOrFail($lineId);
        
        $dailyProgram = DailyProgram::with(['schedules', 'strikes'])
            ->where('id_work_center', $productionLine->id_work_center)
            ->where('date', $date)
            ->where('shift', $shift)
            ->first();
        
        if (!$dailyProgram) {
            return response()->json([
                'production_line' => $productionLine,
                'daily_program' => null,
                'kpis' => null,
                'schedules' => [],
                'strikes' => [],
            ]);
        }
        
        $schedules = $dailyProgram->schedules()
            ->where('id_production_line', $lineId)
            ->orderBy('start_time')
            ->get();
        
        $strikes = $dailyProgram->strikes()
            ->where('id_production_lines', $lineId)
            ->orderBy('start_time')
            ->get();
        
        $kpis = $this->kpiService->calculateLineKPIs($dailyProgram, $productionLine, $schedules, $strikes);
        
        return response()->json([
            'production_line' => $productionLine,
            'daily_program' => $dailyProgram,
            'kpis' => $kpis,
            'schedules' => $schedules,
            'strikes' => $strikes,
        ]);
    }
}