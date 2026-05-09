@extends('layouts.app')

@section('title', 'Sin Líneas Asignadas')

@section('content')
<div class="flex items-center justify-center min-h-[60vh]">
    <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-8 max-w-md text-center">
        <div class="text-6xl mb-4">🏭</div>
        <h2 class="text-2xl font-extrabold text-[#0b2a40] mb-3">Sin Líneas de Producción</h2>
        <p class="text-[#6a8090] mb-6">
            No tienes líneas de producción asignadas. Por favor, contacta al administrador del sistema para que te asigne las líneas correspondientes.
        </p>
        <div class="p-4 bg-[#f8f9fb] border border-[#d4dee8] rounded-lg text-left">
            <p class="text-xs text-[#6a8090] mb-2"><strong>Nota:</strong></p>
            <p class="text-xs text-[#6a8090]">
                El administrador puede asignar líneas de producción desde el panel de administración en la sección de gestión de usuarios.
            </p>
        </div>
    </div>
</div>
@endsection
