<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyProgram extends Model
{
    protected $fillable = [
        'date', 
        'id_work_center', 
        'shift', 
        'programmed', 
        'backwardness', 
        'advanced',
        'total_produced',
        'shift_hours'
    ];
    
    protected $casts = [
        'date' => 'date',
        'shift_hours' => 'decimal:1',
    ];
    
    public function workCenter()
    {
        return $this->belongsTo(WorkCenter::class, 'id_work_center');
    }
    
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'id_daily_program');
    }
    
    public function strikes()
    {
        return $this->hasMany(Strike::class, 'id_daily_program');
    }
    
    // Calcular total a producir
    public function getTotalToProduceAttribute()
    {
        return max($this->programmed + $this->backwardness - $this->advanced, 0);
    }
    
    // Calcular producción esperada por hora
    public function getExpectedPerHourAttribute()
    {
        return $this->shift_hours > 0 ? round($this->total_to_produce / $this->shift_hours, 2) : 0;
    }
    
    // Calcular eficiencia
    public function getEfficiencyAttribute()
    {
        if ($this->total_to_produce == 0) return 0;
        return round(($this->total_produced / $this->total_to_produce) * 100, 2);
    }
    
    // Calcular diferencia
    public function getDifferenceAttribute()
    {
        return $this->total_produced - $this->total_to_produce;
    }
    
    // Obtener color del semáforo
    public function getTrafficLightColorAttribute()
    {
        $totalStrikeMinutes = $this->strikes()->sum('minutes');
        $hasActiveStrike = $this->strikes()->whereNull('end_time')->exists();
        
        // Rojo: paro activo o paros >= 30 min o avance < 95%
        if ($hasActiveStrike || $totalStrikeMinutes >= 30 || $this->efficiency < 95) {
            return 'red';
        }
        
        // Amarillo: paros >= 20 min o avance 95-99%
        if ($totalStrikeMinutes >= 20 || ($this->efficiency >= 95 && $this->efficiency < 100)) {
            return 'yellow';
        }
        
        // Verde: avance >= 100% y sin paros activos
        if ($this->efficiency >= 100 && !$hasActiveStrike) {
            return 'green';
        }
        
        return 'gray';
    }
}