@extends('layouts.app')

@section('title', 'Asignar Líneas de Producción')

@section('content')
<div class="flex flex-col gap-2.5">
    {{-- Header --}}
    <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
        <div class="px-4 py-3 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold tracking-widest uppercase text-[#174060]">Módulo Administrador</span>
                <h1 class="text-2xl font-extrabold text-[#0b2a40] leading-none">Asignar Líneas de Producción</h1>
                <div class="text-sm text-[#4e6070] font-semibold mt-1">Gestión de accesos para Operadores de Área</div>
            </div>
            
            <a href="{{ route('admin.users.index') }}" 
               class="px-4 py-2 bg-[#f4f7fa] text-[#4e6070] border border-[#d4dee8] rounded-md text-xs font-bold hover:bg-[#e8edf2]">
                ← Volver
            </a>
        </div>
    </div>
    
    {{-- Lista de Operadores --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @forelse($operadores as $operador)
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-5">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-[#0b2a40]">{{ $operador->name }}</h3>
                        <p class="text-sm text-[#6a8090]">{{ $operador->email }}</p>
                    </div>
                    <span class="px-3 py-1 rounded-full bg-[#fef3c7] text-[#92400e] border border-[#fde68a] text-xs font-bold">
                        Operador
                    </span>
                </div>
                
                <form method="POST" action="{{ route('admin.users.production-lines.update', $operador) }}">
                    @csrf
                    
                    <div class="mb-4">
                        <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070] mb-2 block">
                            Líneas Asignadas
                        </label>
                        <div class="space-y-2 max-h-60 overflow-y-auto p-3 border border-[#d4dee8] rounded-lg bg-[#f4f7fa]">
                            @foreach($productionLines as $line)
                                <label class="flex items-center gap-2 p-2 hover:bg-white rounded cursor-pointer">
                                    <input type="checkbox" name="production_lines[]" value="{{ $line->id }}"
                                           {{ $operador->productionLines->contains($line->id) ? 'checked' : '' }}
                                           class="w-4 h-4 text-[#0b2a40] border-[#d4dee8] rounded focus:ring-[#174060]">
                                    <div class="flex-1">
                                        <span class="text-sm font-semibold text-[#0c1c28]">{{ $line->title }}</span>
                                        <span class="text-xs text-[#6a8090] ml-2">({{ $line->workCenter->name }})</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between pt-3 border-t border-[#d4dee8]">
                        <div class="text-xs text-[#6a8090]">
                            <strong>{{ $operador->productionLines->count() }}</strong> línea(s) asignada(s)
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
                <p class="text-[#6a8090]">No hay operadores de área registrados en el sistema.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
