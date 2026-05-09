<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = ['id_profile', 'title'];
    
    public function users()
    {
        return $this->hasMany(User::class, 'id_profile', 'id_profile');
    }
}