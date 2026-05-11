<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\PermisosController;
use App\Http\Controllers\SupervisorController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/test-simple', function() {
    return response()->json(['status' => 'ok', 'message' => 'Ruta de prueba funcionando']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    
    // Producción
    Route::get('/produccion/{date}', [ProductionController::class, 'getByDate']);
    Route::get('/produccion-stats/{date}', [ProductionController::class, 'getStats']);
    Route::put('/produccion-hora/{scheduleId}', [ProductionController::class, 'updateHour']);
    
    // Andon
    Route::get('/production-lines', [ProductionController::class, 'getProductionLines']);
    Route::get('/machines/status', [ProductionController::class, 'getMachinesStatus']);
    Route::get('/hourly-production', [ProductionController::class, 'getHourlyProduction']);
    Route::get('/dashboard-kpis', [ProductionController::class, 'getDashboardKPIs']);
    
    // Permisos
    Route::get('/permissions/data', function() {
        return response()->json([
            'users' => \App\Models\User::with('profile')->get(),
            'workCenters' => \App\Models\WorkCenter::all(),
            'productionLines' => \App\Models\ProductionLine::with('workCenter')->get(),
        ]);
    });
    Route::get('/permissions/user/{userId}', [PermisosController::class, 'getUserPermissions']);
    Route::post('/permissions/work-centers/{userId}', [PermisosController::class, 'saveWorkCenterPermissions']);
    Route::post('/permissions/production-lines/{userId}', [PermisosController::class, 'saveProductionLinePermissions']);
    
    // Supervisor
    Route::post('/supervisor/production/auto-save', [SupervisorController::class, 'autoSaveProduction']);
    Route::post('/supervisor/production/save', [SupervisorController::class, 'saveProductionTable']);
    Route::post('/supervisor/daily-program', [SupervisorController::class, 'storeDailyProgram']);
    Route::post('/supervisor/strikes', [SupervisorController::class, 'storeStrike']);
    Route::put('/supervisor/strikes/{strike}/end', [SupervisorController::class, 'endStrike']);
    Route::get('/supervisor/strikes/{dailyProgramId}', [SupervisorController::class, 'getStrikesByProgram']);
    Route::get('/supervisor/production/data', [SupervisorController::class, 'getProductionData']);
    Route::post('/supervisor/schedule/update', [SupervisorController::class, 'updateScheduleProduction']);
});