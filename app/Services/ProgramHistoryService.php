<?php

namespace App\Services;

use App\Models\Program;
use App\Models\DailyProgram;
use App\Models\WorkCenter;
use App\Models\ProductionLine;
use App\Models\Schedule;
use Carbon\Carbon;

class ProgramHistoryService
{
    /**
     * Obtener historial por programa específico
     */
    public function getByProgram($programId)
    {
        $program = Program::with(['details', 'creator'])->find($programId);
        
        if (!$program) {
            return null;
        }

        $dailyPrograms = DailyProgram::where('program_id', $programId)
            ->with(['workCenter', 'schedules.productionLine'])
            ->orderBy('date')
            ->orderBy('shift')
            ->get();

        $history = $dailyPrograms->map(function ($dailyProgram) {
            return [
                'id' => $dailyProgram->id,
                'date' => Carbon::parse($dailyProgram->date)->format('d/m/Y'),
                'shift' => $dailyProgram->shift,
                'work_center' => $dailyProgram->workCenter->name,
                'work_center_id' => $dailyProgram->workCenter->id,
                'phase' => $dailyProgram->workCenter->phase,
                'programmed' => $dailyProgram->programmed,
                'produced' => $dailyProgram->total_produced,
                'backwardness' => $dailyProgram->backwardness,
                'advanced' => $dailyProgram->advanced,
                'rejected' => $dailyProgram->total_rejected,
                'efficiency' => $dailyProgram->efficiency,
                'lines' => $dailyProgram->schedules->map(function ($schedule) {
                    return [
                        'line' => $schedule->productionLine->title,
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                        'produced' => $schedule->produced,
                        'rejected' => $schedule->rejected,
                    ];
                }),
            ];
        });

        return [
            'program' => [
                'id' => $program->id,
                'codigo' => $program->codigo,
                'fecha_entrega' => $program->fecha_entrega ? Carbon::parse($program->fecha_entrega)->format('d/m/Y') : null,
                'fecha_fase1' => $program->fecha_fase1 ? Carbon::parse($program->fecha_fase1)->format('d/m/Y') : null,
                'fecha_fase2' => $program->fecha_fase2 ? Carbon::parse($program->fecha_fase2)->format('d/m/Y') : null,
                'fecha_fase3' => $program->fecha_fase3 ? Carbon::parse($program->fecha_fase3)->format('d/m/Y') : null,
                'fecha_fase4' => $program->fecha_fase4 ? Carbon::parse($program->fecha_fase4)->format('d/m/Y') : null,
                'total_piezas' => $program->total_piezas,
                'total_time' => $program->total_time,
                'creator' => $program->creator->name ?? null,
            ],
            'history' => $history,
        ];
    }

    /**
     * Obtener historial por centro de trabajo y fecha
     */
    public function getByWorkCenterAndDate($workCenterId, $date)
    {
        $workCenter = WorkCenter::find($workCenterId);
        
        if (!$workCenter) {
            return null;
        }

        $dailyPrograms = DailyProgram::where('id_work_center', $workCenterId)
            ->where('date', Carbon::parse($date)->format('Y-m-d'))
            ->with(['program', 'schedules.productionLine'])
            ->orderBy('shift')
            ->get();

        $history = $dailyPrograms->map(function ($dailyProgram) {
            return [
                'id' => $dailyProgram->id,
                'date' => Carbon::parse($dailyProgram->date)->format('d/m/Y'),
                'shift' => $dailyProgram->shift,
                'program_code' => $dailyProgram->program ? $dailyProgram->program->codigo : 'N/A',
                'program_id' => $dailyProgram->program_id,
                'programmed' => $dailyProgram->programmed,
                'produced' => $dailyProgram->total_produced,
                'backwardness' => $dailyProgram->backwardness,
                'advanced' => $dailyProgram->advanced,
                'rejected' => $dailyProgram->total_rejected,
                'efficiency' => $dailyProgram->efficiency,
                'lines' => $dailyProgram->schedules->map(function ($schedule) {
                    return [
                        'line' => $schedule->productionLine->title,
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                        'produced' => $schedule->produced,
                        'rejected' => $schedule->rejected,
                    ];
                }),
            ];
        });

        return [
            'work_center' => [
                'id' => $workCenter->id,
                'name' => $workCenter->name,
                'phase' => $workCenter->phase,
            ],
            'date' => Carbon::parse($date)->format('d/m/Y'),
            'history' => $history,
        ];
    }

    /**
     * Obtener historial global por fecha (todos los centros)
     */
    public function getByDate($date)
    {
        $dailyPrograms = DailyProgram::where('date', Carbon::parse($date)->format('Y-m-d'))
            ->with(['workCenter', 'program', 'schedules.productionLine'])
            ->orderBy('shift')
            ->get();

        // Ordenar por fase y nombre del centro de trabajo después de obtener los datos
        $dailyPrograms = $dailyPrograms->sortBy(function ($dp) {
            return [$dp->workCenter->phase, $dp->workCenter->name, $dp->shift];
        });

        $groupedByWorkCenter = $dailyPrograms->groupBy('id_work_center')->map(function ($programs, $workCenterId) {
            $workCenter = WorkCenter::find($workCenterId);
            
            return [
                'work_center' => [
                    'id' => $workCenter->id,
                    'name' => $workCenter->name,
                    'phase' => $workCenter->phase,
                ],
                'programs' => $programs->map(function ($dailyProgram) {
                    return [
                        'id' => $dailyProgram->id,
                        'date' => Carbon::parse($dailyProgram->date)->format('d/m/Y'),
                        'shift' => $dailyProgram->shift,
                        'program_code' => $dailyProgram->program ? $dailyProgram->program->codigo : 'N/A',
                        'program_id' => $dailyProgram->program_id,
                        'programmed' => $dailyProgram->programmed,
                        'produced' => $dailyProgram->total_produced,
                        'backwardness' => $dailyProgram->backwardness,
                        'advanced' => $dailyProgram->advanced,
                        'rejected' => $dailyProgram->total_rejected,
                        'efficiency' => $dailyProgram->efficiency,
                        'lines' => $dailyProgram->schedules->map(function ($schedule) {
                            return [
                                'line' => $schedule->productionLine->title,
                                'start_time' => $schedule->start_time,
                                'end_time' => $schedule->end_time,
                                'produced' => $schedule->produced,
                                'rejected' => $schedule->rejected,
                            ];
                        }),
                    ];
                }),
            ];
        })->values();

        return [
            'date' => Carbon::parse($date)->format('d/m/Y'),
            'history' => $groupedByWorkCenter,
        ];
    }

    /**
     * Obtener lista de programas disponibles para filtros
     */
    public function getAvailablePrograms()
    {
        return Program::with('creator')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($program) {
                return [
                    'id' => $program->id,
                    'codigo' => $program->codigo,
                    'fecha_entrega' => $program->fecha_entrega ? Carbon::parse($program->fecha_entrega)->format('d/m/Y') : null,
                    'total_piezas' => $program->total_piezas,
                ];
            });
    }

    /**
     * Obtener lista de centros de trabajo disponibles
     */
    public function getAvailableWorkCenters()
    {
        return WorkCenter::orderBy('phase')->orderBy('name')->get();
    }
}
