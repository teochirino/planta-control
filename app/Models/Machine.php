<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    protected $fillable = ['id_work_center', 'title', 'state'];
    
    public function workCenter()
    {
        return $this->belongsTo(WorkCenter::class, 'id_work_center');
    }
    
    public function breakdowns()
    {
        return $this->hasMany(Breakdown::class, 'id_machine');
    }
}