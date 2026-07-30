<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoProgramado extends Model
{
    protected $table = 'videos_programados';
    
    protected $fillable = [
        'nombre',
        'ruta_video',
        'hora_reproduccion',
        'dias_semana',
        'activo',
        'ultima_reproduccion',
    ];

    protected $casts = [
        'dias_semana' => 'array',
        'activo' => 'boolean',
        'ultima_reproduccion' => 'datetime',
    ];
}
