<?php

namespace App\Observers;

use App\Models\Attribute;
use App\Models\DailyProgram;
use App\Models\Schedule;
use App\Models\Strike;
use App\Models\WorkCenterBalance;
use App\Support\PanelVersion;
use Illuminate\Database\Eloquent\Model;

/**
 * Marca el centro de trabajo como "cambiado" cada vez que se toca algo que el
 * panel de TV muestra.
 *
 * Va por eventos de Eloquent y no llamada por llamada en cada controlador, para
 * que cualquier ruta de escritura -actual o futura- quede cubierta sola.
 */
class PanelVersionObserver
{
    /**
     * Centro de trabajo de cada daily_program ya resuelto en esta petición.
     *
     * @var array<int, int|null>
     */
    private static array $programCenters = [];

    public function saved(Model $model): void
    {
        // Un guardado que no cambió nada (por ejemplo el autoguardado reenviando
        // el mismo valor) no debe hacer recargar las TVs.
        if (! $model->wasRecentlyCreated && ! $model->wasChanged()) {
            return;
        }

        PanelVersion::bump($this->workCenterId($model));
    }

    public function deleted(Model $model): void
    {
        PanelVersion::bump($this->workCenterId($model));
    }

    private function workCenterId(Model $model): ?int
    {
        if ($model instanceof DailyProgram
            || $model instanceof Attribute
            || $model instanceof WorkCenterBalance) {
            return $model->id_work_center ? (int) $model->id_work_center : null;
        }

        if ($model instanceof Schedule || $model instanceof Strike) {
            return $this->centerOfProgram($model->id_daily_program);
        }

        return null;
    }

    private function centerOfProgram($dailyProgramId): ?int
    {
        if (! $dailyProgramId) {
            return null;
        }

        $id = (int) $dailyProgramId;

        if (! array_key_exists($id, self::$programCenters)) {
            $center = DailyProgram::where('id', $id)->value('id_work_center');
            self::$programCenters[$id] = $center ? (int) $center : null;
        }

        return self::$programCenters[$id];
    }
}
