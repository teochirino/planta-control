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
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique()->comment('Formato: DD-M-YYYY-XXX');
            $table->date('fecha_entrega')->comment('Fecha final de entrega');
            $table->date('fecha_fase1')->comment('Inicio Fase 1');
            $table->date('fecha_fase2')->comment('Inicio Fase 2');
            $table->date('fecha_fase3')->comment('Inicio Fase 3');
            $table->date('fecha_fase4')->comment('Inicio Fase 4');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
