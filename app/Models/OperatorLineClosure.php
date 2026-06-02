<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperatorLineClosure extends Model
{
    protected $fillable = [
        'id_daily_program',
        'id_production_line',
        'closed_by',
        'closed_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    public function dailyProgram()
    {
        return $this->belongsTo(DailyProgram::class, 'id_daily_program');
    }

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class, 'id_production_line');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
