<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RejectedPiece extends Model
{
    protected $fillable = [
        'id_schedule',
        'id_daily_program',
        'id_work_center',
        'id_production_line',
        'quantity',
        'rejection_reason',
        'rejected_by',
        'rejected_at',
        'resolution_status',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
        'new_pieces_quantity',
        'new_pieces_schedule_id',
    ];
    
    protected $casts = [
        'rejected_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];
    
    public function schedule()
    {
        return $this->belongsTo(Schedule::class, 'id_schedule');
    }
    
    public function dailyProgram()
    {
        return $this->belongsTo(DailyProgram::class, 'id_daily_program');
    }
    
    public function workCenter()
    {
        return $this->belongsTo(WorkCenter::class, 'id_work_center');
    }
    
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class, 'id_production_line');
    }
    
    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
    
    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
    
    public function replacementSchedule()
    {
        return $this->belongsTo(Schedule::class, 'new_pieces_schedule_id');
    }
    
    // Scope para piezas pendientes de resolución
    public function scopePending($query)
    {
        return $query->where('resolution_status', 'pendiente');
    }
    
    // Scope para piezas resueltas
    public function scopeResolved($query)
    {
        return $query->whereIn('resolution_status', ['reparada', 'reemplazada', 'desechada']);
    }
    
    // Scope por centro de trabajo
    public function scopeByWorkCenter($query, $workCenterId)
    {
        return $query->where('id_work_center', $workCenterId);
    }
    
    // Scope por fecha
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('rejected_at', $date);
    }
    
    // Scope por estado de resolución
    public function scopeByStatus($query, $status)
    {
        return $query->where('resolution_status', $status);
    }
}
