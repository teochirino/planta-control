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
        Schema::create('production_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_daily_program')->constrained('daily_programs')->onDelete('cascade');
            $table->foreignId('id_work_center')->constrained('work_centers');
            
            // Tipo de ajuste
            $table->enum('adjustment_type', ['manual_count', 'quality_rejection', 'transfer', 'correction'])
                  ->default('correction')
                  ->comment('Tipo de ajuste realizado');
            
            // Valores
            $table->integer('previous_value')->default(0)->comment('Valor antes del ajuste');
            $table->integer('new_value')->default(0)->comment('Valor después del ajuste');
            $table->integer('difference')->default(0)->comment('Diferencia (new - previous)');
            
            // Referencia a lo que se ajustó
            $table->string('reference_type')->nullable()->comment('Tipo de referencia (schedule, daily_program, etc.)');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('ID de la referencia');
            
            // Motivo y trazabilidad
            $table->text('reason')->nullable()->comment('Motivo del ajuste');
            $table->foreignId('adjusted_by')->constrained('users')->comment('Usuario que hizo el ajuste');
            $table->text('notes')->nullable()->comment('Notas adicionales');
            
            $table->timestamps();
            
            $table->index(['id_daily_program', 'adjustment_type']);
            $table->index(['id_work_center', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_adjustments');
    }
};
