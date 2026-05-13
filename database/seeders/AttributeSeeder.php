<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkCenter;
use App\Models\Attribute;
use Carbon\Carbon;

class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        echo "📝 Insertando atributos para cada centro de trabajo...\n";
        
        $attributeNames = [
            'Materia Prima',
            'MOD',
            'Programa',
            'Calidad',
            'Ingeniería'
        ];
        
        $workCenters = WorkCenter::all();
        
        foreach ($workCenters as $workCenter) {
            foreach ($attributeNames as $index => $name) {
                Attribute::create([
                    'id_work_center' => $workCenter->id,
                    'name' => $name,
                    'color' => 'verde',
                    'color_changed_at' => Carbon::now(),
                    'order' => $index,
                    'active' => 1,
                ]);
            }
            echo "   ✅ Atributos creados para: {$workCenter->name}\n";
        }
        
        echo "\n✅ Atributos insertados correctamente\n";
        echo "   - Total atributos: " . Attribute::count() . "\n\n";
    }
}
