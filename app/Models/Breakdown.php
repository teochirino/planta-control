<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Breakdown extends Model
{
    protected $table = 'breakdowns';
    
    protected $fillable = [
        'id_machine',
        'id_user',
        'reason',
        'start_date',
        'end_date',
        'minutes'
    ];
    
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'minutes' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    // Relación con Machine
    public function machine()
    {
        return $this->belongsTo(Machine::class, 'id_machine');
    }
    
    // Relación con User (supervisor que reporta)
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
    
    // Accesor para duración formateada
    public function getFormattedDurationAttribute()
    {
        if ($this->minutes) {
            $hours = floor($this->minutes / 60);
            $minutes = $this->minutes % 60;
            
            if ($hours > 0) {
                return "{$hours}h {$minutes}min";
            }
            return "{$minutes} minutos";
        }
        
        // Si no hay minutos calculados, calcular entre fechas
        if ($this->end_date) {
            $diffInMinutes = $this->start_date->diffInMinutes($this->end_date);
            $hours = floor($diffInMinutes / 60);
            $minutes = $diffInMinutes % 60;
            
            if ($hours > 0) {
                return "{$hours}h {$minutes}min";
            }
            return "{$minutes} minutos";
        }
        
        return "En curso";
    }
    
    // Accesor para saber si está activa
    public function getIsActiveAttribute()
    {
        return is_null($this->end_date);
    }
    
    // Mutador: calcula minutos automáticamente al guardar end_date
    public function setEndDateAttribute($value)
    {
        $this->attributes['end_date'] = $value;
        
        if ($value && $this->start_date) {
            $start = \Carbon\Carbon::parse($this->start_date);
            $end = \Carbon\Carbon::parse($value);
            $this->attributes['minutes'] = $start->diffInMinutes($end);
        }
    }
    
    // Scope: averías activas (sin end_date)
    public function scopeActive($query)
    {
        return $query->whereNull('end_date');
    }
    
    // Scope: averías por máquina
    public function scopeByMachine($query, $machineId)
    {
        return $query->where('id_machine', $machineId);
    }
    
    // Scope: averías por fecha
    public function scopeFromDate($query, $date)
    {
        return $query->whereDate('start_date', $date);
    }
    
    // Scope: averías entre fechas
    public function scopeDateBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_date', [$startDate, $endDate]);
    }
    
    // Scope: averías por usuario que reportó
    public function scopeByReporter($query, $userId)
    {
        return $query->where('id_user', $userId);
    }
}