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
        Schema::table('production_adjustments', function (Blueprint $table) {
            // Referencias a programas para transferencias
            $table->foreignId('source_program_id')->nullable()->constrained('programs')->onDelete('set null')->comment('Programa origen (para transferencias)');
            $table->foreignId('target_program_id')->nullable()->constrained('programs')->onDelete('set null')->comment('Programa destino (para transferencias)');
            
            // Campo específico que se ajustó
            $table->enum('field_adjusted', ['backwardness', 'advanced', 'total_produced', 'programmed'])->nullable()->comment('Campo específico que se ajustó');
            
            // Categoría del ajuste
            $table->enum('adjustment_category', ['correction', 'transfer', 'discovery'])->default('correction')->comment('Categoría: correction=ajuste manual, transfer=redistribución, discovery=descubrimiento físico');
            
            // Índices para búsquedas
            $table->index(['adjustment_category', 'created_at']);
            $table->index(['source_program_id', 'target_program_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_adjustments', function (Blueprint $table) {
            $table->dropForeign(['source_program_id']);
            $table->dropForeign(['target_program_id']);
            $table->dropIndex(['adjustment_category', 'created_at']);
            $table->dropIndex(['source_program_id', 'target_program_id']);
            $table->dropColumn(['source_program_id', 'target_program_id', 'field_adjusted', 'adjustment_category']);
        });
    }
};
