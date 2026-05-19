<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        echo "🗑️  Limpiando datos anteriores...\n";
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('products')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        echo "✅ Datos anteriores eliminados\n\n";
        echo "📝 Importando productos desde CSV...\n";
        
        $csvFile = database_path('../referencia/productos.csv');
        
        if (!file_exists($csvFile)) {
            echo "❌ Archivo CSV no encontrado: $csvFile\n";
            return;
        }
        
        $handle = fopen($csvFile, 'r');
        
        if ($handle === false) {
            echo "❌ No se pudo abrir el archivo CSV\n";
            return;
        }
        
        // Leer cabecera
        $header = fgetcsv($handle, 0, ';');
        
        $count = 0;
        $batch = [];
        
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $batch[] = [
                'modelo' => $row[0],
                'id_work_center' => $row[1],
                'tiempo' => $row[2],
                'piezas' => $row[3],
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            $count++;
            
            // Insertar en lotes de 1000
            if (count($batch) >= 1000) {
                DB::table('products')->insert($batch);
                $batch = [];
                echo "   Insertados $count registros...\n";
            }
        }
        
        // Insertar los restantes
        if (!empty($batch)) {
            DB::table('products')->insert($batch);
        }
        
        fclose($handle);
        
        echo "\n✅ Importación completada: $count registros\n";
        echo "📊 Total de productos: " . Product::count() . "\n\n";
    }
}
