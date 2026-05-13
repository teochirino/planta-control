<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attribute extends Model
{
    protected $fillable = [
        'id_work_center',
        'name',
        'color',
        'color_changed_at',
        'order',
        'active'
    ];
    
    protected $casts = [
        'color_changed_at' => 'datetime',
        'active' => 'boolean',
    ];
    
    protected $appends = ['elapsed_time'];
    
    public function workCenter()
    {
        return $this->belongsTo(WorkCenter::class, 'id_work_center');
    }
    
    public function colorChangeHistories()
    {
        return $this->hasMany(ColorChangeHistory::class, 'id_attribute');
    }
    
    public function getElapsedTimeAttribute()
    {
        if (!$this->color_changed_at) {
            return '0h 0m';
        }
        
        $diff = Carbon::now()->diff($this->color_changed_at);
        
        if ($diff->days > 0) {
            return "{$diff->days}d {$diff->h}h";
        }
        
        return "{$diff->h}h {$diff->i}m";
    }
}
