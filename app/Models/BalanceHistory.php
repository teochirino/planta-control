<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceHistory extends Model
{
    protected $table = 'balance_history';
    
    protected $fillable = [
        'id_work_center',
        'id_daily_program',
        'processed_by',
        'programmed',
        'backwardness',
        'advanced',
        'total_to_produce',
        'total_produced',
        'total_rejected',
        'final_backwardness',
        'final_advanced',
        'processed_at',
    ];
    
    protected $casts = [
        'processed_at' => 'datetime',
    ];
    
    public function workCenter()
    {
        return $this->belongsTo(WorkCenter::class, 'id_work_center');
    }
    
    public function dailyProgram()
    {
        return $this->belongsTo(DailyProgram::class, 'id_daily_program');
    }
    
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
