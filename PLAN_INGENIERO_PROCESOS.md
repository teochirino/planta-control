# Plan de Implementación - Módulo Ingeniero de Procesos

## Contexto
- **Perfil**: Ingeniero de Procesos (id_profile = 6)
- **Objetivo**: Crear y consultar programas de fabricación de productos
- **Fecha de solicitud**: 19 de mayo de 2026

## Requisitos Funcionales

### 1. Creación de Programas
- Ingresar múltiples productos (modelo y cantidad de cada uno)
- Generar código único de programa: formato `día-mes-año-3númerosaleatorios` (ej: 19-5-2026-526)
- Definir fecha final de entrega
- Calcular automáticamente fechas de fases (1, 2, 3, 4)

### 2. Lógica de Fechas
- **Fecha mínima de entrega**: 4 días hábiles después de la fecha actual (excluyendo sábados y domingos)
- **Cálculo de fases**:
  - Fase 4 = Fecha de entrega
  - Fase 3 = 1 día hábil antes de Fase 4
  - Fase 2 = 1 día hábil antes de Fase 3
  - Fase 1 = 1 día hábil antes de Fase 2
- **Ejemplo**: Si entrega es jueves → Fase 1 lunes, Fase 2 martes, Fase 3 miércoles, Fase 4 jueves

### 3. Consulta de Programas
- Ver programa con tabla de detalles
- Cálculos:
  - `total_pieces` = cantidad_solicitada × products.piezas
  - `total_time` = cantidad_solicitada × products.tiempo
- Mostrar columnas: Fase, Centro de Trabajo, Modelo, Cantidad Solicitada, Piezas Totales, Tiempo Total (NUEVO)
- Agrupar por fase (1, 2, 3, 4)

## Estructura de Base de Datos

### Tabla `programs`
```php
Schema::create('programs', function (Blueprint $table) {
    $table->id();
    $table->string('codigo', 20)->unique()->comment('Formato: DD-M-YYYY-XXX');
    $table->date('fecha_entrega')->comment('Fecha final de entrega');
    $table->date('fecha_fase1')->comment('Inicio Fase 1');
    $table->date('fecha_fase2')->comment('Inicio Fase 2');
    $table->date('fecha_fase3')->comment('Inicio Fase 3');
    $table->date('fecha_fase4')->comment('Inicio Fase 4');
    $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
    $table->timestamps();
});
```

### Tabla `program_details`
```php
Schema::create('program_details', function (Blueprint $table) {
    $table->id();
    $table->foreignId('program_id')->constrained('programs')->onDelete('cascade');
    $table->string('modelo', 20)->comment('Modelo del producto');
    $table->integer('cantidad_solicitada')->unsigned()->comment('Cantidad solicitada');
    $table->timestamps();
    
    $table->index(['program_id', 'modelo']);
});
```

## Modelos

### App\Models\Program
```php
class Program extends Model
{
    protected $fillable = ['codigo', 'fecha_entrega', 'fecha_fase1', 'fecha_fase2', 'fecha_fase3', 'fecha_fase4', 'created_by'];
    
    public function details()
    {
        return $this->hasMany(ProgramDetail::class);
    }
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public static function generateUniqueCode()
    {
        do {
            $code = now()->format('d-m-Y') . '-' . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        } while (self::where('codigo', $code)->exists());
        
        return $code;
    }
    
    public static function calculatePhaseDates($deliveryDate)
    {
        $phase4 = Carbon::parse($deliveryDate);
        $phase3 = self::subtractWorkingDays($phase4, 1);
        $phase2 = self::subtractWorkingDays($phase3, 1);
        $phase1 = self::subtractWorkingDays($phase2, 1);
        
        return [
            'fase1' => $phase1,
            'fase2' => $phase2,
            'fase3' => $phase3,
            'fase4' => $phase4,
        ];
    }
    
    private static function subtractWorkingDays($date, $days)
    {
        $current = $date->copy();
        $count = 0;
        
        while ($count < $days) {
            $current->subDay();
            if (!$current->isWeekend()) {
                $count++;
            }
        }
        
        return $current;
    }
    
    public static function validateMinDeliveryDate($date)
    {
        $minDate = self::addWorkingDays(now(), 4);
        return Carbon::parse($date)->gte($minDate);
    }
    
    private static function addWorkingDays($date, $days)
    {
        $current = $date->copy();
        $count = 0;
        
        while ($count < $days) {
            $current->addDay();
            if (!$current->isWeekend()) {
                $count++;
            }
        }
        
        return $current;
    }
}
```

