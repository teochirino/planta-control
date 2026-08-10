<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * production_lines.cost es un costo por HORA, pero el costo de cada paro se
     * guardó multiplicando los minutos de paro directamente por ese valor (como si
     * fuera por minuto), inflando strikes.cost 60 veces. Se corrige dividiendo entre
     * 60 los registros ya guardados.
     */
    public function up(): void
    {
        DB::statement('UPDATE strikes SET cost = cost / 60 WHERE cost > 0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('UPDATE strikes SET cost = cost * 60 WHERE cost > 0');
    }
};
