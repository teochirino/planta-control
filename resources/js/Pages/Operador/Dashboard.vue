<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DateSelector from '@/Components/DateSelector.vue';
import ShiftSelector from '@/Components/ShiftSelector.vue';
import StrikeModal from '@/Components/StrikeModal.vue';

const props = defineProps({
    productionLines: Array,
    selectedLine: Object,
    selectedDate: String,
    selectedShift: String,
    dailyProgram: Object,
    schedules: Array,
    strikes: Array,
    kpis: Object,
});

const date = ref(props.selectedDate);
const shift = ref(props.selectedShift);
const lineId = ref(props.selectedLine.id);
const showStrikeModal = ref(false);

const totalProduced = computed(() => {
    return props.schedules?.reduce((sum, schedule) => sum + (schedule.produced || 0), 0) || 0;
});

watch([date, shift, lineId], () => {
    router.get(route('operador.dashboard'), {
        production_line_id: lineId.value,
        date: date.value,
        shift: shift.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
});

const updateProduction = (schedule, value) => {
    router.post(route('operador.schedule.update'), {
        schedule_id: schedule.id,
        produced: value,
    }, {
        preserveScroll: true,
        preserveState: true,
        only: ['schedules', 'kpis'],
    });
};

const formatTime = (time) => {
    if (!time) return '-';
    return time.substring(0, 5);
};

const calculateDuration = (strike) => {
    if (!strike.start_time || !strike.end_time) return '-';
    const start = new Date(`2000-01-01 ${strike.start_time}`);
    const end = new Date(`2000-01-01 ${strike.end_time}`);
    const diff = (end - start) / 1000 / 60;
    return `${Math.round(diff)} min`;
};
</script>

<template>
    <Head title="Dashboard Operador" />

    <AuthenticatedLayout>
        <div class="flex flex-col gap-2.5">
            <!-- Selector de Línea de Producción -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <div class="px-4 py-3 flex items-center gap-4 flex-wrap">
                    <span class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Línea de Producción:</span>
                    
                    <div class="flex items-center gap-3 flex-1">
                        <select v-model="lineId"
                                class="border border-[#d4dee8] rounded-md px-3 py-2 text-sm font-bold text-[#0c1c28] bg-white focus:outline-none focus:border-[#174060]">
                            <option v-for="line in productionLines" :key="line.id" :value="line.id">
                                {{ line.title }}
                            </option>
                        </select>
                        
                        <DateSelector v-model="date" label="" />
                        <ShiftSelector v-model="shift" label="" />
                    </div>
                </div>
            </div>
            
            <!-- Información de la Línea -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-6">
                <h2 class="text-xl font-extrabold text-[#0b2a40] mb-2">{{ selectedLine.title }}</h2>
                <p v-if="selectedLine.work_center" class="text-sm text-[#6a8090] mb-4">Centro de Trabajo: <strong>{{ selectedLine.work_center.name }}</strong></p>
                
                <!-- KPIs Simplificados -->
                <div v-if="kpis" class="mb-6 p-4 bg-[#f8f9fb] border border-[#d4dee8] rounded-lg">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-bold text-[#0b2a40]">Indicadores de la Línea</h3>
                        <span class="text-xs font-semibold text-[#6a8090]">{{ shift.charAt(0).toUpperCase() + shift.slice(1) }} - {{ new Date(date).toLocaleDateString('es-MX') }}</span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-2">Fabricadas</div>
                            <div class="text-4xl font-extrabold text-[#0b2a40]">{{ kpis.fabricated?.toLocaleString() || 0 }}</div>
                            <div class="text-xs text-[#6a8090] mt-1">piezas</div>
                        </div>
                        
                        <div class="p-4 bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-2">Min. Paro</div>
                            <div class="text-4xl font-extrabold"
                                 :class="kpis.strike_minutes > 30 ? 'text-[#ba2418]' : (kpis.strike_minutes > 15 ? 'text-[#f59e0b]' : 'text-[#0b8a3d]')">
                                {{ kpis.strike_minutes || 0 }}
                            </div>
                            <div class="text-xs text-[#6a8090] mt-1">minutos</div>
                        </div>
                        
                        <div class="p-4 bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-2">Costo de Paro</div>
                            <div class="text-4xl font-extrabold text-[#ba2418]">${{ kpis.strike_cost?.toLocaleString() || 0 }}</div>
                            <div class="text-xs text-[#6a8090] mt-1">pesos</div>
                        </div>
                    </div>
                </div>
                
                <!-- Mensaje cuando no hay programa -->
                <div v-else class="mb-6 p-6 bg-[#fff6da] border border-[#e8d488] rounded-lg text-center">
                    <div class="text-4xl mb-3">📋</div>
                    <h3 class="text-lg font-bold text-[#0b2a40] mb-2">No hay programa diario registrado</h3>
                    <p class="text-sm text-[#6a8090]">
                        No existe un programa para <strong>{{ selectedLine.title }}</strong> en el turno <strong>{{ shift }}</strong> del <strong>{{ new Date(date).toLocaleDateString('es-MX') }}</strong>.
                    </p>
                    <p class="text-xs text-[#6a8090] mt-2">
                        El supervisor debe crear el programa diario primero.
                    </p>
                </div>
                
                <!-- Producción por Hora -->
                <div v-if="dailyProgram && schedules?.length > 0" class="mb-6">
                    <h3 class="text-sm font-bold text-[#0b2a40] mb-3">📊 Producción por Hora</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-[#0b2a40] text-white">
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase">Hora</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold uppercase">Producido</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="schedule in schedules" :key="schedule.id" class="border-b border-[#d4dee8] hover:bg-[#f8f9fb]">
                                    <td class="px-4 py-3 text-sm font-semibold text-[#0b2a40]">
                                        {{ formatTime(schedule.start_time) }} - {{ formatTime(schedule.end_time) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <input type="number" 
                                               min="0" 
                                               :value="schedule.produced"
                                               @change="updateProduction(schedule, $event.target.value)"
                                               class="w-24 px-3 py-2 border border-[#d4dee8] rounded-md text-center font-bold text-[#0b2a40] focus:outline-none focus:border-[#174060]">
                                    </td>
                                </tr>
                                <tr class="bg-[#f4f7fa] font-bold">
                                    <td class="px-4 py-3 text-sm text-[#0b2a40]">TOTAL</td>
                                    <td class="px-4 py-3 text-center text-lg font-extrabold text-[#0b2a40]">
                                        {{ totalProduced.toLocaleString() }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Registro de Paros -->
                <div v-if="dailyProgram">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-bold text-[#0b2a40]">⚠️ Registro de Paros</h3>
                        <button @click="showStrikeModal = true"
                                class="px-4 py-2 bg-[#ba2418] text-white rounded-md text-xs font-bold hover:opacity-85 transition">
                            + Registrar Paro
                        </button>
                    </div>
                    
                    <div v-if="strikes?.length > 0" class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-[#0b2a40] text-white">
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase">Inicio</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase">Fin</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase">Descripción</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold uppercase">Duración</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="strike in strikes" :key="strike.id" class="border-b border-[#d4dee8]">
                                    <td class="px-4 py-3 text-sm text-[#0b2a40]">{{ formatTime(strike.start_time) }}</td>
                                    <td class="px-4 py-3 text-sm text-[#0b2a40]">
                                        <span v-if="strike.end_time">{{ formatTime(strike.end_time) }}</span>
                                        <span v-else class="text-[#ba2418] font-bold">En curso...</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-[#6a8090]">{{ strike.description }}</td>
                                    <td class="px-4 py-3 text-center text-sm font-bold text-[#ba2418]">
                                        {{ calculateDuration(strike) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="p-6 bg-[#f0fdf4] border border-[#86efac] rounded-lg text-center">
                        <div class="text-3xl mb-2">✅</div>
                        <p class="text-sm text-[#0b8a3d] font-semibold">No hay paros registrados</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Paros -->
        <StrikeModal 
            :show="showStrikeModal"
            :production-line-id="selectedLine.id"
            :daily-program-id="dailyProgram?.id"
            :date="date"
            route-name="operador.strikes.store"
            @close="showStrikeModal = false"
            @saved="showStrikeModal = false" />
    </AuthenticatedLayout>
</template>
