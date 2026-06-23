<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import GerenteMantenimientoSidebar from '@/Components/GerenteMantenimientoSidebar.vue';

const toast = useToast();

const props = defineProps({
    workCenters: Array,
});

const selectedWorkCenter = ref('');
const startDate = ref('');
const endDate = ref('');
const loading = ref(false);
const reportData = ref(null);
const summary = ref(null);

// Inicializar fechas por defecto (semana actual)
const initDates = () => {
    const now = new Date();
    const startOfWeek = new Date(now);
    startOfWeek.setDate(now.getDate() - now.getDay());
    startOfWeek.setHours(0, 0, 0, 0);
    
    const endOfWeek = new Date(startOfWeek);
    endOfWeek.setDate(startOfWeek.getDate() + 6);
    endOfWeek.setHours(23, 59, 59, 999);
    
    startDate.value = startOfWeek.toISOString().split('T')[0];
    endDate.value = endOfWeek.toISOString().split('T')[0];
};

const generateReport = async () => {
    loading.value = true;
    
    try {
        const response = await axios.get(route('gerente-mantenimiento.export'), {
            params: {
                work_center_id: selectedWorkCenter.value || null,
                start_date: startDate.value,
                end_date: endDate.value,
                format: 'json',
            },
        });
        
        reportData.value = response.data.report;
        summary.value = response.data.summary;
        
        toast.success('Reporte generado correctamente');
    } catch (error) {
        console.error('Error al generar reporte:', error);
        toast.error('Error al generar el reporte');
    } finally {
        loading.value = false;
    }
};

const exportToExcel = async () => {
    loading.value = true;
    
    try {
        const url = route('gerente-mantenimiento.export', {
            work_center_id: selectedWorkCenter.value || null,
            start_date: startDate.value,
            end_date: endDate.value,
            format: 'excel',
        });
        
        window.open(url, '_blank');
        toast.success('Reporte Excel descargado');
    } catch (error) {
        console.error('Error al exportar:', error);
        toast.error('Error al exportar a Excel');
    } finally {
        loading.value = false;
    }
};

const exportToCSV = async () => {
    loading.value = true;
    
    try {
        const url = route('gerente-mantenimiento.export', {
            work_center_id: selectedWorkCenter.value || null,
            start_date: startDate.value,
            end_date: endDate.value,
            format: 'csv',
        });
        
        window.open(url, '_blank');
        toast.success('Reporte CSV descargado');
    } catch (error) {
        console.error('Error al exportar:', error);
        toast.error('Error al exportar a CSV');
    } finally {
        loading.value = false;
    }
};

const setQuickDate = (days) => {
    const now = new Date();
    const start = new Date(now);
    start.setDate(now.getDate() - days);
    start.setHours(0, 0, 0, 0);
    
    const end = new Date(now);
    end.setHours(23, 59, 59, 999);
    
    startDate.value = start.toISOString().split('T')[0];
    endDate.value = end.toISOString().split('T')[0];
};

