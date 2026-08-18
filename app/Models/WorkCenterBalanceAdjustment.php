<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkCenterBalanceAdjustment extends Model
{
    protected $fillable = [
        'id_work_center',
        'field_adjusted',
        'previous_value',
        'new_value',
        'difference',
        'reason',
        'adjusted_by',
        'notes',
    ];

    protected $appends = ['field_label', 'adjusted_by_name'];

    protected $casts = [
        'previous_value' => 'integer',
        'new_value' => 'integer',
        'difference' => 'integer',
    ];
    
    public function workCenter()
    {
        return $this->belongsTo(WorkCenter::class, 'id_work_center');
    }
    
    public function adjustedBy()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    // Accessor para el nombre del usuario que hizo el ajuste
    public function getAdjustedByNameAttribute()
    {
        return $this->adjustedBy ? $this->adjustedBy->name : null;
    }

    // Accessor para la etiqueta del campo
    public function getFieldLabelAttribute()
    {
        $labels = [
            'accumulated_backwardness' => 'Atraso Acumulado',
            'accumulated_advanced' => 'Adelanto Acumulado',
        ];

        return $labels[$this->field_adjusted] ?? $this->field_adjusted;
    }

    // Scope para ajustes por campo
    public function scopeByField($query, $field)
    {
        return $query->where('field_adjusted', $field);
    }
    
    // Scope para ajustes recientes
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now('America/Mexico_City')->subDays($days));
    }

    // Scope para ajustes por centro de trabajo
    public function scopeByWorkCenter($query, $workCenterId)
    {
        return $query->where('id_work_center', $workCenterId);
    }

    // Scope para atrasos
    public function scopeBackwardness($query)
    {
        return $query->where('field_adjusted', 'accumulated_backwardness');
    }

    // Scope para adelantos
    public function scopeAdvanced($query)
    {
        return $query->where('field_adjusted', 'accumulated_advanced');
    }
}
