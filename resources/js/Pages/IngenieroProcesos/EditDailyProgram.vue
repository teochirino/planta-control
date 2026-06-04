<template>
    <div class="min-h-screen bg-gray-900 flex">
        <IngenieroProcesosSidebar />
        
        <div class="flex-1 p-6">
            <div class="mb-6">
                <Link :href="route('ingeniero-procesos.production-adjustments')" 
                      class="text-blue-400 hover:text-blue-300 mb-4 inline-block">
                    ← Volver al historial de ajustes
                </Link>
                <h1 class="text-3xl font-bold text-white">Editar Programa Diario</h1>
            </div>

            <!-- Información del programa -->
            <div class="bg-gray-800 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-white mb-4">Información del Programa</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-400 text-sm">Fecha</label>
                        <p class="text-white">{{ formatDate(dailyProgram.date) }}</p>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm">Turno</label>
                        <p class="text-white capitalize">{{ dailyProgram.shift }}</p>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm">Centro de Trabajo</label>
                        <p class="text-white">{{ dailyProgram.work_center?.name }}</p>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm">Programa</label>
                        <p class="text-white">{{ dailyProgram.program?.codigo || 'No asignado' }}</p>
                    </div>
                </div>
            </div>

            <!-- Formulario de ajuste -->
            <div class="bg-gray-800 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-white mb-4">Ajustar Valores</h2>
                
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <label class="block text-gray-300 text-sm mb-2">Programado</label>
                            <input type="number" v-model.number="form.programmed" min="0"
                                   class="w-full bg-gray-700 text-white rounded px-3 py-2">
                            <p class="text-gray-400 text-xs mt-1">Valor actual: {{ dailyProgram.programmed }}</p>
                        </div>
                        <div>
                            <label class="block text-gray-300 text-sm mb-2">Atraso (Backwardness)</label>
                            <input type="number" v-model.number="form.backwardness" min="0"
                                   class="w-full bg-gray-700 text-white rounded px-3 py-2">
                            <p class="text-gray-400 text-xs mt-1">Valor actual: {{ dailyProgram.backwardness }}</p>
                        </div>
                        <div>
                            <label class="block text-gray-300 text-sm mb-2">Adelanto (Advanced)</label>
                            <input type="number" v-model.number="form.advanced" min="0"
                                   class="w-full bg-gray-700 text-white rounded px-3 py-2">
                            <p class="text-gray-400 text-xs mt-1">Valor actual: {{ dailyProgram.advanced }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-gray-300 text-sm mb-2">Total Fabricado</label>
                            <input type="number" v-model.number="form.total_produced" min="0"
                                   class="w-full bg-gray-700 text-white rounded px-3 py-2">
                            <p class="text-gray-400 text-xs mt-1">Valor actual: {{ dailyProgram.total_produced || 0 }}</p>
                        </div>
                        <div>
                            <label class="block text-gray-300 text-sm mb-2">Total Rechazado</label>
                            <input type="number" v-model.number="form.total_rejected" min="0"
                                   class="w-full bg-gray-700 text-white rounded px-3 py-2">
                            <p class="text-gray-400 text-xs mt-1">Valor actual: {{ dailyProgram.total_rejected || 0 }}</p>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-300 text-sm mb-2">Motivo del ajuste *</label>
                        <textarea v-model="form.reason" rows="3" required
                                  class="w-full bg-gray-700 text-white rounded px-3 py-2"
                                  placeholder="Explique el motivo del ajuste..."></textarea>
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-300 text-sm mb-2">Notas adicionales</label>
                        <textarea v-model="form.notes" rows="2"
                                  class="w-full bg-gray-700 text-white rounded px-3 py-2"
                                  placeholder="Información adicional opcional..."></textarea>
                    </div>

                    <div class="flex space-x-4">
                        <button type="submit" 
                                :disabled="form.processing"
                                class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded transition disabled:opacity-50">
                            {{ form.processing ? 'Guardando...' : 'Guardar Ajuste' }}
                        </button>
                        <Link :href="route('ingeniero-procesos.production-adjustments')"
                              class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded transition">
                            Cancelar
                        </Link>
                    </div>
                </form>
            </div>

            <!-- Resumen de cambios -->
            <div v-if="hasChanges" class="bg-gray-800 rounded-lg p-6">
                <h2 class="text-xl font-semibold text-white mb-4">Resumen de Cambios</h2>
                <div class="space-y-2">
                    <div v-if="form.programmed !== dailyProgram.programmed" class="flex justify-between text-white">
                        <span>Programado:</span>
                        <span>{{ dailyProgram.programmed }} → {{ form.programmed }}
                            ({{ form.programmed > dailyProgram.programmed ? '+' : '' }}{{ form.programmed - dailyProgram.programmed }})</span>
                    </div>
                    <div v-if="form.backwardness !== dailyProgram.backwardness" class="flex justify-between text-white">
                        <span>Atraso:</span>
                        <span>{{ dailyProgram.backwardness }} → {{ form.backwardness }}
                            ({{ form.backwardness > dailyProgram.backwardness ? '+' : '' }}{{ form.backwardness - dailyProgram.backwardness }})</span>
                    </div>
                    <div v-if="form.advanced !== dailyProgram.advanced" class="flex justify-between text-white">
                        <span>Adelanto:</span>
                        <span>{{ dailyProgram.advanced }} → {{ form.advanced }}
                            ({{ form.advanced > dailyProgram.advanced ? '+' : '' }}{{ form.advanced - dailyProgram.advanced }})</span>
                    </div>
                    <div v-if="form.total_produced !== (dailyProgram.total_produced || 0)" class="flex justify-between text-white">
                        <span>Total Fabricado:</span>
                        <span>{{ dailyProgram.total_produced || 0 }} → {{ form.total_produced }}
                            ({{ form.total_produced > (dailyProgram.total_produced || 0) ? '+' : '' }}{{ form.total_produced - (dailyProgram.total_produced || 0) }})</span>
                    </div>
                    <div v-if="form.total_rejected !== (dailyProgram.total_rejected || 0)" class="flex justify-between text-white">
                        <span>Total Rechazado:</span>
                        <span>{{ dailyProgram.total_rejected || 0 }} → {{ form.total_rejected }}
                            ({{ form.total_rejected > (dailyProgram.total_rejected || 0) ? '+' : '' }}{{ form.total_rejected - (dailyProgram.total_rejected || 0) }})</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';
import { computed } from 'vue';

const props = defineProps({
    dailyProgram: Object,
});

const form = useForm({
    programmed: props.dailyProgram.programmed,
    backwardness: props.dailyProgram.backwardness,
    advanced: props.dailyProgram.advanced,
    total_produced: props.dailyProgram.total_produced || 0,
    total_rejected: props.dailyProgram.total_rejected || 0,
    reason: '',
    notes: '',
});

const hasChanges = computed(() => {
    return form.programmed !== props.dailyProgram.programmed ||
           form.backwardness !== props.dailyProgram.backwardness ||
           form.advanced !== props.dailyProgram.advanced ||
           form.total_produced !== (props.dailyProgram.total_produced || 0) ||
           form.total_rejected !== (props.dailyProgram.total_rejected || 0);
});

function submit() {
    form.put(route('ingeniero-procesos.daily-programs.update', props.dailyProgram.id), {
        onSuccess: () => {
            form.reset('reason', 'notes');
        },
    });
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('es-MX');
}
</script>
