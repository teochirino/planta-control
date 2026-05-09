<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Gerencia - {{ $selectedWorkCenter->name }}</title>
    
    <!-- Favicons -->
    <link rel="icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/favicon.ico">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #0a1929;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .card {
            background-color: #1a2332;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }
        .kpi-badge {
            background-color: #2d3748;
            border-radius: 8px;
            padding: 0.75rem 1.25rem;
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            min-width: 120px;
        }
        .status-green {
            background-color: #10b981;
            color: white;
        }
        .status-yellow {
            background-color: #f59e0b;
            color: white;
        }
        .status-red {
            background-color: #ef4444;
            color: white;
        }
        .status-gray {
            background-color: #6b7280;
            color: white;
        }
        .compliance-row {
            background-color: #0f1621;
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .compliance-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
        }
        .badge-success {
            background-color: #10b981;
            color: white;
        }
        .badge-warning {
            background-color: #f59e0b;
            color: white;
        }
        .badge-danger {
            background-color: #ef4444;
            color: white;
        }
        .digital-clock {
            font-family: 'Courier New', monospace;
            font-size: 3.5rem;
            font-weight: bold;
            color: white;
            text-align: center;
            letter-spacing: 0.1em;
        }
        .select-custom {
            background-color: #2d3748;
            color: white;
            border: 1px solid #4a5568;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        .select-custom:focus {
            outline: none;
            border-color: #10b981;
        }
    </style>
