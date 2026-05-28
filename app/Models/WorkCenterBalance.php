<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkCenterBalance extends Model
{
    protected $fillable = [
        'id_work_center',
        'accumulated_backwardness',
        'accumulated_advanced',
        'last_calculated_at',
    ];
    
    protected $casts = [
        'last_calculated_at' => 'datetime',
    ];
    
    public function workCenter()
    {
        return $this->belongsTo(WorkCenter::class, 'id_work_center');
    }
    
    // Obtener o crear balance para un centro de trabajo
    public static function getOrCreateForWorkCenter($workCenterId)
    {
        return self::firstOrCreate(
            ['id_work_center' => $workCenterId],
            [
                'accumulated_backwardness' => 0,
                'accumulated_advanced' => 0,
                'last_calculated_at' => null,
            ]
        );
    }
}
