<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'cost',
        'id_machine'
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
    
    public function machine()
    {
        return $this->belongsTo(Machine::class, 'id_machine');
    }
    
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($strike) {
            // Calcular minutos si hay start_time y end_time
            if ($strike->start_time && $strike->end_time) {
                $startParts = explode(':', $strike->start_time);
                $endParts = explode(':', $strike->end_time);
                
                $startMinutes = (int)$startParts[0] * 60 + (int)$startParts[1];
                $endMinutes = (int)$endParts[0] * 60 + (int)$endParts[1];
                
                $minutes = $endMinutes - $startMinutes;
                if ($minutes < 0) {
                    $minutes += 1440;
                }
                
                $strike->minutes = $minutes;
            }
            
            // Calcular costo SIEMPRE que haya minutos. production_lines.cost es un costo
            // por HORA, así que se divide entre 60 antes de multiplicar por los minutos.
            if ($strike->minutes && $strike->id_production_lines) {
                $productionLine = ProductionLine::find($strike->id_production_lines);
                if ($productionLine && $productionLine->cost > 0) {
                    $strike->cost = $strike->minutes * (floatval($productionLine->cost) / 60);
                }
            }
        });
    }
    
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
    
    public function getIsActiveAttribute()
    {
        return $this->start_time && !$this->end_time;
    }
    
    public function scopeFromDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }
}
