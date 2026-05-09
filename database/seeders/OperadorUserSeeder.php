<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class OperadorUserSeeder extends Seeder
{
    public function run(): void
    {
        // Crear usuario operador de prueba
        $operador = User::create([
            'name' => 'Operador Test',
            'email' => 'operador@example.com',
            'password' => Hash::make('password123'),
            'user_main_id' => null,
            'id_profile' => 8,
        ]);
        
        // Asignar líneas de producción al operador
        // Asumiendo que existen líneas con IDs 1, 2, 3
        DB::table('user_production_lines')->insert([
            [
                'user_id' => $operador->id,
                'production_line_id' => 1,
                'can_view' => true,
                'can_edit' => true,
                'can_delete' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $operador->id,
                'production_line_id' => 2,
                'can_view' => true,
                'can_edit' => true,
                'can_delete' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        
        $this->command->info('✅ Usuario operador creado correctamente!');
        $this->command->info('   Email: operador@example.com');
        $this->command->info('   Password: password123');
        $this->command->info('   Líneas asignadas: 1, 2');
    }
}
