# ROLLBACK - Programas de Recuperación (Atrasos)

Este documento describe cómo revertir todos los cambios realizados para la funcionalidad de programas de recuperación.

## Cambios Realizados

### 1. Base de Datos
- **Archivo:** `database/migrations/2026_07_08_090000_add_program_type_to_programs_table.php`
- **Cambio:** Agregó campo `program_type` (enum: 'normal', 'recovery') a tabla `programs`

### 1.5 Modelo
- **Archivo:** `app/Models/Program.php`
- **Cambio:** Agregado `program_type` al array `$fillable` (línea 10)

### 2. Controlador
- **Archivo:** `app/Http/Controllers/IngenieroProcesosController.php`
- **Cambios:**
  - Modificado método `show()` (líneas 100-109): Agregado condicional para programas de recuperación
  - Modificado método `storeRecovery()` (líneas 1399-1483): Ahora calcula fechas de fase basadas en la fase del centro de trabajo y reduce el atraso acumulado
  - Agregado método `calculateRecoveryPhaseDates()` (líneas 946-1001): Calcula fechas de fase para programas de recuperación
  - Agregado método `getWorkCenterBalance()` (líneas 1396-1407): Obtiene el balance acumulado de un centro de trabajo
  - Agregados nuevos métodos (líneas 1247-1525): CRUD completo para programas de recuperación

### 3. Rutas
- **Archivo:** `routes/web.php`
- **Cambio:** Agregado grupo de rutas para programas de recuperación (líneas 200-215), incluyendo la ruta `balance` para obtener el balance del centro

### 4. Vistas Vue
- **Archivos creados:**
  - `resources/js/Pages/IngenieroProcesos/RecoveryIndex.vue`
  - `resources/js/Pages/IngenieroProcesos/CreateRecovery.vue`
  - `resources/js/Pages/IngenieroProcesos/ViewRecoveryProgram.vue`
  - `resources/js/Pages/IngenieroProcesos/EditRecovery.vue`

### 5. Sidebar
- **Archivo:** `resources/js/Components/IngenieroProcesosSidebar.vue`
- **Cambio:** Agregada sección "Recuperación (Atrasos)" con dos enlaces (líneas 63-92)

### 6. Vistas Vue (Modificaciones)
- **Archivos modificados:** Las vistas de recuperación ahora incluyen el sidebar y notificaciones toast para consistencia con el resto del módulo
  - `resources/js/Pages/IngenieroProcesos/RecoveryIndex.vue`
  - `resources/js/Pages/IngenieroProcesos/CreateRecovery.vue`
  - `resources/js/Pages/IngenieroProcesos/ViewRecoveryProgram.vue`
  - `resources/js/Pages/IngenieroProcesos/EditRecovery.vue`
- **Cambios:** 
  - Agregado `<IngenieroProcesosSidebar />` e importación del componente
  - Agregado sistema de notificaciones con `useToast`
  - Agregado watcher para flash messages
  - **CreateRecovery.vue específicamente:** Agregada funcionalidad para cargar automáticamente el balance del centro de trabajo seleccionado y mostrar información de atrasos/adelantos

## Pasos para Rollback Completo

### Paso 1: Revertir Migration de Base de Datos
```bash
php artisan migrate:rollback
```
Esto eliminará el campo `program_type` de la tabla `programs`.

### Paso 1.5: Revertir Cambio en Modelo
**Archivo:** `app/Models/Program.php`

Eliminar `program_type` del array `$fillable` (línea 10):
```php
protected $fillable = ['codigo', 'fecha_entrega', 'fecha_fase1', 'fecha_fase2', 'fecha_fase3', 'fecha_fase4', 'total_time', 'total_piezas', 'created_by'];
```

### Paso 2: Revertir Cambios en Controlador
**Archivo:** `app/Http/Controllers/IngenieroProcesosController.php`

1. **Eliminar el condicional en método `show()`** (líneas 100-109):
   ```php
   // ELIMINAR ESTE BLOQUE COMPLETO
   // ============================================
   // ROLLBACK: Eliminar este bloque condicional para volver al comportamiento original
   // ============================================
   // Si es un programa de recuperación, mostrar vista especial
   if (isset($program->program_type) && $program->program_type === 'recovery') {
       return $this->showRecoveryProgram($program, $request);
   }
   // ============================================
   // FIN ROLLBACK
   // ============================================
   ```