### App\Models\ProgramDetail
```php
class ProgramDetail extends Model
{
    protected $fillable = ['program_id', 'modelo', 'cantidad_solicitada'];
    
    public function program()
    {
        return $this->belongsTo(Program::class);
    }
    
    public function getTotalPiecesAttribute()
    {
        $product = Product::where('modelo', $this->modelo)->first();
        return $product ? $this->cantidad_solicitada * $product->piezas : 0;
    }
    
    public function getTotalTimeAttribute()
    {
        $product = Product::where('modelo', $this->modelo)->first();
        return $product ? $this->cantidad_solicitada * $product->tiempo : 0;
    }
    
    public function getProductInfo()
    {
        return Product::where('modelo', $this->modelo)
            ->with('workCenter')
            ->first();
    }
}
```

### Actualización de App\Models\User
```php
// Agregar método:
public function isIngenieroProcesos()
{
    return $this->id_profile === 6;
}
```

## Middleware

### App\Http\Middleware\EnsureUserIsIngenieroProcesos
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsIngenieroProcesos
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        if (!auth()->user()->isIngenieroProcesos()) {
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }
        
        return $next($request);
    }
}
```

### Actualización de bootstrap/app.php
```php
$middleware->alias([
    'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
    'gerencia' => \App\Http\Middleware\EnsureUserIsGerencia::class,
    'operador' => \App\Http\Middleware\EnsureUserIsOperador::class,
    'calidad' => \App\Http\Middleware\EnsureUserIsCalidad::class,
    'ingeniero_procesos' => \App\Http\Middleware\EnsureUserIsIngenieroProcesos::class, // NUEVO
]);
```

## Controlador

### App\Http\Controllers\IngenieroProcesosController
```php
<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\ProgramDetail;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class IngenieroProcesosController extends Controller
{
    public function index()
    {
        $programs = Program::with('creator')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return Inertia::render('IngenieroProcesos/Index', [
            'programs' => $programs,
        ]);
    }
    
    public function create()
    {
        $products = Product::with('workCenter')
            ->orderBy('modelo')
            ->get()
            ->groupBy('modelo');
        
        return Inertia::render('IngenieroProcesos/CreateProgram', [
            'products' => $products,
            'minDeliveryDate' => Program::addWorkingDays(now(), 4)->format('Y-m-d'),
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'fecha_entrega' => 'required|date|after_or_equal:' . Program::addWorkingDays(now(), 4)->format('Y-m-d'),
            'productos' => 'required|array|min:1',
            'productos.*.modelo' => 'required|string',
            'productos.*.cantidad' => 'required|integer|min:1',
        ]);
        
        DB::beginTransaction();
        
        try {
            $phaseDates = Program::calculatePhaseDates($request->fecha_entrega);
            
            $program = Program::create([
                'codigo' => Program::generateUniqueCode(),
                'fecha_entrega' => $request->fecha_entrega,
                'fecha_fase1' => $phaseDates['fase1'],
                'fecha_fase2' => $phaseDates['fase2'],
                'fecha_fase3' => $phaseDates['fase3'],
                'fecha_fase4' => $phaseDates['fase4'],
                'created_by' => auth()->id(),
            ]);
            
            foreach ($request->productos as $producto) {
                ProgramDetail::create([
                    'program_id' => $program->id,
                    'modelo' => $producto['modelo'],
                    'cantidad_solicitada' => $producto['cantidad'],
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('ingeniero-procesos.show', $program->id)
                ->with('success', 'Programa creado exitosamente.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al crear el programa: ' . $e->getMessage());
        }
    }
    
    public function show(Program $program)
    {
        $program->load('creator');
        
        $details = ProgramDetail::where('program_id', $program->id)
            ->get()
            ->map(function ($detail) {
                $product = Product::where('modelo', $detail->modelo)
                    ->with('workCenter')
                    ->first();
                
                return [
                    'id' => $detail->id,
                    'modelo' => $detail->modelo,
                    'cantidad_solicitada' => $detail->cantidad_solicitada,
                    'work_center' => $product ? $product->workCenter->name : null,
                    'phase' => $product ? $product->workCenter->phase : null,
                    'piezas_por_centro' => $product ? $product->piezas : 0,
                    'tiempo_por_centro' => $product ? $product->tiempo : 0,
                    'total_pieces' => $detail->total_pieces,
                    'total_time' => $detail->total_time,
                ];
            })
            ->sortBy('phase')
            ->groupBy('phase');
        
        return Inertia::render('IngenieroProcesos/ViewProgram', [
            'program' => $program,
            'details' => $details,
        ]);
    }
}
```

## Rutas

### Actualización de routes/web.php
```php
// Agregar después de las rutas de Calidad (línea ~90)

// ============================================
// MÓDULO INGENIERO DE PROCESOS
// ============================================
Route::prefix('ingeniero-procesos')->name('ingeniero-procesos.')->middleware('ingeniero_procesos')->group(function () {
    Route::get('/', [\App\Http\Controllers\IngenieroProcesosController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\IngenieroProcesosController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\IngenieroProcesosController::class, 'store'])->name('store');
    Route::get('/{program}', [\App\Http\Controllers\IngenieroProcesosController::class, 'show'])->name('show');
});
```

### Actualización de redirección del dashboard (línea ~32)
```php
Route::get('/dashboard', function () {
    $user = auth()->user();
    
    if ($user->id_profile === 7) {
        return redirect()->route('admin.users.index');
    } elseif ($user->id_profile === 1) {
        return redirect()->route('gerencia.dashboard');
    } elseif ($user->isIngenieroProcesos()) {  // NUEVO
        return redirect()->route('ingeniero-procesos.index');
    } elseif ($user->isSupervisor()) {
        return redirect()->route('supervisor.dashboard');
    } elseif ($user->isOperador()) {
        return redirect()->route('operador.dashboard');
    } elseif ($user->isCalidad()) {
        return redirect()->route('calidad.registrar-rechazo');
    }
    
    return Inertia::render('Dashboard');
})->name('dashboard');
```

## Componentes Vue

### resources/js/Components/IngenieroProcesosSidebar.vue
```vue
<template>
    <div class="w-64 bg-gray-900 min-h-screen">
        <div class="p-4">
            <h2 class="text-xl font-bold text-white mb-6">Ingeniero de Procesos</h2>
            <nav class="space-y-2">
                <Link :href="route('ingeniero-procesos.index')" 
                      class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg transition">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Programas
                </Link>
                <Link :href="route('ingeniero-procesos.create')" 
                      class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg transition">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Nuevo Programa
                </Link>
            </nav>
        </div>
        <div class="absolute bottom-0 w-64 p-4">
            <button @click="logout" class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                Cerrar Sesión
            </button>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';

function logout() {
    router.post(route('logout'));
}
</script>
```

### resources/js/Pages/IngenieroProcesos/Index.vue
```vue
<template>
    <div class="min-h-screen bg-gray-900 flex">
        <IngenieroProcesosSidebar />
        
        <div class="flex-1 p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-white">Programas de Fabricación</h1>
                <Link :href="route('ingeniero-procesos.create')" 
                      class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                    + Nuevo Programa
                </Link>
            </div>
            
            <div class="bg-gray-800 rounded-lg overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Código</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Fecha Entrega</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Creado Por</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Fecha Creación</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <tr v-for="program in programs" :key="program.id" class="hover:bg-gray-750">
                            <td class="px-6 py-4 whitespace-nowrap text-white">{{ program.codigo }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-white">{{ formatDate(program.fecha_entrega) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-white">{{ program.creator?.name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-white">{{ formatDate(program.created_at) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <Link :href="route('ingeniero-procesos.show', program.id)" 
                                      class="text-blue-400 hover:text-blue-300">
                                    Ver
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <div v-if="programs.length === 0" class="text-center py-8">
                    <p class="text-gray-400">No hay programas registrados</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';

defineProps({
    programs: Array,
});

function formatDate(date) {
    return new Date(date).toLocaleDateString('es-MX');
}
</script>
```

### resources/js/Pages/IngenieroProcesos/CreateProgram.vue
```vue
<template>
    <div class="min-h-screen bg-gray-900 flex">
        <IngenieroProcesosSidebar />
        
        <div class="flex-1 p-6">
            <h1 class="text-3xl font-bold text-white mb-6">Crear Nuevo Programa</h1>
            
            <form @submit.prevent="submit" class="bg-gray-800 rounded-lg p-6">
                <!-- Fecha de Entrega -->
                <div class="mb-6">
                    <label class="block text-gray-300 text-sm font-medium mb-2">Fecha de Entrega</label>
                    <input type="date" 
                           v-model="form.fecha_entrega" 
                           :min="minDeliveryDate"
                           class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                    <p v-if="errors.fecha_entrega" class="text-red-400 text-sm mt-1">{{ errors.fecha_entrega }}</p>
                    <p class="text-gray-400 text-sm mt-1">Mínimo: {{ formatDate(minDeliveryDate) }}</p>
                </div>
                
                <!-- Productos -->
                <div class="mb-6">
                    <label class="block text-gray-300 text-sm font-medium mb-2">Productos</label>
                    
                    <div v-for="(product, index) in form.productos" :key="index" class="flex gap-4 mb-4">
                        <select v-model="product.modelo" 
                                class="flex-1 bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="">Seleccionar modelo</option>
                            <option v-for="model in Object.keys(products)" :key="model" :value="model">
                                {{ model }}
                            </option>
                        </select>
                        
                        <input type="number" 
                               v-model="product.cantidad" 
                               min="1"
                               placeholder="Cantidad"
                               class="w-32 bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                        
                        <button type="button" 
                                @click="removeProduct(index)"
                                v-if="form.productos.length > 1"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                            Eliminar
                        </button>
                    </div>
                    
                    <button type="button" 
                            @click="addProduct"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        + Agregar Producto
                    </button>
                </div>
                
                <!-- Botones -->
                <div class="flex gap-4">
                    <button type="submit" 
                            :disabled="processing"
                            class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition disabled:opacity-50">
                        {{ processing ? 'Guardando...' : 'Crear Programa' }}
                    </button>
                    
                    <Link :href="route('ingeniero-procesos.index')" 
                          class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                        Cancelar
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';

const props = defineProps({
    products: Object,
    minDeliveryDate: String,
});

const form = useForm({
    fecha_entrega: '',
    productos: [{ modelo: '', cantidad: 1 }],
});

const errors = ref({});

function addProduct() {
    form.productos.push({ modelo: '', cantidad: 1 });
}

function removeProduct(index) {
    form.productos.splice(index, 1);
}

function submit() {
    form.post(route('ingeniero-procesos.store'), {
        onError: (errs) => {
            errors.value = errs;
        },
    });
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('es-MX');
}
</script>
```

### resources/js/Pages/IngenieroProcesos/ViewProgram.vue
```vue
<template>
    <div class="min-h-screen bg-gray-900 flex">
        <IngenieroProcesosSidebar />
        
        <div class="flex-1 p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-white">Programa {{ program.codigo }}</h1>
                    <p class="text-gray-400 mt-1">Creado por {{ program.creator?.name }}</p>
                </div>
                <Link :href="route('ingeniero-procesos.index')" 
                      class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                    Volver
                </Link>
            </div>
            
            <!-- Fechas de Fases -->
            <div class="grid grid-cols-4 gap-4 mb-6">
                <div class="bg-gray-800 rounded-lg p-4">
                    <h3 class="text-gray-400 text-sm uppercase mb-2">Fase 1</h3>
                    <p class="text-white font-semibold">{{ formatDate(program.fecha_fase1) }}</p>
                </div>
                <div class="bg-gray-800 rounded-lg p-4">
                    <h3 class="text-gray-400 text-sm uppercase mb-2">Fase 2</h3>
                    <p class="text-white font-semibold">{{ formatDate(program.fecha_fase2) }}</p>
                </div>
                <div class="bg-gray-800 rounded-lg p-4">
                    <h3 class="text-gray-400 text-sm uppercase mb-2">Fase 3</h3>
                    <p class="text-white font-semibold">{{ formatDate(program.fecha_fase3) }}</p>
                </div>
                <div class="bg-gray-800 rounded-lg p-4">
                    <h3 class="text-gray-400 text-sm uppercase mb-2">Fase 4 (Entrega)</h3>
                    <p class="text-white font-semibold">{{ formatDate(program.fecha_fase4) }}</p>
                </div>
            </div>
            
            <!-- Tabla por Fases -->
            <div v-for="(phaseDetails, phase) in details" :key="phase" class="mb-6">
                <h2 class="text-xl font-bold text-white mb-4">Fase {{ phase }}</h2>
                
                <div class="bg-gray-800 rounded-lg overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Centro de Trabajo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Modelo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Cantidad Solicitada</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Piezas Totales</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Tiempo Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <tr v-for="detail in phaseDetails" :key="detail.id" class="hover:bg-gray-750">
                                <td class="px-6 py-4 text-white">{{ detail.work_center }}</td>
                                <td class="px-6 py-4 text-white">{{ detail.modelo }}</td>
                                <td class="px-6 py-4 text-white">{{ detail.cantidad_solicitada }}</td>
                                <td class="px-6 py-4 text-white">{{ detail.total_pieces }}</td>
                                <td class="px-6 py-4 text-white">{{ detail.total_time }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';

defineProps({
    program: Object,
    details: Object,
});

function formatDate(date) {
    return new Date(date).toLocaleDateString('es-MX');
}
</script>
```

## Comandos para Ejecutar

```bash
# Crear migraciones
php artisan make:migration create_programs_table
php artisan make:migration create_program_details_table

# Ejecutar migraciones
php artisan migrate

# Crear middleware
php artisan make:middleware EnsureUserIsIngenieroProcesos

# Crear controlador
php artisan make:controller IngenieroProcesosController

# Limpiar caché
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Notas Adicionales

1. El modelo `Program` necesita el método estático `addWorkingDays` que se usa en el controlador
2. Asegurarse de que la tabla `work_centers` tenga el campo `phase` (ya existe según migración 2026_05_19_211300)
3. La tabla `products` debe tener datos con modelos válidos
4. Considerar agregar validaciones adicionales en el frontend
5. Considerar agregar paginación en el índice de programas
6. Considerar agregar opción de editar/eliminar programas (no incluido en este plan)

## Referencia de Imagen

La imagen proporcionada muestra el formato deseado para la tabla de visualización:
- Agrupación por fases (Fase 1, Fase 2, Fase 3, Fase 4)
- Columnas: Centro, Modelo, Piezas Totales
- Se debe agregar columna "Tiempo Total" según requerimiento
