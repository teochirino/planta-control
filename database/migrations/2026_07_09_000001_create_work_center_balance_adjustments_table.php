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
        Schema::create('work_center_balance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_work_center')->constrained('work_centers')->onDelete('cascade');
            
            // Campo que se ajustó
            $table->enum('field_adjusted', ['accumulated_backwardness', 'accumulated_advanced'])
                  ->comment('Campo que fue ajustado');
            
            // Valores
            $table->integer('previous_value')->default(0)->comment('Valor antes del ajuste');
            $table->integer('new_value')->default(0)->comment('Valor después del ajuste');
            $table->integer('difference')->default(0)->comment('Diferencia (new - previous)');
            
            // Motivo y trazabilidad
            $table->text('reason')->nullable()->comment('Motivo del ajuste');
            $table->foreignId('adjusted_by')->constrained('users')->comment('Usuario que hizo el ajuste');
            $table->text('notes')->nullable()->comment('Notas adicionales');
            
            $table->timestamps();
            
            $table->index(['id_work_center', 'created_at']);
            $table->index(['field_adjusted', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_center_balance_adjustments');
    }
};
