<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Schedule extends Model
{
    protected $table = 'schedules';
    
    protected $fillable = [
        'id_daily_program',
        'id_production_line',
        'start_time',
        'end_time',
        'produced'
    ];
    
    protected $casts = [
        'produced' => 'integer'
    ];
    
    // Relación con DailyProgram
    public function dailyProgram()
    {
        return $this->belongsTo(DailyProgram::class, 'id_daily_program');
    }
    
    // Relación con ProductionLine
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class, 'id_production_line');
    }
    
    // Accessor para start_time (formato HH:MM)
    public function getStartTimeAttribute($value)
    {
        if (!$value) return null;
        // Si es string TIME (ej: "08:00:00") toma primeros 5 caracteres
        if (is_string($value)) {
            return substr($value, 0, 5);
        }
        return $value;
    }
    
    // Accessor para end_time (formato HH:MM)
    public function getEndTimeAttribute($value)
    {
        if (!$value) return null;
        // Si es string TIME (ej: "17:00:00") toma primeros 5 caracteres
        if (is_string($value)) {
            return substr($value, 0, 5);
        }
        return $value;
    }
    
    // Mutador para start_time (asegura formato correcto al guardar)
    public function setStartTimeAttribute($value)
    {
        if ($value) {
            // Si viene en formato HH:MM, agregar :00
            if (strlen($value) == 5) {
                $value = $value . ':00';
            }
        }
        $this->attributes['start_time'] = $value;
    }
    
    // Mutador para end_time (asegura formato correcto al guardar)
    public function setEndTimeAttribute($value)
    {
        if ($value) {
            // Si viene en formato HH:MM, agregar :00
            if (strlen($value) == 5) {
                $value = $value . ':00';
            }
        }
        $this->attributes['end_time'] = $value;
    }
    
    // Accesor para obtener la hora en formato HH:MM
    public function getHourAttribute()
    {
        $start = $this->getStartTimeAttribute($this->attributes['start_time'] ?? null);
        return $start;
    }
    
    // Accesor para rango horario
    public function getTimeRangeAttribute()
    {
        $start = $this->getStartTimeAttribute($this->attributes['start_time'] ?? null);
        $end = $this->getEndTimeAttribute($this->attributes['end_time'] ?? null);
        
        if ($start && $end) {
            return $start . ' - ' . $end;
        }
        return null;
    }
    
    // Scope para filtrar por fecha del programa
    public function scopeByDate($query, $date)
    {
        return $query->whereHas('dailyProgram', function($q) use ($date) {
            $q->where('date', $date);
        });
    }
    
    // Scope para filtrar por línea de producción
    public function scopeByProductionLine($query, $lineId)
    {
        return $query->whereHas('dailyProgram', function($q) use ($lineId) {
            $q->where('id_production_lines', $lineId);
        });
    }
}