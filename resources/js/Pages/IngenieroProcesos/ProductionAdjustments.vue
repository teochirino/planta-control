<template>
    <div class="min-h-screen bg-gray-900 flex">
        <IngenieroProcesosSidebar />
        
        <div class="flex-1 p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-white">Historial de Ajustes</h1>
            </div>

            <!-- Filtros -->
            <div class="bg-gray-800 rounded-lg p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-gray-300 text-sm mb-1">Centro de Trabajo</label>
                        <select v-model="filters.work_center_id" @change="applyFilters"
                                class="w-full bg-gray-700 text-white rounded px-3 py-2">
                            <option value="">Todos los centros</option>
                            <option v-for="center in workCenters" :key="center.id" :value="center.id">
                                {{ center.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-300 text-sm mb-1">Fecha Desde</label>
                        <input type="date" v-model="filters.date_from" @change="applyFilters"
                               class="w-full bg-gray-700 text-white rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-gray-300 text-sm mb-1">Fecha Hasta</label>
                        <input type="date" v-model="filters.date_to" @change="applyFilters"
                               class="w-full bg-gray-700 text-white rounded px-3 py-2">
                    </div>
                </div>
            </div>

            <!-- Tabla de ajustes -->
            <div class="bg-gray-800 rounded-lg overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Fecha/Hora</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Usuario</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Categoría</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Campo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Programa</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Centro</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Anterior</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Nuevo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Diferencia</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Motivo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <tr v-for="adjustment in adjustments.data" :key="adjustment.id" class="hover:bg-gray-750">
                            <td class="px-4 py-3 text-white text-sm">{{ formatDateTime(adjustment.created_at) }}</td>
                            <td class="px-4 py-3 text-white text-sm">{{ adjustment.adjusted_by_name || '-' }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span :class="getCategoryClass(adjustment.adjustment_category)"
                                      class="px-2 py-1 rounded text-xs">
                                    {{ getCategoryLabel(adjustment.adjustment_category) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-white text-sm">{{ adjustment.field_label }}</td>
                            <td class="px-4 py-3 text-white text-sm">
                                {{ adjustment.source_program?.codigo || adjustment.target_program?.codigo || '-' }}
                            </td>
                            <td class="px-4 py-3 text-white text-sm">{{ adjustment.work_center?.name }}</td>
                            <td class="px-4 py-3 text-white text-sm">{{ adjustment.previous_value }}</td>
                            <td class="px-4 py-3 text-white text-sm">{{ adjustment.new_value }}</td>
                            <td class="px-4 py-3 text-sm font-bold"
                                :class="adjustment.difference > 0 ? 'text-green-400' : 
                                       adjustment.difference < 0 ? 'text-red-400' : 'text-gray-400'">
                                {{ adjustment.difference > 0 ? '+' : '' }}{{ adjustment.difference }}
                            </td>
                            <td class="px-4 py-3 text-white text-sm max-w-xs truncate" :title="adjustment.reason">
                                {{ adjustment.reason }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="adjustments.data.length === 0" class="text-center py-8">
                    <p class="text-gray-400">No hay ajustes registrados</p>
                </div>
            </div>

            <!-- Paginación -->
            <div v-if="adjustments.links" class="mt-6 flex justify-center">
                <div class="flex space-x-2">
                    <template v-for="(link, index) in adjustments.links" :key="index">
                        <button v-if="link.url" 
                                @click="goToPage(link.url)"
                                :class="link.active ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'"
                                class="px-4 py-2 rounded"
                                v-html="link.label">
                        </button>
                        <span v-else class="px-4 py-2 text-gray-500" v-html="link.label"></span>
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
