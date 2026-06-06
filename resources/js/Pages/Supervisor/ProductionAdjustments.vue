<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <SupervisorSidebar />
        
        <div class="p-6 ml-16">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-[#0b2a40]">Historial de Ajustes</h1>
            </div>

            <!-- Filtros -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1 text-[#4e6070]">Centro de Trabajo</label>
                        <select v-model="filters.work_center_id" @change="applyFilters"
                                class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none bg-white text-[#0c1c28] border border-[#d4dee8]">
                            <option value="">Todos los centros</option>
                            <option v-for="center in workCenters" :key="center.id" :value="center.id">
                                {{ center.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1 text-[#4e6070]">Fecha Desde</label>
                        <input type="date" v-model="filters.date_from" @change="applyFilters"
                               class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none bg-white text-[#0c1c28] border border-[#d4dee8]">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1 text-[#4e6070]">Fecha Hasta</label>
                        <input type="date" v-model="filters.date_to" @change="applyFilters"
                               class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none bg-white text-[#0c1c28] border border-[#d4dee8]">
                    </div>
                </div>
            </div>

            <!-- Tabla de ajustes -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="bg-[#0b2a40]">
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-white">Fecha/Hora</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-white">Usuario</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-white">Categoría</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-white">Campo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-white">Programa</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-white">Centro</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-white">Anterior</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-white">Nuevo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-white">Diferencia</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-white">Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="adjustment in adjustments.data" :key="adjustment.id" class="border-b border-[#e8eff4]">
                            <td class="px-4 py-3 text-sm text-[#0c1c28] font-semibold">{{ formatDateTime(adjustment.created_at) }}</td>
                            <td class="px-4 py-3 text-sm text-[#0c1c28] font-semibold">{{ adjustment.adjusted_by_name || '-' }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span :class="getCategoryClass(adjustment.adjustment_category)"
                                      class="px-2 py-1 rounded text-xs font-semibold">
                                    {{ getCategoryLabel(adjustment.adjustment_category) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-[#0c1c28] font-semibold">{{ adjustment.field_label }}</td>
                            <td class="px-4 py-3 text-sm text-[#0c1c28] font-semibold">
                                {{ adjustment.source_program?.codigo || adjustment.target_program?.codigo || '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-[#0c1c28] font-semibold">{{ adjustment.work_center?.name }}</td>
                            <td class="px-4 py-3 text-sm text-[#0c1c28] font-semibold">{{ adjustment.previous_value }}</td>
                            <td class="px-4 py-3 text-sm text-[#0c1c28] font-semibold">{{ adjustment.new_value }}</td>
                            <td class="px-4 py-3 text-sm font-bold"
                                :class="adjustment.difference > 0 ? 'text-[#0a7c3e]' : 
                                       adjustment.difference < 0 ? 'text-[#ba2418]' : 'text-[#6a8090]'">
                                {{ adjustment.difference > 0 ? '+' : '' }}{{ adjustment.difference }}
                            </td>
                            <td class="px-4 py-3 text-sm max-w-xs truncate text-[#0c1c28] font-semibold" :title="adjustment.reason">{{ adjustment.reason }}</td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="adjustments.data.length === 0" class="text-center py-8">
                    <p class="text-[#6a8090] font-semibold">No hay ajustes registrados</p>
                </div>
            </div>

            <!-- Paginación -->
            <div v-if="adjustments.links" class="mt-6 flex justify-center">
                <div class="flex space-x-2">
                    <template v-for="(link, index) in adjustments.links" :key="index">
                        <button v-if="link.url" 
                                @click="goToPage(link.url)"
                                :class="link.active ? 'bg-[#0b2a40] text-white' : 'bg-white text-[#0b2a40] border border-[#d4dee8]'"
                                class="px-4 py-2 rounded font-semibold text-sm"
                                v-html="link.label">
                        </button>
                        <span v-else class="px-4 py-2 text-[#6a8090]" v-html="link.label"></span>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import SupervisorSidebar from '@/Components/SupervisorSidebar.vue';
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
    router.get(route('supervisor.production-adjustments'), filters.value, {
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
