@extends('layouts.app')

@section('title', 'Registro Diario de Producción')

@section('content')
<div class="flex flex-col gap-2.5" id="app-production">
    {{-- Top Bar --}}
    <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
        <div class="px-4 py-3 flex items-center justify-between gap-4 flex-wrap">
            <div>
                <span class="text-[10px] font-bold tracking-widest uppercase text-[#174060]">Registro Diario de Producción</span>
                <h1 class="text-2xl font-extrabold text-[#0b2a40] leading-none">{{ $workCenter->name }}</h1>
                <div class="text-sm text-[#4e6070] font-semibold mt-1">
                    {{ \Carbon\Carbon::parse($date)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                </div>
            </div>
            
            <div class="flex items-center gap-2 flex-wrap">
                <input type="date" 
                       id="date-selector" 
                       value="{{ $date }}"
                       class="px-3 py-2 border border-[#d4dee8] rounded-md text-xs font-bold text-[#0c1c28]">
                
                <select id="shift-selector" class="px-3 py-2 border border-[#d4dee8] rounded-md text-xs font-bold text-[#0c1c28]">
                    <option value="matutino" {{ $shift == 'matutino' ? 'selected' : '' }}>Matutino</option>
                    <option value="vespertino" {{ $shift == 'vespertino' ? 'selected' : '' }}>Vespertino</option>
                    <option value="nocturno" {{ $shift == 'nocturno' ? 'selected' : '' }}>Nocturno</option>
                </select>
                
                <div class="px-3 py-2 rounded-full bg-[#0b2a40] text-white text-xs font-bold" id="current-time">
                    --:--
                </div>
            </div>
        </div>
    </div>
    
    {{-- KPIs --}}
    <div class="grid grid-cols-2 md:grid-cols-5 lg:grid-cols-10 gap-2">
        <div class="p-3 bg-white border border-[#d4dee8] rounded-lg">
            <div class="text-[11px] font-bold tracking-widest uppercase text-[#4e6070]">Programado</div>
            <div class="text-2xl font-extrabold text-[#0b2a40]" id="kpi-programmed">0</div>
        </div>
        
        <div class="p-3 bg-white border border-[#d4dee8] rounded-lg">
            <div class="text-[11px] font-bold tracking-widest uppercase text-[#4e6070]">Atraso</div>
            <div class="text-2xl font-extrabold text-[#0b2a40]" id="kpi-backwardness">0</div>
        </div>
        
        <div class="p-3 bg-white border border-[#d4dee8] rounded-lg">
            <div class="text-[11px] font-bold tracking-widest uppercase text-[#4e6070]">Adelantadas</div>
            <div class="text-2xl font-extrabold text-[#0b2a40]" id="kpi-advanced">0</div>
        </div>
        
        <div class="p-3 bg-white border border-[#d4dee8] rounded-lg">
            <div class="text-[11px] font-bold tracking-widest uppercase text-[#4e6070]">Total a Producir</div>
            <div class="text-2xl font-extrabold text-[#0b2a40]" id="kpi-total-to-produce">0</div>
        </div>
        
        <div class="p-3 bg-white border border-[#d4dee8] rounded-lg" id="kpi-produced-box">
            <div class="text-[11px] font-bold tracking-widest uppercase text-[#4e6070]">Fabricadas</div>
            <div class="text-2xl font-extrabold text-[#0b2a40]" id="kpi-produced">0</div>
        </div>
        
        <div class="p-3 bg-white border border-[#d4dee8] rounded-lg" id="kpi-diff-box">
            <div class="text-[11px] font-bold tracking-widest uppercase text-[#4e6070]">Diferencia</div>
            <div class="text-2xl font-extrabold text-[#0b2a40]" id="kpi-difference">0</div>
        </div>
        
        <div class="p-3 bg-white border border-[#d4dee8] rounded-lg" id="kpi-efficiency-box">
            <div class="text-[11px] font-bold tracking-widest uppercase text-[#4e6070]">Cumplimiento</div>
            <div class="text-2xl font-extrabold text-[#0b2a40]" id="kpi-efficiency">0%</div>
        </div>
        
        <div class="p-3 bg-white border border-[#d4dee8] rounded-lg">
            <div class="text-[11px] font-bold tracking-widest uppercase text-[#4e6070]">Min. Paro</div>
            <div class="text-2xl font-extrabold text-[#0b2a40]" id="kpi-strike-minutes">0</div>
        </div>
        
        <div class="p-3 bg-white border border-[#d4dee8] rounded-lg">
            <div class="text-[11px] font-bold tracking-widest uppercase text-[#4e6070]">Horas Activas</div>
            <div class="text-2xl font-extrabold text-[#0b2a40]" id="kpi-hours">9</div>
        </div>
        
        <div class="p-3 bg-white border border-[#d4dee8] rounded-lg">
            <div class="text-[11px] font-bold tracking-widest uppercase text-[#4e6070]">Cap. Instalada</div>
            <div class="text-2xl font-extrabold text-[#0b2a40]">{{ number_format($workCenter->installed_capacity) }}</div>
        </div>
    </div>
    
    {{-- Formulario de Programa Inicial --}}
    <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-4">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-extrabold text-[#0b2a40]">Encabezado de Turno</h2>
            <button onclick="saveProgram()" class="px-4 py-2 bg-[#0b2a40] text-white rounded-md text-xs font-bold hover:opacity-85">
                💾 Guardar Programa
            </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="text-[10px] font-bold tracking-widest uppercase text-[#4e6070]">Fecha</label>
                <input type="date" 
                       id="program-date" 
                       value="{{ $date }}"
                       class="w-full px-3 py-2 border border-[#d4dee8] rounded text-sm font-semibold"
                       readonly>
            </div>
            
            <div>
                <label class="text-[10px] font-bold tracking-widest uppercase text-[#4e6070]">Turno</label>
                <input type="text" 
                       value="{{ ucfirst($shift) }}"
                       class="w-full px-3 py-2 border border-[#d4dee8] rounded text-sm font-semibold bg-[#f4f7fa]"
                       readonly>
            </div>
            
            <div>
                <label class="text-[10px] font-bold tracking-widest uppercase text-[#4e6070]">Programado</label>
                <input type="number" 
                       id="program-programmed" 
                       value="{{ $dailyProgram->programmed ?? 0 }}"
                       class="w-full px-3 py-2 border border-[#d4dee8] rounded text-sm font-semibold"
                       min="0">
            </div>
            
            <div>
                <label class="text-[10px] font-bold tracking-widest uppercase text-[#4e6070]">Atraso</label>
                <input type="number" 
                       id="program-backwardness" 
                       value="{{ $dailyProgram->backwardness ?? 0 }}"
                       class="w-full px-3 py-2 border border-[#d4dee8] rounded text-sm font-semibold"
                       min="0">
            </div>
            
            <div>
                <label class="text-[10px] font-bold tracking-widest uppercase text-[#4e6070]">Adelantadas</label>
                <input type="number" 
                       id="program-advanced" 
                       value="{{ $dailyProgram->advanced ?? 0 }}"
                       class="w-full px-3 py-2 border border-[#d4dee8] rounded text-sm font-semibold"
                       min="0">
            </div>
        </div>
    </div>
    
    {{-- Tabla de Producción Horaria --}}
    <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-[#d4dee8]">
            <h2 class="text-base font-extrabold text-[#0b2a40]">Producción por Hora</h2>
            <p class="text-xs text-[#6a8090] mt-1">Los cambios se guardan automáticamente</p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-[#0b2a40] text-white">
                        <th class="px-3 py-3 text-left text-[11px] font-bold tracking-widest uppercase whitespace-nowrap">Hora</th>
                        @foreach($productionLines as $line)
                            <th class="px-3 py-3 text-center text-[11px] font-bold tracking-widest uppercase whitespace-nowrap">
                                {{ $line->title }}
                            </th>
                        @endforeach
                        <th class="px-3 py-3 text-center text-[11px] font-bold tracking-widest uppercase bg-[#174060] whitespace-nowrap">PPH</th>
                    </tr>
                </thead>
                <tbody id="production-table-body">
                    @foreach($hours as $hour)
                        <tr class="border-b border-[#e8eff4] hover:bg-[#eef5fa]">
                            <td class="px-3 py-2 font-bold text-sm text-[#0c1c28] whitespace-nowrap">
                                {{ $hour['start'] }} - {{ $hour['end'] }}
                            </td>
                            @foreach($productionLines as $line)
                                @php
                                    $scheduleKey = $hour['start'] . '-' . $line->id;
                                    $scheduleCollection = $schedules->get($scheduleKey);
                                    $schedule = $scheduleCollection ? $scheduleCollection->first() : null;
                                    $produced = $schedule ? $schedule->produced : 0;
                                @endphp
                                <td class="px-2 py-2">
                                    <input type="number" 
                                           data-schedule-id="{{ $schedule ? $schedule->id : '' }}"
                                           data-line-id="{{ $line->id }}"
                                           data-hour="{{ $hour['start'] }}"
                                           value="{{ $produced }}"
                                           class="production-input w-full px-2 py-1 border border-[#d4dee8] rounded text-center text-sm font-semibold focus:border-[#174060] focus:outline-none"
                                           min="0">
                                </td>
                            @endforeach
                            <td class="px-3 py-2 text-center font-extrabold text-[#0b2a40] bg-[#f4f7fa]" data-hour-total="{{ $hour['start'] }}">
                                0
                            </td>
                        </tr>
                    @endforeach
                    
                    {{-- Fila de Totales --}}
                    <tr class="bg-[#0c1c28] text-white font-extrabold">
                        <td class="px-3 py-3 text-sm tracking-widest uppercase">Total</td>
                        @foreach($productionLines as $line)
                            <td class="px-3 py-3 text-center text-lg" data-line-total="{{ $line->id }}">0</td>
                        @endforeach
                        <td class="px-3 py-3 text-center text-xl bg-[#174060]" id="grand-total">0</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    {{-- Registro de Paros --}}
    <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-4">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-extrabold text-[#0b2a40]">Registro de Paros</h2>
            <button onclick="openStrikeModal()" class="px-4 py-2 bg-[#ba2418] text-white rounded-md text-xs font-bold hover:opacity-85">
                ⏸ Registrar Paro
            </button>
        </div>
        
        <div id="strikes-list" class="space-y-2">
            {{-- Se llenará dinámicamente con JavaScript --}}
        </div>
    </div>
</div>

{{-- Modal para Registrar Paro --}}
<div id="strike-modal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-4 border-b border-[#d4dee8]">
            <h3 class="text-lg font-extrabold text-[#0b2a40]">Registrar Paro</h3>
        </div>
        
        <div class="p-6 space-y-4">
            <div>
                <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Línea de Producción</label>
                <select id="strike-line" class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1">
                    @foreach($productionLines as $line)
                        <option value="{{ $line->id }}">{{ $line->title }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Hora Inicio</label>
                <input type="time" id="strike-start" class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1">
            </div>
            
            <div>
                <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Hora Fin (opcional)</label>
                <input type="time" id="strike-end" class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1">
            </div>
            
            <div>
                <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Descripción</label>
                <textarea id="strike-description" rows="3" class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1"></textarea>
            </div>
        </div>
        
        <div class="px-6 py-4 border-t border-[#d4dee8] flex gap-2 justify-end">
            <button onclick="closeStrikeModal()" class="px-4 py-2 bg-[#f4f7fa] text-[#4e6070] border border-[#d4dee8] rounded-md text-xs font-bold hover:bg-[#e8edf2]">
                Cancelar
            </button>
            <button onclick="saveStrike()" class="px-4 py-2 bg-[#ba2418] text-white rounded-md text-xs font-bold hover:opacity-85">
                Guardar Paro
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const workCenterId = {{ $workCenter->id }};
const productionLines = @json($productionLines);
const dailyProgram = @json($dailyProgram);
let currentDate = '{{ $date }}';
let currentShift = '{{ $shift }}';
let autoSaveTimeout = null;

// Actualizar reloj
function updateClock() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    document.getElementById('current-time').textContent = `${hours}:${minutes}`;
}
setInterval(updateClock, 1000);
updateClock();

// Calcular totales en tiempo real y auto-guardar
document.querySelectorAll('.production-input').forEach(input => {
    input.addEventListener('input', function() {
        calculateTotals();
        autoSaveProduction(this);
    });
});

function calculateTotals() {
    // Calcular totales por hora (PPH)
    document.querySelectorAll('[data-hour-total]').forEach(cell => {
        const hour = cell.dataset.hourTotal;
        const inputs = document.querySelectorAll(`input[data-hour="${hour}"]`);
        let total = 0;
        inputs.forEach(inp => total += parseInt(inp.value) || 0);
        cell.textContent = total;
    });
    
    // Calcular totales por línea
    productionLines.forEach(line => {
        const inputs = document.querySelectorAll(`input[data-line-id="${line.id}"]`);
        let total = 0;
        inputs.forEach(inp => total += parseInt(inp.value) || 0);
        const cell = document.querySelector(`[data-line-total="${line.id}"]`);
        if (cell) cell.textContent = total;
    });
    
    // Calcular gran total
    let grandTotal = 0;
    document.querySelectorAll('[data-line-total]').forEach(cell => {
        grandTotal += parseInt(cell.textContent) || 0;
    });
    document.getElementById('grand-total').textContent = grandTotal;
    
    // Actualizar KPIs
    updateKPIs();
}

function updateKPIs() {
    const totalProgrammed = parseInt(document.getElementById('program-programmed').value) || 0;
    const totalBackwardness = parseInt(document.getElementById('program-backwardness').value) || 0;
    const totalAdvanced = parseInt(document.getElementById('program-advanced').value) || 0;
    
    const totalToProduce = Math.max(totalProgrammed + totalBackwardness - totalAdvanced, 0);
    const totalProduced = parseInt(document.getElementById('grand-total').textContent) || 0;
    const difference = totalProduced - totalToProduce;
    const efficiency = totalToProduce > 0 ? ((totalProduced / totalToProduce) * 100).toFixed(1) : 0;
    
    document.getElementById('kpi-programmed').textContent = totalProgrammed;
    document.getElementById('kpi-backwardness').textContent = totalBackwardness;
    document.getElementById('kpi-advanced').textContent = totalAdvanced;
    document.getElementById('kpi-total-to-produce').textContent = totalToProduce;
    document.getElementById('kpi-produced').textContent = totalProduced;
    document.getElementById('kpi-difference').textContent = difference >= 0 ? `+${difference}` : difference;
    document.getElementById('kpi-efficiency').textContent = `${efficiency}%`;
    
    // Colorear KPIs según semáforo
    const effBox = document.getElementById('kpi-efficiency-box');
    const diffBox = document.getElementById('kpi-diff-box');
    const prodBox = document.getElementById('kpi-produced-box');
    
    effBox.className = 'p-3 border rounded-lg';
    diffBox.className = 'p-3 border rounded-lg';
    prodBox.className = 'p-3 border rounded-lg';
    
    if (efficiency >= 100) {
        effBox.classList.add('bg-[#e4f5ec]', 'border-[#aadcc4]');
        prodBox.classList.add('bg-[#e4f5ec]', 'border-[#aadcc4]');
    } else if (efficiency >= 95) {
        effBox.classList.add('bg-[#fff6da]', 'border-[#e8d488]');
        prodBox.classList.add('bg-[#fff6da]', 'border-[#e8d488]');
    } else {
        effBox.classList.add('bg-[#fce9e8]', 'border-[#ebbab8]');
        prodBox.classList.add('bg-[#fce9e8]', 'border-[#ebbab8]');
    }
    
    if (difference >= 0) {
        diffBox.classList.add('bg-[#e4f5ec]', 'border-[#aadcc4]');
    } else {
        diffBox.classList.add('bg-[#fce9e8]', 'border-[#ebbab8]');
    }
}

// Guardar programa del centro
async function saveProgram() {
    const programmed = parseInt(document.getElementById('program-programmed').value) || 0;
    const backwardness = parseInt(document.getElementById('program-backwardness').value) || 0;
    const advanced = parseInt(document.getElementById('program-advanced').value) || 0;
    
    try {
        const response = await fetch('{{ route("supervisor.daily-program.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                date: currentDate,
                id_work_center: workCenterId,
                shift: currentShift,
                programmed: programmed,
                backwardness: backwardness,
                advanced: advanced,
                shift_hours: 9
            })
        });
        
        const data = await response.json();
        if (data.success) {
            alert('✅ Programa guardado correctamente');
            location.reload();
        } else {
            alert('❌ Error: ' + data.message);
        }
    } catch (error) {
        alert('❌ Error al guardar: ' + error.message);
    }
}

// Auto-guardar producción individual
async function autoSaveProduction(input) {
    if (!input.dataset.scheduleId) return;
    
    // Debounce: esperar 1 segundo después del último cambio
    clearTimeout(autoSaveTimeout);
    
    autoSaveTimeout = setTimeout(async () => {
        const scheduleId = input.dataset.scheduleId;
        const produced = parseInt(input.value) || 0;
        
        try {
            const response = await fetch('{{ route("supervisor.production.auto-save") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    schedule_id: scheduleId,
                    produced: produced
                })
            });
            
            const data = await response.json();
            if (data.success) {
                // Actualizar KPIs con los datos del servidor
                if (data.kpis) {
                    document.getElementById('kpi-programmed').textContent = data.kpis.programmed;
                    document.getElementById('kpi-backwardness').textContent = data.kpis.backwardness;
                    document.getElementById('kpi-advanced').textContent = data.kpis.advanced;
                    document.getElementById('kpi-total-to-produce').textContent = data.kpis.total_to_produce;
                    document.getElementById('kpi-produced').textContent = data.kpis.fabricated;
                    document.getElementById('kpi-difference').textContent = data.kpis.difference >= 0 ? `+${data.kpis.difference}` : data.kpis.difference;
                    document.getElementById('kpi-efficiency').textContent = `${data.kpis.compliance}%`;
                }
                
                // Mostrar indicador visual de guardado
                input.classList.add('border-green-500');
                setTimeout(() => input.classList.remove('border-green-500'), 1000);
            }
        } catch (error) {
            console.error('Error al auto-guardar:', error);
            input.classList.add('border-red-500');
            setTimeout(() => input.classList.remove('border-red-500'), 2000);
        }
    }, 1000);
}

