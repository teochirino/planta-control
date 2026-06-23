<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\GerenciaController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\OperadorController;
use App\Http\Controllers\PermisosController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RejectedPieceController;
use App\Http\Controllers\NotificationRecipientController;
use App\Http\Controllers\ProductionLineController;
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
        } elseif ($user->isGerenteProduccion()) {
            return redirect()->route('gerencia.monitoreo');
        } elseif ($user->isGerenteMantenimiento()) {
            return redirect()->route('gerente-mantenimiento.dashboard');
        } elseif ($user->isIngenieroProcesos()) {
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
        
        // Destinatarios de notificaciones
        Route::get('/notification-recipients', [NotificationRecipientController::class, 'index'])->name('notification-recipients.index');
        Route::get('/notification-recipients/create', [NotificationRecipientController::class, 'create'])->name('notification-recipients.create');
        Route::post('/notification-recipients', [NotificationRecipientController::class, 'store'])->name('notification-recipients.store');
        Route::get('/notification-recipients/{notificationRecipient}/edit', [NotificationRecipientController::class, 'edit'])->name('notification-recipients.edit');
        Route::put('/notification-recipients/{notificationRecipient}', [NotificationRecipientController::class, 'update'])->name('notification-recipients.update');
        Route::delete('/notification-recipients/{notificationRecipient}', [NotificationRecipientController::class, 'destroy'])->name('notification-recipients.destroy');
    });
    
    // ============================================
    // MÓDULO GERENCIA
    // ============================================
    Route::prefix('gerencia')->name('gerencia.')->group(function () {
        // Rutas exclusivas para Gerencia (id_profile = 1)
        Route::middleware('gerencia')->group(function () {
            Route::get('/dashboard', [GerenciaController::class, 'dashboard'])->name('dashboard');
        });
        
        // Rutas compartidas entre Gerencia y Gerente de Produccion
        Route::middleware('gerencia_or_gerente_produccion')->group(function () {
            Route::get('/monitoreo', [App\Http\Controllers\Gerencia\MonitoreoController::class, 'index'])->name('monitoreo');
            Route::get('/monitoreo-data', [App\Http\Controllers\Gerencia\MonitoreoController::class, 'getData'])->name('monitoreo.data');
        });
    });
    
    // ============================================
    // MÓDULO CALIDAD
    // ============================================
    Route::prefix('calidad')->name('calidad.')->middleware('calidad')->group(function () {
        Route::get('/registrar-rechazo', [\App\Http\Controllers\CalidadController::class, 'registrarRechazo'])->name('registrar-rechazo');
        Route::post('/store-rechazo', [\App\Http\Controllers\CalidadController::class, 'storeRechazo'])->name('store-rechazo');
        Route::get('/rechazos', [\App\Http\Controllers\CalidadController::class, 'rechazos'])->name('rechazos');
        
        // Bitácora de piezas rechazadas
        Route::get('/rejected-pieces', [RejectedPieceController::class, 'index'])->name('rejected-pieces.index');
        Route::post('/rejected-pieces/{id}/repaired', [RejectedPieceController::class, 'markAsRepaired'])->name('rejected-pieces.repaired');
        Route::post('/rejected-pieces/{id}/replaced', [RejectedPieceController::class, 'markAsReplaced'])->name('rejected-pieces.replaced');
        Route::post('/rejected-pieces/{id}/discarded', [RejectedPieceController::class, 'markAsDiscarded'])->name('rejected-pieces.discarded');
        Route::get('/rejected-pieces/schedules', [RejectedPieceController::class, 'getSchedulesForReplacement'])->name('rejected-pieces.schedules');
    });
    
    // ============================================
    // MÓDULO GERENTE DE MANTENIMIENTO
    // ============================================
    Route::prefix('gerente-mantenimiento')->name('gerente-mantenimiento.')->middleware('gerente_mantenimiento')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\GerenteMantenimientoController::class, 'dashboard'])->name('dashboard');
        Route::get('/machines', [\App\Http\Controllers\GerenteMantenimientoController::class, 'machinesList'])->name('machines');
        Route::get('/reports', [\App\Http\Controllers\GerenteMantenimientoController::class, 'reports'])->name('reports');
        
        // API endpoints
        Route::get('/breakdowns/pending', [\App\Http\Controllers\GerenteMantenimientoController::class, 'getPendingBreakdowns'])->name('breakdowns.pending');
        Route::post('/breakdowns/{id}/confirm', [\App\Http\Controllers\GerenteMantenimientoController::class, 'confirmBreakdown'])->name('breakdowns.confirm');
        Route::put('/machines/{id}/state', [\App\Http\Controllers\GerenteMantenimientoController::class, 'updateMachineState'])->name('machines.update-state');
        Route::get('/machines/{id}/breakdowns', [\App\Http\Controllers\GerenteMantenimientoController::class, 'getMachineBreakdowns'])->name('machines.breakdowns');
        Route::get('/export', [\App\Http\Controllers\GerenteMantenimientoController::class, 'exportReport'])->name('export');
    });
    
    // ============================================
    // MÓDULO INGENIERO DE PROCESOS
    // ============================================
    Route::prefix('ingeniero-procesos')->name('ingeniero-procesos.')->middleware('ingeniero_procesos')->group(function () {
        Route::get('/', [\App\Http\Controllers\IngenieroProcesosController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\IngenieroProcesosController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\IngenieroProcesosController::class, 'store'])->name('store');
        
        // CRUD de Productos (rutas específicas primero)
        Route::get('/productos', [\App\Http\Controllers\IngenieroProcesosController::class, 'productsIndex'])->name('products.index');
        Route::get('/productos/create', [\App\Http\Controllers\IngenieroProcesosController::class, 'productCreate'])->name('products.create');
        Route::post('/productos', [\App\Http\Controllers\IngenieroProcesosController::class, 'productStore'])->name('products.store');
        Route::get('/productos/{modelo}/edit', [\App\Http\Controllers\IngenieroProcesosController::class, 'productEdit'])->name('products.edit');
        Route::put('/productos/{modelo}', [\App\Http\Controllers\IngenieroProcesosController::class, 'productUpdate'])->name('products.update');
        Route::delete('/productos/{modelo}', [\App\Http\Controllers\IngenieroProcesosController::class, 'productDestroy'])->name('products.destroy');

        // CRUD de Líneas de Producción
        Route::get('/production-lines', [ProductionLineController::class, 'index'])->name('production-lines.index');
        Route::get('/production-lines/create', [ProductionLineController::class, 'create'])->name('production-lines.create');
        Route::post('/production-lines', [ProductionLineController::class, 'store'])->name('production-lines.store');
        Route::get('/production-lines/{productionLine}/edit', [ProductionLineController::class, 'edit'])->name('production-lines.edit');
        Route::put('/production-lines/{productionLine}', [ProductionLineController::class, 'update'])->name('production-lines.update');
        Route::delete('/production-lines/{productionLine}', [ProductionLineController::class, 'destroy'])->name('production-lines.destroy');

        // Importación de productos desde Excel
        Route::get('/importar-productos', [\App\Http\Controllers\IngenieroProcesosController::class, 'importProductsView'])->name('import.products');
        Route::post('/importar-productos', [\App\Http\Controllers\IngenieroProcesosController::class, 'importProducts'])->name('import.products.store');
        Route::post('/crear-programa-excel', [\App\Http\Controllers\IngenieroProcesosController::class, 'createProgramFromExcel'])->name('import.products.create');

        // Exportación de productos a Excel
        Route::get('/exportar-productos', [\App\Http\Controllers\IngenieroProcesosController::class, 'exportProductsView'])->name('export.products');
        Route::get('/exportar-productos/download', [\App\Http\Controllers\IngenieroProcesosController::class, 'exportProducts'])->name('export.products.download');

        // Ajustes de producción
        Route::get('/ajustes-produccion', [\App\Http\Controllers\IngenieroProcesosController::class, 'productionAdjustments'])->name('production-adjustments');
        Route::get('/registrar-ajustes', [\App\Http\Controllers\IngenieroProcesosController::class, 'registerAdjustmentsView'])->name('register-adjustments');
        Route::get('/registrar-ajustes/load', [\App\Http\Controllers\IngenieroProcesosController::class, 'loadDailyProgramsForAdjustment'])->name('register-adjustments.load');
        Route::get('/daily-programs/{id}/edit', [\App\Http\Controllers\IngenieroProcesosController::class, 'editDailyProgram'])->name('daily-programs.edit');
        Route::put('/daily-programs/{id}', [\App\Http\Controllers\IngenieroProcesosController::class, 'updateDailyProgram'])->name('daily-programs.update');

        // Ruta dinámica de programas (debe ir al final, después de las rutas específicas)
        Route::get('/{program}', [\App\Http\Controllers\IngenieroProcesosController::class, 'show'])->name('show');
        Route::delete('/{program}', [\App\Http\Controllers\IngenieroProcesosController::class, 'destroy'])->name('destroy');
    });
    
    
    // ============================================
    // MÓDULO SUPERVISOR
    // ============================================
    Route::prefix('supervisor')->name('supervisor.')->group(function () {
        Route::get('/dashboard', [SupervisorController::class, 'index'])->name('dashboard');
        Route::get('/daily-production', [SupervisorController::class, 'dailyProduction'])->name('daily-production');
        
        // 🔧 Ruta para obtener paros (GET)
        Route::get('/strikes/{dailyProgramId}', [SupervisorController::class, 'getStrikesByProgram'])
            ->name('strikes.index');
        
        // API endpoints para AJAX
        Route::post('/daily-program', [SupervisorController::class, 'storeDailyProgram'])->name('daily-program.store');
        Route::post('/schedule/update', [SupervisorController::class, 'updateScheduleProduction'])->name('schedule.update');
        Route::post('/production/auto-save', [SupervisorController::class, 'autoSaveProduction'])->name('production.auto-save');
        Route::post('/production/save', [SupervisorController::class, 'saveProductionTable'])->name('production.save');
        Route::get('/production/data', [SupervisorController::class, 'getProductionData'])->name('production.data');
        
        Route::post('/strikes', [SupervisorController::class, 'storeStrike'])->name('strikes.store');
        Route::put('/strikes/{strike}/end', [SupervisorController::class, 'endStrike'])->name('strikes.end');
        
        // Balance y correcciones
        Route::post('/correct-operator-data', [SupervisorController::class, 'correctOperatorData'])->name('correct-operator-data');
        Route::post('/process-balance', [SupervisorController::class, 'processShiftBalance'])->name('process-balance');
        Route::post('/manual-adjustment', [SupervisorController::class, 'registerManualAdjustment'])->name('manual-adjustment');
        Route::get('/adjustments-history', [SupervisorController::class, 'getAdjustmentsHistory'])->name('adjustments-history');
        
        // Ajustes de producción (vistas)
        Route::get('/production-adjustments', [SupervisorController::class, 'productionAdjustments'])->name('production-adjustments');
        Route::get('/register-adjustments', [SupervisorController::class, 'registerAdjustmentsView'])->name('register-adjustments');
        Route::get('/register-adjustments/load', [SupervisorController::class, 'loadDailyProgramsForAdjustment'])->name('register-adjustments.load');
        Route::put('/daily-programs/{id}', [SupervisorController::class, 'updateDailyProgram'])->name('daily-programs.update');
        
        // Atributos - Semáforos del Área
        Route::post('/attributes/{attribute}/change-color', [\App\Http\Controllers\AttributeController::class, 'changeColor'])->name('attributes.change-color');
        Route::get('/attributes/{attribute}/history', [\App\Http\Controllers\AttributeController::class, 'getHistory'])->name('attributes.history');
        Route::get('/attributes/recent-changes', [\App\Http\Controllers\AttributeController::class, 'getRecentChanges'])->name('attributes.recent-changes');
    });
    
    // ============================================
    // MÓDULO OPERADOR
    // ============================================
    Route::prefix('operador')->name('operador.')->middleware('operador')->group(function () {
        Route::get('/dashboard', [OperadorController::class, 'index'])->name('dashboard');
        
        // API endpoints para AJAX
        Route::post('/schedule/update', [OperadorController::class, 'updateScheduleProduction'])->name('schedule.update');
        Route::get('/production/data', [OperadorController::class, 'getProductionData'])->name('production.data');
        Route::post('/close-shift', [OperadorController::class, 'closeShift'])->name('close-shift');
        
        Route::post('/strikes', [OperadorController::class, 'storeStrike'])->name('strikes.store');
        Route::put('/strikes/{id}/end', [OperadorController::class, 'endStrike'])->name('strikes.end');
        // Obtener paros por programa diario
Route::get('/strikes/{dailyProgramId}', [OperadorController::class, 'getStrikesByProgram'])
    ->name('strikes.index');
    });
    
    // ============================================
    // API ENDPOINTS (para Vue)
    // ============================================
    // COMENTADO: Rutas movidas a routes/api.php para evitar duplicación
    // Route::get('/api/permissions/data', function() {
    //     return response()->json([
    //         'users' => \App\Models\User::with('profile')->get(),
    //         'workCenters' => \App\Models\WorkCenter::all(),
    //         'productionLines' => \App\Models\ProductionLine::with('workCenter')->get(),
    //     ]);
    // });
    
    // Route::get('/api/permissions/user/{userId}', [PermisosController::class, 'getUserPermissions']);
    // Route::post('/api/permissions/work-centers/{userId}', [PermisosController::class, 'saveWorkCenterPermissions']);
    // Route::post('/api/permissions/production-lines/{userId}', [PermisosController::class, 'saveProductionLinePermissions']);
    
    // Route::get('/api/produccion/{date}', [ProductionController::class, 'getByDate']);
    // Route::get('/api/produccion-stats/{date}', [ProductionController::class, 'getStats']);
    // Route::put('/api/produccion-hora/{scheduleId}', [ProductionController::class, 'updateHour']);
});

require __DIR__.'/auth.php';