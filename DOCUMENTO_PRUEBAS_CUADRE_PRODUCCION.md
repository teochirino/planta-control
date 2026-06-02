# 📋 Documento de Pruebas - Sistema de Cuadre de Producción
**Fecha de implementación:** 28/05/2026

---

## FASE 1: Verificación de Base de Datos

### 1.1 Verificar que las tablas nuevas se crearon correctamente

Ejecutar estas consultas en la base de datos:

```sql
-- Verificar tabla work_center_balances
SHOW TABLES LIKE 'work_center_balances';
DESCRIBE work_center_balances;

-- Verificar tabla production_adjustments
SHOW TABLES LIKE 'production_adjustments';
DESCRIBE production_adjustments;

-- Verificar tabla rejected_pieces
SHOW TABLES LIKE 'rejected_pieces';
DESCRIBE rejected_pieces;
```

**Resultado esperado:** Las 3 tablas deben existir con la estructura correcta.

### 1.2 Verificar campos nuevos en daily_programs

```sql
DESCRIBE daily_programs;
```

**Campos nuevos que deben existir:**
- `operator_closed` (boolean)
- `operator_closed_at` (timestamp, nullable)
- `operator_closed_by` (foreign key a users, nullable)
- `balance_processed` (boolean)
- `balance_processed_at` (timestamp, nullable)
- `balance_processed_by` (foreign key a users, nullable)

---

## FASE 2: Prueba de Registro de Rechazos con Seguimiento

### 2.1 Como usuario de Calidad (id_profile=4)

1. **Iniciar sesión** como usuario con perfil Calidad
2. **Ir a:** `/calidad/registrar-rechazo`
3. **Registrar un rechazo:**
   - Seleccionar un schedule
   - Ingresar cantidad de piezas rechazadas (ej: 5)
   - Ingresar motivo del rechazo (opcional)
   - Clic en "Guardar"

### 2.2 Verificar en base de datos

```sql
SELECT * FROM rejected_pieces ORDER BY id DESC LIMIT 1;
```

**Resultado esperado:**
- Debe existir un registro con `resolution_status = 'pendiente'`
- Debe tener los campos: `id_schedule`, `id_daily_program`, `quantity`, `rejection_reason`, `rejected_by`, `rejected_at`

---

## FASE 3: Prueba de Bitácora de Piezas Rechazadas

### 3.1 Acceder a la bitácora

1. **Ir a:** `/calidad/rejected-pieces`
2. **Verificar:** Debe aparecer el rechazo registrado en Fase 2

### 3.2 Probar marcar como "Reparada"

1. **Clic en botón "Reparar"** del rechazo
2. **Ingresar notas de reparación** (ej: "Piezas reparadas por ajuste de maquinaria")
3. **Clic en "Confirmar"**

### 3.3 Verificar en base de datos

```sql
SELECT * FROM rejected_pieces WHERE id = <ID del rechazo>;
```

**Resultado esperado:**
- `resolution_status` debe ser `'reparada'`
- `resolution_notes` debe tener el texto ingresado
- `resolved_by` debe tener el ID del usuario que realizó la acción
- `resolved_at` debe tener la fecha y hora

### 3.4 Probar marcar como "Reemplazada"

1. **Registrar un nuevo rechazo** (repetir Fase 2)
2. **Clic en botón "Reemplazar"**
3. **Ingresar:**
   - Cantidad de piezas nuevas hechas (ej: 5)
   - Schedule donde se hicieron las nuevas piezas
   - Notas de reemplazo
4. **Clic en "Confirmar"**

### 3.5 Verificar en base de datos

```sql
SELECT * FROM rejected_pieces WHERE id = <ID del rechazo>;
```

**Resultado esperado:**
- `resolution_status` debe ser `'reemplazada'`
- `new_pieces_quantity` debe tener la cantidad ingresada
- `new_pieces_schedule_id` debe tener el ID del schedule seleccionado

### 3.6 Probar marcar como "Desechada"

1. **Registrar un nuevo rechazo** (repetir Fase 2)
2. **Clic en botón "Desechar"**
3. **Ingresar notas de desecho** (ej: "Piezas dañadas sin recuperación posible")
4. **Clic en "Confirmar"**

### 3.7 Verificar en base de datos

