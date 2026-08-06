<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('work_centers')->where('name', 'Estructura de manparas')->update(['name' => 'Estructura de mamparas']);
        DB::table('work_centers')->where('name', 'Ensamble de manparas')->update(['name' => 'Ensamble de mamparas']);
        DB::table('production_lines')->where('title', 'Estructura de manparas')->update(['title' => 'Estructura de mamparas']);
        DB::table('production_lines')->where('title', 'Ensamble de manparas')->update(['title' => 'Ensamble de mamparas']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('work_centers')->where('name', 'Estructura de mamparas')->update(['name' => 'Estructura de manparas']);
        DB::table('work_centers')->where('name', 'Ensamble de mamparas')->update(['name' => 'Ensamble de manparas']);
        DB::table('production_lines')->where('title', 'Estructura de mamparas')->update(['title' => 'Estructura de manparas']);
        DB::table('production_lines')->where('title', 'Ensamble de mamparas')->update(['title' => 'Ensamble de manparas']);
    }
};
