# Sistema de Control de Planta - Guía de Instalación

## 📋 Requisitos Previos

- PHP 8.2 o superior
- MySQL 8.0 o superior
- Composer
- Node.js y NPM
- Laragon (o servidor local equivalente)

## 🚀 Instalación

### 1. Configurar Base de Datos

Asegúrate de tener dos bases de datos:
- `planta_control` (base de datos principal)
- `italianet_users` (base de datos de usuarios existente)

### 2. Configurar Variables de Entorno

Edita el archivo `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=planta_control
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Ejecutar Migraciones

```bash
php artisan migrate
```

Esto ejecutará las siguientes migraciones:
- ✅ Modificación de tabla `schedules` (agregar `id_production_line`)
- ✅ Modificación de tabla `daily_programs` (agregar `total_produced`, `shift_hours`)
- ✅ Modificación de tabla `strikes` (agregar `date`, `start_time`, `end_time`, `cost`, `id_daily_program`)

### 4. Instalar Dependencias Frontend

```bash
npm install
npm run build
```

## 📊 Estructura de la Base de Datos

### Tablas Principales

#### `work_centers` (Centros de Trabajo)
- `id`
- `name`
- `installed_capacity` - Capacidad instalada por hora

#### `production_lines` (Líneas de Producción)
- `id`
- `id_work_center`
- `title`
- `installed_capacity`
- `cost` - Costo por minuto de paro

#### `daily_programs` (Programas Diarios)
- `id`
- `date`
- `id_production_lines`
- `shift` (matutino, vespertino, nocturno)
- `programmed` - Piezas programadas
- `backwardness` - Piezas en atraso
- `advanced` - Piezas adelantadas
- `total_produced` - Total producido (acumulador)
- `shift_hours` - Horas del turno

#### `schedules` (Horarios de Producción)
- `id`
- `id_daily_program`
- `id_production_line` - **NUEVO CAMPO**
- `start_time`
- `end_time`
- `produced` - Piezas producidas en esta hora

#### `strikes` (Paros de Producción)
- `id`
- `id_production_lines`
- `date` - **NUEVO CAMPO**
- `id_daily_program` - **NUEVO CAMPO**
- `description`
- `start_time` - **NUEVO CAMPO**
- `end_time` - **NUEVO CAMPO**
- `minutes` - Calculado automáticamente
- `cost` - **NUEVO CAMPO** - Calculado automáticamente

#### `users` (Usuarios del Sistema)
- `id`
- `name`
- `email`
- `password`
- `user_main_id` - ID del usuario en italianet_users
- `id_profile` - Perfil del usuario

#### `user_work_centers` (Asignación de Centros)
- `user_id`
- `work_center_id`

## 👥 Perfiles de Usuario

1. **Gerencia** (id_profile = 1)
2. **Gerente de Producción** (id_profile = 2)
3. **Gerente de Mantenimiento** (id_profile = 3)
4. **Calidad** (id_profile = 4)
5. **Supervisor de Área** (id_profile = 5) ⭐
6. **Ingeniería de Procesos** (id_profile = 6)

## 🎯 Funcionalidades Principales

### Módulo Administrador

**Acceso:** Usuarios con perfil "Gerencia" (id_profile = 1)

**Rutas:**
- `/admin/users` - Lista de usuarios
- `/admin/users/import` - Importar usuarios desde italianet_users
- `/admin/users/{user}/edit` - Editar usuario
- `/admin/work-centers/assign` - Asignar centros de trabajo a supervisores

**Funcionalidades:**
1. ✅ Importar usuarios desde base de datos `italianet_users`
2. ✅ Asignar perfiles a usuarios
3. ✅ Asignar centros de trabajo a Supervisores de Área
4. ✅ Editar y eliminar usuarios

### Módulo Supervisor

**Acceso:** Usuarios con perfil "Supervisor de Área" (id_profile = 5)

**Rutas:**
- `/supervisor/dashboard` - Dashboard principal
- `/supervisor/daily-production` - Registro diario de producción

**Funcionalidades:**

#### 1. Dashboard
- Selector de centro de trabajo (si tiene múltiples asignados)
- Vista de líneas de producción del centro
- Acceso rápido al registro diario

#### 2. Registro Diario de Producción
- ✅ Selector de fecha y turno
- ✅ Formulario de programa del turno (programado, atraso, adelantadas)
- ✅ **Tabla horaria con múltiples líneas de producción**
- ✅ Captura de producción por hora y por línea
- ✅ Cálculo automático de totales (PPH - Piezas Por Hora)
- ✅ KPIs en tiempo real:
  - Programado
  - Atraso
  - Adelantadas
  - Total a Producir
  - Fabricadas
  - Diferencia
  - Cumplimiento (%)
  - Minutos de Paro
- ✅ Registro de paros con:
  - Línea de producción afectada
  - Hora inicio y fin
  - Descripción
  - Cálculo automático de minutos
  - Cálculo automático de costo (minutos × cost de la línea)

## 🚦 Lógica del Semáforo

El sistema calcula automáticamente el estado de cada línea de producción:

### 🟢 Verde (Normal)
- Avance real ≥ 100% del esperado
- Sin paros activos

### 🟡 Amarillo (Riesgo)
- Avance entre 95-99% del esperado
- Paros acumulados ≥ 20 minutos

### 🔴 Rojo (Crítico)
- Avance < 95% del esperado
- Paro activo
- Paros acumulados ≥ 30 minutos

**Fórmula:**
```
Avance esperado = (meta/hora) × horas transcurridas
Eficiencia = (total_produced / total_to_produce) × 100
```

## 🎨 Diseño Visual

El sistema utiliza **Tailwind CSS** con la paleta de colores del archivo de referencia:

### Colores Principales
- **Navy:** `#0b2a40` - Encabezados y botones principales
- **Navy2:** `#174060` - Elementos secundarios
- **Green:** `#0a7c3e` - Estados positivos
- **Amber:** `#a87000` - Estados de advertencia
- **Red:** `#ba2418` - Estados críticos
- **Soft:** `#f4f7fa` - Fondos suaves
- **Border:** `#d4dee8` - Bordes

