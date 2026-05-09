<?php
// database/seeders/UserPermissionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar tablas existentes
        DB::table('user_work_centers')->truncate();
        DB::table('user_production_lines')->truncate();
        
        // ============================================
        // USUARIO TEST (id=1) - Supervisor
        // ============================================
        
        // Centros de trabajo que puede ver/editar
        DB::table('user_work_centers')->insert([
            [
                'user_id' => 1,
                'work_center_id' => 1,
                'can_view' => true,
                'can_edit' => true,
                'can_delete' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 1,
                'work_center_id' => 2,
                'can_view' => true,
                'can_edit' => false,
                'can_delete' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        
        // Líneas de producción que puede ver/editar
        DB::table('user_production_lines')->insert([
            [
                'user_id' => 1,
                'production_line_id' => 1,  // Línea A
                'can_view' => true,
                'can_edit' => true,
                'can_delete' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 1,
                'production_line_id' => 2,  // Línea B
                'can_view' => true,
                'can_edit' => true,
                'can_delete' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 1,
                'production_line_id' => 3,  // Línea Pintura
                'can_view' => true,
                'can_edit' => false,
                'can_delete' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        
        // ============================================
        // USUARIO GERENTE (id=2) - Ve todo, edita todo
        // Nota: Los gerentes no necesitan asignaciones explícitas
        // porque el código les da acceso total por su perfil
        // ============================================
        
        $this->command->info('✅ Permisos de usuario insertados correctamente!');
        $this->command->info('   - Usuario 1 (test@example.com): acceso a WC 1 y 2, líneas 1,2,3');
    }
}