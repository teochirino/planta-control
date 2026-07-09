<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <IngenieroProcesosSidebar />
        
        <div class="p-6 ml-16">
            <div class="mb-6">
                <button @click="goBack"
                        class="text-sm font-semibold mb-2 transition"
                        style="color: #0b2a40;">
                    ← Volver a la lista
                </button>
                <h1 class="text-3xl font-bold" style="color: #0b2a40;">Editar Balance - {{ workCenter.name }}</h1>
                <p class="text-sm mt-2" style="color: #6a8090;">Fase {{ workCenter.phase }}</p>
            </div>

            <div class="rounded-lg p-6" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-semibold mb-2" style="color: #4e6070;">Atraso Acumulado</label>
                            <input type="number" v-model.number="form.accumulated_backwardness" min="0"
                                   class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none"
                                   style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;">
                            <p class="text-xs mt-1" style="color: #6a8090;">Valor actual: {{ workCenter.accumulated_backwardness }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2" style="color: #4e6070;">Adelanto Acumulado</label>
                            <input type="number" v-model.number="form.accumulated_advanced" min="0"
                                   class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none"
                                   style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;">
                            <p class="text-xs mt-1" style="color: #6a8090;">Valor actual: {{ workCenter.accumulated_advanced }}</p>
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

                    <div class="mb-6 p-4 rounded-lg" style="background: #f4f7fa; border: 1px solid #e8eff4;">
                        <h3 class="text-sm font-semibold mb-2" style="color: #0b2a40;">Último Cálculo Automático</h3>
                        <p class="text-sm" style="color: #6a8090;">
                            {{ workCenter.last_calculated_at_formatted || 'No ha sido calculado automáticamente' }}
                        </p>
                    </div>

                    <div class="flex space-x-4">
                        <button type="submit" 
                                :disabled="form.processing"
                                class="px-6 py-2 rounded-lg transition font-semibold text-sm disabled:opacity-50"
                                style="background: #0a7c3e; color: #fff;">
                            {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                        </button>
                        <button type="button" @click="goBack"
                                class="px-6 py-2 rounded-lg transition font-semibold text-sm"
                                style="background: #fff; color: #0b2a40; border: 1px solid #d4dee8;">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Alerta de información -->
            <div class="mt-6 rounded-lg p-4" style="background: #fff3cd; border: 1px solid #ffc107;">
                <h3 class="text-sm font-semibold mb-2" style="color: #856404;">⚠️ Advertencia</h3>
                <ul class="text-sm space-y-1" style="color: #856404;">
                    <li>• Este ajuste modificará el balance acumulado del centro de trabajo.</li>
                    <li>• Los nuevos valores se usarán en los próximos programas diarios creados.</li>
                    <li>• Este cambio quedará registrado en el historial de auditoría.</li>
                    <li>• Asegúrese de tener una justificación válida para este ajuste.</li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3';
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';

const props = defineProps({
    workCenter: Object,
});

const form = useForm({
    accumulated_backwardness: props.workCenter.accumulated_backwardness,
    accumulated_advanced: props.workCenter.accumulated_advanced,
    reason: '',
    notes: '',
});

function submit() {
    form.put(route('ingeniero-procesos.work-center-balances.update', props.workCenter.id), {
        onSuccess: () => {
            router.get(route('ingeniero-procesos.work-center-balances.index'));
        },
    });
}

function goBack() {
    router.get(route('ingeniero-procesos.work-center-balances.index'));
}
</script>
