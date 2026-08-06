<?php

namespace App\Services;

use App\Models\DailyProgram;
use App\Models\Schedule;
use App\Models\Strike;
use App\Models\WorkCenter;

class PlantOverviewService
{
    const GREEN_THRESHOLD = 90;
    const AMBER_THRESHOLD = 70;

    /**
     * Arma el tablero de planta completa para un día (todos los turnos del día,
     * no solo el turno en curso: un programa extendido a vespertino reparte el
     * programado original entre matutino y vespertino, así que el cumplimiento
     * real solo se ve completo sumando el día).
     */
    public function build(string $date): array
    {
        $workCenters = WorkCenter::with('productionLines')
            ->orderBy('phase')
            ->orderBy('id')
            ->get();

        $machines = [];

        foreach ($workCenters as $workCenter) {
            $machines = array_merge($machines, $this->buildWorkCenterTiles($workCenter, $date));
        }

        return [
            'date' => $date,
            'machines' => $machines,
            'stats' => $this->buildStats($machines, $workCenters->count()),
        ];
    }

    private function buildWorkCenterTiles(WorkCenter $workCenter, string $date): array
    {
        $dailyPrograms = DailyProgram::where('id_work_center', $workCenter->id)
            ->where('date', $date)
            ->get();

        $lines = $workCenter->productionLines;

        if ($lines->isEmpty()) {
            return [];
        }

        if ($dailyPrograms->isEmpty()) {
            return $lines->map(fn ($line) => $this->idleTile($workCenter, $line))->all();
        }

        $totalToProduce = $dailyPrograms->sum(
            fn (DailyProgram $program) => max($program->programmed + $program->backwardness - $program->advanced, 0)
        );

        $dailyProgramIds = $dailyPrograms->pluck('id');
        $totalCapacity = $lines->sum('installed_capacity');

        $producedByLine = Schedule::whereIn('id_daily_program', $dailyProgramIds)
            ->get()
            ->groupBy('id_production_line')
            ->map(fn ($schedules) => $schedules->sum('produced'));

        $strikesByLine = Strike::whereIn('id_daily_program', $dailyProgramIds)
            ->orderByDesc('id')
            ->get()
            ->groupBy('id_production_lines');

        return $lines->map(function ($line) use ($workCenter, $totalToProduce, $totalCapacity, $lines, $producedByLine, $strikesByLine) {
            $weight = $totalCapacity > 0
                ? ($line->installed_capacity ?? 0) / $totalCapacity
                : 1 / $lines->count();

            $lineToProduce = round($totalToProduce * $weight);
            $lineProduced = $producedByLine->get($line->id, 0);
            $lineStrikes = $strikesByLine->get($line->id, collect());

            $pct = $lineToProduce > 0
                ? round(($lineProduced / $lineToProduce) * 100)
                : ($lineProduced > 0 ? 100 : 0);

            return $this->buildTile($workCenter, $line, $pct, $lineStrikes);
        })->all();
    }

    private function buildTile(WorkCenter $workCenter, $line, int $pct, $strikes): array
    {
        $activeStrike = $strikes->first(fn (Strike $strike) => !$strike->end_time);

        if ($activeStrike) {
            $status = 'red';
            $reason = $activeStrike->description ?: 'Paro activo';
        } else {
            $status = $this->statusForPct($pct);
            $reason = null;

            if ($status !== 'green') {
                $lastStrike = $strikes->first();
                $reason = $lastStrike?->description
                    ?: ($status === 'amber' ? 'Atraso vs. programa' : 'Producción por debajo de lo esperado');
            }
        }

        return [
            'area' => $workCenter->name,
            'phase' => $workCenter->phase,
            'name' => $line->title,
            'pct' => $pct,
            'status' => $status,
            'reason' => $reason,
        ];
    }

    private function idleTile(WorkCenter $workCenter, $line): array
    {
        return [
            'area' => $workCenter->name,
            'phase' => $workCenter->phase,
            'name' => $line->title,
            'pct' => 0,
            'status' => 'gray',
            'reason' => 'Sin programa registrado hoy',
        ];
    }

    private function statusForPct(int $pct): string
    {
        if ($pct >= self::GREEN_THRESHOLD) {
            return 'green';
        }

        if ($pct >= self::AMBER_THRESHOLD) {
            return 'amber';
        }

        return 'red';
    }

    private function buildStats(array $machines, int $areasCount): array
    {
        $withData = array_filter($machines, fn ($m) => $m['status'] !== 'gray');
        $total = count($withData);

        $green = count(array_filter($withData, fn ($m) => $m['status'] === 'green'));
        $amber = count(array_filter($withData, fn ($m) => $m['status'] === 'amber'));
        $red = count(array_filter($withData, fn ($m) => $m['status'] === 'red'));
        $gray = count($machines) - $total;
        $avgPct = $total > 0 ? round(array_sum(array_column($withData, 'pct')) / $total) : 0;

        return [
            'avg_pct' => $avgPct,
            'green' => $green,
            'amber' => $amber,
            'red' => $red,
            'gray' => $gray,
            'areas' => $areasCount,
        ];
    }
}
