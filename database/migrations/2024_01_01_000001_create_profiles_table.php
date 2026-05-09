// database/migrations/2024_01_01_000001_create_profiles_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('id_profile')->unique();
            $table->string('title');
            $table->timestamps();
        });

        // Insertar perfiles
        DB::table('profiles')->insert([
            ['id_profile' => 1, 'title' => 'Gerencia'],
            ['id_profile' => 2, 'title' => 'Gerente de Produccion'],
            ['id_profile' => 3, 'title' => 'Gerente de Mantenimiento'],
            ['id_profile' => 4, 'title' => 'Calidad'],
            ['id_profile' => 5, 'title' => 'Supervisor de area'],
            ['id_profile' => 6, 'title' => 'Ingenieria de Procesos'],
            ['id_profile' => 7, 'title' => 'Administrador'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};