# Implementación del Perfil "Operador de Área"

## Resumen
Se ha implementado exitosamente el nuevo perfil **"Operador de Área"** (id_profile=8) en el sistema de control de planta. Este perfil permite a los operadores gestionar líneas de producción específicas asignadas, con funcionalidades similares al Supervisor de Área pero con diferencias clave.

## Diferencias entre Supervisor y Operador

### Supervisor de Área (id_profile=5)
- Se le asignan **Centros de Trabajo**
- Puede crear "Encabezado de Turno" (programado, atraso, adelanto)
- KPIs completos (10 indicadores)
- Gestiona todas las líneas del centro de trabajo
- Vista completa del dashboard con información del centro

### Operador de Área (id_profile=8)
- Se le asignan **Líneas de Producción** específicas
- **NO puede crear** "Encabezado de Turno" (solo lectura)
- KPIs simplificados (3 indicadores: FABRICADAS, MIN. PARO, COSTO DE PARO)
- Gestiona solo las líneas asignadas
- Select desplegable para elegir línea de producción
- Comparte los mismos datos con Supervisores (actualizaciones en tiempo real)

## Archivos Creados

### 1. Migración
- `database/migrations/2026_05_08_000001_add_operador_profile.php`
  - Agrega el perfil "Operador de area" con id_profile=8

### 2. Modelos Actualizados
- `app/Models/User.php`
  - Método `isOperador()` - Verifica si el usuario es operador
  - Método `canViewProductionLine()` - Verifica permisos de visualización
  - Método `canEditProductionLine()` - Verifica permisos de edición
  - Método `getAccessibleProductionLines()` - Obtiene líneas accesibles
  - Relación `productionLines()` - Relación many-to-many con líneas

- `app/Models/ProductionLine.php`
  - Relación `users()` - Relación many-to-many con usuarios

### 3. Controlador
- `app/Http/Controllers/OperadorController.php`
  - `index()` - Dashboard principal del operador
  - `updateScheduleProduction()` - Actualizar producción por hora
  - `storeStrike()` - Registrar paro
  - `endStrike()` - Finalizar paro
  - `getProductionData()` - Obtener datos de producción (AJAX)

### 4. Middleware
- `app/Http/Middleware/EnsureUserIsOperador.php`
  - Protege las rutas del módulo operador
  - Registrado como alias 'operador' en `bootstrap/app.php`

### 5. Vistas
- `resources/views/operador/dashboard.blade.php`
  - Dashboard principal con select de líneas
  - KPIs simplificados (3 indicadores)
  - Tabla de producción por hora
  - Registro de paros
  - Modal para registrar nuevos paros

- `resources/views/operador/no-production-lines.blade.php`
  - Vista cuando el operador no tiene líneas asignadas

### 6. Vistas de Administrador Actualizadas
- `resources/views/admin/users/index.blade.php`
  - Muestra líneas de producción asignadas a operadores
  - Botón "📊 Asignar Líneas"

- `resources/views/admin/users/edit.blade.php`
  - Sección para asignar líneas de producción a operadores
  - Toggle automático según perfil seleccionado

- `resources/views/admin/users/import.blade.php`
  - Sección para asignar líneas al importar operadores
  - Toggle automático según perfil seleccionado

- `resources/views/admin/users/assign-production-lines.blade.php`
  - Vista dedicada para asignar líneas a operadores
  - Similar a la de asignar centros a supervisores

### 7. Controlador de Admin Actualizado
- `app/Http/Controllers/AdminController.php`
  - `assignProductionLines()` - Vista para asignar líneas
  - `updateProductionLines()` - Actualizar líneas asignadas
  - Métodos `importUser()` y `update()` actualizados para manejar líneas

### 8. Rutas
- `routes/web.php`
  - Grupo de rutas `/operador` con middleware 'operador'
  - Rutas de administrador para asignar líneas
  - Redirección en dashboard principal para operadores

### 9. Seeder
- `database/seeders/OperadorUserSeeder.php`
  - Crea usuario de prueba: operador@example.com / password123
  - Asigna líneas de producción 1 y 2

## Características Implementadas

### ✅ Dashboard del Operador
- Select desplegable para elegir línea de producción asignada
- Selector de fecha y turno
- KPIs simplificados:
  - **FABRICADAS**: Total de piezas producidas
  - **MIN. PARO**: Minutos totales de paro
  - **COSTO DE PARO**: Costo calculado de los paros

