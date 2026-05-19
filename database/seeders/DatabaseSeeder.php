<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        echo "\n";
        echo "╔════════════════════════════════════════════╗\n";
        echo "║   SISTEMA DE CONTROL DE PLANTA - SEEDER   ║\n";
        echo "╚════════════════════════════════════════════╝\n\n";
        
        // 1. Crear centros de trabajo, líneas y datos de producción
        echo "📦 Paso 1: Creando centros de trabajo y líneas de producción...\n";
        $this->call(WorkCenterSeeder::class);
        
        echo "\n";
        
        // 2. Importar productos desde CSV
        echo "📦 Paso 2: Importando productos desde CSV...\n";
        $this->call(ProductSeeder::class);
        
        echo "\n";
        
        // 3. Crear atributos para semáforos del área
        echo "🚦 Paso 3: Creando atributos para semáforos del área...\n";
        $this->call(AttributeSeeder::class);
        
        echo "\n";
        
        // 4. Crear usuarios y asignar centros de trabajo
        echo "👥 Paso 4: Creando usuarios y asignando centros...\n";
        $this->call(UserWorkCenterSeeder::class);
        
        echo "\n";
        echo "╔════════════════════════════════════════════╗\n";
        echo "║          ✅ SEEDER COMPLETADO              ║\n";
        echo "╚════════════════════════════════════════════╝\n";
        echo "\n";
        echo "🚀 Puedes iniciar sesión con:\n";
        echo "   • admin@planta.com / password\n";
        echo "   • supervisor@planta.com / password\n";
        echo "   • supervisor2@planta.com / password\n\n";
    }
}