</head>
<body class="text-gray-100">
    <div class="container mx-auto px-4 py-6 max-w-7xl">
        
        <!-- Header con selector de centro de trabajo -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-white">{{ strtoupper($selectedWorkCenter->name) }}</h1>
                <p class="text-gray-400 text-sm mt-1">Dashboard de Gerencia - Monitoreo en tiempo real</p>
            </div>
            <div class="flex items-center gap-4">
                <form method="GET" action="{{ route('gerencia.dashboard') }}" id="filterForm" class="flex items-center gap-3">
                    <select name="work_center_id" class="select-custom" onchange="this.form.submit()">
                        @foreach($workCenters as $wc)
                            <option value="{{ $wc->id }}" {{ $wc->id == $selectedWorkCenter->id ? 'selected' : '' }}>
                                {{ $wc->name }}
                            </option>
                        @endforeach
                    </select>
                    <select name="shift" class="select-custom" onchange="this.form.submit()">
                        <option value="">Todos los turnos</option>
                        <option value="matutino" {{ $selectedShift == 'matutino' ? 'selected' : '' }}>Matutino</option>
                        <option value="vespertino" {{ $selectedShift == 'vespertino' ? 'selected' : '' }}>Vespertino</option>
                        <option value="nocturno" {{ $selectedShift == 'nocturno' ? 'selected' : '' }}>Nocturno</option>
                    </select>
                </form>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-medium transition">
                        Cerrar Sesión
                    </button>
                </form>
            </div>
        </div>

        <!-- KPIs Header -->
        <div class="flex gap-4 mb-6 overflow-x-auto pb-2">
            <div class="kpi-badge">
                <span class="text-gray-400 text-xs uppercase">Capacidad Instalada</span>
                <span class="text-3xl font-bold text-white mt-1">{{ $kpis['capacidad_instalada'] ?? 0 }}</span>
            </div>
            <div class="kpi-badge">
                <span class="text-gray-400 text-xs uppercase">Programado</span>
                <span class="text-3xl font-bold text-white mt-1">{{ $kpis['programado'] ?? 0 }}</span>
            </div>
            <div class="kpi-badge">
                <span class="text-gray-400 text-xs uppercase">Atrasado</span>
                <span class="text-3xl font-bold text-white mt-1">{{ $kpis['atrasado'] ?? 0 }}</span>
            </div>
            <div class="kpi-badge">
                <span class="text-gray-400 text-xs uppercase">A Producir</span>
                <span class="text-3xl font-bold text-white mt-1">{{ $kpis['a_producir'] ?? 0 }}</span>
            </div>
            <div class="kpi-badge">
                <span class="text-gray-400 text-xs uppercase">Piezas Producidas</span>
                <span class="text-3xl font-bold text-white mt-1">{{ $kpis['piezas_producidas'] ?? 0 }}</span>
            </div>
            <div class="kpi-badge">
                <span class="text-gray-400 text-xs uppercase">Horas Extras</span>
                <span class="text-3xl font-bold text-white mt-1">{{ $kpis['horas_extras_status'] ?? 'NO' }}</span>
            </div>
            <div class="kpi-badge">
                <span class="text-gray-400 text-xs uppercase">Horas Extras</span>
                <span class="text-3xl font-bold text-white mt-1">{{ $kpis['horas_extras'] ?? 0.0 }}</span>
            </div>
        </div>

        <!-- Grid Principal -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Columna Izquierda -->
            <div class="space-y-6">
                
                <!-- Estado general del área -->
                <div class="card">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase mb-3">Estado general del área</h3>
                    <div class="text-center py-4">
                        <div class="inline-flex items-center justify-center w-24 h-24 rounded-full mb-3
                            {{ $areaStatus['color'] == 'green' ? 'bg-green-500' : '' }}
                            {{ $areaStatus['color'] == 'yellow' ? 'bg-yellow-500' : '' }}
                            {{ $areaStatus['color'] == 'red' ? 'bg-red-500' : '' }}
                            {{ $areaStatus['color'] == 'gray' ? 'bg-gray-500' : '' }}">
                            <span class="text-3xl font-bold text-white">{{ $areaStatus['label'] }}</span>
                        </div>
                        <p class="text-white font-semibold text-lg">Operación normal y estable</p>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-700">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-gray-400 text-sm">Acceso</span>
                            <span class="text-white font-semibold">{{ now()->format('H:i:s') }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400 text-sm">Tiempo en estado</span>
                            <span class="text-white font-semibold">{{ $areaStatus['time'] }}</span>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-700">
                        <p class="text-gray-400 text-xs uppercase mb-2">Información</p>
                        <p class="text-gray-300 text-sm">{{ $areaStatus['message'] ?? 'La operación se mantiene dentro de los parámetros esperados, sin incidencias que afecten el rendimiento del área.' }}</p>
                    </div>
                </div>

                <!-- Calidad y trabajos -->
                <div class="card">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase mb-4">Calidad y trabajos</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center p-3 bg-gray-800 rounded-lg">
                            <p class="text-gray-400 text-xs uppercase mb-1">Piezas Rechazadas</p>
                            <p class="text-3xl font-bold text-white">{{ $qualityMetrics['piezas_rechazadas'] ?? 0 }}</p>
                        </div>
                        <div class="text-center p-3 bg-gray-800 rounded-lg">
                            <p class="text-gray-400 text-xs uppercase mb-1">Rechazos Garantía</p>
                            <p class="text-3xl font-bold text-white">{{ $qualityMetrics['rechazos_garantia'] ?? 0 }}</p>
                        </div>
                        <div class="text-center p-3 bg-gray-800 rounded-lg">
                            <p class="text-gray-400 text-xs uppercase mb-1">Inspecciones</p>
                            <p class="text-3xl font-bold text-white">{{ $qualityMetrics['inspecciones_realizadas'] ?? 0 }}</p>
                        </div>
                        <div class="text-center p-3 bg-gray-800 rounded-lg">
                            <p class="text-gray-400 text-xs uppercase mb-1">Reprocesos Calidad</p>
                            <p class="text-3xl font-bold text-white">{{ $qualityMetrics['reprocesos_calidad'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Columna Central -->
            <div class="space-y-6">
                
                <!-- Avance del turno -->
                <div class="card">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase mb-3">Avance del turno</h3>
                    <p class="text-gray-400 text-xs mb-2">Hora actual, {{ now()->format('d') }} de {{ now()->translatedFormat('F') }} de {{ now()->year }}</p>
                    <div class="digital-clock" id="currentTime">{{ now()->format('H:i:s') }}</div>
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div class="text-center">
                            <p class="text-gray-400 text-xs uppercase">Turno</p>
                            <p class="text-white font-semibold text-lg">{{ ucfirst($selectedShift) }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-gray-400 text-xs uppercase">Producción</p>
                            <p class="text-white font-semibold text-lg">{{ $dailyProgram->total_produced ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <!-- Paros y costo -->
                <div class="card">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase mb-4">Paros y costo</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center p-4 bg-gray-800 rounded-lg">
                            <p class="text-gray-400 text-xs uppercase mb-2">Cantidad de paros</p>
                            <p class="text-5xl font-bold text-white">{{ $metrics['total_strikes'] ?? 0 }}</p>
                        </div>
                        <div class="text-center p-4 bg-gray-800 rounded-lg">
                            <p class="text-gray-400 text-xs uppercase mb-2">Costo de paros</p>
                            <p class="text-5xl font-bold text-red-400">${{ number_format($metrics['strike_cost'] ?? 0, 0) }}</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Columna Derecha -->
            <div class="space-y-6">
                
                <!-- Cámara en vivo -->
                <div class="card">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase mb-3">Cámara en vivo</h3>
                    <div class="bg-gray-800 rounded-lg h-40 flex items-center justify-center">
                        <div class="text-center">
                            <svg class="w-16 h-16 mx-auto text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            <p class="text-gray-500 text-sm">EN VIVO</p>
                        </div>
                    </div>
                    <div class="mt-3 flex justify-between text-xs">
                        <button class="px-3 py-1 bg-green-600 rounded text-white font-semibold">Activar webcam</button>
                        <div class="flex gap-2">
                            <button class="px-3 py-1 bg-gray-700 rounded text-white">Detalle</button>
                            <button class="px-3 py-1 bg-gray-700 rounded text-white">Cámara / IP</button>
                        </div>
                    </div>
                </div>

                <!-- Vista previa de cámara -->
                <div class="card">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase mb-3">Vista previa de cámara</h3>
                    <div class="bg-gray-800 rounded-lg h-32 flex items-center justify-center">
                        <p class="text-gray-500 text-sm">Sin vista previa disponible</p>
                    </div>
                </div>

                <!-- Cumplimiento reciente -->
                <div class="card">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase mb-4">Cumplimiento reciente</h3>
                    <div class="space-y-2">
                        @forelse($recentCompliance as $item)
                        <div class="compliance-row">
                            <div class="flex-1">
                                <p class="text-white font-semibold text-sm">{{ $item['date'] }}</p>
                                <div class="flex gap-4 text-xs text-gray-400 mt-1">
                                    <span>Prog. 1: {{ $item['prog_1'] }}</span>
                                    <span>Real 1: {{ $item['real_1'] }}</span>
                                    <span>Prog. 2: {{ $item['prog_2'] }}</span>
                                    <span>Real 2: {{ $item['real_2'] }}</span>
                                    <span>Prog. 3: {{ $item['prog_3'] }}</span>
                                    <span>Real 3: {{ $item['real_3'] }}</span>
                                </div>
                            </div>
                            <span class="compliance-badge badge-{{ $item['status'] }}">{{ $item['compliance'] }}%</span>
                        </div>
                        @empty
                        <p class="text-gray-500 text-sm text-center py-4">No hay datos de cumplimiento reciente</p>
                        @endforelse
                    </div>
                </div>

            </div>

        </div>

        <!-- Información importante -->
        <div class="card mt-6">
            <h3 class="text-sm font-semibold text-gray-400 uppercase mb-3">Información importante</h3>
            <textarea class="w-full bg-gray-800 text-gray-300 rounded-lg p-3 border border-gray-700 focus:border-green-500 focus:outline-none" rows="3" placeholder="Observaciones operacionales del turno, incidencias relevantes, material pendiente de recibir, etc."></textarea>
        </div>

    </div>

    <script>
        // Actualizar reloj cada segundo
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('currentTime').textContent = `${hours}:${minutes}:${seconds}`;
        }
        
        setInterval(updateClock, 1000);
        updateClock();

        // Auto-refresh cada 30 segundos para actualizar datos
        setTimeout(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
