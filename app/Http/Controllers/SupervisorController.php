<?php

namespace App\Http\Controllers;

use App\Models\WorkCenter;
use App\Models\ProductionLine;
use App\Models\DailyProgram;
use App\Models\Schedule;
use App\Models\Strike;
use App\Models\ProductionAdjustment;
use App\Models\Program;
use App\Models\NotificationRecipient;
use App\Mail\MachineBreakdownNotification;
use App\Services\BalanceService;
use App\Services\KPIService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class SupervisorController extends Controller
{
    protected $balanceService;
    protected $kpiService;

    public function __construct(BalanceService $balanceService, KPIService $kpiService)
    {
        $this->balanceService = $balanceService;
        $this->kpiService = $kpiService;
    }

    // Dashboard principal del supervisor
    public function index(Request $request)
    {
        $user = auth()->user();
        $workCenters = $user->workCenters;
        
        if ($workCenters->isEmpty()) {
            return Inertia::render('Supervisor/NoWorkCenters');
        }
        
        // Centro de trabajo seleccionado
        $selectedWorkCenterId = $request->get('work_center_id');
        if (!$selectedWorkCenterId) {
            $selectedWorkCenterId = $workCenters->first()->id;
        }
        
        $selectedWorkCenter = WorkCenter::with(['productionLines', 'attributes'])->findOrFail($selectedWorkCenterId);
        
        // Fecha del día en curso en la planta (México), no la del servidor (UTC) ni la del navegador del supervisor.
        $selectedDate = now('America/Mexico_City')->format('Y-m-d');
        $selectedShift = $request->get('shift', 'matutino');

        // Obtener programa diario del centro
        $dailyProgram = DailyProgram::with(['schedules', 'strikes', 'program'])
            ->where('id_work_center', $selectedWorkCenterId)
            ->where('date', $selectedDate)
            ->where('shift', $selectedShift)
            ->first();

        $kpis = null;
        if ($dailyProgram) {
            $kpis = $this->calculateCenterKPIs($dailyProgram, $selectedWorkCenter);
        }

        return Inertia::render('Supervisor/Dashboard', [
            'workCenters' => $workCenters,
            'selectedWorkCenter' => $selectedWorkCenter,
            'selectedDate' => $selectedDate,
            'selectedShift' => $selectedShift,
            'dailyProgram' => $dailyProgram,
            'kpis' => $kpis,
            'attributes' => $selectedWorkCenter->attributes,
        ]);
    }
    
    // Panel de Información del supervisor (similar al operador pero con centros asignados directamente)
    public function informationPanel(Request $request)
    {
        $user = auth()->user();
        
        // Obtener centros de trabajo asignados al supervisor
        $workCenters = $user->workCenters;
        
        if ($workCenters->isEmpty()) {
            return Inertia::render('Supervisor/NoWorkCenters');
        }
        
        // Obtener parámetros de selección
        $selectedWorkCenterId = $request->get('work_center_id');
        $selectedDate = $request->get('date', now('America/Mexico_City')->format('Y-m-d'));
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
                // Obtener todas las líneas de producción de este centro
                $productionLinesForCenter = ProductionLine::where('id_work_center', $selectedWorkCenterId)->get();
                
                // Obtener programa diario
                $dailyProgram = DailyProgram::with(['schedules', 'strikes', 'program'])
                    ->where('id_work_center', $selectedWorkCenterId)
                    ->where('date', $selectedDate)
                    ->where('shift', $selectedShift)
                    ->first();
                
                if ($dailyProgram) {
                    // Calcular KPIs a nivel de centro de trabajo
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
        
        return Inertia::render('Supervisor/InformationPanel', [
            'workCenters' => $workCenters,
            'productionLines' => $productionLinesForCenter,
            'selectedWorkCenter' => $selectedWorkCenter,
            'selectedDate' => $selectedDate,
            'selectedShift' => $selectedShift,
            'dailyProgram' => $dailyProgram,
            'productionLinesForCenter' => $productionLinesForCenter,
            'allKPIs' => $allKPIs,
            'centerKPIs' => $centerKPIs,
        ]);
    }

    // Panel TV - Vista optimizada para pantallas grandes con diseño específico
    public function tvPanels(Request $request)
    {
        $user = auth()->user();

        // Obtener centros de trabajo asignados al supervisor
        $workCenters = $user->workCenters;

        if ($workCenters->isEmpty()) {
            return Inertia::render('Supervisor/NoWorkCenters');
        }

        // Obtener parámetros de selección
        $selectedWorkCenterId = $request->get('work_center_id');
        $selectedDate = $request->get('date', now('America/Mexico_City')->format('Y-m-d'));
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
                // Cargar attributes del centro de trabajo
                $selectedWorkCenter->load('attributes');

                // Obtener todas las líneas de producción de este centro
                $productionLinesForCenter = ProductionLine::where('id_work_center', $selectedWorkCenterId)->get();

                // Obtener programa diario
                $dailyProgram = DailyProgram::with(['schedules', 'strikes', 'program'])
                    ->where('id_work_center', $selectedWorkCenterId)
                    ->where('date', $selectedDate)
                    ->where('shift', $selectedShift)
                    ->first();

                if ($dailyProgram) {
                    // Calcular KPIs a nivel de centro de trabajo
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

                    // Obtener schedules existentes agrupados por hora y línea para la tabla de producción por hora
                    $schedulesMap = $dailyProgram->schedules
                        ->keyBy(function($schedule) {
                            return $schedule->start_time . '-' . $schedule->id_production_line;
                        });
                } else {
                    $schedulesMap = collect();
                }
            }
        }

        // Generar horarios según el turno
        $startTime = $selectedShift === 'matutino' ? '08:00' : '17:00';
        $hours = $this->generateHourlySchedule($startTime, 9);

        // Obtener historial de cumplimiento de los últimos 3 días
        $recentHistory = $this->getRecentComplianceHistory($selectedWorkCenterId, $selectedShift);

        return Inertia::render('Supervisor/TVPanels', [
            'workCenters' => $workCenters,
            'productionLines' => $productionLinesForCenter,
            'selectedWorkCenter' => $selectedWorkCenter,
            'selectedDate' => $selectedDate,
            'selectedShift' => $selectedShift,
            'dailyProgram' => $dailyProgram,
            'productionLinesForCenter' => $productionLinesForCenter,
            'allKPIs' => $allKPIs,
            'centerKPIs' => $centerKPIs,
            'attributes' => $selectedWorkCenter ? $selectedWorkCenter->attributes : collect(),
            'hours' => $hours,
            'existingSchedules' => $schedulesMap ?? collect(),
            'recentHistory' => $recentHistory,
        ]);
    }

    // Registro diario de producción
    public function dailyProduction(Request $request)
    {
        $user = auth()->user();
        $workCenterId = $request->get('work_center_id');
        
        if (!$workCenterId) {
            $firstCenter = $user->workCenters->first();
            if (!$firstCenter) {
                return redirect()->route('supervisor.dashboard')
                    ->with('error', 'No tienes centros de trabajo asignados.');
            }
            $workCenterId = $firstCenter->id;
        }
        
        // Verificar que el usuario tenga acceso a este centro
        if (!$user->canViewWorkCenter($workCenterId)) {
            return redirect()->route('supervisor.dashboard')
                ->with('error', 'No tienes acceso a este centro de trabajo.');
        }
        
        // Fecha del día en curso en la planta (México), no la del servidor (UTC).
        $date = now('America/Mexico_City')->format('Y-m-d');
        $shift = $request->get('shift', 'matutino');

        $workCenter = WorkCenter::with('productionLines')->findOrFail($workCenterId);
        $productionLines = $workCenter->productionLines;
        
        // Obtener o crear daily_program del centro
        $dailyProgram = DailyProgram::with(['schedules', 'strikes'])
            ->where('date', $date)
            ->where('id_work_center', $workCenterId)
            ->where('shift', $shift)
            ->first();
        
        // Obtener balance acumulado del centro de trabajo
        $workCenterBalance = \App\Models\WorkCenterBalance::where('id_work_center', $workCenterId)->first();
        $accumulatedBackwardness = $workCenterBalance ? $workCenterBalance->accumulated_backwardness : 0;
        $accumulatedAdvanced = $workCenterBalance ? $workCenterBalance->accumulated_advanced : 0;
        
        // Si no existe programa, crear uno vacío
        if (!$dailyProgram) {
            // Crear un programa principal con código único para programas automáticos
            $mainProgram = Program::create([
                'codigo' => Program::generateUniqueCode(),
                'fecha_entrega' => Carbon::parse($date)->addDays(7), // Entrega en 7 días por defecto
                'fecha_fase1' => Carbon::parse($date)->addDays(4),
                'fecha_fase2' => Carbon::parse($date)->addDays(5),
                'fecha_fase3' => Carbon::parse($date)->addDays(6),
                'fecha_fase4' => Carbon::parse($date)->addDays(7),
                'total_piezas' => 0,
                'total_time' => 0,
                'created_by' => auth()->id() ?? 1,
            ]);
            
            $dailyProgram = DailyProgram::create([
                'date' => $date,
                'id_work_center' => $workCenterId,
                'shift' => $shift,
                'programmed' => 0,
                'backwardness' => $accumulatedBackwardness,
                'advanced' => $accumulatedAdvanced,
                'shift_hours' => 9.0,
                'program_id' => $mainProgram->id,
            ]);
        } else {
            // Si el programa ya existe pero no tiene program_id, asignarle uno
            if (!$dailyProgram->program_id) {
                $mainProgram = Program::create([
                    'codigo' => Program::generateUniqueCode(),
                    'fecha_entrega' => Carbon::parse($date)->addDays(7),
                    'fecha_fase1' => Carbon::parse($date)->addDays(4),
                    'fecha_fase2' => Carbon::parse($date)->addDays(5),
                    'fecha_fase3' => Carbon::parse($date)->addDays(6),
                    'fecha_fase4' => Carbon::parse($date)->addDays(7),
                    'total_piezas' => 0,
                    'total_time' => 0,
                    'created_by' => auth()->id() ?? 1,
                ]);
                $dailyProgram->update(['program_id' => $mainProgram->id]);
            }
            
            // Si el programa ya existe pero no ha sido procesado, actualizar con el balance acumulado
            // SOLO si no fue editado manualmente por ingeniería ni por supervisor
            if (!$dailyProgram->balance_processed && !$dailyProgram->manually_edited_by_engineering && !$dailyProgram->manually_edited_by_supervisor) {
                $dailyProgram->update([
                    'backwardness' => $accumulatedBackwardness,
                    'advanced' => $accumulatedAdvanced,
                ]);
            }
        }
        
        // Generar horarios (8:00 a 17:00 por defecto para turno matutino)
        $startTime = $shift === 'matutino' ? '08:00' : '17:00';
        $hours = $this->generateHourlySchedule($startTime, 9);
        
        // Generar schedules para todas las líneas si no existen
        $this->generateSchedulesForProgram($dailyProgram, $productionLines);
        
        // Obtener schedules existentes agrupados por hora y línea
        $schedules = Schedule::where('id_daily_program', $dailyProgram->id)
            ->get()
            ->keyBy(function($schedule) {
                return $schedule->start_time . '-' . $schedule->id_production_line;
            });

        // Obtener líneas cerradas por el operador
        $closedLines = \App\Models\OperatorLineClosure::where('id_daily_program', $dailyProgram->id)
            ->with('productionLine', 'closedBy')
            ->get();

        // Obtener máquinas del centro de trabajo
        $machines = \App\Models\Machine::where('id_work_center', $workCenterId)->get();

        // Un programa de recuperación reutiliza "programmed" como meta de atraso a
        // recuperar; el frontend usa esta relación solo para invertir las etiquetas
        // "Programado"/"Atraso" en pantalla, sin tocar los valores ni el balance.
        $dailyProgram->load('program');

        return Inertia::render('Supervisor/DailyProduction', [
            'workCenter' => $workCenter,
            'productionLines' => $productionLines,
            'dailyProgram' => $dailyProgram,
            'date' => $date,
            'shift' => $shift,
            'hours' => $hours,
            'existingSchedules' => $schedules,
            'closedLines' => $closedLines,
            'machines' => $machines,
        ]);
    }
    
    // Guardar programa diario del centro
    public function storeDailyProgram(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'id_work_center' => 'required|exists:work_centers,id',
            'shift' => 'required|in:matutino,vespertino,nocturno',
            'programmed' => 'required|integer|min:0',
            'backwardness' => 'nullable|integer|min:0',
            'advanced' => 'nullable|integer|min:0',
            'shift_hours' => 'nullable|numeric|min:1',
        ]);

        DB::beginTransaction();
        try {
            // Verificar si el programa ya existe
            $existingProgram = DailyProgram::where('date', $request->date)
                ->where('id_work_center', $request->id_work_center)
                ->where('shift', $request->shift)
                ->first();

            // Calcular valores de backwardness y advanced
            if ($existingProgram) {
                // Si el programa ya existe pero no ha sido procesado, usar el balance acumulado del centro
                // SOLO si no fue editado manualmente por ingeniería ni por supervisor
                if (!$existingProgram->balance_processed && !$existingProgram->manually_edited_by_engineering && !$existingProgram->manually_edited_by_supervisor) {
                    $workCenterBalance = \App\Models\WorkCenterBalance::where('id_work_center', $request->id_work_center)->first();
                    $backwardness = $workCenterBalance ? $workCenterBalance->accumulated_backwardness : 0;
                    $advanced = $workCenterBalance ? $workCenterBalance->accumulated_advanced : 0;
                } else {
                    // Si ya fue procesado o fue editado manualmente, mantener valores actuales
                    $backwardness = $existingProgram->backwardness;
                    $advanced = $existingProgram->advanced;
                }
            } else {
                // Si es un programa nuevo, usar el balance acumulado del centro de trabajo
                $workCenterBalance = \App\Models\WorkCenterBalance::where('id_work_center', $request->id_work_center)->first();
                $backwardness = $workCenterBalance ? $workCenterBalance->accumulated_backwardness : 0;
                $advanced = $workCenterBalance ? $workCenterBalance->accumulated_advanced : 0;
            }

            // Si es un programa nuevo, crear un Program principal con código único
            if (!$existingProgram) {
                $mainProgram = Program::create([
                    'codigo' => Program::generateUniqueCode(),
                    'fecha_entrega' => Carbon::parse($request->date)->addDays(7),
                    'fecha_fase1' => Carbon::parse($request->date)->addDays(4),
                    'fecha_fase2' => Carbon::parse($request->date)->addDays(5),
                    'fecha_fase3' => Carbon::parse($request->date)->addDays(6),
                    'fecha_fase4' => Carbon::parse($request->date)->addDays(7),
                    'total_piezas' => 0,
                    'total_time' => 0,
                    'created_by' => auth()->id() ?? 1,
                ]);
                $programId = $mainProgram->id;
            } else {
                $programId = $existingProgram->program_id;
            }

            $program = DailyProgram::updateOrCreate(
                [
                    'date' => $request->date,
                    'id_work_center' => $request->id_work_center,
                    'shift' => $request->shift,
                ],
                [
                    'programmed' => $request->programmed,
                    'backwardness' => $backwardness,
                    'advanced' => $advanced,
                    'shift_hours' => $request->shift_hours ?? 9.0,
                    'program_id' => $programId,
                ]
            );

            // Generar schedules para todas las líneas del centro
            $workCenter = WorkCenter::with('productionLines')->findOrFail($request->id_work_center);
            $this->generateSchedulesForProgram($program, $workCenter->productionLines);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Programa guardado correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    // Actualizar producción por hora
    public function updateScheduleProduction(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'produced' => 'required|integer|min:0',
        ]);
        
        $schedule = Schedule::findOrFail($request->schedule_id);
        $schedule->update(['produced' => $request->produced]);
        
        // Actualizar total_produced en daily_program
        $this->updateDailyProgramTotal($schedule->id_daily_program);
        
        return response()->json(['success' => true]);
    }
    
    // Guardar producción masiva (toda la tabla)
    public function saveProductionTable(Request $request)
    {
        $request->validate([
            'schedules' => 'required|array',
            'schedules.*.id' => 'required|exists:schedules,id',
            'schedules.*.produced' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $dailyProgramIds = [];

            foreach ($request->schedules as $scheduleData) {
                $schedule = Schedule::findOrFail($scheduleData['id']);

                // Verificar que el programa no haya sido procesado
                $dailyProgram = DailyProgram::find($schedule->id_daily_program);
                if ($dailyProgram && $dailyProgram->balance_processed) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'No se puede modificar la producción de un programa que ya ha sido procesado.'], 403);
                }

                $schedule->update(['produced' => $scheduleData['produced']]);
                $dailyProgramIds[$schedule->id_daily_program] = true;
            }

            // Actualizar totales de todos los daily_programs afectados
            foreach (array_keys($dailyProgramIds) as $dpId) {
                $this->updateDailyProgramTotal($dpId);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Producción guardada correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    // Registrar paro
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
        
        \Log::info('Supervisor: Registrando paro', [
            'id_machine' => $request->id_machine,
            'all_data' => $request->all()
        ]);
        
        $strike = Strike::create([
            'id_production_lines' => $request->id_production_line,
            'id_daily_program' => $request->id_daily_program,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'description' => $request->description,
            'id_machine' => $request->id_machine,
        ]);
        
        \Log::info('Supervisor: Strike creado', [
            'strike_id' => $strike->id,
            'strike_id_machine' => $strike->id_machine
        ]);
        
        // Si se seleccionó una máquina, crear breakdown y actualizar estado
        if ($request->id_machine) {
            \App\Models\Breakdown::create([
                'id_machine' => $request->id_machine,
                'id_user' => auth()->id(),
                'reason' => $request->description,
                'start_date' => now('America/Mexico_City'),
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
                        $request->start_time
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
    
    // Finalizar paro activo
    public function endStrike(Request $request, Strike $strike)
    {
        $request->validate([
            'end_time' => 'required',
        ]);
        
        $strike->update(['end_time' => $request->end_time]);
        
        // Calcular minutos si hay inicio y fin
        if ($strike->start_time && $strike->end_time) {
            $start = Carbon::parse($strike->start_time);
            $end = Carbon::parse($strike->end_time);
            $minutes = $start->diffInMinutes($end);
            $strike->update(['minutes' => $minutes]);
        }
        
        // Si el strike tiene una máquina asociada, finalizar breakdown y actualizar estado
        if ($strike->id_machine) {
            $breakdown = \App\Models\Breakdown::where('id_machine', $strike->id_machine)
                ->whereNull('end_date')
                ->first();
            
            if ($breakdown) {
                $breakdown->update([
                    'end_date' => now('America/Mexico_City'),
                ]);
            }
            
            // Actualizar estado de máquina a operativo
            \App\Models\Machine::where('id', $strike->id_machine)->update(['state' => 'operativo']);
        }
        
        return response()->json([
            'success' => true,
            'strike' => $strike,
            'message' => 'Paro finalizado correctamente'
        ]);
    }
    
    // Auto-guardar producción individual (AJAX)
    public function autoSaveProduction(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'produced' => 'required|integer|min:0',
        ]);

        try {
            $schedule = Schedule::findOrFail($request->schedule_id);

            // Verificar que el programa no haya sido procesado
            $dailyProgram = DailyProgram::find($schedule->id_daily_program);
            if ($dailyProgram && $dailyProgram->balance_processed) {
                return response()->json(['success' => false, 'message' => 'No se puede modificar la producción de un programa que ya ha sido procesado.'], 403);
            }

            $schedule->update(['produced' => $request->produced]);

            // Actualizar total_produced en daily_program
            $this->updateDailyProgramTotal($schedule->id_daily_program);

            // Obtener programa actualizado con KPIs
            $dailyProgram = DailyProgram::with(['schedules', 'strikes', 'workCenter'])
                ->findOrFail($schedule->id_daily_program);

            $kpis = $this->calculateCenterKPIs($dailyProgram, $dailyProgram->workCenter);

            return response()->json([
                'success' => true,
                'kpis' => $kpis,
                'total_produced' => $dailyProgram->total_produced
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    // Obtener datos para la vista de producción (AJAX)
    public function getProductionData(Request $request)
    {
        $workCenterId = $request->get('work_center_id');
        $date = $request->get('date', now('America/Mexico_City')->format('Y-m-d'));
        $shift = $request->get('shift', 'matutino');

        $workCenter = WorkCenter::with('productionLines')->findOrFail($workCenterId);

        // Obtener programa del centro
        $dailyProgram = DailyProgram::with(['schedules', 'strikes'])
            ->where('id_work_center', $workCenterId)
            ->where('date', $date)
            ->where('shift', $shift)
            ->first();
        
        if (!$dailyProgram) {
            return response()->json([
                'work_center' => $workCenter,
                'daily_program' => null,
                'kpis' => null,
            ]);
        }
        
        $kpis = $this->calculateCenterKPIs($dailyProgram, $workCenter);
        
        return response()->json([
            'work_center' => $workCenter,
            'daily_program' => $dailyProgram,
            'kpis' => $kpis,
            'schedules' => $dailyProgram->schedules,
            'strikes' => $dailyProgram->strikes,
        ]);
    }
    
    // Métodos auxiliares privados
    
    private function generateHourlySchedule($startTime, $hours)
    {
        $schedule = [];
        $current = Carbon::parse($startTime);
        
        for ($i = 0; $i < $hours; $i++) {
            $start = $current->format('H:i');
            $end = $current->addHour()->format('H:i');
            $schedule[] = ['start' => $start, 'end' => $end];
        }
        
        return $schedule;
    }
    
    private function generateSchedulesForProgram(DailyProgram $program, $productionLines)
    {
        $startTime = $program->shift === 'matutino' ? '08:00' : '17:00';
        
        $hours = $this->generateHourlySchedule($startTime, (int)$program->shift_hours);
        
        // Generar schedules para cada línea del centro
        foreach ($productionLines as $line) {
            foreach ($hours as $hour) {
                Schedule::firstOrCreate(
                    [
                        'id_daily_program' => $program->id,
                        'id_production_line' => $line->id,
                        'start_time' => $hour['start'],
                        'end_time' => $hour['end'],
                    ],
                    [
                        'produced' => 0,
                    ]
                );
            }
        }
    }
    
    private function updateDailyProgramTotal($dailyProgramId)
    {
        $total = Schedule::where('id_daily_program', $dailyProgramId)
            ->sum('produced');
        
        DailyProgram::where('id', $dailyProgramId)
            ->update(['total_produced' => $total]);
    }
    
    private function calculateCenterKPIs(DailyProgram $program, WorkCenter $workCenter)
    {
        $totalProduced = $program->schedules->sum('produced');
        $totalToProduced = max($program->programmed + $program->backwardness - $program->advanced, 0);
        $difference = $totalProduced - $totalToProduced;
        $compliance = $totalToProduced > 0 ? round(($totalProduced / $totalToProduced) * 100, 2) : 0;
        
        // Calcular total de minutos de paros
        $totalStrikeMinutes = $program->strikes->sum('minutes');
        
        $totalMinutes = ($program->shift_hours ?? 9) * 60;
        
        // Calcular ahorro de activos (costo de paros evitados). productionLines.cost es
        // un costo por HORA; se divide entre 60 para obtener el costo por minuto.
        $avgCostPerHour = $workCenter->productionLines->avg('cost') ?? 0;
        $savedAmount = ($avgCostPerHour / 60) * ($totalMinutes - $totalStrikeMinutes);
        
        return [
            'programmed' => $program->programmed,
            'backwardness' => $program->backwardness,
            'advanced' => $program->advanced,
            'total_to_produce' => $totalToProduced,
            'fabricated' => $totalProduced,
            'difference' => $difference,
            'compliance' => $compliance,
            'saved_amount' => round($savedAmount, 2),
            'installed_capacity' => $workCenter->installed_capacity,
            'is_recovery' => optional($program->program)->program_type === 'recovery',
        ];
    }

    // Obtener paros por programa diario
    public function getStrikesByProgram($dailyProgramId)
    {
        $strikes = Strike::with('productionLine')
            ->where('id_daily_program', $dailyProgramId)
            ->get();
        
        return response()->json($strikes);
    }
    
    // Corregir datos del operador
    public function correctOperatorData(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'corrected_produced' => 'required|integer|min:0',
            'correction_reason' => 'required|string',
        ]);
        
        DB::beginTransaction();
        try {
            $schedule = Schedule::findOrFail($request->schedule_id);
            $oldValue = $schedule->produced;
            
            // Actualizar el valor corregido
            $schedule->update(['produced' => $request->corrected_produced]);
            
            // Registrar la corrección
            ProductionAdjustment::create([
                'id_daily_program' => $schedule->id_daily_program,
                'id_work_center' => $schedule->dailyProgram->id_work_center,
                'adjustment_type' => 'correction',
                'previous_value' => $oldValue,
                'new_value' => $request->corrected_produced,
                'difference' => $request->corrected_produced - $oldValue,
                'reason' => $request->correction_reason,
                'adjusted_by' => auth()->id(),
                'reference_type' => 'schedule',
                'reference_id' => $schedule->id,
            ]);
            
            // Recalcular totales automáticamente
            $this->updateDailyProgramTotal($schedule->id_daily_program);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Corrección aplicada y balances recalculados',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    // Procesar balance del turno
    public function processShiftBalance(Request $request)
    {
        $request->validate([
            'daily_program_id' => 'required|exists:daily_programs,id',
        ]);
        
        // Verificar que sea supervisor
        if (!auth()->user()->isSupervisor()) {
            return response()->json(['success' => false, 'message' => 'Solo supervisores pueden procesar balances'], 403);
        }
        
        $dailyProgram = DailyProgram::findOrFail($request->daily_program_id);
        
        // Procesar balance
        $result = $this->balanceService->processEndOfShiftBalance($dailyProgram);
        
        return response()->json([
            'success' => true,
            'message' => 'Balance procesado correctamente',
            'backwardness' => $result->accumulated_backwardness,
            'advanced' => $result->accumulated_advanced,
        ]);
    }
    
    // Extender programa matutino a turno vespertino
    public function extendToVespertino(Request $request)
    {
        $request->validate([
            'daily_program_id' => 'required|exists:daily_programs,id',
            'pieces_to_extend' => 'required|integer|min:0',
        ]);
        
        // Verificar que sea supervisor
        if (!auth()->user()->isSupervisor()) {
            return response()->json(['success' => false, 'message' => 'Solo supervisores pueden extender programas'], 403);
        }
        
        DB::beginTransaction();
        
        try {
            $dailyProgram = DailyProgram::findOrFail($request->daily_program_id);
            
            // Verificar que el programa sea matutino
            if ($dailyProgram->shift !== 'matutino') {
                return response()->json(['success' => false, 'message' => 'Solo se pueden extender programas matutinos'], 400);
            }
            
            // Verificar que no exista ya un programa vespertino para el mismo día y centro
            $existingVespertino = DailyProgram::where('date', $dailyProgram->date)
                ->where('id_work_center', $dailyProgram->id_work_center)
                ->where('shift', 'vespertino')
                ->first();
            
            if ($existingVespertino) {
                return response()->json(['success' => false, 'message' => 'Ya existe un programa vespertino para este día y centro'], 400);
            }
            
            $piecesToExtend = $request->pieces_to_extend;
            
            // Verificar que haya suficientes piezas para extender
            $totalAvailable = $dailyProgram->programmed + $dailyProgram->backwardness - $dailyProgram->advanced;
            if ($piecesToExtend > $totalAvailable) {
                return response()->json(['success' => false, 'message' => "Solo hay {$totalAvailable} piezas disponibles para extender"], 400);
            }
            
            // Reducir el programa matutino
            $dailyProgram->update([
                'programmed' => max($dailyProgram->programmed - $piecesToExtend, 0),
            ]);
            
            // Crear programa vespertino
            $vespertinoProgram = DailyProgram::create([
                'date' => $dailyProgram->date,
                'id_work_center' => $dailyProgram->id_work_center,
                'shift' => 'vespertino',
                'programmed' => $piecesToExtend,
                'backwardness' => 0,
                'advanced' => 0,
                'shift_hours' => 9.0, // Vespertino: 17:00 - 01:40 (aprox 8h 40min, redondeado a 9)
                'program_id' => $dailyProgram->program_id,
            ]);
            
            // Generar schedules para el programa vespertino
            $productionLines = ProductionLine::where('id_work_center', $dailyProgram->id_work_center)->get();
            $this->generateSchedulesForProgram($vespertinoProgram, $productionLines);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "Programa extendido exitosamente al turno vespertino con {$piecesToExtend} piezas",
                'vespertino_program_id' => $vespertinoProgram->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    // Registrar ajuste manual
    public function registerManualAdjustment(Request $request)
    {
        $request->validate([
            'daily_program_id' => 'required|exists:daily_programs,id',
            'adjustment_type' => 'required|in:manual_count,quality_rejection,transfer,correction',
            'previous_value' => 'required|integer',
            'new_value' => 'required|integer',
            'reason' => 'required|string',
            'notes' => 'nullable|string',
        ]);
        
        $dailyProgram = DailyProgram::findOrFail($request->daily_program_id);
        
        try {
            $adjustment = $this->balanceService->registerManualAdjustment($dailyProgram->id, [
                'id_work_center' => $dailyProgram->id_work_center,
                'adjustment_type' => $request->adjustment_type,
                'previous_value' => $request->previous_value,
                'new_value' => $request->new_value,
                'reason' => $request->reason,
                'notes' => $request->notes,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Ajuste manual registrado correctamente',
                'adjustment' => $adjustment,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    // Obtener historial de ajustes
    public function getAdjustmentsHistory(Request $request)
    {
        $workCenterId = $request->get('work_center_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        
        if (!$workCenterId) {
            return response()->json(['error' => 'Se requiere work_center_id'], 400);
        }

        $adjustments = $this->balanceService->getAdjustmentsHistory($workCenterId, $startDate, $endDate);

        // Si es una petición AJAX, devolver JSON
        if ($request->expectsJson()) {
            return response()->json($adjustments);
        }

        // Si es una petición de navegador, devolver vista Inertia
        $workCenter = WorkCenter::findOrFail($workCenterId);

        return Inertia::render('Supervisor/AdjustmentsHistory', [
            'workCenter' => $workCenter,
            'adjustments' => $adjustments,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    // ============================================
    // AJUSTES DE PRODUCCIÓN (VISTAS)
    // ============================================

    public function registerAdjustmentsView()
    {
        $user = auth()->user();
        $workCenters = $user->workCenters()->orderBy('work_centers.name')->get();
        $programs = Program::select('id', 'codigo', 'fecha_entrega', 'fecha_fase1', 'fecha_fase2', 'fecha_fase3', 'fecha_fase4')
            ->orderBy('fecha_entrega', 'desc')
            ->get();

        // Formatear fechas para la vista
        $programs->transform(function ($program) {
            $program->fecha_entrega_formatted = $program->fecha_entrega ? Carbon::parse($program->fecha_entrega)->format('d/m/Y') : null;
            $program->fecha_fase1_formatted = $program->fecha_fase1 ? Carbon::parse($program->fecha_fase1)->format('d/m/Y') : null;
            $program->fecha_fase2_formatted = $program->fecha_fase2 ? Carbon::parse($program->fecha_fase2)->format('d/m/Y') : null;
            $program->fecha_fase3_formatted = $program->fecha_fase3 ? Carbon::parse($program->fecha_fase3)->format('d/m/Y') : null;
            $program->fecha_fase4_formatted = $program->fecha_fase4 ? Carbon::parse($program->fecha_fase4)->format('d/m/Y') : null;
            return $program;
        });

        return Inertia::render('Supervisor/RegisterAdjustments', [
            'workCenters' => $workCenters,
            'programs' => $programs,
        ]);
    }

    public function loadDailyProgramsForAdjustment(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'work_center_id' => 'required|exists:work_centers,id',
            'phase_date' => 'required|date',
        ]);

        // Verificar que el supervisor tiene acceso a este centro de trabajo
        $user = auth()->user();
        if (!$user->workCenters()->where('work_centers.id', $request->work_center_id)->exists()) {
            return back()->with('error', 'No tienes permiso para ver programas de este centro.');
        }

        // Buscar daily programs por fecha y centro
        $dailyPrograms = DailyProgram::with(['workCenter', 'program', 'engineeringEditedBy'])
            ->where('date', $request->phase_date)
            ->where('id_work_center', $request->work_center_id)
            ->orderBy('shift')
            ->get()
            ->map(function ($dailyProgram) {
                $dailyProgram->date_formatted = $dailyProgram->date ? Carbon::parse($dailyProgram->date)->format('d/m/Y') : null;
                $dailyProgram->engineering_edited_at_formatted = $dailyProgram->engineering_edited_at ? Carbon::parse($dailyProgram->engineering_edited_at)->format('d/m/Y H:i') : null;
                return $dailyProgram;
            });

        $user = auth()->user();
        $workCenters = $user->workCenters()->orderBy('work_centers.name')->get();
        $programs = Program::select('id', 'codigo', 'fecha_entrega', 'fecha_fase1', 'fecha_fase2', 'fecha_fase3', 'fecha_fase4')
            ->orderBy('fecha_entrega', 'desc')
            ->get();

        // Formatear fechas para la vista
        $programs->transform(function ($program) {
            $program->fecha_entrega_formatted = $program->fecha_entrega ? Carbon::parse($program->fecha_entrega)->format('d/m/Y') : null;
            $program->fecha_fase1_formatted = $program->fecha_fase1 ? Carbon::parse($program->fecha_fase1)->format('d/m/Y') : null;
            $program->fecha_fase2_formatted = $program->fecha_fase2 ? Carbon::parse($program->fecha_fase2)->format('d/m/Y') : null;
            $program->fecha_fase3_formatted = $program->fecha_fase3 ? Carbon::parse($program->fecha_fase3)->format('d/m/Y') : null;
            $program->fecha_fase4_formatted = $program->fecha_fase4 ? Carbon::parse($program->fecha_fase4)->format('d/m/Y') : null;
            return $program;
        });

        return Inertia::render('Supervisor/RegisterAdjustments', [
            'workCenters' => $workCenters,
            'programs' => $programs,
            'dailyPrograms' => $dailyPrograms,
        ]);
    }

    public function productionAdjustments(Request $request)
    {
        $user = auth()->user();
        $workCenterIds = $user->workCenters()->pluck('work_centers.id')->toArray();

        // Si no hay centros asignados, retornar vacío
        if (empty($workCenterIds)) {
            return Inertia::render('Supervisor/ProductionAdjustments', [
                'adjustments' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 50, 1),
                'filters' => $request->only(['work_center_id', 'date_from', 'date_to']),
                'workCenters' => [],
            ]);
        }

        $query = ProductionAdjustment::with(['dailyProgram', 'workCenter', 'adjustedBy', 'sourceProgram', 'targetProgram'])
            ->whereIn('id_work_center', $workCenterIds);

        // Filtro por centro de trabajo
        if ($request->has('work_center_id') && $request->work_center_id) {
            $query->where('id_work_center', $request->work_center_id);
        }

        // Filtros de fecha
        if ($request->has('date_from') && $request->date_from) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $adjustments = $query->orderBy('created_at', 'desc')->paginate(50);
        $workCenters = $user->workCenters()->orderBy('work_centers.name')->get();

        return Inertia::render('Supervisor/ProductionAdjustments', [
            'adjustments' => $adjustments,
            'filters' => $request->only(['work_center_id', 'date_from', 'date_to']),
            'workCenters' => $workCenters,
        ]);
    }

    public function updateDailyProgram(Request $request, $id)
    {
        $request->validate([
            'programmed' => 'required|integer|min:0',
            'backwardness' => 'required|integer|min:0',
            'advanced' => 'required|integer|min:0',
            'total_produced' => 'required|integer|min:0',
            'total_rejected' => 'required|integer|min:0',
            'reason' => 'required|string',
        ]);

        $dailyProgram = DailyProgram::findOrFail($id);

        // Verificar que el supervisor tiene acceso a este centro de trabajo
        $user = auth()->user();
        if (!$user->workCenters()->where('work_centers.id', $dailyProgram->id_work_center)->exists()) {
            return back()->with('error', 'No tienes permiso para editar este programa.');
        }

        DB::beginTransaction();

        try {
            // Guardar valores anteriores
            $previousProgrammed = $dailyProgram->programmed;
            $previousBackwardness = $dailyProgram->backwardness;
            $previousAdvanced = $dailyProgram->advanced;
            $previousProduced = $dailyProgram->total_produced;
            $previousRejected = $dailyProgram->total_rejected;

            // Actualizar el daily program
            $dailyProgram->update([
                'programmed' => $request->programmed,
                'backwardness' => $request->backwardness,
                'advanced' => $request->advanced,
                'total_produced' => $request->total_produced,
                'total_rejected' => $request->total_rejected,
                'manually_edited_by_supervisor' => true,
                'supervisor_edited_at' => now('America/Mexico_City'),
                'supervisor_edited_by' => auth()->id(),
            ]);

            // Registrar ajustes si hubo cambios
            if ($previousProgrammed != $request->programmed) {
                ProductionAdjustment::create([
                    'id_daily_program' => $dailyProgram->id,
                    'id_work_center' => $dailyProgram->id_work_center,
                    'adjustment_type' => 'correction',
                    'field_adjusted' => 'programmed',
                    'previous_value' => $previousProgrammed,
                    'new_value' => $request->programmed,
                    'difference' => $request->programmed - $previousProgrammed,
                    'adjustment_category' => 'correction',
                    'reason' => $request->reason,
                    'adjusted_by' => auth()->id(),
                    'notes' => $request->notes,
                ]);
            }

            if ($previousBackwardness != $request->backwardness) {
                ProductionAdjustment::create([
                    'id_daily_program' => $dailyProgram->id,
                    'id_work_center' => $dailyProgram->id_work_center,
                    'adjustment_type' => 'correction',
                    'field_adjusted' => 'backwardness',
                    'previous_value' => $previousBackwardness,
                    'new_value' => $request->backwardness,
                    'difference' => $request->backwardness - $previousBackwardness,
                    'adjustment_category' => 'correction',
                    'reason' => $request->reason,
                    'adjusted_by' => auth()->id(),
                    'notes' => $request->notes,
                ]);

                // Actualizar balance acumulado del centro de trabajo
                $workCenterBalance = \App\Models\WorkCenterBalance::getOrCreateForWorkCenter($dailyProgram->id_work_center);
                $workCenterBalance->accumulated_backwardness = $request->backwardness;
                $workCenterBalance->last_calculated_at = now('America/Mexico_City');
                $workCenterBalance->save();
            }

            if ($previousAdvanced != $request->advanced) {
                ProductionAdjustment::create([
                    'id_daily_program' => $dailyProgram->id,
                    'id_work_center' => $dailyProgram->id_work_center,
                    'adjustment_type' => 'correction',
                    'field_adjusted' => 'advanced',
                    'previous_value' => $previousAdvanced,
                    'new_value' => $request->advanced,
                    'difference' => $request->advanced - $previousAdvanced,
                    'adjustment_category' => 'correction',
                    'reason' => $request->reason,
                    'adjusted_by' => auth()->id(),
                    'notes' => $request->notes,
                ]);

                // Actualizar balance acumulado del centro de trabajo
                $workCenterBalance = \App\Models\WorkCenterBalance::getOrCreateForWorkCenter($dailyProgram->id_work_center);
                $workCenterBalance->accumulated_advanced = $request->advanced;
                $workCenterBalance->last_calculated_at = now('America/Mexico_City');
                $workCenterBalance->save();
            }

            if ($previousProduced != $request->total_produced) {
                ProductionAdjustment::create([
                    'id_daily_program' => $dailyProgram->id,
                    'id_work_center' => $dailyProgram->id_work_center,
                    'adjustment_type' => 'correction',
                    'field_adjusted' => 'total_produced',
                    'previous_value' => $previousProduced,
                    'new_value' => $request->total_produced,
                    'difference' => $request->total_produced - $previousProduced,
                    'adjustment_category' => 'correction',
                    'reason' => $request->reason,
                    'adjusted_by' => auth()->id(),
                    'notes' => $request->notes,
                ]);
            }

            if ($previousRejected != $request->total_rejected) {
                ProductionAdjustment::create([
                    'id_daily_program' => $dailyProgram->id,
                    'id_work_center' => $dailyProgram->id_work_center,
                    'adjustment_type' => 'correction',
                    'field_adjusted' => 'total_rejected',
                    'previous_value' => $previousRejected,
                    'new_value' => $request->total_rejected,
                    'difference' => $request->total_rejected - $previousRejected,
                    'adjustment_category' => 'correction',
                    'reason' => $request->reason,
                    'adjusted_by' => auth()->id(),
                    'notes' => $request->notes,
                ]);
            }

            DB::commit();

            return back()->with('success', 'Programa actualizado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar el programa: ' . $e->getMessage());
        }
    }

    // Obtener historial de cumplimiento de los últimos 3 días
    private function getRecentComplianceHistory($workCenterId, $shift)
    {
        $history = [];
        
        for ($i = 1; $i <= 3; $i++) {
            $date = now('America/Mexico_City')->subDays($i)->format('Y-m-d');
            
            $dailyProgram = DailyProgram::where('id_work_center', $workCenterId)
                ->where('date', $date)
                ->where('shift', $shift)
                ->first();
            
            if ($dailyProgram) {
                $totalProduced = $dailyProgram->schedules->sum('produced');
                $totalToProduce = max($dailyProgram->programmed + $dailyProgram->backwardness - $dailyProgram->advanced, 0);
                $compliance = $totalToProduce > 0 ? round(($totalProduced / $totalToProduce) * 100, 1) : 0;
            } else {
                $compliance = 0;
            }
            
            $history[] = [
                'date' => now('America/Mexico_City')->subDays($i)->format('d/m/Y'),
                'value' => $compliance
            ];
        }
        
        return array_reverse($history); // Mostrar en orden cronológico (más antiguo primero)
    }

    // Historial por Centro de Trabajo
    public function centerHistory(Request $request)
    {
        $user = auth()->user();
        $workCenters = $user->workCenters;

        if ($workCenters->isEmpty()) {
            return Inertia::render('Supervisor/NoWorkCenters');
        }

        $selectedWorkCenterId = $request->get('work_center_id');
        if (!$selectedWorkCenterId) {
            $selectedWorkCenterId = $workCenters->first()->id;
        }

        $selectedWorkCenter = $workCenters->where('id', $selectedWorkCenterId)->first();

        // Filtros de fecha
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        \Log::info('CenterHistory filters', [
            'work_center_id' => $selectedWorkCenterId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'all_params' => $request->all()
        ]);

        // Query para obtener el historial
        $query = DailyProgram::where('id_work_center', $selectedWorkCenterId);

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            // Incluir todo el día final agregando 1 día y usando <
            $query->where('date', '<', Carbon::parse($endDate)->addDay()->format('Y-m-d'));
        }

        // Ordenar descendente por fecha y paginar
        $dailyPrograms = $query->with(['program', 'workCenter'])
            ->orderBy('date', 'desc')
            ->orderBy('shift', 'desc')
            ->paginate(15);

        // Calcular faltantes a producir para cada registro
        $dailyPrograms->getCollection()->transform(function ($program) {
            // Total a producir: programado + atrasos - adelantos
            $totalToProduce = max($program->programmed + $program->backwardness - $program->advanced, 0);

            // Faltantes a producir: total a producir - producido
            // Si hay adelantos, no se restan de los faltantes porque ya se restaron del total a producir
            $missingToProduce = max($totalToProduce - ($program->total_produced ?? 0), 0);

            // Debug
            \Log::info('CenterHistory calculation', [
                'program_id' => $program->id,
                'date' => $program->date,
                'programmed' => $program->programmed,
                'backwardness' => $program->backwardness,
                'advanced' => $program->advanced,
                'total_produced' => $program->total_produced,
                'total_to_produce' => $totalToProduce,
                'missing_to_produce' => $missingToProduce
            ]);

            $program->total_to_produce = $totalToProduce;
            $program->missing_to_produce = $missingToProduce;

            return $program;
        });

        return Inertia::render('Supervisor/CenterHistory', [
            'workCenters' => $workCenters,
            'selectedWorkCenter' => $selectedWorkCenter,
            'dailyPrograms' => $dailyPrograms,
            'filters' => [
                'work_center_id' => $selectedWorkCenterId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    // Exportar historial a Excel
    public function exportCenterHistoryExcel(Request $request)
    {
        $request->validate([
            'work_center_id' => 'required|exists:work_centers,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $user = auth()->user();

        // Verificar que el usuario tenga acceso a este centro
        if (!$user->canViewWorkCenter($request->work_center_id)) {
            return response()->json(['error' => 'No tienes acceso a este centro de trabajo'], 403);
        }

        $workCenter = WorkCenter::findOrFail($request->work_center_id);

        // Query para obtener el historial
        $query = DailyProgram::where('id_work_center', $request->work_center_id);

        if ($request->start_date) {
            $query->where('date', '>=', $request->start_date);
        }

        if ($request->end_date) {
            // Incluir todo el día final agregando 1 día y usando <
            $query->where('date', '<', Carbon::parse($request->end_date)->addDay()->format('Y-m-d'));
        }

        $dailyPrograms = $query->orderBy('date', 'desc')
            ->orderBy('shift', 'desc')
            ->get();

        // Crear archivo Excel
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Encabezados
        $sheet->setCellValue('A1', 'Fecha');
        $sheet->setCellValue('B1', 'Turno');
        $sheet->setCellValue('C1', 'Programado');
        $sheet->setCellValue('D1', 'Atrasos');
        $sheet->setCellValue('E1', 'Avance');
        $sheet->setCellValue('F1', 'Producción Real');
        $sheet->setCellValue('G1', 'Total a Producir');
        $sheet->setCellValue('H1', 'Faltantes a Producir');

        // Estilos para encabezados
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => '0B2A40']],
        ];

        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

        // Datos
        $row = 2;
        foreach ($dailyPrograms as $program) {
            $totalToProduce = max($program->programmed + $program->backwardness - $program->advanced, 0);
            $missingToProduce = max($totalToProduce - ($program->total_produced ?? 0), 0);

            $sheet->setCellValue('A' . $row, \Carbon\Carbon::parse($program->date)->format('d/m/Y'));
            $sheet->setCellValue('B' . $row, ucfirst($program->shift));
            $sheet->setCellValue('C' . $row, $program->programmed);
            $sheet->setCellValue('D' . $row, $program->backwardness);
            $sheet->setCellValue('E' . $row, $program->advanced);
            $sheet->setCellValue('F' . $row, $program->total_produced ?? 0);
            $sheet->setCellValue('G' . $row, $totalToProduce);
            $sheet->setCellValue('H' . $row, $missingToProduce);

            $row++;
        }

        // Auto-size columnas
        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Nombre del archivo
        $fileName = 'Historial_' . str_replace(' ', '_', $workCenter->name) . '_' . now()->format('Y-m-d_His') . '.xlsx';

        // Generar respuesta
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}