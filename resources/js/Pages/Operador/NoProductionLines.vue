<template>
    <AuthenticatedLayout>
        <div class="flex items-center justify-center min-h-[60vh]">
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-8 max-w-md text-center">
                <div class="text-6xl mb-4">📊</div>
                <h2 class="text-2xl font-extrabold text-[#0b2a40] mb-3">Sin Líneas de Producción</h2>
                <p class="text-[#4e6070] mb-6">
                    No tienes líneas de producción asignadas. Contacta al administrador.
                </p>
                <Link :href="route('dashboard')" 
                      class="inline-block px-6 py-3 bg-[#0b2a40] text-white rounded-md text-sm font-bold hover:opacity-85">
                    Volver al Dashboard
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Link } from '@inertiajs/vue3'
</script>