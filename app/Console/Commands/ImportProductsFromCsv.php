<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

#[Signature('app:import-products-from-csv')]
#[Description('Import products from productos3.csv and truncate table')]
class ImportProductsFromCsv extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting import process...');

        // Truncate the products table
        $this->info('Truncating products table...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Product::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->info('Products table truncated.');

        // Read CSV file
        $csvPath = base_path('referencia/productos3.csv');
        if (!file_exists($csvPath)) {
            $this->error('CSV file not found: ' . $csvPath);
            return 1;
        }

        $this->info('Reading CSV file...');
        $file = fopen($csvPath, 'r');
        
        // Skip header
        fgetcsv($file, 0, ';');
        
        $count = 0;
        while (($row = fgetcsv($file, 0, ';')) !== false) {
            Product::create([
                'modelo' => $row[0],
                'id_work_center' => $row[1],
                'tiempo' => $row[2],
                'piezas' => $row[3],
            ]);
            $count++;
            
            if ($count % 1000 === 0) {
                $this->info("Imported {$count} records...");
            }
        }
        
        fclose($file);
        
        $this->info("Import completed. Total records imported: {$count}");
        return 0;
    }
}
