@extends('layouts.app')

@section('title', 'Dashboard Operador')

@section('content')
<div class="flex flex-col gap-2.5">
    {{-- Selector de Línea de Producción --}}
    <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
        <div class="px-4 py-3 flex items-center gap-4 flex-wrap">
            <span class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Línea de Producción:</span>
            
            <form method="GET" action="{{ route('operador.dashboard') }}" id="dashboard-form" class="flex items-center gap-3 flex-1">
                <select name="production_line_id" 
                        onchange="this.form.submit()"
                        class="border border-[#d4dee8] rounded-md px-3 py-2 text-sm font-bold text-[#0c1c28] bg-white focus:outline-none focus:border-[#174060]">
                    @foreach($productionLines as $line)
                        <option value="{{ $line->id }}" {{ $selectedLine->id == $line->id ? 'selected' : '' }}>
                            {{ $line->title }}
                        </option>
                    @endforeach
                </select>
                
                <input type="date" 
                       name="date" 
                       value="{{ $selectedDate }}"
                       onchange="this.form.submit()"
                       class="border border-[#d4dee8] rounded-md px-3 py-2 text-sm font-bold text-[#0c1c28]">
                
                <select name="shift" 
                        onchange="this.form.submit()"
                        class="border border-[#d4dee8] rounded-md px-3 py-2 text-sm font-bold text-[#0c1c28] bg-white">
                    <option value="matutino" {{ $selectedShift == 'matutino' ? 'selected' : '' }}>Matutino</option>
                    <option value="vespertino" {{ $selectedShift == 'vespertino' ? 'selected' : '' }}>Vespertino</option>
                    <option value="nocturno" {{ $selectedShift == 'nocturno' ? 'selected' : '' }}>Nocturno</option>
                </select>
            </form>
        </div>
    </div>
    
    {{-- Información de la Línea --}}
    <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-6">
        <h2 class="text-xl font-extrabold text-[#0b2a40] mb-2">{{ $selectedLine->title }}</h2>
        <p class="text-sm text-[#6a8090] mb-4">Centro de Trabajo: <strong>{{ $selectedLine->workCenter->name }}</strong></p>
        
        @if($kpis)
        {{-- KPIs Simplificados del Operador --}}
        <div class="mb-6 p-4 bg-[#f8f9fb] border border-[#d4dee8] rounded-lg">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-[#0b2a40]">Indicadores de la Línea</h3>
                <span class="text-xs font-semibold text-[#6a8090]">{{ ucfirst($selectedShift) }} - {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Fabricadas --}}
                <div class="p-4 bg-white border border-[#d4dee8] rounded-lg text-center">
                    <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-2">Fabricadas</div>
                    <div class="text-4xl font-extrabold text-[#0b2a40]">{{ number_format($kpis['fabricated']) }}</div>
                    <div class="text-xs text-[#6a8090] mt-1">piezas</div>
                </div>
                
                {{-- Minutos de Paro --}}
                <div class="p-4 bg-white border border-[#d4dee8] rounded-lg text-center">
                    <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-2">Min. Paro</div>
                    <div class="text-4xl font-extrabold {{ $kpis['strike_minutes'] > 30 ? 'text-[#ba2418]' : ($kpis['strike_minutes'] > 15 ? 'text-[#f59e0b]' : 'text-[#0b8a3d]') }}">
                        {{ number_format($kpis['strike_minutes']) }}
                    </div>
                    <div class="text-xs text-[#6a8090] mt-1">minutos</div>
                </div>
                
                {{-- Costo de Paro --}}
                <div class="p-4 bg-white border border-[#d4dee8] rounded-lg text-center">
                    <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-2">Costo de Paro</div>
                    <div class="text-4xl font-extrabold text-[#ba2418]">${{ number_format($kpis['strike_cost'], 0) }}</div>
                    <div class="text-xs text-[#6a8090] mt-1">pesos</div>
                </div>
            </div>
        </div>
        @else
        {{-- Mensaje cuando no hay programa --}}
        <div class="mb-6 p-6 bg-[#fff6da] border border-[#e8d488] rounded-lg text-center">
            <div class="text-4xl mb-3">📋</div>
            <h3 class="text-lg font-bold text-[#0b2a40] mb-2">No hay programa diario registrado</h3>
            <p class="text-sm text-[#6a8090]">
                No existe un programa para <strong>{{ $selectedLine->title }}</strong> en el turno <strong>{{ ucfirst($selectedShift) }}</strong> del <strong>{{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</strong>.
            </p>
            <p class="text-xs text-[#6a8090] mt-2">
                El supervisor debe crear el programa diario primero.
            </p>
        </div>
        @endif
        
        @if($dailyProgram && $schedules->isNotEmpty())
        {{-- Producción por Hora --}}
        <div class="mb-6">
            <h3 class="text-sm font-bold text-[#0b2a40] mb-3">📊 Producción por Hora</h3>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-[#0b2a40] text-white">
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase">Hora</th>
                            <th class="px-4 py-3 text-center text-xs font-bold uppercase">Producido</th>
                            <th class="px-4 py-3 text-center text-xs font-bold uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($schedules as $schedule)
                        <tr class="border-b border-[#d4dee8] hover:bg-[#f8f9fb]">
                            <td class="px-4 py-3 text-sm font-semibold text-[#0b2a40]">
                                {{ $schedule->start_time }} - {{ $schedule->end_time }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <input type="number" 
                                       min="0" 
                                       value="{{ $schedule->produced }}"
                                       data-schedule-id="{{ $schedule->id }}"
                                       class="schedule-input w-24 px-3 py-2 border border-[#d4dee8] rounded-md text-center font-bold text-[#0b2a40] focus:outline-none focus:border-[#174060]"
                                       onchange="updateProduction(this)">
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="save-indicator-{{ $schedule->id }} text-xs text-[#0b8a3d] hidden">✓ Guardado</span>
                            </td>
                        </tr>
                        @endforeach
                        <tr class="bg-[#f4f7fa] font-bold">
                            <td class="px-4 py-3 text-sm text-[#0b2a40]">TOTAL</td>
                            <td class="px-4 py-3 text-center text-lg font-extrabold text-[#0b2a40]" id="total-produced">
                                {{ number_format($schedules->sum('produced')) }}
                            </td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- Registro de Paros --}}
        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-[#0b2a40]">⚠️ Registro de Paros</h3>
                <button onclick="openStrikeModal()" class="px-4 py-2 bg-[#ba2418] text-white rounded-md text-xs font-bold hover:opacity-85 transition">
                    + Registrar Paro
                </button>
            </div>
            
            @if($strikes->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-[#0b2a40] text-white">
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase">Inicio</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase">Fin</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase">Descripción</th>
                            <th class="px-4 py-3 text-center text-xs font-bold uppercase">Duración</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($strikes as $strike)
                        <tr class="border-b border-[#d4dee8]">
                            <td class="px-4 py-3 text-sm text-[#0b2a40]">{{ \Carbon\Carbon::parse($strike->start_time)->format('H:i') }}</td>
                            <td class="px-4 py-3 text-sm text-[#0b2a40]">
                                @if($strike->end_time)
                                    {{ \Carbon\Carbon::parse($strike->end_time)->format('H:i') }}
                                @else
                                    <span class="text-[#ba2418] font-bold">En curso...</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-[#6a8090]">{{ $strike->description }}</td>
                            <td class="px-4 py-3 text-center text-sm font-bold text-[#ba2418]">
                                @if($strike->end_time)
                                    {{ \Carbon\Carbon::parse($strike->start_time)->diffInMinutes(\Carbon\Carbon::parse($strike->end_time)) }} min
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="p-6 bg-[#f0fdf4] border border-[#86efac] rounded-lg text-center">
                <div class="text-3xl mb-2">✅</div>
                <p class="text-sm text-[#0b8a3d] font-semibold">No hay paros registrados</p>
            </div>
            @endif
        </div>
        @endif
    </div>
</div>

{{-- Modal para Registrar Paro --}}
<div id="strikeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
        <h3 class="text-lg font-bold text-[#0b2a40] mb-4">Registrar Paro</h3>
        <form id="strikeForm" onsubmit="submitStrike(event)">
            <div class="mb-4">
                <label class="block text-sm font-bold text-[#0b2a40] mb-2">Hora de Inicio</label>
                <input type="time" id="strike_start_time" required class="w-full px-3 py-2 border border-[#d4dee8] rounded-md">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold text-[#0b2a40] mb-2">Hora de Fin (opcional)</label>
                <input type="time" id="strike_end_time" class="w-full px-3 py-2 border border-[#d4dee8] rounded-md">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold text-[#0b2a40] mb-2">Descripción</label>
                <textarea id="strike_description" required rows="3" class="w-full px-3 py-2 border border-[#d4dee8] rounded-md"></textarea>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-[#ba2418] text-white rounded-md font-bold hover:opacity-85">
                    Guardar
                </button>
                <button type="button" onclick="closeStrikeModal()" class="flex-1 px-4 py-2 bg-[#6a8090] text-white rounded-md font-bold hover:opacity-85">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function updateProduction(input) {
    const scheduleId = input.dataset.scheduleId;
    const produced = input.value;
    
    fetch('{{ route("operador.schedule.update") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            schedule_id: scheduleId,
            produced: produced
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar indicador de guardado
            const indicator = document.querySelector(`.save-indicator-${scheduleId}`);
            indicator.classList.remove('hidden');
            setTimeout(() => indicator.classList.add('hidden'), 2000);
            
            // Actualizar total
            document.getElementById('total-produced').textContent = data.total_produced.toLocaleString();
            
            // Actualizar KPIs si están disponibles
            if (data.kpis) {
                updateKPIs(data.kpis);
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

function updateKPIs(kpis) {
    // Actualizar los KPIs en la interfaz si es necesario
    location.reload();
}

function openStrikeModal() {
    document.getElementById('strikeModal').classList.remove('hidden');
    document.getElementById('strike_start_time').value = new Date().toTimeString().slice(0,5);
}

function closeStrikeModal() {
    document.getElementById('strikeModal').classList.add('hidden');
    document.getElementById('strikeForm').reset();
}

function submitStrike(event) {
    event.preventDefault();
    
    const formData = {
        id_production_line: {{ $selectedLine->id }},
        id_daily_program: {{ $dailyProgram->id ?? 'null' }},
        date: '{{ $selectedDate }}',
        start_time: document.getElementById('strike_start_time').value,
        end_time: document.getElementById('strike_end_time').value || null,
        description: document.getElementById('strike_description').value
    };
    
    fetch('{{ route("operador.strikes.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeStrikeModal();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al registrar el paro');
    });
}
</script>
@endsection
