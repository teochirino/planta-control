<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\PermisosController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and will be assigned
| to the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rutas de producción
Route::middleware('auth:sanctum')->group(function () {
    // ============================================
    // PRODUCCIÓN DIARIA (CRUD)
    // ============================================
    
    // Obtener producción por fecha
    Route::get('/produccion/{date}', [ProductionController::class, 'getByDate']);
    
    // Obtener estadísticas por fecha
    Route::get('/produccion-stats/{date}', [ProductionController::class, 'getStats']);
    
    // Actualizar producción por hora
    Route::put('/produccion-hora/{scheduleId}', [ProductionController::class, 'updateHour']);
    
    // ============================================
    // ANDON DASHBOARD
    // ============================================
    
    // Obtener líneas de producción
    Route::get('/production-lines', [ProductionController::class, 'getProductionLines']);
    
    // Obtener estado de máquinas
    Route::get('/machines/status', [ProductionController::class, 'getMachinesStatus']);
    
    // Obtener producción por hora para gráfico
    Route::get('/hourly-production', [ProductionController::class, 'getHourlyProduction']);
    
    // Obtener KPIs del dashboard
    Route::get('/dashboard-kpis', [ProductionController::class, 'getDashboardKPIs']);
    
    // ============================================
    // ADMINISTRACIÓN DE PERMISOS
    // ============================================
    
    // Obtener datos para la interfaz de permisos
    Route::get('/permissions/data', function() {
        return response()->json([
            'users' => \App\Models\User::with('profile')->get(),
            'workCenters' => \App\Models\WorkCenter::all(),
            'productionLines' => \App\Models\ProductionLine::with('workCenter')->get(),
        ]);
    });
    
    // Obtener permisos de un usuario
    Route::get('/permissions/user/{userId}', [PermisosController::class, 'getUserPermissions']);
    
    // Guardar permisos de centros de trabajo
    Route::post('/permissions/work-centers/{userId}', [PermisosController::class, 'saveWorkCenterPermissions']);
    
    // Guardar permisos de líneas de producción
    Route::post('/permissions/production-lines/{userId}', [PermisosController::class, 'saveProductionLinePermissions']);
});