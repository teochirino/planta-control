<!-- resources/js/Pages/IngenieroProcesos/EditRecovery.vue -->
<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <IngenieroProcesosSidebar />
        
        <div class="p-6 ml-16">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Editar Programa de Recuperación</h1>
                <div class="flex gap-3">
                    <Link :href="route('ingeniero-procesos.recuperacion.show', program.id)" 
                          class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                        </svg>
                        Volver
                    </Link>
                </div>
            </div>

            <!-- Información del programa -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Información del Programa</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <span class="text-sm text-gray-500">Código:</span>
                        <p class="text-lg font-medium text-blue-600">{{ program.codigo }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Fecha Entrega:</span>
                        <p class="text-lg font-medium">{{ program.fecha_entrega_formatted }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Creado Por:</span>
                        <p class="text-lg font-medium">{{ program.creator?.name || 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Editar Daily Programs -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Programas Diarios</h2>
                
                <form @submit.prevent="submit">
                    <div v-if="dailyPrograms.length === 0" class="text-center py-4 text-gray-500">
                        No hay programas diarios registrados
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="(dp, index) in dailyPrograms" :key="dp.id" 
                             class="border border-gray-200 rounded-lg p-4">
                            <h3 class="font-medium text-gray-800 mb-3">
                                {{ dp.work_center?.name || 'N/A' }} - {{ formatDate(dp.date) }} - {{ dp.shift }}
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Programado *
                                    </label>
                                    <input type="number" 
                                           v-model.number="form.daily_programs[index].programmed" 
                                           min="0"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Horas de Turno
                                    </label>
                                    <input type="number" 
                                           v-model.number="form.daily_programs[index].shift_hours" 
                                           min="1" 
                                           max="24"
                                           step="0.5"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div class="flex items-end">
                                    <div class="text-sm text-gray-500">
                                        <p>Producido: {{ dp.total_produced }}</p>
                                        <p>Atraso: {{ dp.backwardness }}</p>
                                        <p>Adelanto: {{ dp.advanced }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" 
                                :disabled="form.processing"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 font-medium">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            {{ form.processing ? 'Guardando...' : 'Actualizar Programa' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import { useToast } from 'vue-toastification';
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';

const page = usePage();
const toast = useToast();

const props = defineProps({
    program: Object,
    dailyPrograms: Array,
    workCenters: Array,
});

const form = useForm({
    daily_programs: props.dailyPrograms.map(dp => ({
        id: dp.id,
        programmed: dp.programmed,
        shift_hours: dp.shift_hours,
    })),
});

const submit = () => {
    form.put(route('ingeniero-procesos.recuperacion.update', props.program.id), {
        onSuccess: () => {
            toast.success('Programa actualizado exitosamente');
        },
        onError: () => {
            toast.error('Error al actualizar el programa');
        },
    });
};

// Función para formatear fecha
const formatDate = (date) => {
    if (!date) return 'N/A';
    const d = new Date(date);
    return d.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

// Mostrar notificaciones de flash messages
watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
        toast.success(flash.success);
    }
    if (flash?.error) {
        toast.error(flash.error);
    }
}, { immediate: true });
</script>
