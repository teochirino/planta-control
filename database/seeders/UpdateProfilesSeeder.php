<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class UpdateProfilesSeeder extends Seeder
{
    public function run(): void
    {
        echo "🔄 Actualizando perfiles de usuarios...\n\n";
        
        // 1. Insertar perfil Administrador si no existe
        $profileExists = DB::table('profiles')->where('id_profile', 7)->exists();
        
        if (!$profileExists) {
            DB::table('profiles')->insert([
                'id_profile' => 7,
                'title' => 'Administrador',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "✅ Perfil 'Administrador' (id=7) creado\n";
        } else {
            echo "⚠️  Perfil 'Administrador' (id=7) ya existe\n";
        }
        
        // 2. Actualizar usuario admin@planta.com a perfil 7 (Administrador)
        $adminUser = User::where('email', 'admin@planta.com')->first();
        
        if ($adminUser) {
            $adminUser->update(['id_profile' => 7]);
            echo "✅ Usuario 'admin@planta.com' actualizado a perfil Administrador (id=7)\n";
        } else {
            echo "⚠️  Usuario 'admin@planta.com' no encontrado\n";
        }
        
        // 3. Crear nuevo usuario Gerencia si no existe
        $gerenciaUser = User::where('email', 'gerencia@planta.com')->first();
        
        if (!$gerenciaUser) {
            User::create([
                'name' => 'Gerencia General',
                'email' => 'gerencia@planta.com',
                'password' => Hash::make('gerencia123'),
                'id_profile' => 1,
                'user_main_id' => null,
            ]);
            echo "✅ Usuario 'gerencia@planta.com' creado con perfil Gerencia (id=1)\n";
        } else {
            echo "⚠️  Usuario 'gerencia@planta.com' ya existe\n";
        }
        
        echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📋 RESUMEN DE CREDENCIALES:\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "👤 Administrador (CRUD):  admin@planta.com / password\n";
        echo "👤 Gerencia (Dashboard):  gerencia@planta.com / gerencia123\n";
        echo "👤 Supervisor:            supervisor@planta.com / password\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    }
}
