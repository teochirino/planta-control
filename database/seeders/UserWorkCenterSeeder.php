<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\WorkCenter;
use Illuminate\Support\Facades\Hash;

class UserWorkCenterSeeder extends Seeder
{
    public function run(): void
    {
        // Crear usuario Admin si no existe
        $admin = User::firstOrCreate(
            ['email' => 'admin@planta.com'],
            [
                'name' => 'Administrador Sistema',
                'password' => Hash::make('password'),
                'id_profile' => 1, // Gerencia
            ]
        );
        
        echo "✅ Usuario Admin creado: admin@planta.com / password\n";
        
        // Crear usuario Supervisor si no existe
        $supervisor = User::firstOrCreate(
            ['email' => 'supervisor@planta.com'],
            [
                'name' => 'Supervisor de Área',
                'password' => Hash::make('password'),
                'id_profile' => 5, // Supervisor de Área
            ]
        );
        
        echo "✅ Usuario Supervisor creado: supervisor@planta.com / password\n";
        
        // Obtener todos los centros de trabajo
        $workCenters = WorkCenter::all();
        
        if ($workCenters->isEmpty()) {
            echo "⚠️ No hay centros de trabajo. Ejecuta WorkCenterSeeder primero.\n";
            return;
        }
        
        // Asignar todos los centros al supervisor
        foreach ($workCenters as $wc) {
            if (!$supervisor->workCenters()->where('work_center_id', $wc->id)->exists()) {
                $supervisor->workCenters()->attach($wc->id);
                echo "   → Asignado: {$wc->name}\n";
            }
        }
        
        echo "\n✅ Supervisor tiene acceso a {$workCenters->count()} centro(s) de trabajo\n";
        
        // Crear supervisor adicional con acceso limitado
        $supervisor2 = User::firstOrCreate(
            ['email' => 'supervisor2@planta.com'],
            [
                'name' => 'Supervisor Norte',
                'password' => Hash::make('password'),
                'id_profile' => 5,
            ]
        );
        
        // Asignar solo el primer centro
        $firstCenter = $workCenters->first();
        if ($firstCenter && !$supervisor2->workCenters()->where('work_center_id', $firstCenter->id)->exists()) {
            $supervisor2->workCenters()->attach($firstCenter->id);
            echo "✅ Supervisor2 creado con acceso a: {$firstCenter->name}\n";
        }
        
        echo "\n📋 Resumen de usuarios:\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "👤 Admin:       admin@planta.com / password\n";
        echo "👤 Supervisor:  supervisor@planta.com / password\n";
        echo "👤 Supervisor2: supervisor2@planta.com / password\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    }
}
