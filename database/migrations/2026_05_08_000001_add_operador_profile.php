<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar perfil "Operador de area" con id_profile=8
        DB::table('profiles')->insert([
            'id_profile' => 8,
            'title' => 'Operador de area',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('profiles')->where('id_profile', 8)->delete();
    }
};
