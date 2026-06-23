<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-gray-100 flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8 text-center">
            <div class="text-6xl mb-4">⚙️</div>
            <h1 class="text-2xl font-extrabold text-[#0b2a40] mb-2">No hay centros de trabajo</h1>
            <p class="text-[#4e6070] mb-6">
                No se han configurado centros de trabajo en el sistema. 
                Contacte al administrador para que configure los centros de trabajo.
            </p>
            <Link :href="route('dashboard')" 
                  class="inline-block px-6 py-3 bg-[#0b2a40] text-white rounded-lg font-bold hover:bg-[#4e6070] transition-colors">
                Volver al Dashboard
            </Link>
        </div>
    </div>
</template>
