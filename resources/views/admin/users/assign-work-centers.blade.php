@extends('layouts.app')

@section('title', 'Asignar Centros de Trabajo')

@section('content')
<div class="flex flex-col gap-2.5">
    {{-- Header --}}
    <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
        <div class="px-4 py-3 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold tracking-widest uppercase text-[#174060]">Módulo Administrador</span>
                <h1 class="text-2xl font-extrabold text-[#0b2a40] leading-none">Asignar Centros de Trabajo</h1>
                <div class="text-sm text-[#4e6070] font-semibold mt-1">Gestión de accesos para Supervisores de Área</div>
            </div>
            
            <a href="{{ route('admin.users.index') }}" 
               class="px-4 py-2 bg-[#f4f7fa] text-[#4e6070] border border-[#d4dee8] rounded-md text-xs font-bold hover:bg-[#e8edf2]">
                ← Volver
            </a>
        </div>
    </div>
    
    {{-- Lista de Supervisores --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @forelse($supervisors as $supervisor)
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-5">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-[#0b2a40]">{{ $supervisor->name }}</h3>
                        <p class="text-sm text-[#6a8090]">{{ $supervisor->email }}</p>
                    </div>
                    <span class="px-3 py-1 rounded-full bg-[#e4f5ec] text-[#0a7c3e] border border-[#aadcc4] text-xs font-bold">
                        Supervisor
                    </span>
                </div>
                
                <form method="POST" action="{{ route('admin.users.work-centers.update', $supervisor) }}">
                    @csrf
                    
                    <div class="mb-4">
                        <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070] mb-2 block">
                            Centros Asignados
                        </label>
                        <div class="space-y-2 max-h-60 overflow-y-auto p-3 border border-[#d4dee8] rounded-lg bg-[#f4f7fa]">
                            @foreach($workCenters as $wc)
                                <label class="flex items-center gap-2 p-2 hover:bg-white rounded cursor-pointer">
                                    <input type="checkbox" name="work_centers[]" value="{{ $wc->id }}"
                                           {{ $supervisor->workCenters->contains($wc->id) ? 'checked' : '' }}
                                           class="w-4 h-4 text-[#0b2a40] border-[#d4dee8] rounded focus:ring-[#174060]">
                                    <span class="text-sm font-semibold text-[#0c1c28]">{{ $wc->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between pt-3 border-t border-[#d4dee8]">
                        <div class="text-xs text-[#6a8090]">
                            <strong>{{ $supervisor->workCenters->count() }}</strong> centro(s) asignado(s)
                        </div>
                        <button type="submit" 
                                class="px-4 py-2 bg-[#0b2a40] text-white rounded-md text-xs font-bold hover:opacity-85">
                            💾 Guardar
                        </button>
                    </div>
                </form>
            </div>
        @empty
            <div class="col-span-2 bg-white border border-[#d4dee8] rounded-xl shadow-sm p-8 text-center">
                <div class="text-4xl mb-3">👥</div>
                <p class="text-[#6a8090]">No hay supervisores de área registrados en el sistema.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
