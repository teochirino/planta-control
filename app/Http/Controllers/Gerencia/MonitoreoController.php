<?php

namespace App\Http\Controllers\Gerencia;

use App\Http\Controllers\Controller;
use App\Models\WorkCenter;
use App\Models\DailyProgram;
use App\Models\Strike;
use App\Models\RejectedPiece;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MonitoreoController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Renderizar vista según el perfil
        if ($user->isGerenteProduccion()) {
            return Inertia::render('GerenteProduccion/Monitoreo');
        }
        
        return Inertia::render('Gerencia/Monitoreo');
    }
    
    public function getData(Request $request)
{
    $date = $request->get('date', now()->format('Y-m-d'));
    
    $workCenters = WorkCenter::with(['productionLines'])->get();
    
    $result = [];
    
    foreach ($workCenters as $wc) {
        // Obtener programas del día para este centro
        $dailyPrograms = DailyProgram::with(['schedules.productionLine'])
            ->where('id_work_center', $wc->id)
            ->where('date', $date)
            ->get();
        
        // Calcular totales
        $totalProgrammed = $dailyPrograms->sum('programmed');
        $totalBackwardness = $dailyPrograms->sum('backwardness');
        $totalAdvanced = $dailyPrograms->sum('advanced');
        
        $totalProduced = 0;
        foreach ($dailyPrograms as $dp) {
            $totalProduced += $dp->schedules->sum('produced');
        }
        
        $totalToProduce = max($totalProgrammed + $totalBackwardness - $totalAdvanced, 0);
        $efficiency = $totalToProduce > 0 ? round(($totalProduced / $totalToProduce) * 100, 2) : 0;
        
        // Calcular OEE
        $oee = $this->calculateOEE($wc, $dailyPrograms, $date);
        
        // Calcular producción por línea
        $productionByLine = [];
        foreach ($wc->productionLines as $line) {
            $lineProduced = 0;
            foreach ($dailyPrograms as $dp) {
                $lineProduced += $dp->schedules
                    ->where('id_production_line', $line->id)
                    ->sum('produced');
            }
            
            $productionByLine[] = [
                'id' => $line->id,
                'title' => $line->title,
                'produced' => $lineProduced
            ];
        }
        
        $result[] = [
            'id' => $wc->id,
            'name' => $wc->name,
            'installed_capacity' => $wc->installed_capacity,
            'kpis' => [
                'programmed' => $totalProgrammed,
                'backwardness' => $totalBackwardness,
                'advanced' => $totalAdvanced,
                'produced' => $totalProduced,
                'efficiency' => $efficiency,
                'total_to_produce' => $totalToProduce,
                'oee' => $oee,
            ],
            'production_lines' => $productionByLine,
            'hourly_data' => $this->calculateHourlyData($dailyPrograms, $totalToProduce),
            'has_data' => $dailyPrograms->count() > 0
        ];
    }
    
    return response()->json([
        'date' => $date,
        'workCenters' => $result,
    ]);
}
    
    private function calculateHourlyData($dailyPrograms, $totalToProduce)
    {
        if ($dailyPrograms->isEmpty()) {
            return [
                'labels' => [],
                'expected' => [],
                'produced' => []
            ];
        }
        
        // Obtener horas de trabajo del primer programa
        $shiftHours = $dailyPrograms->first()->shift_hours ?? 8;
        
        // Determinar hora de inicio del turno (tomar del primer schedule)
        $firstSchedule = $dailyPrograms->first()->schedules->first();
        $startHour = $firstSchedule ? (int)substr($firstSchedule->start_time, 0, 2) : 8;
        
        // Generar etiquetas de horas
        $labels = [];
        $expected = [];
        $produced = [];
        
        $expectedPerHour = $shiftHours > 0 ? $totalToProduce / $shiftHours : 0;
        $cumulativeExpected = 0;
        $cumulativeProduced = 0;
        
        for ($i = 0; $i <= $shiftHours; $i++) {
            $currentHour = $startHour + $i;
            $hourLabel = sprintf('%02d:00', $currentHour % 24);
            $labels[] = $hourLabel;
            
            // Producción esperada acumulada
            $cumulativeExpected = round($expectedPerHour * $i, 2);
            $expected[] = $cumulativeExpected;
            
            // Producción real acumulada hasta esta hora
            $hourProduced = 0;
            foreach ($dailyPrograms as $dp) {
                foreach ($dp->schedules as $schedule) {
                    $scheduleStartHour = (int)substr($schedule->start_time, 0, 2);
                    if ($scheduleStartHour <= $currentHour) {
                        $hourProduced += $schedule->produced;
                    }
                }
            }
            $cumulativeProduced = $hourProduced;
            $produced[] = $cumulativeProduced;
        }
        
        return [
            'labels' => $labels,
            'expected' => $expected,
            'produced' => $produced
        ];
    }
    
    private function calculateOEE($workCenter, $dailyPrograms, $date)
    {
        if ($dailyPrograms->isEmpty()) {
            return 0;
        }
        
        // 1. Calcular Disponibilidad
        // Tiempo planificado: sumar shift_hours de todos los programas del día
        $plannedTime = $dailyPrograms->sum('shift_hours') * 60; // Convertir a minutos
        
        // Tiempo de paros: sumar minutos de strikes de las líneas de este centro
        $productionLineIds = $workCenter->productionLines->pluck('id');
        $downtimeMinutes = Strike::whereIn('id_production_lines', $productionLineIds)
            ->whereDate('date', $date)
            ->sum('minutes') ?? 0;
        
        // Tiempo real productivo
        $productiveTime = max($plannedTime - $downtimeMinutes, 0);
        
        // Disponibilidad = Tiempo real productivo / Tiempo planificado
        $availability = $plannedTime > 0 ? ($productiveTime / $plannedTime) : 0;
        
        // 2. Calcular Rendimiento
        // Piezas producidas totales
        $totalProduced = 0;
        foreach ($dailyPrograms as $dp) {
            $totalProduced += $dp->schedules->sum('produced');
        }
        
        // Piezas esperadas: programmed + backwardness - advanced
        $totalProgrammed = $dailyPrograms->sum('programmed');
        $totalBackwardness = $dailyPrograms->sum('backwardness');
        $totalAdvanced = $dailyPrograms->sum('advanced');
        $expectedPieces = max($totalProgrammed + $totalBackwardness - $totalAdvanced, 0);
        
        // Rendimiento = Piezas producidas / Piezas esperadas
        $performance = $expectedPieces > 0 ? ($totalProduced / $expectedPieces) : 0;
        
        // 3. Calcular Calidad
        // Piezas rechazadas: usar rejected_pieces.quantity
        $rejectedPieces = RejectedPiece::where('id_work_center', $workCenter->id)
            ->whereDate('rejected_at', $date)
            ->sum('quantity') ?? 0;
        
        // Piezas buenas = Piezas totales - Piezas rechazadas
        $goodPieces = max($totalProduced - $rejectedPieces, 0);
        
        // Calidad = Piezas buenas / Piezas totales
        $quality = $totalProduced > 0 ? ($goodPieces / $totalProduced) : 0;
        
        // OEE = Disponibilidad x Rendimiento x Calidad
        $oee = $availability * $performance * $quality;
        
        // Retornar como porcentaje con 2 decimales
        return round($oee * 100, 2);
    }
}