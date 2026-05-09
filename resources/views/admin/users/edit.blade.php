@extends('layouts.app')

@section('title', 'Editar Usuario')

@section('content')
<div class="flex flex-col gap-2.5">
    {{-- Header --}}
    <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
        <div class="px-4 py-3 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold tracking-widest uppercase text-[#174060]">Módulo Administrador</span>
                <h1 class="text-2xl font-extrabold text-[#0b2a40] leading-none">Editar Usuario</h1>
            </div>
            
            <a href="{{ route('admin.users.index') }}" 
               class="px-4 py-2 bg-[#f4f7fa] text-[#4e6070] border border-[#d4dee8] rounded-md text-xs font-bold hover:bg-[#e8edf2]">
                ← Volver
            </a>
        </div>
    </div>
    
    {{-- Formulario --}}
    <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-6">
        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Nombre</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1 @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Perfil de Usuario</label>
                    <select name="id_profile" id="edit-profile" required
                            class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1"
                            onchange="toggleWorkCenters()">
                        @foreach($profiles as $profile)
                            <option value="{{ $profile->id_profile }}" {{ $user->id_profile == $profile->id_profile ? 'selected' : '' }}>
                                {{ $profile->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">ID Usuario Principal</label>
                    <input type="text" value="{{ $user->user_main_id ?? 'N/A' }}" disabled
                           class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1 bg-[#f4f7fa]">
                </div>
            </div>
            
            <div id="work-centers-section" class="mt-6 {{ $user->id_profile == 5 ? '' : 'hidden' }}">
                <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Centros de Trabajo</label>
                <p class="text-xs text-[#6a8090] mb-3">Selecciona los centros que este supervisor podrá gestionar</p>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 p-4 border border-[#d4dee8] rounded-lg bg-[#f4f7fa]">
                    @foreach($workCenters as $wc)
                        <label class="flex items-center gap-2 p-2 hover:bg-white rounded cursor-pointer">
                            <input type="checkbox" name="work_centers[]" value="{{ $wc->id }}"
                                   {{ $user->workCenters->contains($wc->id) ? 'checked' : '' }}
                                   class="w-4 h-4 text-[#0b2a40] border-[#d4dee8] rounded focus:ring-[#174060]">
                            <span class="text-sm font-semibold text-[#0c1c28]">{{ $wc->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            
            <div id="production-lines-section" class="mt-6 {{ $user->id_profile == 8 ? '' : 'hidden' }}">
                <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Líneas de Producción</label>
                <p class="text-xs text-[#6a8090] mb-3">Selecciona las líneas que este operador podrá gestionar</p>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 p-4 border border-[#d4dee8] rounded-lg bg-[#f4f7fa]">
                    @foreach($productionLines as $line)
                        <label class="flex items-center gap-2 p-2 hover:bg-white rounded cursor-pointer">
                            <input type="checkbox" name="production_lines[]" value="{{ $line->id }}"
                                   {{ $user->productionLines->contains($line->id) ? 'checked' : '' }}
                                   class="w-4 h-4 text-[#0b2a40] border-[#d4dee8] rounded focus:ring-[#174060]">
                            <span class="text-sm font-semibold text-[#0c1c28]">{{ $line->title }}</span>
                            <span class="text-xs text-[#6a8090]">({{ $line->workCenter->name }})</span>
                        </label>
                    @endforeach
                </div>
            </div>
            
            <div class="mt-6 flex gap-2 justify-end">
                <a href="{{ route('admin.users.index') }}" 
                   class="px-4 py-2 bg-[#f4f7fa] text-[#4e6070] border border-[#d4dee8] rounded-md text-xs font-bold hover:bg-[#e8edf2]">
                    Cancelar
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-[#0b2a40] text-white rounded-md text-xs font-bold hover:opacity-85">
                    💾 Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function toggleWorkCenters() {
    const profile = document.getElementById('edit-profile').value;
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
