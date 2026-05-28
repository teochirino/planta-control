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
    ];
    
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
}
