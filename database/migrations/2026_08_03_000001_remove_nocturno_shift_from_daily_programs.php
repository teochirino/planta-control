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
            // Modificar el enum para eliminar 'nocturno'
            $table->enum('shift', ['matutino', 'vespertino'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_programs', function (Blueprint $table) {
            // Revertir el enum para incluir 'nocturno'
            $table->enum('shift', ['matutino', 'vespertino', 'nocturno'])->change();
        });
    }
};
