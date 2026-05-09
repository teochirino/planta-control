<?php

namespace App\Http\Controllers;

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
            return view('operador.no-production-lines');
        }
        
        $selectedLineId = $request->get('production_line_id');
        
        if ($selectedLineId) {
            session(['selected_production_line_id' => $selectedLineId]);
        } else {
            $selectedLineId = session('selected_production_line_id', $productionLines->first()->id);
        }
        
        $selectedLine = ProductionLine::with('workCenter')->findOrFail($selectedLineId);
        
        $selectedDate = $request->get('date');
        if ($selectedDate) {
            session(['selected_date' => $selectedDate]);
        } else {
            $selectedDate = session('selected_date', now()->format('Y-m-d'));
        }
        
        $selectedShift = $request->get('shift');
        if ($selectedShift) {
            session(['selected_shift' => $selectedShift]);
        } else {
            $selectedShift = session('selected_shift', 'matutino');
        }
        
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
        
        return view('operador.dashboard', compact(
            'productionLines',
            'selectedLine',
            'selectedDate',
            'selectedShift',
            'dailyProgram',
            'schedules',
            'strikes',
            'kpis'
        ));
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
        
        $strike = Strike::create([
            'id_production_lines' => $request->id_production_line,
            'id_daily_program' => $request->id_daily_program,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'description' => $request->description,
        ]);
        
        return response()->json([
            'success' => true, 
            'strike' => $strike->load('productionLine'),
            'message' => 'Paro registrado correctamente'
        ]);
    }
    
    public function endStrike(Request $request, Strike $strike)
    {
        $request->validate([
            'end_time' => 'required',
        ]);
        
        $user = auth()->user();
        
        if (!$user->canEditProductionLine($strike->id_production_lines)) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para finalizar este paro'], 403);
        }
        
        $strike->update(['end_time' => $request->end_time]);
        
        return response()->json([
            'success' => true,
            'strike' => $strike,
            'message' => 'Paro finalizado correctamente'
        ]);
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
