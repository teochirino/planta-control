<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import GerenteMantenimientoSidebar from '@/Components/GerenteMantenimientoSidebar.vue';

const toast = useToast();

const props = defineProps({
    workCenters: Array,
    selectedWorkCenter: Object,
    selectedDate: String,
    machines: Array,
    stats: Object,
});

const selectedWorkCenterId = ref(props.selectedWorkCenter.id);
const selectedDate = ref(props.selectedDate);
const showModal = ref(false);
const selectedMachine = ref(null);
const machineBreakdowns = ref([]);
const loadingBreakdowns = ref(false);
const showConfirmModal = ref(false);
const confirmAction = ref(null);
const confirmMachineId = ref(null);
const confirmNewState = ref(null);
let timerInterval = null;

const changeWorkCenter = (id) => {
    selectedWorkCenterId.value = id;
    window.location.href = route('gerente-mantenimiento.dashboard', {
        work_center_id: id,
        date: selectedDate.value,
    });
};

const changeDate = (date) => {
    selectedDate.value = date;
    window.location.href = route('gerente-mantenimiento.dashboard', {
        work_center_id: selectedWorkCenterId.value,
        date: date,
    });
};

const getStateColor = (state) => {
    switch (state) {
        case 'operativo': return 'bg-green-500';
        case 'averiado': return 'bg-red-500';
        case 'mantenimiento': return 'bg-yellow-500';
        default: return 'bg-gray-500';
    }
};

const getStateLabel = (state) => {
    switch (state) {
        case 'operativo': return 'Operativo';
        case 'averiado': return 'Averiado';
        case 'mantenimiento': return 'Mantenimiento';
        default: return 'Desconocido';
    }
};

const viewDetails = async (machine) => {
    selectedMachine.value = machine;
    loadingBreakdowns.value = true;
    showModal.value = true;
    
    try {
        const response = await axios.get(route('gerente-mantenimiento.machines.breakdowns', machine.id));
        machineBreakdowns.value = response.data.breakdowns;
    } catch (error) {
        console.error('Error al cargar breakdowns:', error);
        toast.error('Error al cargar el historial de averías');
    } finally {
        loadingBreakdowns.value = false;
    }
};

const closeModal = () => {
    showModal.value = false;
    selectedMachine.value = null;
    machineBreakdowns.value = [];
};

const updateMachineState = (machineId, newState) => {
    const stateLabels = {
        'operativo': 'Operativa',
        'mantenimiento': 'Mantenimiento',
        'averiado': 'Averiada'
    };
    
    confirmMachineId.value = machineId;
    confirmNewState.value = newState;
    confirmAction.value = `¿Cambiar estado de máquina a ${stateLabels[newState]}?`;
    showConfirmModal.value = true;
};

const confirmStateChange = async () => {
    showConfirmModal.value = false;
    
    try {
        await axios.put(route('gerente-mantenimiento.machines.update-state', confirmMachineId.value), {
            state: confirmNewState.value
        });
        toast.success('Estado de máquina actualizado correctamente');
        window.location.reload();
    } catch (error) {
        console.error('Error al actualizar estado:', error);
        toast.error('Error al actualizar el estado de la máquina');
    }
};