2. **Eliminar toda la sección CRUD de recuperación** (líneas 1247-1525):
   ```php
   // ELIMINAR ESTA SECCIÓN COMPLETA
   // ============================================
   // CRUD DE PROGRAMAS DE RECUPERACIÓN (ATRASOS)
   // ============================================
   // ROLLBACK: Eliminar toda esta sección para volver al comportamiento original
   // ============================================
   
   [TODOS LOS MÉTODOS DE RECUPERACIÓN]
   
   // ============================================
   // FIN CRUD DE PROGRAMAS DE RECUPERACIÓN
   // ============================================
   ```

3. **Eliminar el método `calculateRecoveryPhaseDates()`** (líneas 946-1001):
   ```php
   // ELIMINAR ESTE MÉTODO COMPLETO
   /**
    * Calcular fechas de fase para programas de recuperación
    * Basado en la fase del centro de trabajo seleccionado
    */
   private function calculateRecoveryPhaseDates($selectedDate, $workCenterPhase)
   {
       [TODO EL CÓDIGO DEL MÉTODO]
   }
   ```

4. **Eliminar el método `getWorkCenterBalance()`** (líneas 1396-1407):
   ```php
   // ELIMINAR ESTE MÉTODO COMPLETO
   /**
    * Obtener balance acumulado de un centro de trabajo
    */
   public function getWorkCenterBalance($workCenterId)
   {
       $balance = \App\Models\WorkCenterBalance::where('id_work_center', $workCenterId)->first();
       
       return response()->json([
           'accumulated_backwardness' => $balance ? $balance->accumulated_backwardness : 0,
           'accumulated_advanced' => $balance ? $balance->accumulated_advanced : 0,
       ]);
   }
   ```

5. **Revertir cambios en método `storeRecovery()`**: Eliminar la lógica de reducción de atraso acumulado (líneas 1467-1472)

### Paso 3: Revertir Cambios en Rutas
**Archivo:** `routes/web.php`

Eliminar el grupo de rutas de recuperación (líneas 200-215):
```php
// ELIMINAR ESTE BLOQUE COMPLETO
// ============================================
// ROLLBACK: Eliminar estas rutas para volver al comportamiento original
// ============================================
// CRUD DE PROGRAMAS DE RECUPERACIÓN (ATRASOS)
Route::prefix('recuperacion')->name('recuperacion.')->group(function () {
    Route::get('/', [\App\Http\Controllers\IngenieroProcesosController::class, 'recoveryIndex'])->name('index');
    Route::get('/create', [\App\Http\Controllers\IngenieroProcesosController::class, 'createRecovery'])->name('create');
    Route::post('/', [\App\Http\Controllers\IngenieroProcesosController::class, 'storeRecovery'])->name('store');
    Route::get('/{program}', [\App\Http\Controllers\IngenieroProcesosController::class, 'showRecovery'])->name('show');
    Route::get('/{program}/edit', [\App\Http\Controllers\IngenieroProcesosController::class, 'editRecovery'])->name('edit');
    Route::put('/{program}', [\App\Http\Controllers\IngenieroProcesosController::class, 'updateRecovery'])->name('update');
    Route::delete('/{program}', [\App\Http\Controllers\IngenieroProcesosController::class, 'destroyRecovery'])->name('destroy');
});
// ============================================
// FIN ROLLBACK
// ============================================
```

### Paso 4: Revertir Cambios en Sidebar
**Archivo:** `resources/js/Components/IngenieroProcesosSidebar.vue`

