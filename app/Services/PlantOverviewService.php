<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\DailyProgram;
use App\Models\Schedule;
use App\Models\Strike;
use App\Models\WorkCenter;

class PlantOverviewService
{
    const GREEN_THRESHOLD = 90;
    const AMBER_THRESHOLD = 70;

    const ATTRIBUTE_COLOR_MAP = [
        'rojo' => 'red',
        'amarillo' => 'amber',
        'verde' => 'green',
        'gris' => 'gray',
    ];

    const ATTRIBUTE_COLOR_RANK = [
        'red' => 3,
        'amber' => 2,
        'gray' => 1,
        'green' => 0,
    ];

    /**
     * Arma el tablero de planta completa para un día (todos los turnos del día,
     * no solo el turno en curso: un programa extendido a vespertino reparte el
     * programado original entre matutino y vespertino, así que el cumplimiento
     * real solo se ve completo sumando el día).
     */
    public function build(string $date): array
    {
        $workCenters = WorkCenter::with(['productionLines', 'attributes'])
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

        $areaSummary = $this->areaAttributesSummary($workCenter);

        if ($dailyPrograms->isEmpty()) {
            return $lines->map(fn ($line) => $this->idleTile($workCenter, $line, $areaSummary))->all();
        }

        $totalToProduce = $dailyPrograms->sum(
            fn (DailyProgram $program) => max($program->programmed + $program->backwardness - $program->advanced, 0)
        );
        $totalShiftHours = max($dailyPrograms->sum('shift_hours'), 0.1);
        $expectedPerHourCenter = $totalToProduce / $totalShiftHours;

        $dailyProgramIds = $dailyPrograms->pluck('id');
        $totalCapacity = $lines->sum('installed_capacity');

        $schedulesByLine = Schedule::whereIn('id_daily_program', $dailyProgramIds)
            ->get()
            ->groupBy('id_production_line');

        $strikesByLine = Strike::whereIn('id_daily_program', $dailyProgramIds)
            ->orderByDesc('id')
            ->get()
            ->groupBy('id_production_lines');

        return $lines->map(function ($line) use ($workCenter, $expectedPerHourCenter, $totalCapacity, $totalShiftHours, $lines, $schedulesByLine, $strikesByLine, $areaSummary) {
            $weight = $totalCapacity > 0
                ? ($line->installed_capacity ?? 0) / $totalCapacity
                : 1 / $lines->count();

            $lineSchedules = $schedulesByLine->get($line->id, collect());
            $hoursElapsed = $lineSchedules->filter(fn ($s) => $s->produced > 0)->count();
            $lineStrikes = $strikesByLine->get($line->id, collect());

            if ($hoursElapsed === 0) {
                return $this->notStartedTile($workCenter, $line, $areaSummary);
            }

            $lineProduced = $lineSchedules->sum('produced');

            // Cumplimiento contra el plan: lo que le corresponde a esta línea del programa
            // del día (repartido por su peso de capacidad), prorrateado a las horas ya transcurridas.
            $expectedSoFarPlan = $expectedPerHourCenter * $weight * $hoursElapsed;
            $pctPlan = $expectedSoFarPlan > 0
                ? round(($lineProduced / $expectedSoFarPlan) * 100)
                : ($lineProduced > 0 ? 100 : 0);

            // Aprovechamiento de capacidad: la capacidad instalada es por día completo, así que se
            // prorratea por la fracción del turno ya transcurrida, no se multiplica por hora.
            $expectedSoFarCapacity = ($line->installed_capacity ?? 0) * ($hoursElapsed / $totalShiftHours);
            $pctCapacity = $expectedSoFarCapacity > 0
                ? round(($lineProduced / $expectedSoFarCapacity) * 100)
                : ($lineProduced > 0 ? 100 : 0);

            return $this->buildTile($workCenter, $line, $pctPlan, $pctCapacity, $lineStrikes, $areaSummary);
        })->all();
    }

    private function buildTile(WorkCenter $workCenter, $line, int $pctPlan, int $pctCapacity, $strikes, array $areaSummary): array
    {
        $activeStrike = $strikes->first(fn (Strike $strike) => !$strike->end_time);

        if ($activeStrike) {
            $status = 'red';
            $reason = $activeStrike->description ?: 'Paro activo';
        } else {
            $status = $this->statusForPct($pctPlan);
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
            'pct' => $pctPlan,
            'pct_capacity' => $pctCapacity,
            'status' => $status,
            'reason' => $reason,
            'area_status' => $areaSummary['status'],
            'area_attributes' => $areaSummary['attributes'],
        ];
    }

    private function idleTile(WorkCenter $workCenter, $line, array $areaSummary): array
    {
        return [
            'area' => $workCenter->name,
            'phase' => $workCenter->phase,
            'name' => $line->title,
            'pct' => 0,
            'pct_capacity' => 0,
            'status' => 'gray',
            'reason' => 'Sin programa registrado hoy',
            'area_status' => $areaSummary['status'],
            'area_attributes' => $areaSummary['attributes'],
        ];
    }

    private function notStartedTile(WorkCenter $workCenter, $line, array $areaSummary): array
    {
        return [
            'area' => $workCenter->name,
            'phase' => $workCenter->phase,
            'name' => $line->title,
            'pct' => 0,
            'pct_capacity' => 0,
            'status' => 'gray',
            'reason' => 'El turno aún no ha iniciado',
            'area_status' => $areaSummary['status'],
            'area_attributes' => $areaSummary['attributes'],
        ];
    }

    private function areaAttributesSummary(WorkCenter $workCenter): array
    {
        $attributes = $workCenter->attributes->where('active', true);

        if ($attributes->isEmpty()) {
            return ['status' => 'gray', 'attributes' => []];
        }

        $mapped = $attributes->map(fn (Attribute $attribute) => [
            'name' => $attribute->name,
            'color' => self::ATTRIBUTE_COLOR_MAP[$attribute->color] ?? 'gray',
            'changed_at' => $attribute->color_changed_at?->toIso8601String(),
        ])->values();

        $worst = $mapped->reduce(function ($carry, $attr) {
            $rank = self::ATTRIBUTE_COLOR_RANK[$attr['color']] ?? 0;
            return $rank > $carry['rank'] ? ['rank' => $rank, 'color' => $attr['color']] : $carry;
        }, ['rank' => -1, 'color' => 'green']);

        return ['status' => $worst['color'], 'attributes' => $mapped->all()];
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