const cancelConfirm = () => {
    showConfirmModal.value = false;
    confirmMachineId.value = null;
    confirmNewState.value = null;
    confirmAction.value = null;
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getMinutesInProgress = (startDate) => {
    if (!startDate) return '-';
    const start = new Date(startDate);
    const now = new Date();
    const diffMs = now - start;
    const diffMins = Math.floor(diffMs / 60000);
    return diffMins + ' min';
};

// Timer para actualizar minutos en curso cada minuto
onMounted(() => {
    timerInterval = setInterval(() => {
        // Forzar actualización reactiva si el modal está abierto
        if (showModal.value) {
            // La función getMinutesInProgress se recalcula automáticamente
        }
    }, 60000); // Cada minuto
});

onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
});
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <GerenteMantenimientoSidebar />
        
        <div class="ml-0 lg:ml-64 p-6">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-3xl font-extrabold text-[#0b2a40]">Dashboard de Mantenimiento</h1>
                    <p class="text-[#4e6070] mt-1">Monitoreo y gestión de averías de máquinas</p>
                </div>

                <!-- Filtros -->
                <div class="bg-white rounded-xl shadow-sm p-4 mb-6 flex gap-4 items-center">
                    <div>
                        <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Centro de Trabajo</label>
                        <select v-model="selectedWorkCenterId" @change="changeWorkCenter(selectedWorkCenterId)"
                                class="w-48 px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1">
                            <option v-for="wc in workCenters" :key="wc.id" :value="wc.id">
                                {{ wc.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Fecha</label>
                        <input type="date" v-model="selectedDate" @change="changeDate(selectedDate)"
                               class="px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1">
                    </div>
                </div>

                <!-- Estadísticas Generales -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-xl shadow-sm p-4">
                        <div class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Horas Paro (Hoy)</div>
                        <div class="text-2xl font-extrabold text-[#0b2a40] mt-2">{{ stats.total_daily_hours }}h</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-4">
                        <div class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Horas Paro (Semana)</div>
                        <div class="text-2xl font-extrabold text-[#0b2a40] mt-2">{{ stats.total_weekly_hours }}h</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-4">
                        <div class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Pendientes Confirmación</div>
                        <div class="text-2xl font-extrabold text-orange-600 mt-2">{{ stats.pending_confirmations }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-4">
                        <div class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Máquinas Activas</div>
                        <div class="text-2xl font-extrabold text-green-600 mt-2">{{ stats.active_machines }}/{{ stats.total_machines }}</div>
                    </div>
                </div>

                <!-- Lista de Máquinas -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-[#d4dee8]">
                        <h2 class="text-lg font-extrabold text-[#0b2a40]">Estado de Máquinas</h2>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-[#f4f7fa]">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold tracking-widest uppercase text-[#4e6070]">Máquina</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold tracking-widest uppercase text-[#4e6070]">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold tracking-widest uppercase text-[#4e6070]">Horas Hoy</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold tracking-widest uppercase text-[#4e6070]">Horas Semana</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold tracking-widest uppercase text-[#4e6070]">Pendientes</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold tracking-widest uppercase text-[#4e6070]">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#d4dee8]">
                                <tr v-for="machine in machines" :key="machine.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-[#0b2a40]">{{ machine.title }}</div>
                                        <div class="text-xs text-[#4e6070]">{{ machine.work_center }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span :class="getStateColor(machine.state)" class="px-3 py-1 rounded-full text-xs font-bold text-white">
                                            {{ getStateLabel(machine.state) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-semibold text-[#0b2a40]">
                                        {{ machine.daily_hours }}h
                                    </td>
                                    <td class="px-6 py-4 text-right font-semibold text-[#0b2a40]">
                                        {{ machine.weekly_hours }}h
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span v-if="machine.pending_breakdowns > 0" 
                                              class="px-3 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-600">
                                            {{ machine.pending_breakdowns }}
                                        </span>
                                        <span v-else class="text-gray-400">0</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button @click="viewDetails(machine)" 
                                                    class="text-[#4e6070] hover:text-[#0b2a40] font-bold text-xs">
                                                Ver Detalles
                                            </button>
                                            <div class="flex gap-1">
                                                <button @click="updateMachineState(machine.id, 'operativo')" 
                                                        class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-bold hover:bg-green-200"
                                                        title="Poner Operativa">
                                                    ✓
                                                </button>
                                                <button @click="updateMachineState(machine.id, 'mantenimiento')" 
                                                        class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs font-bold hover:bg-yellow-200"
                                                        title="Poner en Mantenimiento">
                                                    🔧
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal de Detalles de Máquina -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-[#0b2a40]">Historial de Averías - {{ selectedMachine?.title }}</h3>
                    <button @click="closeModal" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
                </div>
                
                <div v-if="loadingBreakdowns" class="text-center py-8">
                    <div class="text-gray-500">Cargando historial...</div>
                </div>
                
                <div v-else-if="machineBreakdowns.length === 0" class="text-center py-8">
                    <div class="text-gray-500">No hay averías registradas para esta máquina</div>
                </div>
                
                <div v-else class="space-y-4">
                    <div v-for="breakdown in machineBreakdowns" :key="breakdown.id" 
                         class="border border-[#d4dee8] rounded-lg p-4 hover:bg-gray-50">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-1">
                                <div class="font-bold text-[#0b2a40] mb-1">{{ breakdown.reason }}</div>
                                <div class="text-sm text-[#4e6070]">
                                    <span class="font-semibold">Registrado por:</span> {{ breakdown.user?.name || 'N/A' }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div :class="breakdown.end_date ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700'" 
                                     class="px-3 py-1 rounded-full text-xs font-bold mb-2">
                                    {{ breakdown.end_date ? 'Finalizado' : 'En curso' }}
                                </div>
                                <div class="text-sm font-semibold text-[#0b2a40]">
                                    <span v-if="breakdown.end_date">{{ breakdown.minutes || '-' }} minutos</span>
                                    <span v-else class="text-orange-600">{{ getMinutesInProgress(breakdown.start_date) }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 text-sm mt-3 pt-3 border-t border-[#d4dee8]">
                            <div>
                                <span class="text-[#4e6070]">Inicio:</span>
                                <span class="font-semibold text-[#0b2a40] ml-2">{{ formatDate(breakdown.start_date) }}</span>
                            </div>
                            <div>
                                <span class="text-[#4e6070]">Fin:</span>
                                <span class="font-semibold text-[#0b2a40] ml-2">{{ formatDate(breakdown.end_date) }}</span>
                            </div>
                        </div>
                        
                        <div v-if="breakdown.confirmed_by" class="mt-3 pt-3 border-t border-[#d4dee8] text-sm">
                            <span class="text-[#4e6070]">Confirmado por:</span>
                            <span class="font-semibold text-[#0b2a40] ml-2">{{ breakdown.confirmedBy?.name || 'N/A' }}</span>
                            <span class="text-[#4e6070] ml-4">|</span>
                            <span class="text-[#4e6070] ml-4">Minutos confirmados:</span>
                            <span class="font-semibold text-[#0b2a40] ml-2">{{ breakdown.confirmed_minutes || '-' }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button @click="closeModal" 
                            class="px-4 py-2 bg-[#6a8090] text-white rounded-md font-bold hover:opacity-85">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Modal de Confirmación de Cambio de Estado -->
        <div v-if="showConfirmModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
                <div class="mb-4">
                    <h3 class="text-lg font-bold text-[#0b2a40]">Confirmar Cambio de Estado</h3>
                    <p class="text-[#4e6070] mt-2">{{ confirmAction }}</p>
                </div>
                
                <div class="flex gap-3 justify-end">
                    <button @click="cancelConfirm" 
                            class="px-4 py-2 bg-[#f4f7fa] text-[#4e6070] border border-[#d4dee8] rounded-md font-bold hover:bg-[#e8edf2]">
                        Cancelar
                    </button>
                    <button @click="confirmStateChange" 
                            class="px-4 py-2 bg-[#0b2a40] text-white rounded-md font-bold hover:opacity-85">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
