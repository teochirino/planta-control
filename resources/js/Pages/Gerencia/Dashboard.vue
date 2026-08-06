<!-- resources/js/Pages/Gerencia/Dashboard.vue -->
<template>
    <div class="min-h-screen bg-gray-900">
        <GerenciaSidebar />
        
        <div class="container mx-auto px-4 py-6 pt-16 max-w-7xl">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-white">{{ workCenterName }}</h1>
                    <p class="text-gray-400 text-sm mt-1">Dashboard de Gerencia - Monitoreo en tiempo real</p>
                </div>
                <div class="flex items-center gap-4">
                    <select v-model="selectedWorkCenterId" @change="cambiarCentro" class="select-custom">
                        <option v-for="wc in workCenters" :key="wc.id" :value="wc.id">
                            {{ wc.name }}
                        </option>
                    </select>
                    <select v-model="selectedShift" @change="cambiarTurno" class="select-custom">
                        <option value="">Todos los turnos</option>
                        <option value="matutino">Matutino</option>
                        <option value="vespertino">Vespertino</option>
                    </select>
                    <button @click="logout" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-medium transition">
                        Cerrar Sesión
                    </button>
                </div>
            </div>

            <!-- KPIs -->
            <div class="flex gap-4 mb-6 overflow-x-auto pb-2">
                <div class="kpi-badge">
                    <span class="text-gray-400 text-xs uppercase">Capacidad Instalada</span>
                    <span class="text-3xl font-bold text-white mt-1">{{ kpis.capacidad_instalada || 0 }}</span>
                </div>
                <div class="kpi-badge">
                    <span class="text-gray-400 text-xs uppercase">Programado</span>
                    <span class="text-3xl font-bold text-white mt-1">{{ kpis.programado || 0 }}</span>
                </div>
                <div class="kpi-badge">
                    <span class="text-gray-400 text-xs uppercase">Atrasado</span>
                    <span class="text-3xl font-bold text-white mt-1">{{ kpis.atrasado || 0 }}</span>
                </div>
                <div class="kpi-badge">
                    <span class="text-gray-400 text-xs uppercase">A Producir</span>
                    <span class="text-3xl font-bold text-white mt-1">{{ kpis.a_producir || 0 }}</span>
                </div>
                <div class="kpi-badge">
                    <span class="text-gray-400 text-xs uppercase">Piezas Producidas</span>
                    <span class="text-3xl font-bold text-white mt-1">{{ kpis.piezas_producidas || 0 }}</span>
                </div>
                <div class="kpi-badge">
                    <span class="text-gray-400 text-xs uppercase">Horas Extras</span>
                    <span class="text-3xl font-bold text-white mt-1">{{ kpis.horas_extras_status || 'NO' }}</span>
                </div>
                <div class="kpi-badge">
                    <span class="text-gray-400 text-xs uppercase">Horas Extras</span>
                    <span class="text-3xl font-bold text-white mt-1">{{ kpis.horas_extras || 0 }}</span>
                </div>
            </div>

            <!-- Grid Principal -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Columna Izquierda -->
                <div class="space-y-6">
                    <!-- Estado general -->
                    <div class="card">
                        <h3 class="text-sm font-semibold text-gray-400 uppercase mb-3">Estado general del área</h3>
                        <div class="text-center py-4">
                            <div class="inline-flex items-center justify-center w-24 h-24 rounded-full mb-3" :class="areaStatus.colorClass">
                                <span class="text-3xl font-bold text-white">{{ areaStatus.label }}</span>
                            </div>
                            <p class="text-white font-semibold text-lg">Operación normal y estable</p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-700">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-400 text-sm">Acceso</span>
                                <span class="text-white font-semibold">{{ currentTime }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-400 text-sm">Tiempo en estado</span>
                                <span class="text-white font-semibold">{{ areaStatus.time }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Calidad -->
                    <div class="card">
                        <h3 class="text-sm font-semibold text-gray-400 uppercase mb-4">Calidad y trabajos</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center p-3 bg-gray-800 rounded-lg">
                                <p class="text-gray-400 text-xs uppercase mb-1">Piezas Rechazadas</p>
                                <p class="text-3xl font-bold text-white">{{ qualityMetrics.piezas_rechazadas || 0 }}</p>
                            </div>
                            <div class="text-center p-3 bg-gray-800 rounded-lg">
                                <p class="text-gray-400 text-xs uppercase mb-1">Rechazos Garantía</p>
                                <p class="text-3xl font-bold text-white">{{ qualityMetrics.rechazos_garantia || 0 }}</p>
                            </div>
                            <div class="text-center p-3 bg-gray-800 rounded-lg">
                                <p class="text-gray-400 text-xs uppercase mb-1">Inspecciones</p>
                                <p class="text-3xl font-bold text-white">{{ qualityMetrics.inspecciones_realizadas || 0 }}</p>
                            </div>
                            <div class="text-center p-3 bg-gray-800 rounded-lg">
                                <p class="text-gray-400 text-xs uppercase mb-1">Reprocesos Calidad</p>
                                <p class="text-3xl font-bold text-white">{{ qualityMetrics.reprocesos_calidad || 0 }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Central -->
                <div class="space-y-6">
                    <div class="card">
                        <h3 class="text-sm font-semibold text-gray-400 uppercase mb-3">Avance del turno</h3>
                        <p class="text-gray-400 text-xs mb-2">Hora actual, {{ currentDate }}</p>
                        <div class="digital-clock">{{ currentTime }}</div>
                        <div class="mt-4 grid grid-cols-2 gap-4">
                            <div class="text-center">
                                <p class="text-gray-400 text-xs uppercase">Turno</p>
                                <p class="text-white font-semibold text-lg">{{ shiftLabel }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-gray-400 text-xs uppercase">Producción</p>
                                <p class="text-white font-semibold text-lg">{{ kpis.piezas_producidas || 0 }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Paros -->
                    <div class="card">
                        <h3 class="text-sm font-semibold text-gray-400 uppercase mb-4">Paros y costo</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center p-4 bg-gray-800 rounded-lg">
                                <p class="text-gray-400 text-xs uppercase mb-2">Cantidad de paros</p>
                                <p class="text-5xl font-bold text-white">{{ totalStrikes }}</p>
                            </div>
                            <div class="text-center p-4 bg-gray-800 rounded-lg">
                                <p class="text-gray-400 text-xs uppercase mb-2">Costo de paros</p>
                                <p class="text-5xl font-bold text-red-400">${{ formatNumber(strikeCost) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha -->
                <div class="space-y-6">
                    <div class="card">
                        <h3 class="text-sm font-semibold text-gray-400 uppercase mb-3">Cámara en vivo</h3>
                        <div class="bg-gray-800 rounded-lg h-40 flex items-center justify-center">
                            <div class="text-center">
                                <svg class="w-16 h-16 mx-auto text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                <p class="text-gray-500 text-sm">EN VIVO</p>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <h3 class="text-sm font-semibold text-gray-400 uppercase mb-4">Cumplimiento reciente</h3>
                        <div class="space-y-2">
                            <div v-for="item in recentCompliance" :key="item.date" class="compliance-row">
                                <div class="flex-1">
                                    <p class="text-white font-semibold text-sm">{{ item.date }}</p>
                                    <div class="flex gap-4 text-xs text-gray-400 mt-1">
                                        <span>Prog. 1: {{ item.prog_1 }}</span>
                                        <span>Real 1: {{ item.real_1 }}</span>
                                        <span>Prog. 2: {{ item.prog_2 }}</span>
                                        <span>Real 2: {{ item.real_2 }}</span>
                                    </div>
                                </div>
                                <span class="compliance-badge" :class="`badge-${item.status}`">{{ item.compliance }}%</span>
                            </div>
                            <div v-if="recentCompliance.length === 0" class="text-center py-4">
                                <p class="text-gray-500 text-sm">No hay datos de cumplimiento reciente</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Observaciones -->
            <div class="card mt-6">
                <h3 class="text-sm font-semibold text-gray-400 uppercase mb-3">Información importante</h3>
                <textarea v-model="observaciones" class="w-full bg-gray-800 text-gray-300 rounded-lg p-3 border border-gray-700 focus:border-green-500 focus:outline-none" rows="3" placeholder="Observaciones operacionales del turno, incidencias relevantes, material pendiente de recibir, etc."></textarea>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import GerenciaSidebar from '@/Components/GerenciaSidebar.vue';

// Props del servidor
const props = defineProps({
    workCenters: Array,
    selectedWorkCenter: Object,
    selectedShift: String,
    kpis: Object,
    areaStatus: Object,
    qualityMetrics: Object,
    metrics: Object,
    recentCompliance: Array,
});

// Estado reactivo
const selectedWorkCenterId = ref(props.selectedWorkCenter?.id || null);
const selectedShift = ref(props.selectedShift || '');
const observaciones = ref('');
const workCenterName = computed(() => {
    const wc = props.workCenters?.find(w => w.id === selectedWorkCenterId.value);
    return wc?.name || props.selectedWorkCenter?.name || '';
});

const shiftLabel = computed(() => {
    const shifts = { matutino: 'Matutino', vespertino: 'Vespertino' };
    return shifts[selectedShift.value] || 'Todos';
});

const totalStrikes = computed(() => props.metrics?.total_strikes || 0);
const strikeCost = computed(() => props.metrics?.strike_cost || 0);

// Reloj
const currentTime = ref('');
const currentDate = ref('');
let clockInterval = null;

function updateClock() {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('es-MX');
    currentDate.value = now.toLocaleDateString('es-MX', { day: 'numeric', month: 'long', year: 'numeric' });
}

// Métodos
function cambiarCentro() {
    router.get(route('gerencia.dashboard.detalle'), {
        work_center_id: selectedWorkCenterId.value,
        shift: selectedShift.value,
    }, { preserveState: true, preserveScroll: true });
}

function cambiarTurno() {
    router.get(route('gerencia.dashboard.detalle'), {
        work_center_id: selectedWorkCenterId.value,
        shift: selectedShift.value,
    }, { preserveState: true, preserveScroll: true });
}

function logout() {
    router.post(route('logout'));
}

function formatNumber(value) {
    return new Intl.NumberFormat('es-MX').format(value || 0);
}

// Auto-refresh cada 5 minutos
let refreshInterval = null;

onMounted(() => {
    updateClock();
    clockInterval = setInterval(updateClock, 1000);
    refreshInterval = setInterval(() => {
        router.reload({ only: ['kpis', 'metrics', 'areaStatus', 'qualityMetrics', 'recentCompliance'] });
    }, 300000); // 5 minutos (300,000 ms)
});

onUnmounted(() => {
    if (clockInterval) clearInterval(clockInterval);
    if (refreshInterval) clearInterval(refreshInterval);
});
</script>

<style scoped>
.card {
    background-color: #1a2332;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}
.kpi-badge {
    background-color: #2d3748;
    border-radius: 8px;
    padding: 0.75rem 1.25rem;
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    min-width: 120px;
}
.select-custom {
    background-color: #2d3748;
    color: white;
    border: 1px solid #4a5568;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}
.digital-clock {
    font-family: 'Courier New', monospace;
    font-size: 3.5rem;
    font-weight: bold;
    color: white;
    text-align: center;
    letter-spacing: 0.1em;
}
.compliance-row {
    background-color: #0f1621;
    border-radius: 8px;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.compliance-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.875rem;
}
.badge-success {
    background-color: #10b981;
    color: white;
}
.badge-warning {
    background-color: #f59e0b;
    color: white;
}
.badge-danger {
    background-color: #ef4444;
    color: white;
}
</style>