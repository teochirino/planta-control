<!-- resources/js/Pages/IngenieroProcesos/ViewRecoveryProgram.vue -->
<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <IngenieroProcesosSidebar />
        
        <div class="p-6 ml-16">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Programa de Recuperación</h1>
                <div class="flex gap-3">
                    <Link :href="route('ingeniero-procesos.recuperacion.index')" 
                          class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                        </svg>
                        Volver
                    </Link>
                    <Link :href="route('ingeniero-procesos.recuperacion.edit', program.id)" 
                          class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                        </svg>
                        Editar
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
                    <div>
                        <span class="text-sm text-gray-500">Fecha Creación:</span>
                        <p class="text-lg font-medium">{{ program.created_at_formatted }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Tipo:</span>
                        <p class="text-lg font-medium text-orange-600">Recuperación</p>
                    </div>
                </div>
                <div v-if="program.observaciones" class="mt-4 pt-4 border-t border-gray-200">
                    <span class="text-sm text-gray-500">Observaciones:</span>
                    <p class="text-gray-800 whitespace-pre-line">{{ program.observaciones }}</p>
                </div>
            </div>

            <!-- Daily Programs -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Programas Diarios</h2>
                
                <div v-if="dailyPrograms.length === 0" class="text-center py-4 text-gray-500">
                    No hay programas diarios registrados
                </div>

                <div v-else class="space-y-4">
                    <div v-for="dp in dailyPrograms" :key="dp.id" 
                         class="border border-gray-200 rounded-lg p-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <span class="text-sm text-gray-500">Centro de Trabajo:</span>
                                <p class="font-medium">{{ dp.work_center }}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500">Fecha:</span>
                                <p class="font-medium">{{ dp.date_formatted }}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500">Turno:</span>
                                <p class="font-medium capitalize">{{ dp.shift }}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500">Horas Turno:</span>
                                <p class="font-medium">{{ dp.shift_hours }}h</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500">Programado:</span>
                                <p class="font-medium text-blue-600">{{ dp.programed }}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500">Atraso:</span>
                                <p class="font-medium text-red-600">{{ dp.backwardness }}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500">Adelanto:</span>
                                <p class="font-medium text-green-600">{{ dp.advanced }}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500">Producido:</span>
                                <p class="font-medium text-purple-600">{{ dp.total_produced }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import { useToast } from 'vue-toastification';
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';

const page = usePage();
const toast = useToast();

const props = defineProps({
    program: Object,
    dailyPrograms: Array,
});

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
