<!-- resources/js/Pages/IngenieroProcesos/CreateRecovery.vue -->
<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <IngenieroProcesosSidebar />
        
        <div class="p-6 ml-16">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Crear Programa de Recuperación</h1>
                <Link :href="route('ingeniero-procesos.recuperacion.index')" 
                      class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                    Volver
                </Link>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Centro de Trabajo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Centro de Trabajo *
                            </label>
                            <select v-model="form.work_center_id" 
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    required>
                                <option value="">Seleccione un centro de trabajo</option>
                                <option v-for="wc in workCenters" :key="wc.id" :value="wc.id">
                                    {{ wc.name }} (Fase {{ wc.phase }})
                                </option>
                            </select>
                        </div>

                        <!-- Fecha -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Fecha *
                            </label>
                            <input type="date" 
                                   v-model="form.date" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                        </div>
                    </div>

                    <!-- Información de Balance Actual -->
                    <div v-if="form.work_center_id" class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">Balance Actual del Centro</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm text-gray-600">Atrasos acumulados:</span>
                                <p class="text-lg font-bold" :class="currentBackwardness > 0 ? 'text-red-600' : 'text-green-600'">
                                    {{ currentBackwardness }} piezas
                                </p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600">Adelantos acumulados:</span>
                                <p class="text-lg font-bold" :class="currentAdvanced > 0 ? 'text-blue-600' : 'text-gray-600'">
                                    {{ currentAdvanced }} piezas
                                </p>
                            </div>
                        </div>
                        <p v-if="currentBackwardness > 0" class="text-xs text-gray-500 mt-2">
                            * El valor de atrasos se ha cargado automáticamente en el campo de cantidad de piezas. Puede modificarlo si lo desea.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <!-- Turno -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Turno *
                            </label>
                            <select v-model="form.shift" 
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    required>
                                <option value="">Seleccione un turno</option>
                                <option value="matutino">Matutino (08:00 - 16:00)</option>
                                <option value="vespertino">Vespertino (16:00 - 00:00)</option>
                                <option value="nocturno">Nocturno (00:00 - 08:00)</option>
                            </select>
                        </div>

                        <!-- Cantidad de Piezas -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Cantidad de Piezas *
                            </label>
                            <input type="number" 
                                   v-model.number="form.cantidad_piezas" 
                                   min="1"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                        </div>

                        <!-- Horas de Turno -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Horas de Turno (opcional, default: 9)
                            </label>
                            <input type="number" 
                                   v-model.number="form.shift_hours" 
                                   min="1" 
                                   max="24"
                                   step="0.5"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="9">
                        </div>

                        <!-- Observaciones -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Observaciones (opcional)
                            </label>
                            <textarea v-model="form.observaciones" 
                                      rows="3"
                                      maxlength="500"
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Comentarios adicionales sobre este programa de recuperación..."></textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <Link :href="route('ingeniero-procesos.recuperacion.index')" 
                              class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition font-medium">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                            Cancelar
                        </Link>
                        <button type="submit" 
                                :disabled="form.processing"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 font-medium">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            {{ form.processing ? 'Guardando...' : 'Crear Programa' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useToast } from 'vue-toastification';
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';

const page = usePage();
const toast = useToast();

const props = defineProps({
    workCenters: Array,
});

const form = useForm({
    work_center_id: '',
    date: '',
    shift: '',
    cantidad_piezas: '',
    shift_hours: null,
    observaciones: '',
});

const currentBackwardness = ref(0);
const currentAdvanced = ref(0);
const loadingBalance = ref(false);

// Cargar balance del centro de trabajo cuando se selecciona
watch(() => form.work_center_id, async (newWorkCenterId) => {
    if (newWorkCenterId) {
        loadingBalance.value = true;
        try {
            const response = await fetch(route('ingeniero-procesos.recuperacion.balance', newWorkCenterId));
            const data = await response.json();
            currentBackwardness.value = data.accumulated_backwardness;
            currentAdvanced.value = data.accumulated_advanced;
            
            // Si hay atrasos, poner ese valor por defecto en cantidad_piezas
            if (data.accumulated_backwardness > 0) {
                form.cantidad_piezas = data.accumulated_backwardness;
            } else {
                form.cantidad_piezas = '';
            }
        } catch (error) {
            console.error('Error al cargar balance:', error);
            currentBackwardness.value = 0;
            currentAdvanced.value = 0;
        } finally {
            loadingBalance.value = false;
        }
    } else {
        currentBackwardness.value = 0;
        currentAdvanced.value = 0;
        form.cantidad_piezas = '';
    }
});

const submit = () => {
    form.post(route('ingeniero-procesos.recuperacion.store'), {
        onSuccess: () => {
            // Success message handled by backend flash message
        },
        onError: (errors) => {
            toast.error('Por favor corrija los errores en el formulario');
        },
    });
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
