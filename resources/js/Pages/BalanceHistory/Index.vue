<script setup>
import { ref, onMounted } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import SupervisorSidebar from '@/Components/SupervisorSidebar.vue'
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue'

const page = usePage()
const props = defineProps({
    history: Object,
    workCenters: Object,
    filters: Object
})

const filters = ref({
    work_center_id: props.filters?.work_center_id || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || ''
})

const applyFilters = () => {
    const currentUrl = window.location.pathname
    console.log('Applying filters:', filters.value)
    console.log('Current URL:', currentUrl)
    router.get(currentUrl, filters.value, {
        preserveState: true,
        preserveScroll: true
    })
}

const clearFilters = () => {
    filters.value = {
        work_center_id: '',
        date_from: '',
        date_to: ''
    }
    const currentUrl = window.location.pathname
    router.get(currentUrl, filters.value, {
        preserveState: true,
        preserveScroll: true
    })
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('es-MX', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const isSupervisor = () => {
    return route().current('supervisor.*')
}

const isIngeniero = () => {
    return route().current('ingeniero-procesos.*')
}
</script>

<template>
    <Head title="Historial de Procesar Balance" />

    <div class="min-h-screen bg-gray-50">
        <SupervisorSidebar v-if="isSupervisor()" />
        <IngenieroProcesosSidebar v-if="isIngeniero()" />
        <div class="ml-64 p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Historial de Procesar Balance</h1>
                <p class="mt-2 text-gray-600">Registro de todos los procesamientos de balance realizados</p>
            </div>

            <!-- Filtros -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Centro de Trabajo</label>
                        <select 
                            v-model="filters.work_center_id"
                            @change="applyFilters"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Todos</option>
                            <option v-for="center in workCenters" :key="center.id" :value="center.id">
                                {{ center.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Desde</label>
                        <input 
                            type="date" 
                            v-model="filters.date_from" 
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Hasta</label>
                        <input 
                            type="date" 
                            v-model="filters.date_to" 
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                    <div class="flex items-end gap-2">
                        <button 
                            @click="applyFilters"
                            class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition"
                        >
                            Filtrar
                        </button>
                        <button 
                            @click="clearFilters"
                            class="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition"
                        >
                            Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla de historial -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Centro</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Procesado por</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Programado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Atrasos Inicio</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Adelantos Inicio</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total a Producir</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Fabricado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Rechazado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Atrasos Final</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Adelantos Final</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="record in history.data" :key="record.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatDate(record.processed_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ record.work_center?.name || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ record.processed_by?.name || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    {{ record.programmed }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    <span :class="record.backwardness > 0 ? 'text-red-600 font-semibold' : ''">
                                        {{ record.backwardness }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    <span :class="record.advanced > 0 ? 'text-green-600 font-semibold' : ''">
                                        {{ record.advanced }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    {{ record.total_to_produce }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    {{ record.total_produced }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    <span :class="record.total_rejected > 0 ? 'text-red-600' : ''">
                                        {{ record.total_rejected }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    <span :class="record.final_backwardness > 0 ? 'text-red-600 font-semibold' : ''">
                                        {{ record.final_backwardness }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    <span :class="record.final_advanced > 0 ? 'text-green-600 font-semibold' : ''">
                                        {{ record.final_advanced }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="history.data.length === 0">
                                <td colspan="11" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No hay registros de procesamiento de balance
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="history.links" class="bg-white px-4 py-3 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Mostrando {{ history.from }} a {{ history.to }} de {{ history.total }} registros
                        </div>
                        <div class="flex gap-2">
                            <template v-for="link in history.links" :key="link.label">
                                <Link 
                                    v-if="link.url" 
                                    :href="link.url"
                                    v-html="link.label"
                                    class="px-3 py-1 border rounded hover:bg-gray-50"
                                    :class="{ 'bg-blue-50 text-blue-600 border-blue-300': link.active }"
                                />
                                <span 
                                    v-else
                                    v-html="link.label"
                                    class="px-3 py-1 border rounded text-gray-400"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
