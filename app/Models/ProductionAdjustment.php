<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionAdjustment extends Model
{
    protected $fillable = [
        'id_daily_program',
        'id_work_center',
        'adjustment_type',
        'previous_value',
        'new_value',
        'difference',
        'reference_type',
        'reference_id',
        'reason',
        'adjusted_by',
        'notes',
        'source_program_id',
        'target_program_id',
        'field_adjusted',
        'adjustment_category',
    ];

    protected $appends = ['field_label', 'adjusted_by_name'];

    protected $casts = [
        'previous_value' => 'integer',
        'new_value' => 'integer',
        'difference' => 'integer',
    ];
    
    public function dailyProgram()
    {
        return $this->belongsTo(DailyProgram::class, 'id_daily_program');
    }
    
    public function workCenter()
    {
        return $this->belongsTo(WorkCenter::class, 'id_work_center');
    }
    
    public function adjustedBy()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    public function sourceProgram()
    {
        return $this->belongsTo(Program::class, 'source_program_id');
    }

    public function targetProgram()
    {
        return $this->belongsTo(Program::class, 'target_program_id');
    }

    // Accessor para el nombre del usuario que hizo el ajuste
    public function getAdjustedByNameAttribute()
    {
        return $this->adjustedBy ? $this->adjustedBy->name : null;
    }

    // Scope para ajustes por tipo
    public function scopeByType($query, $type)
    {
        return $query->where('adjustment_type', $type);
    }
    
    // Scope para ajustes recientes
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Scope para ajustes por categoría
    public function scopeByCategory($query, $category)
    {
        return $query->where('adjustment_category', $category);
    }

    // Scope para ajustes por campo
    public function scopeByField($query, $field)
    {
        return $query->where('field_adjusted', $field);
    }

    public function getFieldLabelAttribute()
    {
        $labels = [
            'programmed' => 'Programado',
            'backwardness' => 'Atraso',
            'advanced' => 'Adelanto',
            'total_produced' => 'Total Fabricado',
            'total_rejected' => 'Total Rechazado',
        ];

        return $labels[$this->field_adjusted] ?? $this->field_adjusted;
    }

    // Scope para transferencias
    public function scopeTransfers($query)
    {
        return $query->where('adjustment_category', 'transfer');
    }

    // Scope para correcciones
    public function scopeCorrections($query)
    {
        return $query->where('adjustment_category', 'correction');
    }

    // Scope para descubrimientos
    public function scopeDiscoveries($query)
    {
        return $query->where('adjustment_category', 'discovery');
    }
}
