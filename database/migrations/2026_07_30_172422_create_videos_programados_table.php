<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('videos_programados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('ruta_video');
            $table->time('hora_reproduccion');
            $table->json('dias_semana');
            $table->boolean('activo')->default(true);
            $table->dateTime('ultima_reproduccion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos_programados');
    }
};
