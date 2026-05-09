<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Deshabilitar foreign keys temporalmente
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Limpiar datos existentes para evitar conflictos
        DB::table('breakdowns')->truncate();
        DB::table('machines')->truncate();
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        Schema::table('machines', function (Blueprint $table) {
            // Eliminar la foreign key y columna anterior
            $table->dropForeign(['id_production_lines']);
            $table->dropColumn('id_production_lines');
            
            // Agregar la nueva relación con work_centers
            $table->foreignId('id_work_center')
                  ->after('id')
                  ->constrained('work_centers')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            // Revertir los cambios
            $table->dropForeign(['id_work_center']);
            $table->dropColumn('id_work_center');
            
            $table->foreignId('id_production_lines')
                  ->after('id')
                  ->constrained('production_lines')
                  ->onDelete('cascade');
        });
    }
};
