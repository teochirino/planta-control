<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class CalidadUserSeeder extends Seeder
{
    public function run(): void
    {
        // Verificar si ya existe un usuario con perfil Calidad
        $existingUser = User::where('id_profile', 4)->first();
        
        if ($existingUser) {
            $this->command->info('Ya existe un usuario con perfil Calidad: ' . $existingUser->email);
            return;
        }
        
        // Crear usuario con perfil Calidad
        $user = User::create([
            'name' => 'Operador de Calidad',
            'email' => 'calidad@planta.com',
            'password' => Hash::make('password'),
            'id_profile' => 4, // Perfil Calidad
        ]);
        
        $this->command->info('Usuario de Calidad creado exitosamente:');
        $this->command->info('Email: calidad@planta.com');
        $this->command->info('Password: password');
    }
}
