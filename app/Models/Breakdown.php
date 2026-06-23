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
        'minutes',
        'confirmed_by',
        'confirmed_at',
        'confirmed_minutes'
    ];
    
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'minutes' => 'integer',
        'confirmed_minutes' => 'integer',
        'confirmed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    public function machine()
    {
        return $this->belongsTo(Machine::class, 'id_machine');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
    
    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
    
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
    
    public function getIsActiveAttribute()
    {
        return is_null($this->end_date);
    }
    
    public function setEndDateAttribute($value)
    {
        $this->attributes['end_date'] = $value;
        
        if ($value && $this->start_date) {
            $start = \Carbon\Carbon::parse($this->start_date);
            $end = \Carbon\Carbon::parse($value);
            $this->attributes['minutes'] = $start->diffInMinutes($end);
        }
    }
    
    public function scopeActive($query)
    {
        return $query->whereNull('end_date');
    }
    
    public function scopeByMachine($query, $machineId)
    {
        return $query->where('id_machine', $machineId);
    }
    
    public function scopeFromDate($query, $date)
    {
        return $query->whereDate('start_date', $date);
    }
    
    public function scopeDateBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_date', [$startDate, $endDate]);
    }
    
    public function scopeByReporter($query, $userId)
    {
        return $query->where('id_user', $userId);
    }
}
