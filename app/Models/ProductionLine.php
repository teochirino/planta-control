<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionLine extends Model
{
    protected $fillable = ['id_work_center', 'title', 'installed_capacity', 'cost'];
    
    protected $casts = [
        'cost' => 'decimal:2',
    ];
    
    public function workCenter()
    {
        return $this->belongsTo(WorkCenter::class, 'id_work_center');
    }
    
    public function strikes()
    {
        return $this->hasMany(Strike::class, 'id_production_lines');
    }
    
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'id_production_line');
    }
    
    public function users()
    {
        return $this->belongsToMany(
            User::class, 
            'user_production_lines', 
            'production_line_id', 
            'user_id'
        )->withTimestamps()->withPivot('can_view', 'can_edit', 'can_delete');
    }
}
