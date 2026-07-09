<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <IngenieroProcesosSidebar />
        
        <div class="p-6 ml-16">
            <div class="mb-6 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold" style="color: #0b2a40;">Configuración de Balance Inicial</h1>
                    <p class="text-sm mt-2" style="color: #6a8090;">Gestione los atrasos y adelantos iniciales por centro de trabajo</p>
                </div>
                <button @click="goToHistory"
                        class="px-4 py-2 rounded-lg font-semibold text-sm transition"
                        style="background: #fff; color: #0b2a40; border: 1px solid #d4dee8;">
                    📜 Ver Historial
                </button>
            </div>

            <!-- Tabla de Centros de Trabajo -->
            <div class="rounded-lg overflow-hidden" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                <table class="w-full">
                    <thead>
                        <tr style="background: #0b2a40;">
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Centro de Trabajo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Fase</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Atraso Acumulado</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Adelanto Acumulado</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Último Cálculo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="center in workCenters" :key="center.id" style="border-bottom: 1px solid #e8eff4;">
                            <td class="px-4 py-3 text-sm" style="color: #0c1c28; font-weight: 600;">{{ center.name }}</td>
                            <td class="px-4 py-3 text-sm" style="color: #0c1c28; font-weight: 600;">Fase {{ center.phase }}</td>
                            <td class="px-4 py-3 text-sm" style="color: #a87000; font-weight: 600;">{{ center.accumulated_backwardness }}</td>
                            <td class="px-4 py-3 text-sm" style="color: #0a7c3e; font-weight: 600;">{{ center.accumulated_advanced }}</td>
                            <td class="px-4 py-3 text-sm" style="color: #6a8090; font-weight: 600;">{{ center.last_calculated_at_formatted || 'Nunca' }}</td>
                            <td class="px-4 py-3 text-sm">
                                <button @click="editCenter(center.id)"
                                        class="px-3 py-1 rounded text-sm font-semibold transition"
                                        style="background: #0b2a40; color: #fff;">
                                    Editar
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="workCenters.length === 0" class="text-center py-8">
                    <p style="color: #6a8090; font-weight: 600;">No hay centros de trabajo registrados</p>
                </div>
            </div>

            <!-- Información -->
            <div class="mt-6 rounded-lg p-4" style="background: #f4f7fa; border: 1px solid #e8eff4;">
                <h3 class="text-sm font-semibold mb-2" style="color: #0b2a40;">ℹ️ Información Importante</h3>
                <ul class="text-sm space-y-1" style="color: #6a8090;">
                    <li>• Los valores configurados aquí se usarán como balance inicial al crear nuevos programas diarios.</li>
                    <li>• El sistema de balance diario continuará actualizando estos valores automáticamente.</li>
                    <li>• Todos los cambios quedan registrados en el historial para auditoría.</li>
                    <li>• Use esta sección solo para configuración inicial o correcciones manuales.</li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';

const props = defineProps({
    workCenters: Array,
});

function editCenter(centerId) {
    router.get(route('ingeniero-procesos.work-center-balances.edit', centerId));
}

function goToHistory() {
    router.get(route('ingeniero-procesos.work-center-balances.history'));
}
</script>
