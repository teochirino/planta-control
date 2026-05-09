<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
</script>

<template>
    <Head title="Sin Líneas Asignadas" />

    <AuthenticatedLayout>
        <div class="flex items-center justify-center min-h-[60vh]">
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-8 max-w-md text-center">
                <div class="text-6xl mb-4">⚠️</div>
                <h2 class="text-2xl font-extrabold text-[#0b2a40] mb-3">Sin Líneas de Producción Asignadas</h2>
                <p class="text-[#4e6070] mb-6">
                    No tienes líneas de producción asignadas. Por favor contacta al administrador del sistema para que te asigne las líneas correspondientes.
                </p>
                <div class="p-4 bg-[#fff6da] border border-[#e8d488] rounded-lg text-sm text-[#a87000]">
                    <strong>Nota:</strong> Como operador de área, necesitas tener al menos una línea de producción asignada para poder registrar la producción.
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
