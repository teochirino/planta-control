<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class GerenciaUserSeeder extends Seeder
{
    public function run(): void
    {
        // Verificar si ya existe un usuario con perfil Gerencia
        $existingUser = User::where('id_profile', 1)->first();
        
        if ($existingUser) {
            echo "⚠️  Ya existe un usuario con perfil Gerencia: {$existingUser->email}\n";
            return;
        }
        
        // Crear usuario Gerencia
        $user = User::create([
            'name' => 'Gerencia General',
            'email' => 'gerencia@planta.com',
            'password' => Hash::make('gerencia123'),
            'id_profile' => 1,
            'user_main_id' => null,
        ]);
        
        echo "✅ Usuario Gerencia creado exitosamente\n";
        echo "   Email: {$user->email}\n";
        echo "   Password: gerencia123\n";
        echo "   Perfil: Gerencia (id=1)\n";
    }
}