### ✅ Producción por Hora
- Tabla con horarios del turno
- Input para capturar producción por hora
- Auto-guardado con AJAX
- Indicador visual de guardado exitoso
- Actualización automática del total

### ✅ Registro de Paros
- Botón para registrar nuevo paro
- Modal con formulario:
  - Hora de inicio (requerido)
  - Hora de fin (opcional)
  - Descripción (requerido)
- Tabla con historial de paros
- Cálculo automático de duración

### ✅ Datos Compartidos
- Los schedules (producción por hora) son compartidos entre Supervisor y Operador
- Cuando un Operador actualiza la producción, el Supervisor ve los cambios
- Cuando un Supervisor actualiza, el Operador ve los cambios
- Mismo comportamiento para registro de paros

### ✅ Gestión de Usuarios (Admin)
- Importar usuarios con perfil Operador
- Asignar líneas de producción al crear/editar
- Vista dedicada para gestionar asignaciones
- Visualización de líneas asignadas en lista de usuarios

## Tabla de Base de Datos Utilizada

### user_production_lines
Ya existente en el sistema, contiene:
- `user_id` - ID del usuario (operador)
- `production_line_id` - ID de la línea de producción
- `can_view` - Permiso de visualización (boolean)
- `can_edit` - Permiso de edición (boolean)
- `can_delete` - Permiso de eliminación (boolean)
- `created_at` y `updated_at`

## Pasos para Activar la Funcionalidad

### 1. Ejecutar Migración
```bash
php artisan migrate
```

### 2. (Opcional) Ejecutar Seeder de Prueba
```bash
php artisan db:seed --class=OperadorUserSeeder
```

### 3. Acceder al Sistema
- **Usuario de prueba**: operador@example.com
- **Contraseña**: password123

### 4. Asignar Líneas desde Admin
1. Iniciar sesión como administrador
2. Ir a "Gestión de Usuarios"
3. Hacer clic en "📊 Asignar Líneas"
4. Seleccionar líneas para cada operador
5. Guardar cambios

## Flujo de Trabajo

### Para el Administrador:
1. Importar o crear usuario con perfil "Operador de area"
2. Asignar líneas de producción específicas
3. El operador puede iniciar sesión y ver solo sus líneas

### Para el Operador:
1. Iniciar sesión en el sistema
2. Seleccionar línea de producción del dropdown
3. Seleccionar fecha y turno
4. Capturar producción por hora
5. Registrar paros cuando ocurran
6. Los datos se comparten automáticamente con supervisores

### Para el Supervisor:
1. Ve las actualizaciones del operador en tiempo real
2. Puede editar los mismos registros
3. Ambos trabajan sobre los mismos datos

## Permisos y Seguridad

- Middleware `operador` protege todas las rutas del módulo
- Validación de permisos en cada acción del controlador
- Solo puede editar líneas asignadas con `can_edit=true`
- No puede acceder a líneas no asignadas
- Redirección automática al dashboard correcto según perfil

## Notas Importantes

1. **Datos Compartidos**: El sistema usa la misma tabla `schedules` para Supervisor y Operador, garantizando sincronización automática.

2. **Sin Encabezado de Turno**: Los operadores NO pueden crear ni editar el encabezado de turno (programado, atraso, adelanto). Esto debe ser creado por el Supervisor.

3. **Dependencia del Supervisor**: El operador necesita que el supervisor haya creado el programa diario primero para poder capturar producción.

4. **KPIs Simplificados**: Solo muestra 3 indicadores en lugar de los 10 del supervisor.

5. **Tabla Intermedia**: Usa `user_production_lines` para la relación many-to-many con permisos granulares.

## Próximos Pasos Sugeridos

- [ ] Agregar notificaciones cuando un operador registra un paro
- [ ] Implementar dashboard en tiempo real con WebSockets
- [ ] Agregar reportes específicos para operadores
- [ ] Implementar historial de cambios (auditoría)
- [ ] Agregar validaciones adicionales de negocio

## Conclusión

La implementación está **completa y funcional**. El perfil "Operador de Área" permite una gestión granular de líneas de producción, manteniendo la integridad de los datos compartidos con los supervisores y proporcionando una interfaz simplificada enfocada en las tareas específicas del operador.
