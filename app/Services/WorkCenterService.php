<?php

namespace App\Services;

use App\Models\WorkCenter;
use App\Models\User;

class WorkCenterService
{
    /**
     * Obtener centros de trabajo del usuario
     */
    public function getUserWorkCenters(User $user)
    {
        return $user->workCenters()->with('productionLines')->get();
    }

    /**
     * Obtener todos los centros de trabajo
     */
    public function getAllWorkCenters()
    {
        return WorkCenter::with('productionLines')->get();
    }

    /**
     * Verificar si el usuario puede ver el centro
     */
    public function canUserViewWorkCenter(User $user, int $workCenterId): bool
    {
        if ($user->id_profile === 1) {
            return true;
        }

        if ($user->id_profile === 5) {
            return $user->workCenters()->where('work_centers.id', $workCenterId)->exists();
        }

        return false;
    }

    /**
     * Asignar centros de trabajo a un usuario
     */
    public function assignWorkCenters(User $user, array $workCenterIds): void
    {
        $user->workCenters()->sync($workCenterIds);
    }
}
