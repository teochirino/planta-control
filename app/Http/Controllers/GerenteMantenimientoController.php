<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\Breakdown;
use App\Models\WorkCenter;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;

class GerenteMantenimientoController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        
        // Gerente de Mantenimiento puede ver TODOS los centros de trabajo
        $workCenters = WorkCenter::with('machines')->get();
        
        if ($workCenters->isEmpty()) {
            return Inertia::render('GerenteMantenimiento/NoWorkCenters');
        }
        
        // Centro de trabajo seleccionado
        $selectedWorkCenterId = $request->get('work_center_id');
        if (!$selectedWorkCenterId) {
            $selectedWorkCenterId = $workCenters->first()->id;
        }
        
        $selectedWorkCenter = WorkCenter::with('machines')->findOrFail($selectedWorkCenterId);
        
        // Fecha actual
        $selectedDate = $request->get('date', now()->format('Y-m-d'));
        
        // Obtener todas las máquinas del centro con sus breakdowns
        $machines = Machine::with(['workCenter', 'breakdowns' => function($query) use ($selectedDate) {
            $query->whereDate('start_date', $selectedDate);
        }])->where('id_work_center', $selectedWorkCenterId)->get();
        
        // Calcular estadísticas por máquina
        $machinesData = $machines->map(function($machine) use ($selectedDate) {
            $dailyMinutes = $machine->breakdowns->sum('minutes');
            $weeklyMinutes = Breakdown::where('id_machine', $machine->id)
                ->whereBetween('start_date', [
                    Carbon::parse($selectedDate)->startOfWeek(),
                    Carbon::parse($selectedDate)->endOfWeek()
                ])
                ->sum('minutes');
            
            // Breakdowns pendientes de confirmación
            $pendingBreakdowns = $machine->breakdowns->whereNull('confirmed_by');
            
            return [
                'id' => $machine->id,
                'title' => $machine->title,
                'state' => $machine->state,
                'work_center' => $machine->workCenter->name,
                'daily_minutes' => $dailyMinutes,
                'daily_hours' => round($dailyMinutes / 60, 2),
                'weekly_minutes' => $weeklyMinutes,
                'weekly_hours' => round($weeklyMinutes / 60, 2),
                'pending_breakdowns' => $pendingBreakdowns->count(),
                'total_breakdowns' => $machine->breakdowns->count(),
                'active_breakdown' => $machine->breakdowns->whereNull('end_date')->first(),
            ];
        });
        
        // Estadísticas totales del centro
        $totalDailyMinutes = $machinesData->sum('daily_minutes');
        $totalWeeklyMinutes = $machinesData->sum('weekly_minutes');
        $totalPendingConfirmations = $machinesData->sum('pending_breakdowns');
        $activeMachines = $machinesData->where('state', 'operativo')->count();
        $brokenMachines = $machinesData->where('state', 'averiado')->count();
        $maintenanceMachines = $machinesData->where('state', 'mantenimiento')->count();
        
        return Inertia::render('GerenteMantenimiento/Dashboard', [
            'workCenters' => $workCenters,
            'selectedWorkCenter' => $selectedWorkCenter,
            'selectedDate' => $selectedDate,
            'machines' => $machinesData,
            'stats' => [
                'total_daily_hours' => round($totalDailyMinutes / 60, 2),
                'total_weekly_hours' => round($totalWeeklyMinutes / 60, 2),
                'pending_confirmations' => $totalPendingConfirmations,
                'active_machines' => $activeMachines,
                'broken_machines' => $brokenMachines,
                'maintenance_machines' => $maintenanceMachines,
                'total_machines' => $machinesData->count(),
            ],
        ]);
    }
    
    // Obtener breakdowns pendientes de confirmación
    public function getPendingBreakdowns(Request $request)
    {
        $workCenterId = $request->get('work_center_id');
        $date = $request->get('date', now()->format('Y-m-d'));
        
        $breakdowns = Breakdown::with(['machine.workCenter', 'user'])
            ->whereHas('machine', function($query) use ($workCenterId) {
                if ($workCenterId) {
                    $query->where('id_work_center', $workCenterId);
                }
            })
            ->whereDate('start_date', $date)
            ->whereNull('confirmed_by')
            ->orderBy('start_date', 'desc')
            ->get();
        
        return response()->json($breakdowns);
    }
    
    // Confirmar breakdown
    public function confirmBreakdown(Request $request, $id)
    {
        $request->validate([
            'confirmed_minutes' => 'required|integer|min:0',
        ]);
        
        $breakdown = Breakdown::findOrFail($id);
        
        $breakdown->update([
            'confirmed_by' => auth()->id(),
            'confirmed_at' => now(),
            'confirmed_minutes' => $request->confirmed_minutes,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Avería confirmada correctamente',
            'breakdown' => $breakdown->load('confirmedBy'),
        ]);
    }
    
    // Actualizar estado de máquina
    public function updateMachineState(Request $request, $id)
    {
        $request->validate([
            'state' => 'required|in:operativo,mantenimiento,averiado',
        ]);
        
        $machine = Machine::findOrFail($id);
        $oldState = $machine->state;
        $machine->update(['state' => $request->state]);
        
        // Si se cambia a operativo, finalizar el breakdown activo y el strike activo de la máquina
        if ($request->state === 'operativo' && $oldState !== 'operativo') {
            // Finalizar breakdown activo
            $activeBreakdown = Breakdown::where('id_machine', $id)
                ->whereNull('end_date')
                ->first();
            
            if ($activeBreakdown) {
                $activeBreakdown->update([
                    'end_date' => now(),
                ]);
            }
            
            // Finalizar strike activo asociado a la máquina
            $activeStrike = \App\Models\Strike::where('id_machine', $id)
                ->whereNull('end_time')
                ->first();
            
            \Log::info('GerenteMantenimiento: Buscando strike para máquina ' . $id, [
                'strike_encontrado' => $activeStrike ? $activeStrike->id : null,
                'strike_data' => $activeStrike ? $activeStrike->toArray() : null
            ]);
            
            if ($activeStrike) {
                $endTime = now()->format('H:i');
                $activeStrike->update([
                    'end_time' => $endTime,
                ]);
                
                // Calcular minutos si hay inicio y fin
                if ($activeStrike->start_time) {
                    $startParts = explode(':', $activeStrike->start_time);
                    $endParts = explode(':', $endTime);
                    
                    $startMinutes = (int)$startParts[0] * 60 + (int)$startParts[1];
                    $endMinutes = (int)$endParts[0] * 60 + (int)$endParts[1];
                    
                    $minutes = $endMinutes - $startMinutes;
                    if ($minutes < 0) {
                        $minutes += 1440;
                    }
                    
                    $activeStrike->update(['minutes' => $minutes]);
                    
                    // Calcular costo
                    $productionLine = $activeStrike->productionLine;
                    if ($productionLine && $productionLine->cost > 0) {
                        $cost = $minutes * floatval($productionLine->cost);
                        $activeStrike->update(['cost' => $cost]);
                    }
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Estado de máquina actualizado correctamente',
            'machine' => $machine,
        ]);
    }
    
    // Exportar reporte de averías
    public function exportReport(Request $request)
    {
        $workCenterId = $request->get('work_center_id');
        $startDate = $request->get('start_date', now()->startOfWeek()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfWeek()->format('Y-m-d'));
        
        $machines = Machine::with(['workCenter', 'breakdowns' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('start_date', [$startDate, $endDate]);
        }]);
        
        if ($workCenterId) {
            $machines->where('id_work_center', $workCenterId);
        }
        
        $machines = $machines->get();
        
        $reportData = $machines->map(function($machine) use ($startDate, $endDate) {
            $totalMinutes = $machine->breakdowns->sum('minutes');
            $confirmedMinutes = $machine->breakdowns->whereNotNull('confirmed_by')->sum('confirmed_minutes');
            
            return [
                'machine' => $machine->title,
                'work_center' => $machine->workCenter->name,
                'state' => $machine->state,
                'total_breakdowns' => $machine->breakdowns->count(),
                'total_minutes' => $totalMinutes,
                'total_hours' => round($totalMinutes / 60, 2),
                'confirmed_minutes' => $confirmedMinutes,
                'confirmed_hours' => round($confirmedMinutes / 60, 2),
                'pending_confirmations' => $machine->breakdowns->whereNull('confirmed_by')->count(),
            ];
        });
        
        return response()->json([
            'report' => $reportData,
            'summary' => [
                'total_machines' => $machines->count(),
                'total_hours' => round($reportData->sum('total_hours'), 2),
                'total_confirmed_hours' => round($reportData->sum('confirmed_hours'), 2),
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
            ],
        ]);
    }
    
    // Obtener historial de breakdowns de una máquina
    public function getMachineBreakdowns(Request $request, $machineId)
    {
        $machine = Machine::with('workCenter')->findOrFail($machineId);
        
        $breakdowns = Breakdown::with(['user', 'confirmedBy'])
            ->where('id_machine', $machineId)
            ->orderBy('start_date', 'desc')
            ->get();
        
        return response()->json([
            'machine' => $machine,
            'breakdowns' => $breakdowns,
        ]);
    }
}
