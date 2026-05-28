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
        Schema::table('daily_programs', function (Blueprint $table) {
            // Campos para cierre de turno por operador
            $table->boolean('operator_closed')->default(false)->after('total_rejected')
                  ->comment('Indica si el operador cerró el turno');
            $table->timestamp('operator_closed_at')->nullable()->after('operator_closed')
                  ->comment('Cuándo el operador cerró el turno');
            $table->foreignId('operator_closed_by')->nullable()->after('operator_closed_at')
                  ->constrained('users')
                  ->comment('Usuario (operador) que cerró el turno');
            
            // Campos para procesamiento de balance por supervisor
            $table->boolean('balance_processed')->default(false)->after('operator_closed_by')
                  ->comment('Indica si el balance fue procesado');
            $table->timestamp('balance_processed_at')->nullable()->after('balance_processed')
                  ->comment('Cuándo se procesó el balance');
            $table->foreignId('balance_processed_by')->nullable()->after('balance_processed_at')
                  ->constrained('users')
                  ->comment('Usuario (supervisor) que procesó el balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_programs', function (Blueprint $table) {
            $table->dropForeign(['operator_closed_by']);
            $table->dropForeign(['balance_processed_by']);
            $table->dropColumn([
                'operator_closed',
                'operator_closed_at',
                'operator_closed_by',
                'balance_processed',
                'balance_processed_at',
                'balance_processed_by'
            ]);
        });
    }
};