## 📱 Uso del Sistema

### Para Administradores

1. **Importar Usuarios:**
   - Ir a "Administrador" → "Importar Usuario"
   - Seleccionar usuario de la lista de italianet_users
   - Asignar perfil
   - Si es Supervisor, seleccionar centros de trabajo
   - Guardar

2. **Asignar Centros de Trabajo:**
   - Ir a "Administrador" → "Asignar Centros"
   - Seleccionar supervisor
   - Marcar los centros que puede gestionar
   - Guardar

### Para Supervisores

1. **Acceder al Dashboard:**
   - Login con credenciales
   - Seleccionar centro de trabajo (si tiene múltiples)
   - Ver información del centro

2. **Registrar Producción Diaria:**
   - Clic en "Registro Diario de Producción"
   - Seleccionar fecha y turno
   - Llenar programa del turno (programado, atraso, adelantadas)
   - Guardar programa
   - Capturar producción por hora en cada línea
   - Guardar producción

3. **Registrar Paros:**
   - Clic en "Registrar Paro"
   - Seleccionar línea afectada
   - Ingresar hora inicio y fin
   - Describir el motivo
   - Guardar (el sistema calcula minutos y costo automáticamente)

## 🔧 Características Técnicas

### Backend
- **Framework:** Laravel 11
- **Base de datos:** MySQL con dos conexiones
- **Modelos Eloquent** con relaciones completas
- **Controladores:** AdminController, SupervisorController
- **Validaciones:** Request validation en todos los endpoints
- **Transacciones:** DB transactions para operaciones críticas

### Frontend
- **Tailwind CSS** para estilos
- **JavaScript Vanilla** para interactividad
- **AJAX** para actualizaciones sin recargar página
- **Cálculos en tiempo real** de totales y KPIs

### Seguridad
- **Gates de autorización** para Admin y Supervisor
- **CSRF Protection** en todos los formularios
- **Middleware de autenticación** en todas las rutas protegidas

## 📊 Tabla de Producción con Múltiples Líneas

La tabla horaria muestra:

```
| HORA      | LÍNEA 1 | LÍNEA 2 | LÍNEA 3 | ... | PPH   |
|-----------|---------|---------|---------|-----|-------|
| 08:00-09:00 |   50    |   30    |   20    | ... |  100  |
| 09:00-10:00 |   45    |   35    |   25    | ... |  105  |
| ...       |   ...   |   ...   |   ...   | ... |  ...  |
| TOTAL     |   450   |   300   |   250   | ... | 1000  |
```

- Cada celda es editable
- Los totales se calculan automáticamente
- PPH = Piezas Por Hora (suma de todas las líneas)

## 🐛 Solución de Problemas

### Error: "Connection refused" a italianet_users
**Solución:** Verifica que la base de datos `italianet_users` existe y las credenciales son correctas.

### Error: "Column not found: id_production_line"
**Solución:** Ejecuta las migraciones: `php artisan migrate`

### Los totales no se calculan
**Solución:** Verifica que JavaScript está habilitado y no hay errores en la consola del navegador.

### No aparecen usuarios para importar
**Solución:** Verifica que en `italianet_users.users` existan usuarios con `email` no nulo y `status = 1`.

## 📞 Soporte

Para dudas o problemas, contacta al equipo de desarrollo.

---

**Versión:** 1.0.0  
**Fecha:** Mayo 2026  
**Desarrollado con:** Laravel 11 + Tailwind CSS
