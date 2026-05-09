<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\GerenciaController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\OperadorController;
use App\Http\Controllers\PermisosController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        
        // Redirigir según el perfil
        if ($user->id_profile === 7) {
            return redirect()->route('admin.users.index');
        } elseif ($user->id_profile === 1) {
            return redirect()->route('gerencia.dashboard');
        } elseif ($user->isSupervisor()) {
            return redirect()->route('supervisor.dashboard');
        } elseif ($user->isOperador()) {
            return redirect()->route('operador.dashboard');
        }
        
        return Inertia::render('Dashboard');
    })->name('dashboard');
    
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // ============================================
    // RUTAS INERTIA (VUE)
    // ============================================
    
    // Andon Dashboard
    Route::get('/andon', function () {
        return Inertia::render('AndonDashboard');
    })->name('andon');
    
    // Producción Diaria
    Route::get('/produccion', [ProductionController::class, 'index'])->name('produccion');
    
    // Administración de Permisos
    Route::get('/permisos', [PermisosController::class, 'index'])->name('permisos');
    
    // ============================================
    // MÓDULO ADMINISTRADOR
    // ============================================
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/users', [AdminController::class, 'index'])->name('users.index');
        Route::get('/users/import', [AdminController::class, 'importView'])->name('users.import');
        Route::post('/users/import', [AdminController::class, 'importUser'])->name('users.import.store');
        Route::get('/users/{user}/edit', [AdminController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [AdminController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [AdminController::class, 'destroy'])->name('users.destroy');
        
        Route::get('/work-centers/assign', [AdminController::class, 'assignWorkCenters'])->name('work-centers.assign');
        Route::post('/users/{user}/work-centers', [AdminController::class, 'updateWorkCenters'])->name('users.work-centers.update');
        
        Route::get('/production-lines/assign', [AdminController::class, 'assignProductionLines'])->name('production-lines.assign');
        Route::post('/users/{user}/production-lines', [AdminController::class, 'updateProductionLines'])->name('users.production-lines.update');
    });
    
    // ============================================
    // MÓDULO GERENCIA
    // ============================================
    Route::prefix('gerencia')->name('gerencia.')->middleware('gerencia')->group(function () {
        Route::get('/dashboard', [GerenciaController::class, 'dashboard'])->name('dashboard');
    });
    
    // ============================================
    // MÓDULO SUPERVISOR
    // ============================================
    Route::prefix('supervisor')->name('supervisor.')->group(function () {
        Route::get('/dashboard', [SupervisorController::class, 'index'])->name('dashboard');
        Route::get('/daily-production', [SupervisorController::class, 'dailyProduction'])->name('daily-production');
        
        // API endpoints para AJAX
        Route::post('/daily-program', [SupervisorController::class, 'storeDailyProgram'])->name('daily-program.store');
        Route::post('/schedule/update', [SupervisorController::class, 'updateScheduleProduction'])->name('schedule.update');
        Route::post('/production/auto-save', [SupervisorController::class, 'autoSaveProduction'])->name('production.auto-save');
        Route::post('/production/save', [SupervisorController::class, 'saveProductionTable'])->name('production.save');
        Route::get('/production/data', [SupervisorController::class, 'getProductionData'])->name('production.data');
        
        Route::post('/strikes', [SupervisorController::class, 'storeStrike'])->name('strikes.store');
        Route::put('/strikes/{strike}/end', [SupervisorController::class, 'endStrike'])->name('strikes.end');
    });
    
    // ============================================
    // MÓDULO OPERADOR
    // ============================================
    Route::prefix('operador')->name('operador.')->middleware('operador')->group(function () {
        Route::get('/dashboard', [OperadorController::class, 'index'])->name('dashboard');
        
        // API endpoints para AJAX
        Route::post('/schedule/update', [OperadorController::class, 'updateScheduleProduction'])->name('schedule.update');
        Route::get('/production/data', [OperadorController::class, 'getProductionData'])->name('production.data');
        
        Route::post('/strikes', [OperadorController::class, 'storeStrike'])->name('strikes.store');
        Route::put('/strikes/{strike}/end', [OperadorController::class, 'endStrike'])->name('strikes.end');
    });
    
    // ============================================
    // API ENDPOINTS (para Vue)
    // ============================================
    Route::get('/api/permissions/data', function() {
        return response()->json([
            'users' => \App\Models\User::with('profile')->get(),
            'workCenters' => \App\Models\WorkCenter::all(),
            'productionLines' => \App\Models\ProductionLine::with('workCenter')->get(),
        ]);
    });
    
    Route::get('/api/permissions/user/{userId}', [PermisosController::class, 'getUserPermissions']);
    Route::post('/api/permissions/work-centers/{userId}', [PermisosController::class, 'saveWorkCenterPermissions']);
    Route::post('/api/permissions/production-lines/{userId}', [PermisosController::class, 'saveProductionLinePermissions']);
    
    Route::get('/api/produccion/{date}', [ProductionController::class, 'getByDate']);
    Route::get('/api/produccion-stats/{date}', [ProductionController::class, 'getStats']);
    Route::put('/api/produccion-hora/{scheduleId}', [ProductionController::class, 'updateHour']);
});

require __DIR__.'/auth.php';