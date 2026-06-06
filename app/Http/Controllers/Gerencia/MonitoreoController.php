<?php

namespace App\Http\Controllers\Gerencia;

use App\Http\Controllers\Controller;
use App\Models\WorkCenter;
use App\Models\DailyProgram;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MonitoreoController extends Controller
{
    public function index()
    {
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
            ],
            'production_lines' => $productionByLine,
            'has_data' => $dailyPrograms->count() > 0
        ];
    }
    
    return response()->json([
        'date' => $date,
        'workCenters' => $result,
    ]);
}
}