// Guardar producción
async function saveProduction() {
    const schedules = [];
    
    document.querySelectorAll('.production-input').forEach(input => {
        if (input.dataset.scheduleId) {
            schedules.push({
                id: input.dataset.scheduleId,
                produced: parseInt(input.value) || 0
            });
        }
    });
    
    try {
        const response = await fetch('{{ route("supervisor.production.save") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ schedules })
        });
        
        const data = await response.json();
        if (data.success) {
            alert('✅ Producción guardada correctamente');
        } else {
            alert('❌ Error: ' + data.message);
        }
    } catch (error) {
        alert('❌ Error al guardar: ' + error.message);
    }
}

// Modal de paros
function openStrikeModal() {
    document.getElementById('strike-modal').classList.remove('hidden');
    document.getElementById('strike-start').value = new Date().toTimeString().slice(0, 5);
}

function closeStrikeModal() {
    document.getElementById('strike-modal').classList.add('hidden');
}

async function saveStrike() {
    const lineId = document.getElementById('strike-line').value;
    const startTime = document.getElementById('strike-start').value;
    const endTime = document.getElementById('strike-end').value;
    const description = document.getElementById('strike-description').value;
    
    if (!description) {
        alert('Por favor ingrese una descripción del paro');
        return;
    }
    
    const dailyProgramId = dailyPrograms[lineId]?.id;
    
    try {
        const response = await fetch('{{ route("supervisor.strikes.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                id_production_line: lineId,
                id_daily_program: dailyProgramId,
                date: currentDate,
                start_time: startTime,
                end_time: endTime || null,
                description: description
            })
        });
        
        const data = await response.json();
        if (data.success) {
            alert('✅ Paro registrado correctamente');
            closeStrikeModal();
            loadStrikes();
        } else {
            alert('❌ Error: ' + data.message);
        }
    } catch (error) {
        alert('❌ Error al registrar paro: ' + error.message);
    }
}

// Cambio de fecha/turno
document.getElementById('date-selector').addEventListener('change', function() {
    const newDate = this.value;
    const shift = document.getElementById('shift-selector').value;
    window.location.href = `{{ route('supervisor.daily-production') }}?work_center_id=${workCenterId}&date=${newDate}&shift=${shift}`;
});

document.getElementById('shift-selector').addEventListener('change', function() {
    const date = document.getElementById('date-selector').value;
    const newShift = this.value;
    window.location.href = `{{ route('supervisor.daily-production') }}?work_center_id=${workCenterId}&date=${date}&shift=${newShift}`;
});

// Inicializar
calculateTotals();
</script>
@endpush
