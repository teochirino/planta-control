@extends('layouts.app')

@section('title', 'Gestión de Usuarios')

@section('content')
<div class="flex flex-col gap-2.5">
    {{-- Header --}}
    <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
        <div class="px-4 py-3 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold tracking-widest uppercase text-[#174060]">Módulo Administrador</span>
                <h1 class="text-2xl font-extrabold text-[#0b2a40] leading-none">Gestión de Usuarios</h1>
            </div>
            
            <div class="flex gap-2">
                <a href="{{ route('admin.users.import') }}" 
                   class="px-4 py-2 bg-[#0b2a40] text-white rounded-md text-xs font-bold hover:opacity-85">
                    ➕ Importar Usuario
                </a>
                <a href="{{ route('admin.work-centers.assign') }}" 
                   class="px-4 py-2 bg-[#174060] text-white rounded-md text-xs font-bold hover:opacity-85">
                    🏭 Asignar Centros
                </a>
                <a href="{{ route('admin.production-lines.assign') }}" 
                   class="px-4 py-2 bg-[#0b8a3d] text-white rounded-md text-xs font-bold hover:opacity-85">
                    📊 Asignar Líneas
                </a>
            </div>
        </div>
    </div>
    
    {{-- Tabla de Usuarios --}}
    <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-[#0b2a40] text-white">
                        <th class="px-4 py-3 text-left text-[11px] font-bold tracking-widest uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold tracking-widest uppercase">Nombre</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold tracking-widest uppercase">Email</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold tracking-widest uppercase">Perfil</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold tracking-widest uppercase">Asignaciones</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold tracking-widest uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr class="border-b border-[#e8eff4] hover:bg-[#eef5fa]">
                            <td class="px-4 py-3 text-sm font-bold text-[#0c1c28]">{{ $user->id }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-[#0c1c28]">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-sm text-[#4e6070]">{{ $user->email }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-bold bg-[#f4f7fa] text-[#4e6070] border border-[#d4dee8]">
                                    {{ $user->profile->title ?? 'Sin perfil' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-[#4e6070]">
                                @if($user->id_profile == 5 && $user->workCenters->count() > 0)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($user->workCenters as $wc)
                                            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-[#e4f5ec] text-[#0a7c3e] border border-[#aadcc4]">
                                                🏭 {{ $wc->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @elseif($user->id_profile == 8 && $user->productionLines->count() > 0)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($user->productionLines as $line)
                                            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-[#fef3c7] text-[#92400e] border border-[#fde68a]">
                                                📊 {{ $line->title }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-[#6a8090]">Sin asignaciones</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.users.edit', $user) }}" 
                                       class="px-3 py-1 bg-[#174060] text-white rounded text-xs font-bold hover:opacity-85">
                                        ✏️ Editar
                                    </a>
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" 
                                          onsubmit="return confirm('¿Estás seguro de eliminar este usuario?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="px-3 py-1 bg-[#fce9e8] text-[#ba2418] border border-[#ebbab8] rounded text-xs font-bold hover:bg-[#ba2418] hover:text-white">
                                            🗑️ Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-[#6a8090]">
                                No hay usuarios registrados. Importa usuarios desde la base de datos italianet_users.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($users->hasPages())
            <div class="px-4 py-3 border-t border-[#d4dee8]">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
