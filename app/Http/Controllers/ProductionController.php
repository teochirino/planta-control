<?php

namespace App\Http\Controllers;

use App\Models\DailyProgram;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProductionController extends Controller
{
    public function index()
    {
        return Inertia::render('Production/Index');
    }
    
    // app/Http/Controllers/ProductionController.php

// Modificar el método getByDate
public function getByDate($date)
{
    $user = auth()->user();
    
    // Obtener líneas que el usuario puede ver
    $accessibleLines = $user->getAccessibleProductionLines()->pluck('id');
    
    $programs = DailyProgram::with(['productionLine.workCenter', 'schedules'])
        ->where('date', $date)
        ->whereIn('id_production_lines', $accessibleLines)
        ->get()
        ->map(function($program) use ($user) {
            $canEdit = $user->canEditProductionLine($program->id_production_lines);
            $totalProduced = $program->schedules->sum('produced');
            $efficiency = $program->programmed > 0 
                ? round(($totalProduced / $program->programmed) * 100, 2) 
                : 0;
            
            return [
                'id' => $program->id,
                'linea' => $program->productionLine->title,
                'work_center' => $program->productionLine->workCenter->name,
                'turno' => $program->shift,
                'programado' => $program->programmed,
                'producido' => $totalProduced,
                'eficiencia' => $efficiency,
                'can_edit' => $canEdit,
                'schedules' => $program->schedules->map(function($schedule) {
                    return [
                        'id' => $schedule->id,
                        'hora_inicio' => substr($schedule->start_time, 0, 5),
                        'hora_fin' => substr($schedule->end_time, 0, 5),
                        'producido' => $schedule->produced
                    ];
                })
            ];
        });
    
    return response()->json($programs);
}

// Modificar updateHour para verificar permisos
public function updateHour(Request $request, $scheduleId)
{
    $schedule = Schedule::findOrFail($scheduleId);
    $user = auth()->user();
    
    // Verificar si el usuario puede editar esta línea
    if (!$user->canEditProductionLine($schedule->dailyProgram->id_production_lines)) {
        return response()->json(['error' => 'No tienes permiso para editar'], 403);
    }
    
    $request->validate([
        'producido' => 'required|integer|min:0'
    ]);
    
    $schedule->produced = $request->producido;
    $schedule->save();
    
   
}
    
    public function getStats($date)
    {
        $totalProgramado = DailyProgram::where('date', $date)->sum('programmed');
        $totalProducido = DB::table('daily_programs')
            ->join('schedules', 'daily_programs.id', '=', 'schedules.id_daily_program')
            ->where('daily_programs.date', $date)
            ->sum('schedules.produced');
        
        $eficienciaGlobal = $totalProgramado > 0 
            ? round(($totalProducido / $totalProgramado) * 100, 2) 
            : 0;
        
        return response()->json([
            'total_programado' => $totalProgramado,
            'total_producido' => $totalProducido,
            'eficiencia_global' => $eficienciaGlobal
        ]);
    }


    // Obtener líneas de producción para el selector
public function getProductionLines()
{
    $lines = ProductionLine::with('workCenter')
        ->get()
        ->map(function($line) {
            return [
                'id' => $line->id,
                'title' => $line->title,
                'work_center' => $line->workCenter->name,
            ];
        });
    
    return response()->json($lines);
}

// Obtener estado de máquinas para el grid
public function getMachinesStatus()
{
    $machines = Machine::with('productionLine')
        ->get()
        ->map(function($machine) {
            // Calcular eficiencia de la máquina basada en producción reciente
            $eficiencia = $this->calculateMachineEfficiency($machine->id);
            $disponibilidad = $this->calculateMachineAvailability($machine->id);
            $prodHora = $this->calculateHourlyProduction($machine->id);
            
            // Determinar estado basado en eficiencia
            $estado = $machine->state;
            if ($eficiencia < 60 && $machine->state == 'operativo') {
                $estado = 'rendimiento_bajo';
            }
            
            return [
                'id' => $machine->id,
                'title' => $machine->title,
                'linea' => $machine->productionLine->title,
                'estado' => $estado,
                'eficiencia' => $eficiencia,
                'disponibilidad' => $disponibilidad,
                'prodHora' => $prodHora,
                'image' => null, // Para agregar imágenes después
            ];
        });
    
    return response()->json($machines);
}

// Calcular eficiencia de máquina
private function calculateMachineEfficiency($machineId)
{
    $today = date('Y-m-d');
    
    // Buscar programa diario que incluya esta máquina
    $dailyProgram = DailyProgram::whereHas('productionLine.machines', function($q) use ($machineId) {
        $q->where('id', $machineId);
    })->where('date', $today)->first();
    
    if (!$dailyProgram) return 0;
    
    $totalProduced = $dailyProgram->schedules()->sum('produced');
    $programmed = $dailyProgram->programmed;
    
    return $programmed > 0 ? round(($totalProduced / $programmed) * 100, 2) : 0;
}

// Calcular disponibilidad de máquina
private function calculateMachineAvailability($machineId)
{
    // Buscar averías activas o recientes
    $breakdowns = Breakdown::where('id_machine', $machineId)
        ->whereDate('start_date', date('Y-m-d'))
        ->sum('minutes');
    
    $totalMinutos = 480; // 8 horas de turno
    $disponibilidad = $totalMinutos > 0 
        ? round((($totalMinutos - $breakdowns) / $totalMinutos) * 100, 2) 
        : 100;
    
    return max(0, $disponibilidad);
}

// Calcular producción por hora
private function calculateHourlyProduction($machineId)
{
    $today = date('Y-m-d');
    
    $dailyProgram = DailyProgram::whereHas('productionLine.machines', function($q) use ($machineId) {
        $q->where('id', $machineId);
    })->where('date', $today)->first();
    
    if (!$dailyProgram) return 0;
    
    $totalProduced = $dailyProgram->schedules()->sum('produced');
    $horasActivas = $dailyProgram->schedules()->count();
    
    return $horasActivas > 0 ? round($totalProduced / $horasActivas) : 0;
}

// Obtener producción por hora para el gráfico
public function getHourlyProduction()
{
    $today = date('Y-m-d');
    
    $schedules = Schedule::with('dailyProgram')
        ->whereHas('dailyProgram', function($q) use ($today) {
            $q->where('date', $today);
        })
        ->get()
        ->groupBy(function($schedule) {
            return date('H:00', strtotime($schedule->start_time));
        })
        ->map(function($group) {
            return $group->sum('produced');
        });
    
    $labels = [];
    $values = [];
    
    for ($i = 8; $i <= 16; $i++) {
        $hour = sprintf('%02d:00', $i);
        $labels[] = $hour;
        $values[] = $schedules->get($hour, 0);
    }
    
    return response()->json([
        'labels' => $labels,
        'values' => $values
    ]);
}

// Calcular KPIs del dashboard
public function getDashboardKPIs()
{
    $today = date('Y-m-d');
    
    // Totales del día
    $totalProgramado = DailyProgram::where('date', $today)->sum('programmed');
    $totalProducido = Schedule::whereHas('dailyProgram', function($q) use ($today) {
        $q->where('date', $today);
    })->sum('produced');
    
    // OEE (simplificado)
    $oee = $totalProgramado > 0 
        ? round(($totalProducido / $totalProgramado) * 100, 2) 
        : 0;
    
    // Eficiencia
    $eficiencia = $oee;
    
    // Disponibilidad (sin averías)
    $totalBreakdowns = Breakdown::whereDate('start_date', $today)->sum('minutes');
    $totalMinutos = 480 * DailyProgram::where('date', $today)->count();
    $disponibilidad = $totalMinutos > 0 
        ? round((($totalMinutos - $totalBreakdowns) / $totalMinutos) * 100, 2) 
        : 100;
    
    return response()->json([
        'oee' => [
            'value' => $oee,
            'color' => $oee >= 85 ? 'good' : ($oee >= 70 ? 'warn' : 'bad')
        ],
        'produccion' => [
            'value' => $totalProducido,
            'total' => $totalProgramado,
            'color' => $totalProducido >= $totalProgramado ? 'good' : ($totalProducido >= $totalProgramado * 0.7 ? 'warn' : 'bad')
        ],
        'eficiencia' => [
            'value' => $eficiencia,
            'color' => $eficiencia >= 85 ? 'good' : ($eficiencia >= 70 ? 'warn' : 'bad')
        ],
        'disponibilidad' => [
            'value' => $disponibilidad,
            'color' => $disponibilidad >= 90 ? 'good' : ($disponibilidad >= 75 ? 'warn' : 'bad')
        ]
    ]);
}
}