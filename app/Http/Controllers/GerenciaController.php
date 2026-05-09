<?php

namespace App\Http\Controllers;

use App\Models\WorkCenter;
use App\Models\DailyProgram;
use App\Models\Schedule;
use App\Models\Strike;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GerenciaController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        
        // Gerencia puede ver TODOS los centros de trabajo
        $workCenters = WorkCenter::with('productionLines')->get();
        
        if ($workCenters->isEmpty()) {
            return view('gerencia.no-work-centers');
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
        // Si no se especifica turno, buscar el primer programa disponible del día
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
        $recentCompliance = [];
        
        // Obtener cumplimiento de los últimos 5 días
        $recentCompliance = $this->getRecentCompliance($selectedWorkCenterId, 5);
        
        // Estado general del área
        $areaStatus = $this->calculateAreaStatus($dailyProgram, $selectedWorkCenter);
        
        return view('gerencia.dashboard', compact(
            'workCenters',
            'selectedWorkCenter',
            'selectedDate',
            'selectedShift',
            'dailyProgram',
            'kpis',
            'metrics',
            'recentCompliance',
            'qualityMetrics',
            'areaStatus'
        ));
    }
    
    private function getCurrentShift()
    {
        $hour = now()->hour;
        
        if ($hour >= 8 && $hour < 16) {
            return 'matutino';
        } elseif ($hour >= 16 && $hour < 24) {
            return 'vespertino';
        } else {
            return 'nocturno';
        }
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
                'prog_3' => $programs->where('shift', 'nocturno')->first()->programmed ?? 0,
                'real_3' => $programs->where('shift', 'nocturno')->first()->total_produced ?? 0,
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
            ];
        }
        
        // Determinar estado basado en paros activos
        $activeStrike = $program->strikes()
            ->whereNull('end_time')
            ->orWhere('end_time', '>=', now())
            ->first();
        
        if ($activeStrike) {
            $startTime = Carbon::parse($activeStrike->start_time);
            $duration = $startTime->diff(now());
            
            return [
                'status' => 'stopped',
                'color' => 'red',
                'label' => 'Rojo',
                'time' => $duration->format('%H:%I:%S'),
                'message' => 'La operación se mantiene dentro de los parámetros esperados, sin incidencias que afecten el rendimiento del área.',
            ];
        }
        
        // Verificar cumplimiento
        $totalProduced = $program->schedules->sum('produced');
        $totalToProduced = max($program->programmed + $program->backwardness - $program->advanced, 0);
        $compliance = $totalToProduced > 0 ? ($totalProduced / $totalToProduced) * 100 : 0;
        
        if ($compliance >= 95) {
            return [
                'status' => 'optimal',
                'color' => 'green',
                'label' => 'Verde',
                'time' => now()->format('H:i:s'),
                'message' => 'Operación normal y estable',
            ];
        } elseif ($compliance >= 80) {
            return [
                'status' => 'warning',
                'color' => 'yellow',
                'label' => 'Amarillo',
                'time' => now()->format('H:i:s'),
                'message' => 'Operación con ligeras variaciones',
            ];
        } else {
            return [
                'status' => 'critical',
                'color' => 'red',
                'label' => 'Rojo',
                'time' => now()->format('H:i:s'),
                'message' => 'Requiere atención inmediata',
            ];
        }
    }
}
