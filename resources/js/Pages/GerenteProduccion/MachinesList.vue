<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import GerenteProduccionSidebar from '@/Components/GerenteProduccionSidebar.vue';

const toast = useToast();

const props = defineProps({
    workCenters: Array,
});

const showModal = ref(false);
const selectedMachine = ref(null);
const machineBreakdowns = ref([]);
const loadingBreakdowns = ref(false);
let timerInterval = null;

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

const viewHistory = async (machine) => {
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
import { onMounted, onUnmounted } from 'vue';

onMounted(() => {
    timerInterval = setInterval(() => {
        if (showModal.value) {
            // La función getMinutesInProgress se recalcula automáticamente
        }
    }, 60000);
});

onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
});
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <GerenteProduccionSidebar />
        
        <div class="ml-0 lg:ml-64 p-6">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-3xl font-extrabold text-[#0b2a40]">Estado de Máquinas</h1>
                    <p class="text-[#4e6070] mt-1">Listado de todas las máquinas por centro de trabajo</p>
                </div>

                <!-- Lista de Centros de Trabajo y Máquinas -->
                <div v-if="workCenters.length === 0" class="bg-white rounded-xl shadow-sm p-8 text-center">
                    <div class="text-gray-500">No hay centros de trabajo registrados</div>
                </div>

                <div v-else class="space-y-6">
                    <div v-for="workCenter in workCenters" :key="workCenter.id" class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <!-- Header del Centro de Trabajo -->
                        <div class="px-6 py-4 bg-[#0b2a40]">
                            <h2 class="text-xl font-extrabold text-white">{{ workCenter.name }}</h2>
                            <p class="text-gray-300 text-sm mt-1">{{ workCenter.machines.length }} máquina(s)</p>
                        </div>
                        
                        <!-- Lista de Máquinas del Centro -->
                        <div v-if="workCenter.machines.length === 0" class="p-6 text-center text-gray-500">
                            No hay máquinas registradas en este centro de trabajo
                        </div>
                        
                        <div v-else class="divide-y divide-[#d4dee8]">
                            <div v-for="machine in workCenter.machines" :key="machine.id" 
                                 class="px-6 py-4 hover:bg-gray-50 flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <!-- Indicador de estado -->
                                    <div :class="getStateColor(machine.state)" 
                                         class="w-4 h-4 rounded-full flex-shrink-0"></div>
                                    
                                    <div>
                                        <div class="font-bold text-[#0b2a40]">{{ machine.title }}</div>
                                        <div class="text-sm text-[#4e6070]">Estado: {{ getStateLabel(machine.state) }}</div>
                                    </div>
                                </div>
                                
                                <button @click="viewHistory(machine)" 
                                        class="px-4 py-2 bg-[#0b2a40] text-white rounded-md font-bold text-sm hover:opacity-85 transition-opacity">
                                    Ver Historial
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal de Historial de Averías -->
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
    </div>
</template>