// Inicializar fechas al montar
initDates();
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <GerenteMantenimientoSidebar />
        
        <div class="ml-0 lg:ml-64 p-6">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-3xl font-extrabold text-[#0b2a40]">Reportes de Horas Detenidas</h1>
                    <p class="text-[#4e6070] mt-1">Informe exportable de horas detenidas por máquina y total de planta</p>
                </div>

                <!-- Filtros -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                        <div>
                            <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Centro de Trabajo</label>
                            <select v-model="selectedWorkCenter" 
                                    class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1">
                                <option value="">Todos los centros</option>
                                <option v-for="wc in workCenters" :key="wc.id" :value="wc.id">
                                    {{ wc.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Fecha Inicio</label>
                            <input type="date" v-model="startDate" 
                                   class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1">
                        </div>
                        <div>
                            <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Fecha Fin</label>
                            <input type="date" v-model="endDate" 
                                   class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1">
                        </div>
                        <div>
                            <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Período Rápido</label>
                            <div class="flex gap-2 mt-1">
                                <button @click="setQuickDate(0)" 
                                        class="px-3 py-2 bg-[#f4f7fa] text-[#4e6070] border border-[#d4dee8] rounded-md text-xs font-bold hover:bg-[#e8edf2]">
                                    Hoy
                                </button>
                                <button @click="setQuickDate(7)" 
                                        class="px-3 py-2 bg-[#f4f7fa] text-[#4e6070] border border-[#d4dee8] rounded-md text-xs font-bold hover:bg-[#e8edf2]">
                                    7 días
                                </button>
                                <button @click="setQuickDate(30)" 
                                        class="px-3 py-2 bg-[#f4f7fa] text-[#4e6070] border border-[#d4dee8] rounded-md text-xs font-bold hover:bg-[#e8edf2]">
                                    30 días
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-3">
                        <button @click="generateReport" 
                                :disabled="loading"
                                class="px-6 py-2 bg-[#0b2a40] text-white rounded-md font-bold hover:opacity-85 disabled:opacity-50">
                            {{ loading ? 'Generando...' : 'Generar Reporte' }}
                        </button>
                        <button @click="exportToExcel" 
                                :disabled="loading || !reportData"
                                class="px-6 py-2 bg-green-600 text-white rounded-md font-bold hover:opacity-85 disabled:opacity-50">
                            Exportar Excel
                        </button>
                        <button @click="exportToCSV" 
                                :disabled="loading || !reportData"
                                class="px-6 py-2 bg-blue-600 text-white rounded-md font-bold hover:opacity-85 disabled:opacity-50">
                            Exportar CSV
                        </button>
                    </div>
                </div>

                <!-- Resultados del Reporte -->
                <div v-if="summary" class="space-y-6">
                    <!-- Resumen Total -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-lg font-extrabold text-[#0b2a40] mb-4">Resumen Total de Planta</h2>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="bg-[#f4f7fa] rounded-lg p-4">
                                <div class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Período</div>
                                <div class="text-sm font-semibold text-[#0b2a40] mt-1">
                                    {{ summary.period.start }} a {{ summary.period.end }}
                                </div>
                            </div>
                            <div class="bg-[#f4f7fa] rounded-lg p-4">
                                <div class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Total Máquinas</div>
                                <div class="text-2xl font-extrabold text-[#0b2a40] mt-1">{{ summary.total_machines }}</div>
                            </div>
                            <div class="bg-[#f4f7fa] rounded-lg p-4">
                                <div class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Total Horas Detenidas</div>
                                <div class="text-2xl font-extrabold text-red-600 mt-1">{{ summary.total_hours }}h</div>
                            </div>
                            <div class="bg-[#f4f7fa] rounded-lg p-4">
                                <div class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Horas Confirmadas</div>
                                <div class="text-2xl font-extrabold text-green-600 mt-1">{{ summary.total_confirmed_hours }}h</div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Detalles -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-[#d4dee8]">
                            <h2 class="text-lg font-extrabold text-[#0b2a40]">Detalle por Máquina</h2>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-[#f4f7fa]">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-bold tracking-widest uppercase text-[#4e6070]">Máquina</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold tracking-widest uppercase text-[#4e6070]">Centro</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold tracking-widest uppercase text-[#4e6070]">Estado</th>
                                        <th class="px-6 py-3 text-right text-xs font-bold tracking-widest uppercase text-[#4e6070]">Averías</th>
                                        <th class="px-6 py-3 text-right text-xs font-bold tracking-widest uppercase text-[#4e6070]">Minutos</th>
                                        <th class="px-6 py-3 text-right text-xs font-bold tracking-widest uppercase text-[#4e6070]">Horas</th>
                                        <th class="px-6 py-3 text-right text-xs font-bold tracking-widest uppercase text-[#4e6070]">Min. Confirmados</th>
                                        <th class="px-6 py-3 text-right text-xs font-bold tracking-widest uppercase text-[#4e6070]">Horas Confirmadas</th>
                                        <th class="px-6 py-3 text-center text-xs font-bold tracking-widest uppercase text-[#4e6070]">Pendientes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#d4dee8]">
                                    <tr v-for="item in reportData" :key="item.machine" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 font-bold text-[#0b2a40]">{{ item.machine }}</td>
                                        <td class="px-6 py-4 text-sm text-[#4e6070]">{{ item.work_center }}</td>
                                        <td class="px-6 py-4">
                                            <span :class="{
                                                'bg-green-100 text-green-700': item.state === 'operativo',
                                                'bg-red-100 text-red-700': item.state === 'averiado',
                                                'bg-yellow-100 text-yellow-700': item.state === 'mantenimiento'
                                            }" class="px-3 py-1 rounded-full text-xs font-bold">
                                                {{ item.state }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right font-semibold text-[#0b2a40]">{{ item.total_breakdowns }}</td>
                                        <td class="px-6 py-4 text-right font-semibold text-[#0b2a40]">{{ item.total_minutes }}</td>
                                        <td class="px-6 py-4 text-right font-bold text-[#0b2a40]">{{ item.total_hours }}h</td>
                                        <td class="px-6 py-4 text-right font-semibold text-[#0b2a40]">{{ item.confirmed_minutes }}</td>
                                        <td class="px-6 py-4 text-right font-bold text-green-600">{{ item.confirmed_hours }}h</td>
                                        <td class="px-6 py-4 text-center">
                                            <span v-if="item.pending_confirmations > 0" 
                                                  class="px-3 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-600">
                                                {{ item.pending_confirmations }}
                                            </span>
                                            <span v-else class="text-gray-400">0</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Estado vacío -->
                <div v-else class="bg-white rounded-xl shadow-sm p-12 text-center">
                    <div class="text-gray-500 mb-4">
                        <svg class="w-16 h-16 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="text-gray-500 font-semibold">Selecciona un período y genera el reporte</div>
                </div>
            </div>
        </div>
    </div>
</template>
