<?php

namespace App\Services;

use App\Models\DailyProgram;
use App\Models\WorkCenter;
use App\Models\WorkCenterBalance;
use App\Models\Schedule;
use App\Models\RejectedPiece;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DailyProgramService
{
    protected $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * Obtener o crear programa diario
     */
    public function getOrCreateDailyProgram(int $workCenterId, string $date, string $shift): DailyProgram
    {
        // Obtener balance acumulado del centro de trabajo
        $workCenterBalance = WorkCenterBalance::where('id_work_center', $workCenterId)->first();
        $accumulatedBackwardness = $workCenterBalance ? $workCenterBalance->accumulated_backwardness : 0;
        $accumulatedAdvanced = $workCenterBalance ? $workCenterBalance->accumulated_advanced : 0;
        
        $program = DailyProgram::firstOrCreate(
            [
                'date' => $date,
                'id_work_center' => $workCenterId,
                'shift' => $shift,
            ],
            [
                'programmed' => 0,
                'backwardness' => $accumulatedBackwardness,
                'advanced' => $accumulatedAdvanced,
                'shift_hours' => 9.0,
            ]
        );
        
        // Si el programa ya existe pero no ha sido procesado, actualizar con el balance acumulado
        if (!$program->wasRecentlyCreated && !$program->balance_processed) {
            $program->update([
                'backwardness' => $accumulatedBackwardness,
                'advanced' => $accumulatedAdvanced,
            ]);
        }
        
        return $program;
    }

    /**
     * Actualizar programa diario
     */
    public function updateDailyProgram(DailyProgram $program, array $data): DailyProgram
    {
        $program->update([
            'programmed' => $data['programmed'] ?? $program->programmed,
            'backwardness' => $data['backwardness'] ?? $program->backwardness,
            'advanced' => $data['advanced'] ?? $program->advanced,
            'shift_hours' => $data['shift_hours'] ?? $program->shift_hours,
        ]);

        return $program->fresh();
    }

    /**
     * Generar horarios para un programa
     */
    public function generateSchedules(DailyProgram $program, $productionLines): void
    {
        $hours = $this->generateHourlySchedule($program->shift, (int)$program->shift_hours);
        
        foreach ($productionLines as $line) {
            foreach ($hours as $hour) {
                Schedule::firstOrCreate(
                    [
                        'id_daily_program' => $program->id,
                        'id_production_line' => $line->id,
                        'start_time' => $hour['start'],
                        'end_time' => $hour['end'],
                    ],
                    [
                        'produced' => 0,
                    ]
                );
            }
        }
    }

    /**
     * Actualizar total producido del programa
     */
    public function updateTotalProduced(int $dailyProgramId): void
    {
        $total = Schedule::where('id_daily_program', $dailyProgramId)->sum('produced');
        
        DailyProgram::where('id', $dailyProgramId)->update(['total_produced' => $total]);
    }

    /**
     * Generar horarios por hora
     */
    public function generateHourlySchedule(string $shift, int $hours): array
    {
        $startTime = match($shift) {
            'matutino' => '08:00',
            'vespertino' => '17:00',
            default => '08:00',
        };

        $schedule = [];
        $current = Carbon::parse($startTime);
        
        for ($i = 0; $i < $hours; $i++) {
            $start = $current->format('H:i');
            $end = $current->addHour()->format('H:i');
            $schedule[] = ['start' => $start, 'end' => $end];
        }
        
        return $schedule;
    }

    /**
     * Obtener cumplimiento reciente
     */
    public function getRecentCompliance(int $workCenterId, int $days = 5): array
    {
        $compliance = [];
        $today = Carbon::today();
        
        for ($i = 0; $i < $days; $i++) {
            $date = $today->copy()->subDays($i);
            
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

    /**
     * Obtener turno actual basado en la hora
     * Matutino: 08:00 - 17:00
     * Vespertino: 17:00 - 01:40 (del siguiente día)
     */
    public function getCurrentShift(): string
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
}
