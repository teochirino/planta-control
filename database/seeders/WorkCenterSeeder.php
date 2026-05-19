<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\WorkCenter;
use App\Models\ProductionLine;
use App\Models\Machine;

class WorkCenterSeeder extends Seeder
{
    public function run(): void
    {
        echo "🗑️  Limpiando datos anteriores...\n";
        
        // Limpiar tablas en orden correcto (respetando foreign keys)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('schedules')->truncate();
        DB::table('strikes')->truncate();
        DB::table('daily_programs')->truncate();
        DB::table('machines')->truncate();
        DB::table('production_lines')->truncate();
        DB::table('work_centers')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        echo "✅ Datos anteriores eliminados\n\n";
        echo "📝 Insertando datos reales de producción...\n\n";
        
        // ========== 1. CARPINTERÍA CORTE ==========
        $wc1 = WorkCenter::create(['name' => 'Carpintería corte', 'installed_capacity' => 300, 'phase' => 1]);

        ProductionLine::create(['id_work_center' => $wc1->id, 'title' => 'Gabbiani', 'installed_capacity' => 100, 'cost' => 20000]);
        ProductionLine::create(['id_work_center' => $wc1->id, 'title' => 'Sellex', 'installed_capacity' => 100, 'cost' => 20000]);
        ProductionLine::create(['id_work_center' => $wc1->id, 'title' => 'Blue Elephant', 'installed_capacity' => 100, 'cost' => 20000]);

        Machine::create(['id_work_center' => $wc1->id, 'title' => 'Selexx', 'state' => 'operativo']);
        Machine::create(['id_work_center' => $wc1->id, 'title' => 'Gabbiani', 'state' => 'operativo']);
        Machine::create(['id_work_center' => $wc1->id, 'title' => 'Blue Elephant', 'state' => 'operativo']);

        // ========== 2. CORTE LÁSER ==========
        $wc2 = WorkCenter::create(['name' => 'Corte láser', 'installed_capacity' => 200, 'phase' => 1]);

        ProductionLine::create(['id_work_center' => $wc2->id, 'title' => 'Láser Bimex', 'installed_capacity' => 70, 'cost' => 25000]);
        ProductionLine::create(['id_work_center' => $wc2->id, 'title' => 'G-Weike 1', 'installed_capacity' => 65, 'cost' => 25000]);
        ProductionLine::create(['id_work_center' => $wc2->id, 'title' => 'G-Weike 2', 'installed_capacity' => 65, 'cost' => 25000]);

        Machine::create(['id_work_center' => $wc2->id, 'title' => 'G-Weike 1', 'state' => 'operativo']);
        Machine::create(['id_work_center' => $wc2->id, 'title' => 'G-Weike 2', 'state' => 'operativo']);
        Machine::create(['id_work_center' => $wc2->id, 'title' => 'Láser Bimex', 'state' => 'operativo']);

        // ========== 3. ESTRUCTURA DE MUEBLES ==========
        $wc3 = WorkCenter::create(['name' => 'Estructura de muebles', 'installed_capacity' => 150, 'phase' => 2]);
        ProductionLine::create(['id_work_center' => $wc3->id, 'title' => 'Detallado 1', 'installed_capacity' => 75, 'cost' => 15000]);
        ProductionLine::create(['id_work_center' => $wc3->id, 'title' => 'Detallado 2', 'installed_capacity' => 75, 'cost' => 15000]);

        // ========== 4. ENCHAPADO ==========
        $wc4 = WorkCenter::create(['name' => 'Enchapado', 'installed_capacity' => 250, 'phase' => 2]);

        ProductionLine::create(['id_work_center' => $wc4->id, 'title' => 'Homag', 'installed_capacity' => 100, 'cost' => 30000]);
        ProductionLine::create(['id_work_center' => $wc4->id, 'title' => 'Enchapadora Blue Elephant', 'installed_capacity' => 80, 'cost' => 30000]);
        ProductionLine::create(['id_work_center' => $wc4->id, 'title' => 'Enchapadora curvos', 'installed_capacity' => 70, 'cost' => 30000]);

        Machine::create(['id_work_center' => $wc4->id, 'title' => 'Homag', 'state' => 'operativo']);
        Machine::create(['id_work_center' => $wc4->id, 'title' => 'Enchapadora Blue Elephant', 'state' => 'operativo']);
        Machine::create(['id_work_center' => $wc4->id, 'title' => 'Enchapadora curvos', 'state' => 'operativo']);

        // ========== 5. HABILITADO DE MUEBLES ==========
        $wc5 = WorkCenter::create(['name' => 'Habilitado de muebles', 'installed_capacity' => 120, 'phase' => 3]);
        ProductionLine::create(['id_work_center' => $wc5->id, 'title' => 'Habilitado de insertos', 'installed_capacity' => 60, 'cost' => 12000]);
        ProductionLine::create(['id_work_center' => $wc5->id, 'title' => 'Detallado y habilitado', 'installed_capacity' => 60, 'cost' => 12000]);

        // ========== 6. PINTURA ==========
        $wc6 = WorkCenter::create(['name' => 'Pintura', 'installed_capacity' => 180, 'phase' => 3]);

        ProductionLine::create(['id_work_center' => $wc6->id, 'title' => 'Pintura', 'installed_capacity' => 90, 'cost' => 35000]);
        ProductionLine::create(['id_work_center' => $wc6->id, 'title' => 'Habilitado de pintura', 'installed_capacity' => 90, 'cost' => 15000]);

        Machine::create(['id_work_center' => $wc6->id, 'title' => 'Túnel de lavado y secado', 'state' => 'operativo']);
        Machine::create(['id_work_center' => $wc6->id, 'title' => 'Cabina de pintura', 'state' => 'operativo']);
        Machine::create(['id_work_center' => $wc6->id, 'title' => 'Horno de curado y secado', 'state' => 'operativo']);

        // ========== 7. TELAS ==========
        $wc7 = WorkCenter::create(['name' => 'Telas', 'installed_capacity' => 80, 'phase' => 3]);

        ProductionLine::create(['id_work_center' => $wc7->id, 'title' => 'Corte y costura', 'installed_capacity' => 80, 'cost' => 18000]);

        Machine::create(['id_work_center' => $wc7->id, 'title' => 'Ferreti', 'state' => 'operativo']);

        // ========== 8. ENSAMBLE ARCHIVEROS ==========
        $wc8 = WorkCenter::create(['name' => 'Ensamble archiveros', 'installed_capacity' => 100, 'phase' => 4]);
        ProductionLine::create(['id_work_center' => $wc8->id, 'title' => 'Ensamble archiveros', 'installed_capacity' => 100, 'cost' => 10000]);

        // ========== 9. ENSAMBLE LIBREROS ==========
        $wc9 = WorkCenter::create(['name' => 'Ensamble libreros', 'installed_capacity' => 100, 'phase' => 4]);
        ProductionLine::create(['id_work_center' => $wc9->id, 'title' => 'Ensamble libreros', 'installed_capacity' => 100, 'cost' => 10000]);

        // ========== 10. ENSAMBLE DE MUEBLES ==========
        $wc10 = WorkCenter::create(['name' => 'Ensamble de muebles', 'installed_capacity' => 150, 'phase' => 4]);
        ProductionLine::create(['id_work_center' => $wc10->id, 'title' => 'Ensamble de muebles', 'installed_capacity' => 150, 'cost' => 10000]);

        // ========== 11. TROQUELES ==========
        $wc11 = WorkCenter::create(['name' => 'Troqueles', 'installed_capacity' => 100, 'phase' => 1]);
        ProductionLine::create(['id_work_center' => $wc11->id, 'title' => 'Troqueles', 'installed_capacity' => 100, 'cost' => 12000]);

        // ========== 12. ESTRUCTURA DE SILLAS ==========
        $wc12 = WorkCenter::create(['name' => 'Estructura de sillas', 'installed_capacity' => 100, 'phase' => 2]);
        ProductionLine::create(['id_work_center' => $wc12->id, 'title' => 'Estructura de sillas', 'installed_capacity' => 100, 'cost' => 12000]);

        // ========== 13. ESTRUCTURA DE MAMPARAS ==========
        $wc13 = WorkCenter::create(['name' => 'Estructura de manparas', 'installed_capacity' => 100, 'phase' => 2]);
        ProductionLine::create(['id_work_center' => $wc13->id, 'title' => 'Estructura de manparas', 'installed_capacity' => 100, 'cost' => 12000]);

        // ========== 14. CAJAS ==========
        $wc14 = WorkCenter::create(['name' => 'Cajas', 'installed_capacity' => 100, 'phase' => 3]);
        ProductionLine::create(['id_work_center' => $wc14->id, 'title' => 'Cajas', 'installed_capacity' => 100, 'cost' => 10000]);

        // ========== 15. ENSAMBLE DE MAMPARAS ==========
        $wc15 = WorkCenter::create(['name' => 'Ensamble de manparas', 'installed_capacity' => 100, 'phase' => 4]);
        ProductionLine::create(['id_work_center' => $wc15->id, 'title' => 'Ensamble de manparas', 'installed_capacity' => 100, 'cost' => 10000]);

        // ========== 16. ENSAMBLAJE DE SILLAS ==========
        $wc16 = WorkCenter::create(['name' => 'Ensamblaje de sillas', 'installed_capacity' => 100, 'phase' => 4]);
        ProductionLine::create(['id_work_center' => $wc16->id, 'title' => 'Ensamblaje de sillas', 'installed_capacity' => 100, 'cost' => 10000]);

        // ========== 17. ENSAMBLE DE SILLONES ==========
        $wc17 = WorkCenter::create(['name' => 'Ensamble de sillones', 'installed_capacity' => 100, 'phase' => 4]);
        ProductionLine::create(['id_work_center' => $wc17->id, 'title' => 'Ensamble de sillones', 'installed_capacity' => 100, 'cost' => 10000]);
        
        echo "\n✅ Datos reales insertados correctamente\n";
        echo "\n📊 Resumen:\n";
        echo "   - Centros de Trabajo: " . WorkCenter::count() . "\n";
        echo "   - Líneas de Producción: " . ProductionLine::count() . "\n";
        echo "   - Máquinas: " . Machine::count() . "\n\n";
    }
}