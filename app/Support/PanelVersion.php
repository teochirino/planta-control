<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Sello de versión por centro de trabajo para los paneles de TV.
 *
 * Los televisores consultan este sello cada pocos segundos en lugar de recargar
 * el panel completo: la recarga pesada (KPIs por línea, paros, historial) sólo
 * se dispara cuando el sello cambió, es decir, cuando de verdad hubo un cambio.
 *
 * Se guarda en la caché (driver "file" en producción), así que consultarlo no
 * toca MySQL.
 */
class PanelVersion
{
    private const PREFIX = 'tv_panel_version:';

    /**
     * Centros ya marcados en esta petición. Evita escribir la caché una vez por
     * cada fila al guardar la tabla de producción completa.
     *
     * @var array<int, true>
     */
    private static array $bumped = [];

    public static function stamp(?int $workCenterId): string
    {
        if (! $workCenterId) {
            return '0';
        }

        return (string) Cache::get(self::PREFIX.$workCenterId, '0');
    }

    public static function bump(?int $workCenterId): void
    {
        if (! $workCenterId || isset(self::$bumped[$workCenterId])) {
            return;
        }

        self::$bumped[$workCenterId] = true;

        Cache::forever(self::PREFIX.$workCenterId, (string) (int) round(microtime(true) * 1000));
    }
}
