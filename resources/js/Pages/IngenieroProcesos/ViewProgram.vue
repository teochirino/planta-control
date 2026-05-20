<template>
    <div class="min-h-screen bg-gray-900 flex">
        <IngenieroProcesosSidebar />
        
        <div class="flex-1 p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-white">Programa {{ program.codigo }}</h1>
                    <p class="text-gray-400 mt-1">Creado por {{ program.creator?.name }}</p>
                </div>
                <Link :href="route('ingeniero-procesos.index')" 
                      class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                    Volver
                </Link>
            </div>
            
            <!-- Fechas de Fases -->
            <div class="grid grid-cols-4 gap-4 mb-6">
                <div class="bg-gray-800 rounded-lg p-4">
                    <h3 class="text-gray-400 text-sm uppercase mb-2">Fase 1</h3>
                    <p class="text-white font-semibold">{{ program.fecha_fase1_formatted }}</p>
                </div>
                <div class="bg-gray-800 rounded-lg p-4">
                    <h3 class="text-gray-400 text-sm uppercase mb-2">Fase 2</h3>
                    <p class="text-white font-semibold">{{ program.fecha_fase2_formatted }}</p>
                </div>
                <div class="bg-gray-800 rounded-lg p-4">
                    <h3 class="text-gray-400 text-sm uppercase mb-2">Fase 3</h3>
                    <p class="text-white font-semibold">{{ program.fecha_fase3_formatted }}</p>
                </div>
                <div class="bg-gray-800 rounded-lg p-4">
                    <h3 class="text-gray-400 text-sm uppercase mb-2">Fase 4 (Entrega)</h3>
                    <p class="text-white font-semibold">{{ program.fecha_fase4_formatted }}</p>
                </div>
            </div>
            
            <!-- Tabla por Fases -->
            <div v-for="(phaseDetails, phase) in details" :key="phase" class="mb-6">
                <h2 class="text-xl font-bold text-white mb-4">Fase {{ phase }}</h2>
                
                <div class="bg-gray-800 rounded-lg overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Centro de Trabajo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Modelo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Cantidad Solicitada</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Piezas Totales</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Tiempo Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <tr v-for="detail in phaseDetails" :key="detail.id" class="hover:bg-gray-750">
                                <td class="px-6 py-4 text-white">{{ detail.work_center }}</td>
                                <td class="px-6 py-4 text-white">{{ detail.modelo }}</td>
                                <td class="px-6 py-4 text-white">{{ detail.cantidad_solicitada }}</td>
                                <td class="px-6 py-4 text-white">{{ detail.total_pieces }}</td>
                                <td class="px-6 py-4 text-white">{{ detail.total_time }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Totalizador por Fecha y Centro de Trabajo -->
            <div class="mt-8">
                <h2 class="text-2xl font-bold text-white mb-6">Resumen por Fecha y Centro de Trabajo</h2>
                
                <div v-for="(workCenters, date) in totalsByDate" :key="date" class="mb-6">
                    <div class="bg-blue-900 rounded-lg p-4 mb-3">
                        <h3 class="text-lg font-bold text-white">Fecha: {{ date }}</h3>
                    </div>
                    
                    <div class="bg-gray-800 rounded-lg overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Centro de Trabajo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Piezas Totales</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Tiempo Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <tr v-for="(totals, workCenter) in workCenters" :key="workCenter" class="hover:bg-gray-750">
                                    <td class="px-6 py-4 text-white font-semibold">{{ workCenter }}</td>
                                    <td class="px-6 py-4 text-white">{{ totals.total_pieces }}</td>
                                    <td class="px-6 py-4 text-white">{{ totals.total_time }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';

defineProps({
    program: Object,
    details: Object,
    totalsByDate: Object,
});

function formatDate(date) {
    return new Date(date).toLocaleDateString('es-MX');
}
</script>
