@extends('layouts.app')

@section('title', 'Importar Usuario')

@section('content')
<div class="flex flex-col gap-2.5">
    {{-- Header --}}
    <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
        <div class="px-4 py-3 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold tracking-widest uppercase text-[#174060]">Módulo Administrador</span>
                <h1 class="text-2xl font-extrabold text-[#0b2a40] leading-none">Importar Usuario</h1>
                <div class="text-sm text-[#4e6070] font-semibold mt-1">Desde base de datos italianet_users</div>
            </div>
            
            <a href="{{ route('admin.users.index') }}" 
               class="px-4 py-2 bg-[#f4f7fa] text-[#4e6070] border border-[#d4dee8] rounded-md text-xs font-bold hover:bg-[#e8edf2]">
                ← Volver
            </a>
        </div>
    </div>
    
    {{-- Tabla de Usuarios de italianet_users --}}
    <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-[#d4dee8]">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-[#0b2a40]">Usuarios Disponibles</h2>
                    <p class="text-xs text-[#6a8090] mt-1">Selecciona un usuario para importarlo al sistema de control de planta</p>
                </div>
                
                <form method="GET" action="{{ route('admin.users.import') }}" class="flex gap-2">
                    <div class="relative">
                        <input type="text" 
                               name="search" 
                               value="{{ $search ?? '' }}" 
                               placeholder="Buscar por nombre o email..."
                               class="px-4 py-2 pr-10 border border-[#d4dee8] rounded-md text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#174060] w-80">
                        @if($search)
                            <a href="{{ route('admin.users.import') }}" 
                               class="absolute right-3 top-1/2 -translate-y-1/2 text-[#6a8090] hover:text-[#0b2a40]">
                                ✕
                            </a>
                        @endif
                    </div>
                    <button type="submit" 
                            class="px-4 py-2 bg-[#0b2a40] text-white rounded-md text-xs font-bold hover:opacity-85">
                        🔍 Buscar
                    </button>
                </form>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-[#0b2a40] text-white">
                        <th class="px-4 py-3 text-left text-[11px] font-bold tracking-widest uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold tracking-widest uppercase">Nombre</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold tracking-widest uppercase">Email</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold tracking-widest uppercase">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($italianetUsers as $user)
                        <tr class="border-b border-[#e8eff4] hover:bg-[#eef5fa]">
                            <td class="px-4 py-3 text-sm font-bold text-[#0c1c28]">{{ $user->id }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-[#0c1c28]">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-sm text-[#4e6070]">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-center">
                                <button onclick="openImportModal({{ $user->id }}, '{{ $user->name }}', '{{ $user->email }}')" 
                                        class="px-4 py-1.5 bg-[#0b2a40] text-white rounded text-xs font-bold hover:opacity-85">
                                    ➕ Importar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-[#6a8090]">
                                No hay usuarios disponibles en italianet_users
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($italianetUsers->hasPages())
            <div class="px-4 py-3 border-t border-[#d4dee8]">
                {{ $italianetUsers->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Modal de Importación --}}
<div id="import-modal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl">
        <div class="px-6 py-4 border-b border-[#d4dee8]">
            <h3 class="text-lg font-extrabold text-[#0b2a40]">Importar Usuario</h3>
            <p class="text-sm text-[#6a8090] mt-1" id="modal-user-info"></p>
        </div>
        
        <form method="POST" action="{{ route('admin.users.import.store') }}" class="p-6">
            @csrf
            <input type="hidden" name="user_main_id" id="import-user-id">
            
            <div class="mb-4 p-3 bg-[#fef3c7] border border-[#fde68a] rounded-md">
                <div class="flex items-start gap-2">
                    <span class="text-lg">🔑</span>
                    <div>
                        <p class="text-xs font-bold text-[#92400e]">Contraseña por defecto</p>
                        <p class="text-xs text-[#92400e] mt-1">
                            El usuario será importado con la contraseña: <span class="font-bold">password123</span>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="space-y-4">
                <div>
                    <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Perfil de Usuario</label>
                    <select name="id_profile" id="import-profile" required
                            class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1"
                            onchange="toggleWorkCenters()">
                        <option value="">Seleccione un perfil</option>
                        @foreach($profiles as $profile)
                            <option value="{{ $profile->id_profile }}">{{ $profile->title }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div id="work-centers-section" class="hidden">
                    <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Centros de Trabajo</label>
                    <p class="text-xs text-[#6a8090] mb-2">Selecciona los centros que este supervisor podrá gestionar</p>
                    <div class="grid grid-cols-2 gap-2 max-h-60 overflow-y-auto p-3 border border-[#d4dee8] rounded-md bg-[#f4f7fa]">
                        @foreach($workCenters as $wc)
                            <label class="flex items-center gap-2 p-2 hover:bg-white rounded cursor-pointer">
                                <input type="checkbox" name="work_centers[]" value="{{ $wc->id }}"
                                       class="w-4 h-4 text-[#0b2a40] border-[#d4dee8] rounded focus:ring-[#174060]">
                                <span class="text-sm font-semibold text-[#0c1c28]">{{ $wc->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                
                <div id="production-lines-section" class="hidden">
                    <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Líneas de Producción</label>
                    <p class="text-xs text-[#6a8090] mb-2">Selecciona las líneas que este operador podrá gestionar</p>
                    <div class="grid grid-cols-2 gap-2 max-h-60 overflow-y-auto p-3 border border-[#d4dee8] rounded-md bg-[#f4f7fa]">
                        @foreach($productionLines as $line)
                            <label class="flex items-center gap-2 p-2 hover:bg-white rounded cursor-pointer">
                                <input type="checkbox" name="production_lines[]" value="{{ $line->id }}"
                                       class="w-4 h-4 text-[#0b2a40] border-[#d4dee8] rounded focus:ring-[#174060]">
                                <span class="text-sm font-semibold text-[#0c1c28]">{{ $line->title }}</span>
                                <span class="text-xs text-[#6a8090]">({{ $line->workCenter->name }})</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex gap-2 justify-end">
                <button type="button" onclick="closeImportModal()" 
                        class="px-4 py-2 bg-[#f4f7fa] text-[#4e6070] border border-[#d4dee8] rounded-md text-xs font-bold hover:bg-[#e8edf2]">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-[#0b2a40] text-white rounded-md text-xs font-bold hover:opacity-85">
                    Importar Usuario
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openImportModal(userId, userName, userEmail) {
    document.getElementById('import-user-id').value = userId;
    document.getElementById('modal-user-info').textContent = `${userName} (${userEmail})`;
    document.getElementById('import-modal').classList.remove('hidden');
}

function closeImportModal() {
    document.getElementById('import-modal').classList.add('hidden');
    document.getElementById('import-profile').value = '';
    document.getElementById('work-centers-section').classList.add('hidden');
    document.getElementById('production-lines-section').classList.add('hidden');
    document.querySelectorAll('input[name="work_centers[]"]').forEach(cb => cb.checked = false);
    document.querySelectorAll('input[name="production_lines[]"]').forEach(cb => cb.checked = false);
}

function toggleWorkCenters() {
    const profile = document.getElementById('import-profile').value;
    const workCentersSection = document.getElementById('work-centers-section');
    const productionLinesSection = document.getElementById('production-lines-section');
    
    if (profile == '5') {
        workCentersSection.classList.remove('hidden');
        productionLinesSection.classList.add('hidden');
        document.querySelectorAll('input[name="production_lines[]"]').forEach(cb => cb.checked = false);
    } else if (profile == '8') {
        workCentersSection.classList.add('hidden');
        productionLinesSection.classList.remove('hidden');
        document.querySelectorAll('input[name="work_centers[]"]').forEach(cb => cb.checked = false);
    } else {
        workCentersSection.classList.add('hidden');
        productionLinesSection.classList.add('hidden');
        document.querySelectorAll('input[name="work_centers[]"]').forEach(cb => cb.checked = false);
        document.querySelectorAll('input[name="production_lines[]"]').forEach(cb => cb.checked = false);
    }
}
</script>
@endpush
@endsection
