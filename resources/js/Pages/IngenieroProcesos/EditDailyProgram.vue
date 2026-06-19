<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <IngenieroProcesosSidebar />
        
        <div class="p-6 ml-16">
            <div class="mb-6">
                <Link :href="route('ingeniero-procesos.production-adjustments')" 
                      class="mb-4 inline-block font-semibold" style="color: #174060;">
                    ← Volver al historial de ajustes
                </Link>
                <h1 class="text-3xl font-bold" style="color: #0b2a40;">Editar Programa Diario</h1>
            </div>

            <!-- Información del programa -->
            <div class="rounded-lg p-6 mb-6" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                <h2 class="text-xl font-semibold mb-4" style="color: #0b2a40;">Información del Programa</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm" style="color: #6a8090;">Fecha</label>
                        <p style="color: #0c1c28; font-weight: 600;">{{ dailyProgram.date_formatted }}</p>
                    </div>
                    <div>
                        <label class="block text-sm" style="color: #6a8090;">Turno</label>
                        <p style="color: #0c1c28; font-weight: 600;" class="capitalize">{{ dailyProgram.shift }}</p>
                    </div>
                    <div>
                        <label class="block text-sm" style="color: #6a8090;">Centro de Trabajo</label>
                        <p style="color: #0c1c28; font-weight: 600;">{{ dailyProgram.work_center?.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm" style="color: #6a8090;">Programa</label>
                        <p style="color: #0c1c28; font-weight: 600;">{{ dailyProgram.program?.codigo || 'No asignado' }}</p>
                    </div>
                </div>
            </div>

            <!-- Formulario de ajuste -->
            <div class="rounded-lg p-6 mb-6" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                <h2 class="text-xl font-semibold mb-4" style="color: #0b2a40;">Ajustar Valores</h2>
                
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-semibold mb-2" style="color: #4e6070;">Programado</label>
                            <input type="number" v-model.number="form.programmed" min="0"
                                   class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none"
                                   style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;">
                            <p class="text-xs mt-1" style="color: #6a8090;">Valor actual: {{ dailyProgram.programmed }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2" style="color: #4e6070;">Atraso (Backwardness)</label>
                            <input type="number" v-model.number="form.backwardness" min="0"
                                   class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none"
                                   style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;">
                            <p class="text-xs mt-1" style="color: #6a8090;">Valor actual: {{ dailyProgram.backwardness }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2" style="color: #4e6070;">Adelanto (Advanced)</label>
                            <input type="number" v-model.number="form.advanced" min="0"
                                   class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none"
                                   style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;">
                            <p class="text-xs mt-1" style="color: #6a8090;">Valor actual: {{ dailyProgram.advanced }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-semibold mb-2" style="color: #4e6070;">Total Fabricado</label>
                            <input type="number" v-model.number="form.total_produced" min="0"
                                   class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none"
                                   style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;">
                            <p class="text-xs mt-1" style="color: #6a8090;">Valor actual: {{ dailyProgram.total_produced || 0 }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2" style="color: #4e6070;">Total Rechazado</label>
                            <input type="number" v-model.number="form.total_rejected" min="0"
                                   class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none"
                                   style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;">
                            <p class="text-xs mt-1" style="color: #6a8090;">Valor actual: {{ dailyProgram.total_rejected || 0 }}</p>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-semibold mb-2" style="color: #4e6070;">Motivo del ajuste *</label>
                        <textarea v-model="form.reason" rows="3" required
                                  class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none"
                                  style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;"
                                  placeholder="Explique el motivo del ajuste..."></textarea>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-semibold mb-2" style="color: #4e6070;">Notas adicionales</label>
                        <textarea v-model="form.notes" rows="2"
                                  class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none"
                                  style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;"
                                  placeholder="Información adicional opcional..."></textarea>
                    </div>

                    <div class="flex space-x-4">
                        <button type="submit" 
                                :disabled="form.processing"
                                class="px-6 py-2 rounded-lg transition font-semibold text-sm disabled:opacity-50"
                                style="background: #0a7c3e; color: #fff;">
                            {{ form.processing ? 'Guardando...' : 'Guardar Ajuste' }}
                        </button>
                        <Link :href="route('ingeniero-procesos.production-adjustments')"
                              class="px-6 py-2 rounded-lg transition font-semibold text-sm"
                              style="background: #fff; color: #0b2a40; border: 1px solid #d4dee8;">
                            Cancelar
                        </Link>
                    </div>
                </form>
            </div>

            <!-- Resumen de cambios -->
            <div v-if="hasChanges" class="rounded-lg p-6" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                <h2 class="text-xl font-semibold mb-4" style="color: #0b2a40;">Resumen de Cambios</h2>
                <div class="space-y-2">
                    <div v-if="form.programmed !== dailyProgram.programmed" class="flex justify-between" style="color: #0c1c28;">
                        <span>Programado:</span>
                        <span>{{ dailyProgram.programmed }} → {{ form.programmed }}
                            ({{ form.programmed > dailyProgram.programmed ? '+' : '' }}{{ form.programmed - dailyProgram.programmed }})</span>
                    </div>
                    <div v-if="form.backwardness !== dailyProgram.backwardness" class="flex justify-between" style="color: #0c1c28;">
                        <span>Atraso:</span>
                        <span>{{ dailyProgram.backwardness }} → {{ form.backwardness }}
                            ({{ form.backwardness > dailyProgram.backwardness ? '+' : '' }}{{ form.backwardness - dailyProgram.backwardness }})</span>
                    </div>
                    <div v-if="form.advanced !== dailyProgram.advanced" class="flex justify-between" style="color: #0c1c28;">
                        <span>Adelanto:</span>
                        <span>{{ dailyProgram.advanced }} → {{ form.advanced }}
                            ({{ form.advanced > dailyProgram.advanced ? '+' : '' }}{{ form.advanced - dailyProgram.advanced }})</span>
                    </div>
                    <div v-if="form.total_produced !== (dailyProgram.total_produced || 0)" class="flex justify-between" style="color: #0c1c28;">
                        <span>Total Fabricado:</span>
                        <span>{{ dailyProgram.total_produced || 0 }} → {{ form.total_produced }}
                            ({{ form.total_produced > (dailyProgram.total_produced || 0) ? '+' : '' }}{{ form.total_produced - (dailyProgram.total_produced || 0) }})</span>
                    </div>
                    <div v-if="form.total_rejected !== (dailyProgram.total_rejected || 0)" class="flex justify-between" style="color: #0c1c28;">
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
</script>
