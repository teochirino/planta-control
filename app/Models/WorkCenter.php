<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\ProductionLine;

class WorkCenter extends Model
{
    protected $fillable = ['name', 'installed_capacity', 'phase'];
    
    public function productionLines()
    {
        return $this->hasMany(ProductionLine::class, 'id_work_center');
    }
    
    public function machines()
    {
        return $this->hasMany(Machine::class, 'id_work_center');
    }
    
    public function dailyPrograms()
    {
        return $this->hasMany(DailyProgram::class, 'id_work_center');
    }
    
    public function users()
    {
        return $this->belongsToMany(
            User::class, 
            'user_work_centers', 
            'work_center_id', 
            'user_id'
        )->withTimestamps();
    }
    
    public function attributes()
    {
        return $this->hasMany(Attribute::class, 'id_work_center')->orderBy('order');
    }
}