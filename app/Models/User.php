<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'user_main_id',
        'id_profile',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'id_profile' => 'integer',
        ];
    }
    
    public function profile()
    {
        return $this->belongsTo(Profile::class, 'id_profile', 'id_profile');
    }
    
    public function workCenters()
    {
        return $this->belongsToMany(
            WorkCenter::class, 
            'user_work_centers', 
            'user_id', 
            'work_center_id'
        )->withTimestamps();
    }
    
    public function productionLines()
    {
        return $this->belongsToMany(
            ProductionLine::class, 
            'user_production_lines', 
            'user_id', 
            'production_line_id'
        )->withTimestamps()->withPivot('can_view', 'can_edit', 'can_delete');
    }
    
    public function breakdowns()
    {
        return $this->hasMany(Breakdown::class, 'id_user');
    }
    
    // Verificar si es supervisor de área
    public function isSupervisor()
    {
        return $this->id_profile === 5;
    }
    
    // Verificar si es admin (CRUD de usuarios)
    public function isAdmin()
    {
        return $this->id_profile === 7;
    }
    
    // Verificar si es gerencia (Dashboard gerencial)
    public function isGerencia()
    {
        return $this->id_profile === 1;
    }
    
    // Verificar si es operador de área
    public function isOperador()
    {
        return $this->id_profile === 8;
    }
    
    // Verificar si es operador de calidad
    public function isCalidad()
    {
        return $this->id_profile === 4;
    }
    
    // Verificar si el usuario puede ver un centro de trabajo
    public function canViewWorkCenter($workCenterId)
    {
        if ($this->id_profile <= 2) return true;
        
        return $this->workCenters()
            ->where('work_center_id', $workCenterId)
            ->exists();
    }
    
    // Verificar si el usuario puede ver una línea de producción
    public function canViewProductionLine($productionLineId)
    {
        if ($this->id_profile <= 2) return true;
        
        return $this->productionLines()
            ->where('production_line_id', $productionLineId)
            ->exists();
    }
    
    // Verificar si el usuario puede editar una línea de producción
    public function canEditProductionLine($productionLineId)
    {
        if ($this->id_profile <= 2) return true;
        
        return $this->productionLines()
            ->where('production_line_id', $productionLineId)
            ->wherePivot('can_edit', true)
            ->exists();
    }
    
    // Obtener líneas de producción accesibles para el usuario
    public function getAccessibleProductionLines()
    {
        if ($this->id_profile <= 2) {
            return ProductionLine::all();
        }
        
        return $this->productionLines;
    }
}
