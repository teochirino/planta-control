<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Program extends Model
{
    protected $fillable = ['codigo', 'fecha_entrega', 'fecha_fase1', 'fecha_fase2', 'fecha_fase3', 'fecha_fase4', 'total_time', 'total_piezas', 'created_by'];
    
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
            $code = now()->format('d-m-Y') . '-' . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        } while (self::where('codigo', $code)->exists());
        
        return $code;
    }
    
    public static function calculatePhaseDates($deliveryDate)
    {
        $phase4 = Carbon::parse($deliveryDate);
        $phase3 = self::subtractWorkingDays($phase4, 1);
        $phase2 = self::subtractWorkingDays($phase3, 1);
        $phase1 = self::subtractWorkingDays($phase2, 1);
        
        return [
            'fase1' => $phase1,
            'fase2' => $phase2,
            'fase3' => $phase3,
            'fase4' => $phase4,
        ];
    }
    
    private static function subtractWorkingDays($date, $days)
    {
        $current = $date->copy();
        $count = 0;
        
        while ($count < $days) {
            $current->subDay();
            if (!$current->isWeekend()) {
                $count++;
            }
        }
        
        return $current;
    }
    
    public static function validateMinDeliveryDate($date)
    {
        $minDate = self::addWorkingDays(now(), 4);
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
