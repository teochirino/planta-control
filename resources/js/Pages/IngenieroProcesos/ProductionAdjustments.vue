<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <IngenieroProcesosSidebar />
        
        <div class="p-6 ml-16">
            <div class="mb-6">
                <h1 class="text-3xl font-bold" style="color: #0b2a40;">Historial de Ajustes</h1>
            </div>

            <!-- Filtros -->
            <div class="rounded-lg p-4 mb-6" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1" style="color: #4e6070;">Centro de Trabajo</label>
                        <select v-model="filters.work_center_id" @change="applyFilters"
                                class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none"
                                style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;">
                            <option value="">Todos los centros</option>
                            <option v-for="center in workCenters" :key="center.id" :value="center.id">
                                {{ center.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1" style="color: #4e6070;">Fecha Desde</label>
                        <input type="date" v-model="filters.date_from" @change="applyFilters"
                               class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none"
                               style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1" style="color: #4e6070;">Fecha Hasta</label>
                        <input type="date" v-model="filters.date_to" @change="applyFilters"
                               class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none"
                               style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;">
                    </div>
                </div>
            </div>

            <!-- Tabla de ajustes -->
            <div class="rounded-lg overflow-hidden" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                <table class="w-full">
                    <thead>
                        <tr style="background: #0b2a40;">
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Fecha/Hora</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Usuario</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Categoría</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Campo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Programa</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Centro</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Anterior</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Nuevo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Diferencia</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="adjustment in adjustments.data" :key="adjustment.id" style="border-bottom: 1px solid #e8eff4;">
                            <td class="px-4 py-3 text-sm" style="color: #0c1c28; font-weight: 600;">{{ formatDateTime(adjustment.created_at) }}</td>
                            <td class="px-4 py-3 text-sm" style="color: #0c1c28; font-weight: 600;">{{ adjustment.adjusted_by_name || '-' }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span :class="getCategoryClass(adjustment.adjustment_category)"
                                      class="px-2 py-1 rounded text-xs font-semibold">
                                    {{ getCategoryLabel(adjustment.adjustment_category) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm" style="color: #0c1c28; font-weight: 600;">{{ adjustment.field_label }}</td>
                            <td class="px-4 py-3 text-sm" style="color: #0c1c28; font-weight: 600;">
                                {{ adjustment.source_program?.codigo || adjustment.target_program?.codigo || '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm" style="color: #0c1c28; font-weight: 600;">{{ adjustment.work_center?.name }}</td>
                            <td class="px-4 py-3 text-sm" style="color: #0c1c28; font-weight: 600;">{{ adjustment.previous_value }}</td>
                            <td class="px-4 py-3 text-sm" style="color: #0c1c28; font-weight: 600;">{{ adjustment.new_value }}</td>
                            <td class="px-4 py-3 text-sm font-bold"
                                :style="adjustment.difference > 0 ? 'color: #0a7c3e;' : 
                                       adjustment.difference < 0 ? 'color: #ba2418;' : 'color: #6a8090;'">
                                {{ adjustment.difference > 0 ? '+' : '' }}{{ adjustment.difference }}
                            </td>
                            <td class="px-4 py-3 text-sm max-w-xs truncate" :title="adjustment.reason" style="color: #0c1c28; font-weight: 600;">{{ adjustment.reason }}</td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="adjustments.data.length === 0" class="text-center py-8">
                    <p style="color: #6a8090; font-weight: 600;">No hay ajustes registrados</p>
                </div>
            </div>

            <!-- Paginación -->
            <div v-if="adjustments.links" class="mt-6 flex justify-center">
                <div class="flex space-x-2">
                    <template v-for="(link, index) in adjustments.links" :key="index">
                        <button v-if="link.url" 
                                @click="goToPage(link.url)"
                                :style="link.active ? 'background: #0b2a40; color: #fff;' : 'background: #fff; color: #0b2a40; border: 1px solid #d4dee8;'"
                                class="px-4 py-2 rounded font-semibold text-sm"
                                v-html="link.label">
                        </button>
                        <span v-else class="px-4 py-2" style="color: #6a8090;" v-html="link.label"></span>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';
import { ref } from 'vue';

const props = defineProps({
    adjustments: Object,
    filters: Object,
    workCenters: Array,
});

const filters = ref({
    work_center_id: props.filters.work_center_id || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

function applyFilters() {
    router.get(route('ingeniero-procesos.production-adjustments'), filters.value, {
        preserveState: true,
    });
}

function goToPage(url) {
    router.get(url, filters.value, {
        preserveState: true,
    });
}

function formatDateTime(date) {
    return new Date(date).toLocaleString('es-MX');
}

function getCategoryClass(category) {
    switch (category) {
        case 'correction': return 'bg-yellow-600';
        case 'transfer': return 'bg-blue-600';
        case 'discovery': return 'bg-purple-600';
        default: return 'bg-gray-600';
    }
}

function getCategoryLabel(category) {
    switch (category) {
        case 'correction': return 'Corrección';
        case 'transfer': return 'Transferencia';
        case 'discovery': return 'Descubrimiento';
        default: return category;
    }
}

function getFieldLabel(field) {
    switch (field) {
        case 'backwardness': return 'Atraso';
        case 'advanced': return 'Adelanto';
        case 'total_produced': return 'Total Fabricado';
        case 'total_rejected': return 'Total Rechazado';
        case 'programmed': return 'Programado';
        default: return field;
    }
}
</script>
