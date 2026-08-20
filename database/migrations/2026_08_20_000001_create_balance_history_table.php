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
        Schema::create('balance_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_work_center')->constrained('work_centers')->onDelete('cascade');
            $table->foreignId('id_daily_program')->constrained('daily_programs')->onDelete('cascade');
            $table->foreignId('processed_by')->constrained('users')->onDelete('cascade');
            $table->integer('programmed')->comment('Piezas programadas');
            $table->integer('backwardness')->default(0)->comment('Atrasos al inicio del turno');
            $table->integer('advanced')->default(0)->comment('Adelantos al inicio del turno');
            $table->integer('total_to_produce')->comment('Total a producir (programado + atrasos - adelantos)');
            $table->integer('total_produced')->comment('Total fabricado');
            $table->integer('total_rejected')->default(0)->comment('Total rechazado');
            $table->integer('final_backwardness')->default(0)->comment('Atrasos finales calculados');
            $table->integer('final_advanced')->default(0)->comment('Adelantos finales calculados');
            $table->timestamp('processed_at')->comment('Fecha y hora del procesamiento');
            $table->timestamps();
            
            $table->index(['id_work_center', 'processed_at']);
            $table->index('processed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_history');
    }
};
