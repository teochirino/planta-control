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
        'total_rejected',
        'shift_hours',
        'operator_closed',
        'operator_closed_at',
        'operator_closed_by',
        'balance_processed',
        'balance_processed_at',
        'balance_processed_by',
        'program_id',
        'extended_from_matutino',
        'manually_edited_by_engineering',
        'engineering_edited_at',
        'engineering_edited_by',
        'manually_edited_by_supervisor',
        'supervisor_edited_at',
        'supervisor_edited_by'
    ];
    
    protected $casts = [
        'date' => 'date',
        'shift_hours' => 'decimal:1',
    ];
    
    public function workCenter()
    {
        return $this->belongsTo(WorkCenter::class, 'id_work_center');
    }

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'id_daily_program');
    }
    
    public function strikes()
    {
        return $this->hasMany(Strike::class, 'id_daily_program');
    }

    public function operatorLineClosures()
    {
        return $this->hasMany(\App\Models\OperatorLineClosure::class, 'id_daily_program');
    }

    public function engineeringEditedBy()
    {
        return $this->belongsTo(User::class, 'engineering_edited_by');
    }

    public function supervisorEditedBy()
    {
        return $this->belongsTo(User::class, 'supervisor_edited_by');
    }

    // Calcular total a producir
    public function getTotalToProduceAttribute()
    {
        return max($this->programmed + $this->backwardness - $this->advanced, 0);
    }
    
    // Calcular piezas válidas (considerando reparaciones y reemplazos de rechazos)
    public function getValidPiecesAttribute()
    {
        $totalProduced = $this->total_produced ?? 0;
        $totalRejected = $this->total_rejected ?? 0;
        
        // Obtener resoluciones de rechazos
        $resolvedPieces = \App\Models\RejectedPiece::where('id_daily_program', $this->id)
            ->where('resolution_status', '!=', 'pendiente')
            ->get();
        
        $repairedCount = $resolvedPieces->where('resolution_status', 'reparada')->sum('quantity');
        $replacedCount = $resolvedPieces->where('resolution_status', 'reemplazada')->sum('new_pieces_quantity');
        
        // Piezas válidas = producidas - rechazadas + reparadas + reemplazadas
        $validPieces = $totalProduced - $totalRejected + $repairedCount + $replacedCount;
        
        return max($validPieces, 0);
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
    
    // Calcular diferencia (basado en piezas válidas)
    public function getDifferenceAttribute()
    {
        return $this->valid_pieces - $this->total_to_produce;
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
