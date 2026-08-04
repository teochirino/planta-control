<?php

namespace App\Http\Controllers;

use App\Models\WorkCenter;
use App\Models\DailyProgram;
use App\Models\Schedule;
use App\Models\Strike;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class GerenciaController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        
        // Gerencia puede ver TODOS los centros de trabajo
        $workCenters = WorkCenter::with('productionLines')->get();
        
        if ($workCenters->isEmpty()) {
            // Si no hay centros, redirigir a una vista de error en Vue
            return Inertia::render('Gerencia/NoWorkCenters');
        }
        
        // Centro de trabajo seleccionado (con persistencia en sesión)
        $selectedWorkCenterId = $request->get('work_center_id');
        
        if ($selectedWorkCenterId) {
            session(['gerencia_selected_work_center_id' => $selectedWorkCenterId]);
        } else {
            $selectedWorkCenterId = session('gerencia_selected_work_center_id', $workCenters->first()->id);
        }
        
        $selectedWorkCenter = WorkCenter::with('productionLines', 'machines')->findOrFail($selectedWorkCenterId);
        
        // Fecha actual
        $selectedDate = $request->get('date', now()->format('Y-m-d'));
        $selectedShift = $request->get('shift');
        
        // Obtener programa diario del centro
        $dailyProgramQuery = DailyProgram::with(['schedules', 'strikes'])
            ->where('id_work_center', $selectedWorkCenterId)
            ->where('date', $selectedDate);
            
        if ($selectedShift) {
            $dailyProgramQuery->where('shift', $selectedShift);
        }
        
        $dailyProgram = $dailyProgramQuery->first();
        
        // Si encontramos un programa, guardar su turno
        if ($dailyProgram) {
            $selectedShift = $dailyProgram->shift;
        } else {
            $selectedShift = $selectedShift ?? $this->getCurrentShift();
        }
        
        // Calcular KPIs y métricas del centro
        $kpis = $dailyProgram 
            ? $this->calculateKPIs($dailyProgram, $selectedWorkCenter)
            : $this->calculateDefaultKPIs($selectedWorkCenter);
            
        $metrics = $dailyProgram 
            ? $this->calculateMetrics($dailyProgram, $selectedWorkCenter)
            : ['total_strikes' => 0, 'strike_cost' => 0];
            
        $qualityMetrics = $this->calculateQualityMetrics($selectedWorkCenter);
        
        // Obtener cumplimiento de los últimos 5 días
        $recentCompliance = $this->getRecentCompliance($selectedWorkCenterId, 5);
        
        // Estado general del área
        $areaStatus = $this->calculateAreaStatus($dailyProgram, $selectedWorkCenter);
        
        // Agregar la clase de color CSS
        $areaStatus['colorClass'] = $this->getColorClass($areaStatus['color'] ?? 'gray');
        
        return Inertia::render('Gerencia/Dashboard', [
            'workCenters' => $workCenters,
            'selectedWorkCenter' => $selectedWorkCenter,
            'selectedShift' => $selectedShift,
            'kpis' => $kpis,
            'areaStatus' => $areaStatus,
            'qualityMetrics' => $qualityMetrics,
            'metrics' => $metrics,
            'recentCompliance' => $recentCompliance,
        ]);
    }
    
    private function getColorClass($color)
    {
        switch ($color) {
            case 'green': return 'bg-green-500';
            case 'yellow': return 'bg-yellow-500';
            case 'red': return 'bg-red-500';
            default: return 'bg-gray-500';
        }
    }
    
    private function getCurrentShift()
    {
        $hour = now()->hour;
        $minute = now()->minute;
        
        // Matutino: 08:00 - 17:00
        if ($hour >= 8 && $hour < 17) {
            return 'matutino';
        }
        
        // Vespertino: 17:00 - 24:00 y 00:00 - 01:40
        if ($hour >= 17 || ($hour >= 0 && $hour < 2)) {
            // Si es la hora 01:00-01:59, verificar si es antes de 01:40
            if ($hour == 1 && $minute >= 40) {
                return 'matutino'; // Después de 01:40, consideramos matutino del siguiente día
            }
            return 'vespertino';
        }
        
        // Entre 02:00 y 08:00, consideramos matutino del siguiente día
        return 'matutino';
    }
    
    private function calculateKPIs(DailyProgram $program, WorkCenter $workCenter)
    {
        // Capacidad Instalada - del centro de trabajo
        $capacidadInstalada = $workCenter->installed_capacity ?? 0;
        
        // Programado - del programa diario
        $programado = $program->programmed ?? 0;
        
        // Atrasado - del programa diario
        $atrasado = $program->backwardness ?? 0;
        
        // A Producir - programado + atrasado - adelantado
        $aProducir = max($programado + $atrasado - ($program->advanced ?? 0), 0);
        
        // Piezas Producidas - del programa diario
        $piezasProducidas = $program->total_produced ?? 0;
        
        // Horas Extras - placeholder (0 por ahora, hasta que se defina cómo calcularlo)
        $horasExtras = 0;
        $horasExtrasStatus = 'NO';
        
        return [
            'capacidad_instalada' => $capacidadInstalada,
            'programado' => $programado,
            'atrasado' => $atrasado,
            'a_producir' => $aProducir,
            'piezas_producidas' => $piezasProducidas,
            'horas_extras' => $horasExtras,
            'horas_extras_status' => $horasExtrasStatus,
        ];
    }
    
    private function calculateDefaultKPIs(WorkCenter $workCenter)
    {
        // KPIs por defecto cuando no hay programa diario
        return [
            'capacidad_instalada' => $workCenter->installed_capacity ?? 0,
            'programado' => 0,
            'atrasado' => 0,
            'a_producir' => 0,
            'piezas_producidas' => 0,
            'horas_extras' => 0,
            'horas_extras_status' => 'NO',
        ];
    }
    
    private function calculateMetrics(DailyProgram $program, WorkCenter $workCenter)
    {
        // Paros y costo
        $totalStrikes = $program->strikes->count();
        $totalStrikeCost = 0;
        
        // Calcular costo de paros (costo promedio por minuto * minutos de paro)
        $avgCostPerMinute = $workCenter->productionLines->avg('cost') ?? 0;
        $totalStrikeMinutes = $program->strikes->sum('minutes');
        $totalStrikeCost = round(($avgCostPerMinute / 60) * $totalStrikeMinutes, 2);
        
        return [
            'total_strikes' => $totalStrikes,
            'strike_cost' => $totalStrikeCost,
        ];
    }
    
    private function calculateQualityMetrics(WorkCenter $workCenter)
    {
        // Placeholder - valores en 0 hasta que se implementen tablas de calidad
        return [
            'piezas_rechazadas' => 0,
            'rechazos_garantia' => 0,
            'inspecciones_realizadas' => 0,
            'reprocesos_calidad' => 0,
        ];
    }
    
    private function getRecentCompliance($workCenterId, $days = 5)
    {
        $compliance = [];
        $today = Carbon::today();
        
        for ($i = 0; $i < $days; $i++) {
            $date = $today->copy()->subDays($i);
            
            // Obtener programas del día (todos los turnos)
            $programs = DailyProgram::where('id_work_center', $workCenterId)
                ->where('date', $date->format('Y-m-d'))
                ->get();
            
            if ($programs->isEmpty()) {
                continue;
            }
            
            $totalProduced = 0;
            $totalToProduced = 0;
            
            foreach ($programs as $program) {
                $totalProduced += $program->total_produced ?? 0;
                $totalToProduced += max($program->programmed + $program->backwardness - $program->advanced, 0);
            }
            
            $compliancePercent = $totalToProduced > 0 ? round(($totalProduced / $totalToProduced) * 100, 0) : 0;
            
            // Determinar estado
            $status = 'success';
            if ($compliancePercent < 80) {
                $status = 'danger';
            } elseif ($compliancePercent < 95) {
                $status = 'warning';
            }
            
            $compliance[] = [
                'date' => $date->format('d/m/Y'),
                'prog_1' => $programs->where('shift', 'matutino')->first()->programmed ?? 0,
                'real_1' => $programs->where('shift', 'matutino')->first()->total_produced ?? 0,
                'prog_2' => $programs->where('shift', 'vespertino')->first()->programmed ?? 0,
                'real_2' => $programs->where('shift', 'vespertino')->first()->total_produced ?? 0,
                'compliance' => $compliancePercent,
                'status' => $status,
            ];
        }
        
        return $compliance;
    }
    
    private function calculateAreaStatus(DailyProgram $program = null, WorkCenter $workCenter)
    {
        if (!$program) {
            return [
                'status' => 'idle',
                'color' => 'gray',
                'label' => 'Sin datos',
                'time' => '00:00:00',
                'message' => 'No hay programa diario registrado para este centro de trabajo y turno.',
            ];
        }
        
        // Determinar estado basado en paros activos
        $activeStrike = $program->strikes()
            ->where(function($query) {
                $query->whereNull('end_time')
                      ->orWhere('end_time', '>=', now());
            })
            ->first();
        
        if ($activeStrike) {
            $startTime = Carbon::parse($activeStrike->start_time);
            $duration = $startTime->diff(now());
            
            return [
                'status' => 'stopped',
                'color' => 'red',
                'label' => 'Rojo',
                'time' => $duration->format('%H:%I:%S'),
                'message' => 'Hay un paro activo en el área. Se requiere atención inmediata.',
            ];
        }
        
        // Calcular estado basado en producción acumulada
        $totalToProduced = max($program->programmed + $program->backwardness - $program->advanced, 0);
        $shiftHours = $program->shift_hours ?? 8;
        $totalSchedules = $program->schedules->count();
        
        // Calcular producción real acumulada de todos los schedules
        $cumulativeProduced = 0;
        $schedulesWithProduction = 0;
        foreach ($program->schedules as $schedule) {
            $cumulativeProduced += $schedule->produced;
            if ($schedule->produced > 0) {
                $schedulesWithProduction++;
            }
        }
        
        // Si no hay producción registrada, usar total_produced del programa
        if ($cumulativeProduced == 0 && $program->total_produced > 0) {
            $cumulativeProduced = $program->total_produced;
        }
        
        // Verificar si el turno aún no ha iniciado (no hay schedules con producción)
        if ($schedulesWithProduction == 0 && $totalSchedules > 0) {
            return [
                'status' => 'idle',
                'color' => 'gray',
                'label' => 'Sin datos',
                'time' => '00:00:00',
                'message' => 'El turno aún no ha iniciado.',
            ];
        }
        
        // Calcular producción esperada basada en el número de schedules con producción
        // Si hay schedules con producción, usar ese número como horas transcurridas
        $hoursElapsed = $schedulesWithProduction > 0 ? $schedulesWithProduction : 1;
        $expectedPerHour = $shiftHours > 0 ? $totalToProduced / $shiftHours : 0;
        $cumulativeExpected = round($expectedPerHour * $hoursElapsed, 2);
        
        // Verificar si el turno ya terminó (todos los schedules tienen producción)
        if ($schedulesWithProduction >= $totalSchedules && $totalSchedules > 0) {
            // Usar cumplimiento final del turno
            $compliance = $totalToProduced > 0 ? ($cumulativeProduced / $totalToProduced) * 100 : 0;
        } else {
            // Usar cumplimiento acumulado hasta el momento
            $compliance = $cumulativeExpected > 0 ? ($cumulativeProduced / $cumulativeExpected) * 100 : 0;
        }
        
        // Aplicar umbrales solicitados por el usuario
        // Verde: >=90%, Amarillo: 70-89%, Rojo: <70%
        if ($compliance >= 90) {
            return [
                'status' => 'optimal',
                'color' => 'green',
                'label' => 'Verde',
                'time' => now()->format('H:i:s'),
                'message' => 'Operación normal y estable',
            ];
        } elseif ($compliance >= 70) {
            return [
                'status' => 'warning',
                'color' => 'yellow',
                'label' => 'Amarillo',
                'time' => now()->format('H:i:s'),
                'message' => 'Operación con ligeras variaciones.',
            ];
        } else {
            return [
                'status' => 'critical',
                'color' => 'red',
                'label' => 'Rojo',
                'time' => now()->format('H:i:s'),
                'message' => 'El rendimiento está por debajo de lo esperado. Requiere atención.',
            ];
        }
    }
}