<?php

namespace App\Services;

use App\Models\ProductionLine;
use App\Models\User;

class ProductionLineService
{
    /**
     * Obtener líneas de producción del usuario
     */
    public function getUserProductionLines(User $user)
    {
        //return $user->productionLines()->with('workCenter')->get();
        return $user->productionLines()
    ->wherePivot('can_view', true)
    ->with('workCenter')
    ->get();
    }

    /**
     * Verificar si el usuario puede ver la línea
     */
    public function canUserViewLine(User $user, int $lineId): bool
    {
        return $user->productionLines()
            ->where('production_lines.id', $lineId)
            ->wherePivot('can_view', true)
            ->exists();
    }

    /**
     * Verificar si el usuario puede editar la línea
     */
    public function canUserEditLine(User $user, int $lineId): bool
    {
        return $user->productionLines()
            ->where('production_lines.id', $lineId)
            ->wherePivot('can_edit', true)
            ->exists();
    }

    /**
     * Asignar líneas de producción a un usuario
     */
    public function assignProductionLines(User $user, array $lineIds): void
    {
        $syncData = [];
        foreach ($lineIds as $lineId) {
            $syncData[$lineId] = [
                'can_view' => true,
                'can_edit' => true,
                'can_delete' => false
            ];
        }
        
        $user->productionLines()->sync($syncData);
    }
}
