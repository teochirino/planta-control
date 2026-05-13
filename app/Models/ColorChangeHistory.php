<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColorChangeHistory extends Model
{
    protected $fillable = [
        'id_work_center',
        'id_attribute',
        'user_id',
        'previous_color',
        'new_color',
        'comment'
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
    ];
    
    public function workCenter()
    {
        return $this->belongsTo(WorkCenter::class, 'id_work_center');
    }
    
    public function attribute()
    {
        return $this->belongsTo(Attribute::class, 'id_attribute');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
