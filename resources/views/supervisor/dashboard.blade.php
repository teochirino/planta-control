@extends('layouts.app')

@section('title', 'Dashboard Supervisor')

@section('content')
<div class="flex flex-col gap-2.5">
    {{-- Selector de Centro de Trabajo --}}
    <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
        <div class="px-4 py-3 flex items-center gap-4 flex-wrap">
            <span class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Centro de Trabajo:</span>
            
            <form method="GET" action="{{ route('supervisor.dashboard') }}" id="dashboard-form" class="flex items-center gap-3 flex-1">
                <select name="work_center_id" 
                        onchange="this.form.submit()"
                        class="border border-[#d4dee8] rounded-md px-3 py-2 text-sm font-bold text-[#0c1c28] bg-white focus:outline-none focus:border-[#174060]">
                    @foreach($workCenters as $wc)
                        <option value="{{ $wc->id }}" {{ $selectedWorkCenter->id == $wc->id ? 'selected' : '' }}>
                            {{ $wc->name }}
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
            
            <div class="flex gap-2">
                <a href="{{ route('supervisor.daily-production', ['work_center_id' => $selectedWorkCenter->id, 'date' => $selectedDate, 'shift' => $selectedShift]) }}" 
                   class="px-4 py-2 bg-[#0b2a40] text-white rounded-md text-xs font-bold hover:opacity-85 transition">
                    📝 Registro Diario de Producción
                </a>
            </div>
        </div>
    </div>
    
    {{-- Información del Centro --}}
    <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-6">
        <h2 class="text-xl font-extrabold text-[#0b2a40] mb-4">{{ $selectedWorkCenter->name }}</h2>
        
        @if($kpis)
        {{-- Tarjetas de KPIs del Programa Diario --}}
        <div class="mb-6 p-4 bg-[#f8f9fb] border border-[#d4dee8] rounded-lg">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-[#0b2a40]">Programa del Turno</h3>
                <span class="text-xs font-semibold text-[#6a8090]">{{ ucfirst($selectedShift) }} - {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</span>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-5 lg:grid-cols-10 gap-2">
                {{-- Programado --}}
                <div class="p-3 bg-white border border-[#d4dee8] rounded-lg text-center">
                    <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-1">Programado</div>
                    <div class="text-2xl font-extrabold text-[#0b2a40]">{{ number_format($kpis['programmed']) }}</div>
                </div>
                
                {{-- Atraso --}}
                <div class="p-3 bg-white border border-[#d4dee8] rounded-lg text-center">
                    <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-1">Atraso</div>
                    <div class="text-2xl font-extrabold text-[#ba2418]">{{ number_format($kpis['backwardness']) }}</div>
                </div>
                
                {{-- Adelantadas --}}
                <div class="p-3 bg-white border border-[#d4dee8] rounded-lg text-center">
                    <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-1">Adelantadas</div>
                    <div class="text-2xl font-extrabold text-[#0b8a3d]">{{ number_format($kpis['advanced']) }}</div>
                </div>
                
                {{-- Total a Producir --}}
                <div class="p-3 bg-white border border-[#d4dee8] rounded-lg text-center">
                    <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-1">Total a Producir</div>
                    <div class="text-2xl font-extrabold text-[#0b2a40]">{{ number_format($kpis['total_to_produce']) }}</div>
                </div>
                
                {{-- Fabricadas --}}
                <div class="p-3 bg-white border border-[#d4dee8] rounded-lg text-center">
                    <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-1">Fabricadas</div>
                    <div class="text-2xl font-extrabold text-[#0b2a40]">{{ number_format($kpis['fabricated']) }}</div>
                </div>
                
                {{-- Diferencia --}}
                <div class="p-3 bg-white border border-[#d4dee8] rounded-lg text-center">
                    <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-1">Diferencia</div>
                    <div class="text-2xl font-extrabold {{ $kpis['difference'] >= 0 ? 'text-[#0b8a3d]' : 'text-[#ba2418]' }}">
                        {{ $kpis['difference'] >= 0 ? '+' : '' }}{{ number_format($kpis['difference']) }}
                    </div>
                </div>
                
                {{-- Cumplimiento --}}
                <div class="p-3 bg-white border border-[#d4dee8] rounded-lg text-center">
                    <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-1">Cumplimiento</div>
                    <div class="text-2xl font-extrabold {{ $kpis['compliance'] >= 100 ? 'text-[#0b8a3d]' : ($kpis['compliance'] >= 95 ? 'text-[#f59e0b]' : 'text-[#ba2418]') }}">
                        {{ number_format($kpis['compliance'], 1) }}%
                    </div>
                </div>
                
                {{-- Real vs Ideal --}}
                <div class="p-3 bg-white border border-[#d4dee8] rounded-lg text-center">
                    <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-1">Real vs Ideal</div>
                    <div class="text-2xl font-extrabold text-[#0b2a40]">{{ number_format($kpis['real_vs_ideal'], 1) }}%</div>
                </div>
                
                {{-- Capacidad Instalada --}}
                <div class="p-3 bg-white border border-[#d4dee8] rounded-lg text-center">
                    <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-1">Cap. Instalada</div>
                    <div class="text-2xl font-extrabold text-[#0b2a40]">{{ number_format($kpis['installed_capacity']) }}</div>
                </div>
            </div>
        </div>
        @else
        {{-- Mensaje cuando no hay programa --}}
        <div class="mb-6 p-6 bg-[#fff6da] border border-[#e8d488] rounded-lg text-center">
            <div class="text-4xl mb-3">📋</div>
            <h3 class="text-lg font-bold text-[#0b2a40] mb-2">No hay programa diario registrado</h3>
            <p class="text-sm text-[#6a8090] mb-4">
                No existe un programa para <strong>{{ $selectedWorkCenter->name }}</strong> en el turno <strong>{{ ucfirst($selectedShift) }}</strong> del <strong>{{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</strong>.
            </p>
            <a href="{{ route('supervisor.daily-production', ['work_center_id' => $selectedWorkCenter->id, 'date' => $selectedDate, 'shift' => $selectedShift]) }}" 
               class="inline-block px-6 py-3 bg-[#0b2a40] text-white rounded-md text-sm font-bold hover:opacity-85 transition">
                ➕ Crear Programa Diario
            </a>
        </div>
        @endif
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 bg-[#f4f7fa] border border-[#d4dee8] rounded-lg">
                <div class="text-xs font-bold tracking-widest uppercase text-[#4e6070] mb-1">Capacidad Instalada</div>
                <div class="text-3xl font-extrabold text-[#0b2a40]">{{ number_format($selectedWorkCenter->installed_capacity) }}</div>
                <div class="text-xs text-[#6a8090] mt-1">piezas/día</div>
            </div>
            
            <div class="p-4 bg-[#f4f7fa] border border-[#d4dee8] rounded-lg">
                <div class="text-xs font-bold tracking-widest uppercase text-[#4e6070] mb-1">Líneas de Producción</div>
                <div class="text-3xl font-extrabold text-[#0b2a40]">{{ $selectedWorkCenter->productionLines->count() }}</div>
                <div class="text-xs text-[#6a8090] mt-1">líneas activas</div>
            </div>
            
            <div class="p-4 bg-[#f4f7fa] border border-[#d4dee8] rounded-lg">
                <div class="text-xs font-bold tracking-widest uppercase text-[#4e6070] mb-1">Fecha Actual</div>
                <div class="text-xl font-extrabold text-[#0b2a40]">{{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</div>
                <div class="text-xs text-[#6a8090] mt-1">{{ \Carbon\Carbon::parse($selectedDate)->locale('es')->isoFormat('dddd') }}</div>
            </div>
        </div>
        
        <div class="mt-6">
            <h3 class="text-sm font-bold text-[#0b2a40] mb-3">Líneas de Producción</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($selectedWorkCenter->productionLines as $line)
                    <div class="p-3 border border-[#d4dee8] rounded-lg bg-white">
                        <div class="font-bold text-[#0b2a40]">{{ $line->title }}</div>
                        <div class="text-xs text-[#6a8090] mt-1">
                            Cap: {{ number_format($line->installed_capacity) }} pzs/día | 
                            Costo: ${{ number_format($line->cost, 2) }}/min
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
