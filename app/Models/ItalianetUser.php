<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItalianetUser extends Model
{
    protected $connection = 'italianet_users';
    protected $table = 'users';
    
    protected $fillable = [
        'name',
        'email',
        'status',
    ];
    
    // Scope para usuarios activos con email
    public function scopeActiveWithEmail($query)
    {
        return $query->whereNotNull('email')
                     ->where('email', '!=', '')
                     ->where('status', 1);
    }
    
    // Scope para buscar por nombre o email
    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }
        return $query;
    }
}