```sql
SELECT * FROM rejected_pieces WHERE id = <ID del rechazo>;
```

**Resultado esperado:**
- `resolution_status` debe ser `'desechada'`
- `resolution_notes` debe tener el texto ingresado

---

## FASE 4: Prueba de Cierre de Turno del Operador

### 4.1 Como usuario Operador (id_profile=8)

1. **Iniciar sesión** como usuario con perfil Operador
2. **Ir a:** `/operador/dashboard`
3. **Registrar producción** en las horas del turno
4. **Desplazarse al final de la página** donde está la sección "🔒 Cierre de Turno"
5. **Clic en el botón "Cerrar Turno"**
6. **Confirmar** en el diálogo de confirmación
7. **Verificar** que el botón desaparece y muestra el mensaje "Turno cerrado el [fecha y hora]"

### 4.2 Verificar en base de datos

```sql
SELECT * FROM daily_programs WHERE id = <ID del programa>;
```

**Resultado esperado:**
- `operator_closed` debe ser `1` (true)
- `operator_closed_at` debe tener la fecha y hora
- `operator_closed_by` debe tener el ID del operador

---

## FASE 5: Prueba de Ajustes Manuales del Supervisor

### 5.1 Como usuario Supervisor (id_profile=5)

1. **Iniciar sesión** como usuario con perfil Supervisor
2. **Ir a:** `/supervisor/daily-production`
3. **Desplazarse a la sección "⚙️ Acciones del Supervisor"** (al final de la página)
4. **Clic en el botón "Realizar Ajuste"** en la tarjeta "✏️ Ajuste Manual"
5. **Completar el formulario:**
   - Tipo de Ajuste: seleccionar "Conteo Físico", "Corrección de Datos" o "Ajuste de Inventario"
   - Valor Anterior: valor original (prellenado con producción actual)
   - Valor Nuevo: nuevo valor corregido
   - Motivo: descripción del ajuste (ej: "Conteo físico adicional")
   - Notas: comentarios opcionales
6. **Clic en "Guardar Ajuste"**
7. **Verificar** que se muestra el mensaje de éxito y la página se recarga

### 5.2 Verificar en base de datos

```sql
-- Verificar que se registró la corrección
SELECT * FROM production_adjustments ORDER BY id DESC LIMIT 1;

-- Verificar que se actualizó el schedule
SELECT * FROM schedules WHERE id = <ID del schedule>;
```

**Resultado esperado en production_adjustments:**
- `adjustment_type` debe ser `'correction'`
- `previous_value` debe tener el valor anterior
- `new_value` debe tener el valor corregido
- `difference` debe ser la diferencia entre ambos
- `reason` debe tener el motivo ingresado
- `adjusted_by` debe tener el ID del supervisor

---

## FASE 6: Prueba de Procesamiento de Balance

### 6.1 Como Supervisor

1. **Iniciar sesión** como usuario con perfil Supervisor
2. **Ir a:** `/supervisor/daily-production`
3. **Desplazarse a la sección "⚙️ Acciones del Supervisor"** (al final de la página)
4. **Clic en el botón "Procesar Balance"** en la tarjeta "📊 Procesar Balance"
5. **Confirmar** en el diálogo de confirmación
6. **Verificar** que el botón cambia a "✓ Balance Procesado" y muestra la fecha y hora
7. **Verificar** que el estado del turno muestra "✓ Cerrado por operador" (si el operador ya cerró el turno)

### 6.2 Verificar en base de datos

```sql
-- Verificar balance acumulado del centro
SELECT * FROM work_center_balances WHERE id_work_center = <ID del centro>;

-- Verificar que el programa se marcó como procesado
SELECT * FROM daily_programs WHERE id = <ID del programa>;
```

**Resultado esperado en work_center_balances:**
- `accumulated_backwardness` debe tener el atraso acumulado (si hubo)
- `accumulated_advanced` debe tener el adelanto acumulado (si hubo)
- `last_calculated_at` debe tener la fecha y hora

**Resultado esperado en daily_programs:**
- `balance_processed` debe ser `1` (true)
- `balance_processed_at` debe tener la fecha y hora
- `balance_processed_by` debe tener el ID del supervisor

---

## FASE 7: Prueba de Historial de Ajustes

### 7.1 Como Supervisor

