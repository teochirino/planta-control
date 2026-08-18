<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Program extends Model
{
    protected $fillable = ['codigo', 'fecha_entrega', 'fecha_fase1', 'fecha_fase2', 'fecha_fase3', 'fecha_fase4', 'total_time', 'total_piezas', 'created_by', 'program_type', 'observaciones'];

    protected $dates = [
        'fecha_entrega',
        'fecha_fase1',
        'fecha_fase2',
        'fecha_fase3',
        'fecha_fase4',
    ];

    protected $appends = ['fecha_fase1_formatted', 'fecha_fase2_formatted', 'fecha_fase3_formatted', 'fecha_fase4_formatted'];

    public function getFechaFase1FormattedAttribute()
    {
        if (!$this->fecha_fase1) return null;
        if (is_string($this->fecha_fase1)) return $this->fecha_fase1;
        return $this->fecha_fase1->format('Y-m-d');
    }

    public function getFechaFase2FormattedAttribute()
    {
        if (!$this->fecha_fase2) return null;
        if (is_string($this->fecha_fase2)) return $this->fecha_fase2;
        return $this->fecha_fase2->format('Y-m-d');
    }

    public function getFechaFase3FormattedAttribute()
    {
        if (!$this->fecha_fase3) return null;
        if (is_string($this->fecha_fase3)) return $this->fecha_fase3;
        return $this->fecha_fase3->format('Y-m-d');
    }

    public function getFechaFase4FormattedAttribute()
    {
        if (!$this->fecha_fase4) return null;
        if (is_string($this->fecha_fase4)) return $this->fecha_fase4;
        return $this->fecha_fase4->format('Y-m-d');
    }

    public function details()
    {
        return $this->hasMany(ProgramDetail::class);
    }
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public static function generateUniqueCode()
    {
        do {
            $code = now('America/Mexico_City')->format('d-m-Y') . '-' . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        } while (self::where('codigo', $code)->exists());
        
        return $code;
    }
    
    public static function calculatePhaseDates($deliveryDate, $includeSaturdays = false)
    {
        $phase4 = Carbon::parse($deliveryDate);
        $phase3 = self::subtractWorkingDays($phase4, 1, $includeSaturdays);
        $phase2 = self::subtractWorkingDays($phase3, 1, $includeSaturdays);
        $phase1 = self::subtractWorkingDays($phase2, 1, $includeSaturdays);
        
        return [
            'fase1' => $phase1,
            'fase2' => $phase2,
            'fase3' => $phase3,
            'fase4' => $phase4,
        ];
    }
    
    private static function subtractWorkingDays($date, $days, $includeSaturdays = false)
    {
        $current = $date->copy();
        $count = 0;
        
        while ($count < $days) {
            $current->subDay();
            $isWeekend = $current->isWeekend();
            // Si includeSaturdays es true, solo excluimos domingos
            if ($includeSaturdays) {
                if (!$current->isSunday()) {
                    $count++;
                }
            } else {
                // Comportamiento original: excluir sábados y domingos
                if (!$isWeekend) {
                    $count++;
                }
            }
        }
        
        return $current;
    }
    
    public static function validateMinDeliveryDate($date)
    {
        $minDate = self::addWorkingDays(now('America/Mexico_City'), 4);
        return Carbon::parse($date)->gte($minDate);
    }
    
    public static function addWorkingDays($date, $days)
    {
        $current = $date->copy();
        $count = 0;
        
        while ($count < $days) {
            $current->addDay();
            if (!$current->isWeekend()) {
                $count++;
            }
        }
        
        return $current;
    }
}
