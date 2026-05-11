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

class SupervisorController extends Controller
{
    // Dashboard principal del supervisor
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Obtener centros de trabajo del supervisor
        $workCenters = $user->workCenters;
        
        if ($workCenters->isEmpty()) {
            return view('supervisor.no-work-centers');
        }
        
        // Centro de trabajo seleccionado (con persistencia en sesión)
        $selectedWorkCenterId = $request->get('work_center_id');
        
        if ($selectedWorkCenterId) {
            // Si viene en el request, guardarlo en sesión
            session(['selected_work_center_id' => $selectedWorkCenterId]);
        } else {
            // Si no viene en el request, intentar recuperar de sesión
            $selectedWorkCenterId = session('selected_work_center_id', $workCenters->first()->id);
        }
        
        $selectedWorkCenter = WorkCenter::with('productionLines')->findOrFail($selectedWorkCenterId);
        
        // Fecha y turno seleccionados (también con persistencia)
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
        
        // Obtener programa diario del centro
        $dailyProgram = DailyProgram::with(['schedules', 'strikes'])
            ->where('id_work_center', $selectedWorkCenterId)
            ->where('date', $selectedDate)
            ->where('shift', $selectedShift)
            ->first();
        
        // Calcular KPIs del centro
        $kpis = null;
        if ($dailyProgram) {
            $kpis = $this->calculateCenterKPIs($dailyProgram, $selectedWorkCenter);
        }
        
        return view('supervisor.dashboard', compact(
            'workCenters',
            'selectedWorkCenter',
            'selectedDate',
            'selectedShift',
            'dailyProgram',
            'kpis'
        ));
    }
    
    // Registro diario de producción
    public function dailyProduction(Request $request)
    {
        $user = auth()->user();
        $workCenterId = $request->get('work_center_id');
        
        // Si no se proporciona work_center_id, intentar recuperar de sesión
        if (!$workCenterId) {
            $workCenterId = session('selected_work_center_id');
            
            if (!$workCenterId) {
                $firstCenter = $user->workCenters->first();
                if (!$firstCenter) {
                    return redirect()->route('supervisor.dashboard')
                        ->with('error', 'No tienes centros de trabajo asignados.');
                }
                $workCenterId = $firstCenter->id;
            }
        }
        
        // Guardar en sesión para mantener la selección
        session(['selected_work_center_id' => $workCenterId]);
        
        // Verificar que el usuario tenga acceso a este centro
        if (!$user->canViewWorkCenter($workCenterId)) {
            return redirect()->route('supervisor.dashboard')
                ->with('error', 'No tienes acceso a este centro de trabajo.');
        }
        
        // Fecha y turno con persistencia en sesión
        $date = $request->get('date');
        if ($date) {
            session(['selected_date' => $date]);
        } else {
            $date = session('selected_date', now()->format('Y-m-d'));
        }
        
        $shift = $request->get('shift');
        if ($shift) {
            session(['selected_shift' => $shift]);
        } else {
            $shift = session('selected_shift', 'matutino');
        }
        
        $workCenter = WorkCenter::with('productionLines')->findOrFail($workCenterId);
        $productionLines = $workCenter->productionLines;
        
        // Verificar que el centro tenga líneas de producción
        if ($productionLines->isEmpty()) {
            return redirect()->route('supervisor.dashboard')
                ->with('error', 'Este centro de trabajo no tiene líneas de producción configuradas.');
        }
        
        // Obtener o crear daily_program del centro (UNO solo por centro)
        $dailyProgram = DailyProgram::with(['schedules', 'strikes'])
            ->where('date', $date)
            ->where('id_work_center', $workCenterId)
            ->where('shift', $shift)
            ->first();
        
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
            ->groupBy(function($schedule) {
                return $schedule->start_time . '-' . $schedule->id_production_line;
            });
        
        return view('supervisor.daily-production', compact(
            'workCenter',
            'productionLines',
            'dailyProgram',
            'date',
            'shift',
            'hours',
            'schedules'
        ));
    }
}