1. **Iniciar sesión** como usuario con perfil Supervisor
2. **Ir a:** `/supervisor/daily-production`
3. **Desplazarse a la sección "⚙️ Acciones del Supervisor"** (al final de la página)
4. **Clic en el botón "Ver Historial"** en la tarjeta "📜 Historial de Ajustes"
5. **Verificar** que se abre una nueva pestaña con la vista de historial
6. **Verificar** que se muestran los ajustes realizados en el mes actual
7. **Cambiar las fechas** para filtrar por un período específico
8. **Clic en "Actualizar"** para recargar los datos

### 7.2 Verificar en la vista

**Resultado esperado:**
- Debe mostrarse una tabla con todos los ajustes
- Cada ajuste debe mostrar: fecha, tipo, valor anterior, valor nuevo, diferencia, motivo y usuario
- Los resúmenes en la parte superior deben mostrar el conteo por tipo de ajuste

---

## FASE 8: Verificación de Cálculos de Piezas Válidas

### 8.1 Verificar que DailyProgram.php tiene los métodos correctos

Revisar el archivo `app/Models/DailyProgram.php`:

**Debe tener estos campos en fillable (líneas 9-25):**
```php
'operator_closed',
'operator_closed_at',
'operator_closed_by',
'balance_processed',
'balance_processed_at',
'balance_processed_by'
```

**Debe tener el método getValidPiecesAttribute (líneas 54-71):**
```php
public function getValidPiecesAttribute()
{
    $totalProduced = $this->total_produced ?? 0;
    $totalRejected = $this->total_rejected ?? 0;
    
    $resolvedPieces = \App\Models\RejectedPiece::where('id_daily_program', $this->id)
        ->where('resolution_status', '!=', 'pendiente')
        ->get();
    
    $repairedCount = $resolvedPieces->where('resolution_status', 'reparada')->sum('quantity');
    $replacedCount = $resolvedPieces->where('resolution_status', 'reemplazada')->sum('new_pieces_quantity');
    
    $validPieces = $totalProduced - $totalRejected + $repairedCount + $replacedCount;
    
    return max($validPieces, 0);
}
```

**Debe tener el método getDifferenceAttribute modificado (líneas 87-90):**
```php
public function getDifferenceAttribute()
{
    return $this->valid_pieces - $this->total_to_produce;
}
```

### 8.2 Prueba de cálculo

1. **Crear un programa diario** con:
   - `programmed`: 100
   - `backwardness`: 0
   - `advanced`: 0

2. **Registrar producción:** 95 piezas

3. **Registrar rechazo:** 10 piezas

4. **Marcar 5 piezas como reparadas** en la bitácora

5. **Verificar el cálculo:**
```php
// En Tinker o código de prueba:
$program = DailyProgram::find(<ID>);
echo "Total producido: " . $program->total_produced . "\n"; // 95
echo "Total rechazado: " . $program->total_rejected . "\n"; // 10
echo "Piezas válidas: " . $program->valid_pieces . "\n"; // 90 (95 - 10 + 5)
echo "Total a producir: " . $program->total_to_produce . "\n"; // 100
echo "Diferencia: " . $program->difference . "\n"; // -10 (atraso)
```

**Resultado esperado:**
- `valid_pieces` = 90 (95 producidas - 10 rechazadas + 5 reparadas)
- `difference` = -10 (atraso de 10 piezas)

---

## FASE 9: Prueba de Balance Automático entre Días

### 9.1 Crear programa para el día 1

1. **Crear programa diario** para hoy con:
   - `programmed`: 100
   - `backwardness`: 0
   - `advanced`: 0

2. **Registrar producción:** 80 piezas

3. **Procesar balance** del día 1 (Fase 6)

### 9.2 Crear programa para el día 2

1. **Crear programa diario** para mañana con:
   - `programmed`: 100
   - `shift`: mismo turno que el día 1

2. **Verificar que se cargó el atraso:**
```sql
SELECT programmed, backwardness, advanced FROM daily_programs 
WHERE date = 'fecha de mañana' AND id_work_center = <ID>;
```

**Resultado esperado:**
- `backwardness` debe ser 20 (el atraso del día anterior)
- `advanced` debe ser 0

---

## FASE 10: Prueba de Historial de Ajustes