Eliminar la sección de recuperación (líneas 63-92):
```vue
<!-- ELIMINAR ESTE BLOQUE COMPLETO -->
<!-- ============================================ -->
<!-- ROLLBACK: Eliminar esta sección para volver al comportamiento original -->
<!-- ============================================ -->
<!-- Sección Programas de Recuperación -->
<div class="mb-4">
    <p class="text-xs font-semibold uppercase tracking-wider mb-2 px-4" style="color: #6a8090;">Recuperación (Atrasos)</p>
    <Link 
        :href="route('ingeniero-procesos.recuperacion.index')" 
        class="sidebar-link"
        :class="{ 'active': route().current('ingeniero-procesos.recuperacion.index') }"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        <span>Programas de Recuperación</span>
    </Link>
    <Link 
        :href="route('ingeniero-procesos.recuperacion.create')" 
        class="sidebar-link"
        :class="{ 'active': route().current('ingeniero-procesos.recuperacion.create') }"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Nuevo Programa Recuperación</span>
    </Link>
</div>
<!-- ============================================ -->
<!-- FIN ROLLBACK -->
<!-- ============================================ -->
```

### Paso 5: Revertir Cambios en Vistas Vue
**Archivos:** 
- `resources/js/Pages/IngenieroProcesos/RecoveryIndex.vue`
- `resources/js/Pages/IngenieroProcesos/CreateRecovery.vue`
- `resources/js/Pages/IngenieroProcesos/ViewRecoveryProgram.vue`
- `resources/js/Pages/IngenieroProcesos/EditRecovery.vue`

**Cambios a revertir en cada archivo:**
1. Eliminar `<IngenieroProcesosSidebar />` del template
2. Eliminar `import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';` del script
3. Eliminar `import { useToast } from 'vue-toastification';` y `import { usePage } from '@inertiajs/vue3';` del script
4. Eliminar `const page = usePage();` y `const toast = useToast();` del script
5. Eliminar el watcher de flash messages
6. Cambiar `<div class="min-h-screen" style="background: #eaf0f5;">` a `<div class="min-h-screen bg-gray-100">`
7. Cambiar `<div class="p-6 ml-16">` a `<div class="p-6">`

**Cambios adicionales para CreateRecovery.vue:**
8. Eliminar las variables `currentBackwardness`, `currentAdvanced`, `loadingBalance` del script
9. Eliminar el watcher de `form.work_center_id`
10. Eliminar la sección de "Información de Balance Actual" del template (líneas 45-65)
11. Revertir el cambio en el select del centro de trabajo para mostrar solo el nombre sin la fase

### Paso 6: Eliminar Vistas Vue
Eliminar los siguientes archivos:
```bash
rm resources/js/Pages/IngenieroProcesos/RecoveryIndex.vue
rm resources/js/Pages/IngenieroProcesos/CreateRecovery.vue
rm resources/js/Pages/IngenieroProcesos/ViewRecoveryProgram.vue
rm resources/js/Pages/IngenieroProcesos/EditRecovery.vue
```

### Paso 7: Eliminar Archivo de Migration
```bash
rm database/migrations/2026_07_08_090000_add_program_type_to_programs_table.php
```

### Paso 8: Eliminar Este Documento
```bash
rm ROLLBACK_PROGRAMAS_RECUPERACION.md
```

## Verificación de Rollback

Después de realizar el rollback, verificar que:

1. **Base de datos:** El campo `program_type` ya no existe en la tabla `programs`
2. **Controlador:** El método `show()` funciona como antes (sin condicional)
3. **Rutas:** Las rutas de recuperación ya no existen
4. **Sidebar:** La sección de recuperación ya no existe en el sidebar
5. **Vistas:** Las vistas de recuperación fueron eliminadas
6. **Funcionalidad:** Todos los perfiles funcionan normalmente sin la nueva funcionalidad

## Notas Importantes

- **Los programas de recuperación creados permanecerán en la base de datos** como programas normales después del rollback, pero sin el campo `program_type`.
- **Los DailyPrograms creados por programas de recuperación seguirán funcionando normalmente** porque no dependen del campo `program_type`.
- **Todos los demás perfiles del sistema seguirán funcionando correctamente** porque no dependen de esta funcionalidad.

## Contacto

Si tienes problemas durante el rollback, verifica que:
1. No hay programas de recuperación activos con producción registrada
2. No hay paros o rechazos asociados a programas de recuperación
3. El sistema está en un estado estable antes de proceder
