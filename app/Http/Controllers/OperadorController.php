<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\ProductionLine;
use App\Models\DailyProgram;
use App\Models\Schedule;
use App\Models\Strike;
use App\Models\NotificationRecipient;
use App\Mail\MachineBreakdownNotification;
use App\Services\KPIService;
use App\Services\DailyProgramService;
use App\Services\ProductionLineService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

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
        
        $dailyProgram = DailyProgram::with(['schedules', 'strikes', 'operatorLineClosures'])
            ->where('id_work_center', $selectedLine->id_work_center)
            ->where('date', $selectedDate)
            ->where('shift', $selectedShift)
            ->first();

        $kpis = null;
        $schedules = collect();
        $strikes = collect();
        $lineClosure = null;

        if ($dailyProgram) {
            $schedules = $dailyProgram->schedules()
                ->where('id_production_line', $selectedLineId)
                ->orderBy('start_time')
                ->get();

            $strikes = $dailyProgram->strikes()
                ->where('id_production_lines', $selectedLineId)
                ->orderBy('start_time')
                ->get();

            $lineClosure = $dailyProgram->operatorLineClosures()
                ->where('id_production_line', $selectedLineId)
                ->first();

            $kpis = $this->kpiService->calculateLineKPIs($dailyProgram, $selectedLine, $schedules, $strikes);
        }

        // Obtener máquinas del centro de trabajo
        $machines = \App\Models\Machine::where('id_work_center', $selectedLine->id_work_center)->get();
        
        return Inertia::render('Operador/Dashboard', [
            'productionLines' => $productionLines,
            'selectedLine' => $selectedLine,
            'selectedDate' => $selectedDate,
            'selectedShift' => $selectedShift,
            'dailyProgram' => $dailyProgram,
            'schedules' => $schedules,
            'strikes' => $strikes,
            'kpis' => $kpis,
            'lineClosure' => $lineClosure,
            'machines' => $machines,
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
            'id_machine' => 'nullable|exists:machines,id',
        ]);
        
        $user = auth()->user();
        
        if (!$user->canEditProductionLine($request->id_production_line)) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para registrar paros en esta línea'], 403);
        }
        
        \Log::info('Operador: Registrando paro', [
            'id_machine' => $request->id_machine,
            'all_data' => $request->all()
        ]);
        
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
            'cost' => 0,
            'id_machine' => $request->id_machine,
        ]);
        
        \Log::info('Operador: Strike creado', [
            'strike_id' => $strike->id,
            'strike_id_machine' => $strike->id_machine
        ]);
        
        // Si se seleccionó una máquina, crear breakdown y actualizar estado
        if ($request->id_machine) {
            \App\Models\Breakdown::create([
                'id_machine' => $request->id_machine,
                'id_user' => auth()->id(),
                'reason' => $request->description,
                'start_date' => now(),
            ]);
            
            // Actualizar estado de máquina a averiado
            \App\Models\Machine::where('id', $request->id_machine)->update(['state' => 'averiado']);

            // Enviar notificación por email a Mantenimiento
            $recipients = NotificationRecipient::where('name', 'Mantenimiento')
                ->where('is_active', true)
                ->get();

            if ($recipients->isNotEmpty()) {
                $machine = \App\Models\Machine::with('workCenter')->find($request->id_machine);
                $productionLine = ProductionLine::find($request->id_production_line);
                $user = auth()->user();

                foreach ($recipients as $recipient) {
                    Mail::to($recipient->email)->send(new MachineBreakdownNotification(
                        $machine->title,
                        $machine->workCenter->name,
                        $productionLine->title,
                        $user->name,
                        $request->description,
                        $startTime
                    ));
                }
            }
        }
        
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
            
            // Si el strike tiene una máquina asociada, finalizar breakdown y actualizar estado
            if ($strike->id_machine) {
                $breakdown = \App\Models\Breakdown::where('id_machine', $strike->id_machine)
                    ->whereNull('end_date')
                    ->first();
                
                if ($breakdown) {
                    $breakdown->update([
                        'end_date' => now(),
                    ]);
                }
                
                // Actualizar estado de máquina a operativo
                \App\Models\Machine::where('id', $strike->id_machine)->update(['state' => 'operativo']);
            }
            
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
    
    // Cerrar turno (operador)
    public function closeShift(Request $request)
    {
        $request->validate([
            'daily_program_id' => 'required|exists:daily_programs,id',
            'production_line_id' => 'required|exists:production_lines,id',
        ]);

        $dailyProgram = DailyProgram::findOrFail($request->daily_program_id);
        $productionLine = ProductionLine::findOrFail($request->production_line_id);

        // Verificar que el operador tenga acceso a esta línea de producción
        $user = auth()->user();
        $hasAccess = $user->productionLines()
            ->where('production_lines.id', $productionLine->id)
            ->exists();

        if (!$hasAccess) {
            return response()->json(['success' => false, 'message' => 'No tienes acceso a esta línea de producción'], 403);
        }

        // Verificar que la línea pertenezca al centro de trabajo del programa
        if ($productionLine->id_work_center != $dailyProgram->id_work_center) {
            return response()->json(['success' => false, 'message' => 'La línea no pertenece al centro de trabajo del programa'], 403);
        }

        // Verificar si ya está cerrada
        $existingClosure = \App\Models\OperatorLineClosure::where('id_daily_program', $dailyProgram->id)
            ->where('id_production_line', $productionLine->id)
            ->first();

        if ($existingClosure) {
            return response()->json(['success' => false, 'message' => 'Esta línea ya está cerrada'], 400);
        }

        // Guardar cierre de línea
        \App\Models\OperatorLineClosure::create([
            'id_daily_program' => $dailyProgram->id,
            'id_production_line' => $productionLine->id,
            'closed_by' => $user->id,
            'closed_at' => now(),
        ]);

        // Verificar si todas las líneas del programa están cerradas
        $totalLines = ProductionLine::where('id_work_center', $dailyProgram->id_work_center)->count();
        $closedLines = \App\Models\OperatorLineClosure::where('id_daily_program', $dailyProgram->id)->count();

        // Si todas las líneas están cerradas, marcar el programa como cerrado
        if ($closedLines >= $totalLines) {
            $dailyProgram->update([
                'operator_closed' => true,
                'operator_closed_at' => now(),
                'operator_closed_by' => $user->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Línea cerrada correctamente. El supervisor revisará el balance.',
            'closed_lines' => $closedLines,
            'total_lines' => $totalLines,
            'all_closed' => $closedLines >= $totalLines,
        ]);
    }

    public function informationPanel(Request $request)
    {
        $user = auth()->user();
        
        // Obtener líneas de producción asignadas al operador
        $productionLines = $this->productionLineService->getUserProductionLines($user);
        
        if ($productionLines->isEmpty()) {
            return Inertia::render('Operador/NoProductionLines');
        }
        
        // Obtener centros de trabajo únicos basados en las líneas asignadas
        $workCenterIds = $productionLines->pluck('id_work_center')->unique();
        $workCenters = \App\Models\WorkCenter::whereIn('id', $workCenterIds)->get();
        
        // Obtener parámetros de selección
        $selectedWorkCenterId = $request->get('work_center_id');
        $selectedDate = $request->get('date', now()->format('Y-m-d'));
        $selectedShift = $request->get('shift', 'matutino');
        
        // Si no se seleccionó centro, usar el primero
        if (!$selectedWorkCenterId && $workCenters->isNotEmpty()) {
            $selectedWorkCenterId = $workCenters->first()->id;
        }
        
        $selectedWorkCenter = null;
        $dailyProgram = null;
        $productionLinesForCenter = collect();
        $allKPIs = collect();
        $centerKPIs = null;
        
        if ($selectedWorkCenterId) {
            $selectedWorkCenter = $workCenters->where('id', $selectedWorkCenterId)->first();
            
            if ($selectedWorkCenter) {
                // Obtener líneas de producción de este centro que el operador tiene asignadas
                $productionLinesForCenter = $productionLines->where('id_work_center', $selectedWorkCenterId);
                
                // Obtener programa diario
                $dailyProgram = DailyProgram::with(['schedules', 'strikes'])
                    ->where('id_work_center', $selectedWorkCenterId)
                    ->where('date', $selectedDate)
                    ->where('shift', $selectedShift)
                    ->first();
                
                if ($dailyProgram) {
                    // Calcular KPIs a nivel de centro de trabajo (similar al supervisor)
                    $allSchedules = $dailyProgram->schedules;
                    $allStrikes = $dailyProgram->strikes;
                    
                    // Calcular KPIs del centro
                    $centerKPIs = $this->kpiService->calculateCenterKPIs($dailyProgram, $selectedWorkCenter);
                    
                    // Calcular KPIs individuales por línea para mostrar detalles
                    foreach ($productionLinesForCenter as $line) {
                        $schedules = $dailyProgram->schedules()
                            ->where('id_production_line', $line->id)
                            ->orderBy('start_time')
                            ->get();
                        
                        $strikes = $dailyProgram->strikes()
                            ->where('id_production_lines', $line->id)
                            ->orderBy('start_time')
                            ->get();
                        
                        $kpis = $this->kpiService->calculateLineKPIs($dailyProgram, $line, $schedules, $strikes);
                        
                        $allKPIs->push([
                            'line' => $line,
                            'kpis' => $kpis,
                            'schedules' => $schedules,
                            'strikes' => $strikes,
                        ]);
                    }
                }
            }
        }
        
        return Inertia::render('Operador/InformationPanel', [
            'workCenters' => $workCenters,
            'productionLines' => $productionLines,
            'selectedWorkCenter' => $selectedWorkCenter,
            'selectedDate' => $selectedDate,
            'selectedShift' => $selectedShift,
            'dailyProgram' => $dailyProgram,
            'productionLinesForCenter' => $productionLinesForCenter,
            'allKPIs' => $allKPIs,
            'centerKPIs' => $centerKPIs,
        ]);
    }
}