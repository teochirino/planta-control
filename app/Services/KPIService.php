<?php

namespace App\Services;

use App\Models\DailyProgram;
use App\Models\WorkCenter;
use App\Models\ProductionLine;
use Carbon\Carbon;

class KPIService
{
    /**
     * Calcular KPIs para un centro de trabajo
     */
    public function calculateCenterKPIs(DailyProgram $program, WorkCenter $workCenter): array
    {
        $totalProduced = $program->schedules->sum('produced');
        $totalToProduced = max($program->programmed + $program->backwardness - $program->advanced, 0);
        $difference = $totalProduced - $totalToProduced;
        $compliance = $totalToProduced > 0 ? round(($totalProduced / $totalToProduced) * 100, 2) : 0;
        
        $totalStrikeMinutes = $this->calculateTotalStrikeMinutes($program->strikes);
        
        $totalMinutes = ($program->shift_hours ?? 9) * 60;
        
        // productionLines.cost es un costo por HORA; se divide entre 60 para obtener el costo por minuto.
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
            'strike_minutes' => $totalStrikeMinutes,
            // Un programa de recuperación reutiliza "programmed" como meta de atraso a
            // recuperar; el frontend usa esta bandera solo para invertir las etiquetas
            // "Programado"/"Atraso" en pantalla, sin tocar los valores ni el balance.
            'is_recovery' => optional($program->program)->program_type === 'recovery',
        ];
    }

    /**
     * Calcular KPIs para una línea de producción
     */
    public function calculateLineKPIs(DailyProgram $program, ProductionLine $line, $schedules, $strikes): array
    {
        $totalProduced = $schedules->sum('produced');
        $totalStrikeMinutes = $this->calculateTotalStrikeMinutes($strikes);
        
        // line.cost es un costo por HORA; se divide entre 60 para obtener el costo por minuto.
        $costPerHour = $line->cost ?? 0;
        $strikeCost = ($costPerHour / 60) * $totalStrikeMinutes;
        
        return [
            'fabricated' => $totalProduced,
            'strike_minutes' => $totalStrikeMinutes,
            'strike_cost' => round($strikeCost, 2),
        ];
    }

    /**
     * Calcular KPIs para gerencia
     */
    public function calculateGerenciaKPIs(DailyProgram $program = null, WorkCenter $workCenter): array
    {
        if (!$program) {
            return $this->getDefaultGerenciaKPIs($workCenter);
        }

        $capacidadInstalada = $workCenter->installed_capacity ?? 0;
        $programado = $program->programmed ?? 0;
        $atrasado = $program->backwardness ?? 0;
        $aProducir = max($programado + $atrasado - ($program->advanced ?? 0), 0);
        $piezasProducidas = $program->total_produced ?? 0;
        
        return [
            'capacidad_instalada' => $capacidadInstalada,
            'programado' => $programado,
            'atrasado' => $atrasado,
            'a_producir' => $aProducir,
            'piezas_producidas' => $piezasProducidas,
            'horas_extras' => 0,
            'horas_extras_status' => 'NO',
        ];
    }

    /**
     * Calcular minutos totales de paros
     */
    public function calculateTotalStrikeMinutes($strikes): int
    {
        $totalMinutes = 0;
        
        foreach ($strikes as $strike) {
            if ($strike->start_time && $strike->end_time) {
                $start = Carbon::parse($strike->start_time);
                $end = Carbon::parse($strike->end_time);
                $totalMinutes += $start->diffInMinutes($end);
            }
        }
        
        return $totalMinutes;
    }

    /**
     * KPIs por defecto para gerencia
     */
    private function getDefaultGerenciaKPIs(WorkCenter $workCenter): array
    {
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

    /**
     * Calcular métricas de paros
     */
    public function calculateStrikeMetrics(DailyProgram $program, WorkCenter $workCenter): array
    {
        $totalStrikes = $program->strikes->count();
        // productionLines.cost es un costo por HORA; se divide entre 60 para obtener el costo por minuto.
        $avgCostPerHour = $workCenter->productionLines->avg('cost') ?? 0;
        $totalStrikeMinutes = $this->calculateTotalStrikeMinutes($program->strikes);
        $totalStrikeCost = round(($avgCostPerHour / 60) * $totalStrikeMinutes, 2);
        
        return [
            'total_strikes' => $totalStrikes,
            'strike_cost' => $totalStrikeCost,
            'strike_minutes' => $totalStrikeMinutes,
        ];
    }

    /**
     * Calcular métricas de calidad (placeholder)
     */
    public function calculateQualityMetrics(WorkCenter $workCenter): array
    {
        return [
            'piezas_rechazadas' => 0,
            'rechazos_garantia' => 0,
            'inspecciones_realizadas' => 0,
            'reprocesos_calidad' => 0,
        ];
    }
}
