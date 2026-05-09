<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Strike extends Model
{
    protected $table = 'strikes';
    
    protected $fillable = [
        'id_production_lines',
        'date',
        'id_daily_program',
        'description',
        'start_time',
        'end_time',
        'minutes',
        'cost'
    ];
    
    protected $casts = [
        'date' => 'date',
        'minutes' => 'integer',
        'cost' => 'decimal:2',
    ];
    
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class, 'id_production_lines');
    }
    
    public function dailyProgram()
    {
        return $this->belongsTo(DailyProgram::class, 'id_daily_program');
    }
    
    // Calcular minutos y costo automáticamente
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($strike) {
            if ($strike->start_time && $strike->end_time) {
                $start = Carbon::parse($strike->date . ' ' . $strike->start_time);
                $end = Carbon::parse($strike->date . ' ' . $strike->end_time);
                $strike->minutes = $end->diffInMinutes($start);
                
                // Calcular costo automáticamente
                if ($strike->productionLine && $strike->productionLine->cost > 0) {
                    $strike->cost = ($strike->minutes * $strike->productionLine->cost);
                }
            }
        });
    }
    
    // Accesor para formato de minutos (horas:minutos)
    public function getFormattedMinutesAttribute()
    {
        if (!$this->minutes) return '0 min';
        
        $hours = floor($this->minutes / 60);
        $minutes = $this->minutes % 60;
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}min";
        }
        return "{$minutes} min";
    }
    
    // Verificar si el paro está activo
    public function getIsActiveAttribute()
    {
        return $this->start_time && !$this->end_time;
    }
    
    // Scope para filtrar por fecha
    public function scopeFromDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }
}