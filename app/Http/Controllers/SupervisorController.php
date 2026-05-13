<?php

namespace App\Http\Controllers;

use App\Models\WorkCenter;
use App\Models\ProductionLine;
use App\Models\DailyProgram;
use App\Models\Schedule;
use App\Models\Strike;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SupervisorController extends Controller
{
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
        
        // 🔧 FORZAR FECHA ACTUAL - IGNORAR COMPLETAMENTE LA SESIÓN
        $selectedDate = now()->format('Y-m-d');
        $selectedShift = $request->get('shift', 'matutino');
        
        // Obtener programa diario del centro
        $dailyProgram = DailyProgram::with(['schedules', 'strikes'])
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
        
        // 🔧 FORZAR FECHA ACTUAL - IGNORAR COMPLETAMENTE LA SESIÓN
        $date = now()->format('Y-m-d');
        $shift = $request->get('shift', 'matutino');
        
        $workCenter = WorkCenter::with('productionLines')->findOrFail($workCenterId);
        $productionLines = $workCenter->productionLines;
        
        // Obtener o crear daily_program del centro
        $dailyProgram = DailyProgram::with(['schedules', 'strikes'])
            ->where('date', $date)
            ->where('id_work_center', $workCenterId)
            ->where('shift', $shift)
            ->first();
        
        // Si no existe programa, crear uno vacío
        if (!$dailyProgram) {
            $dailyProgram = DailyProgram::create([
                'date' => $date,
                'id_work_center' => $workCenterId,
                'shift' => $shift,
                'programmed' => 0,
                'backwardness' => 0,
                'advanced' => 0,
                'shift_hours' => 9.0,
            ]);
        }
        
        // Generar horarios (8:00 a 17:00 por defecto para turno matutino)
        $startTime = $shift === 'matutino' ? '08:00' : ($shift === 'vespertino' ? '16:00' : '00:00');
        $hours = $this->generateHourlySchedule($startTime, 9);
        
        // Generar schedules para todas las líneas si no existen
        $this->generateSchedulesForProgram($dailyProgram, $productionLines);
        
        // Obtener schedules existentes agrupados por hora y línea
        $schedules = Schedule::where('id_daily_program', $dailyProgram->id)
            ->get()
            ->keyBy(function($schedule) {
                return $schedule->start_time . '-' . $schedule->id_production_line;
            });
        
        return Inertia::render('Supervisor/DailyProduction', [
            'workCenter' => $workCenter,
            'productionLines' => $productionLines,
            'dailyProgram' => $dailyProgram,
            'date' => $date,
            'shift' => $shift,
            'hours' => $hours,
            'existingSchedules' => $schedules,
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
            $program = DailyProgram::updateOrCreate(
                [
                    'date' => $request->date,
                    'id_work_center' => $request->id_work_center,
                    'shift' => $request->shift,
                ],
                [
                    'programmed' => $request->programmed,
                    'backwardness' => $request->backwardness ?? 0,
                    'advanced' => $request->advanced ?? 0,
                    'shift_hours' => $request->shift_hours ?? 9.0,
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
        ]);
        
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
        $date = $request->get('date', now()->format('Y-m-d'));
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
        $startTime = $program->shift === 'matutino' ? '08:00' : 
                    ($program->shift === 'vespertino' ? '16:00' : '00:00');
        
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
        
        // Calcular Real vs Ideal (horas activas)
        $totalMinutes = ($program->shift_hours ?? 9) * 60;
        $activeMinutes = $totalMinutes - $totalStrikeMinutes;
        $realVsIdeal = $totalMinutes > 0 ? round(($activeMinutes / $totalMinutes) * 100, 2) : 0;
        
        // Calcular ahorro de activos (costo de paros evitados)
        $avgCostPerMinute = $workCenter->productionLines->avg('cost') ?? 0;
        $savedAmount = $avgCostPerMinute * ($totalMinutes - $totalStrikeMinutes);
        
        return [
            'programmed' => $program->programmed,
            'backwardness' => $program->backwardness,
            'advanced' => $program->advanced,
            'total_to_produce' => $totalToProduced,
            'fabricated' => $totalProduced,
            'difference' => $difference,
            'compliance' => $compliance,
            'real_vs_ideal' => $realVsIdeal,
            'saved_amount' => round($savedAmount, 2),
            'installed_capacity' => $workCenter->installed_capacity,
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
}