### 10.1 Como Supervisor

**Probar el endpoint de historial:**

**Opción A - Usando Postman/Insomnia:**
- **Método:** GET
- **URL:** `http://tu-dominio/supervisor/adjustments-history?work_center_id=<ID>&start_date=2026-05-01&end_date=2026-05-31`

**Opción B - Usando consola del navegador:**
```javascript
fetch('/supervisor/adjustments-history?work_center_id=<ID>&start_date=2026-05-01&end_date=2026-05-31')
  .then(r => r.json())
  .then(console.log);
```

**Resultado esperado:**
- Debe devolver un array con todos los ajustes registrados en el período
- Cada ajuste debe incluir: tipo, valores anterior/nuevo, diferencia, motivo, usuario, fecha

---

## ✅ Checklist Final de Verificación

- [ ] Tablas nuevas creadas correctamente
- [ ] Campos nuevos en daily_programs agregados
- [ ] Registro de rechazos crea entrada en rejected_pieces
- [ ] Bitácora de rechazos funciona correctamente
- [ ] Estados de resolución (reparada, reemplazada, desechada) funcionan
- [ ] Cierre de turno del operador funciona
- [ ] Corrección de datos por supervisor funciona
- [ ] Procesamiento de balance funciona
- [ ] Ajuste manual funciona
- [ ] Historial de ajustes funciona
- [ ] Cálculo de piezas válidas es correcto
- [ ] Balance automático entre días funciona

---

## 📝 Notas Importantes

1. **Las vistas de frontend YA están implementadas.** Ya no es necesario usar Postman o la consola del navegador para las pruebas. Todas las funcionalidades tienen botones y modales en las interfaces.

2. **Para obtener IDs:** Revisa la base de datos o usa las vistas existentes para identificar los IDs de programas, schedules, etc.

3. **Vistas implementadas:**
   - **Operador/Dashboard.vue:** Botón "Cerrar Turno" visible al final de la página
   - **Supervisor/DailyProduction.vue:** Sección "Acciones del Supervisor" con botones para procesar balance, ajuste manual y ver historial
   - **Supervisor/AdjustmentsHistory.vue:** Vista completa de historial de ajustes
   - **RejectedPieces/Index.vue:** Bitácora de piezas rechazadas con modales para resolución

---

## 📊 Resumen de Cambios Implementados

### Migrations (4 archivos)
- `2026_05_28_000001_create_work_center_balances_table.php`
- `2026_05_28_000002_create_production_adjustments_table.php`
- `2026_05_28_000003_create_rejected_pieces_table.php`
- `2026_05_28_000004_add_shift_closure_fields_to_daily_programs.php`

### Modelos (3 nuevos, 1 modificado)
- **Nuevos:** `WorkCenterBalance.php`, `ProductionAdjustment.php`, `RejectedPiece.php`
- **Modificado:** `DailyProgram.php` (agregados campos fillable, método valid_pieces, modificado difference)

### Servicios (1 nuevo, 1 modificado)
- **Nuevo:** `BalanceService.php`
- **Modificado:** `DailyProgramService.php` (agregado cálculo de balance del día anterior)

### Controladores (1 nuevo, 3 modificados)
- **Nuevo:** `RejectedPieceController.php`
- **Modificados:** `CalidadController.php`, `OperadorController.php`, `SupervisorController.php`

### Vistas (2 nuevas, 2 modificadas)
- **Nuevas:**
  - `RejectedPieces/Index.vue` (bitácora de piezas rechazadas)
  - `Supervisor/AdjustmentsHistory.vue` (historial de ajustes)
- **Modificadas:**
  - `Operador/Dashboard.vue` (agregado botón "Cerrar Turno")
  - `Supervisor/DailyProduction.vue` (agregada sección "Acciones del Supervisor" con procesamiento de balance, ajuste manual y acceso a historial)

### Rutas
- Agregadas rutas para bitácora de rechazos (módulo Calidad)
- Agregadas rutas para balance y correcciones (módulo Supervisor)
- Agregada ruta para cierre de turno (módulo Operador)

---

**✅ Sistema de Cuadre de Producción completamente implementado con interfaces de frontend.**

Todas las funcionalidades están listas para ser probadas directamente desde las interfaces de usuario sin necesidad de herramientas técnicas.
