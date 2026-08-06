<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <SupervisorSidebar />
        <DisplayModeToggle />
        <div :class="isTVMode() ? 'p-8 2xl:p-12' : 'p-6'">
            <div class="flex flex-col gap-2.5">
            <!-- Selector de Centro de Trabajo -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <div :class="isTVMode() ? 'px-6 py-4 2xl:px-8 2xl:py-5' : 'px-4 py-3'" class="flex items-center gap-4 flex-wrap">
                    <span :class="isTVMode() ? 'text-sm 2xl:text-base' : 'text-xs'" class="font-bold tracking-widest uppercase text-[#4e6070]">Centro de Trabajo:</span>
                    
                    <div class="flex items-center gap-3 flex-1">
                        <select v-model="selectedWorkCenterId" @change="cambiarCentro"
                                :class="isTVMode() ? 'px-4 py-3 text-base 2xl:px-5 2xl:py-4 2xl:text-lg' : 'px-3 py-2 text-sm'"
                                class="border border-[#d4dee8] rounded-md font-bold text-[#0c1c28] bg-white focus:outline-none focus:border-[#174060]">
                            <option v-for="wc in workCentersData" :key="wc.id" :value="wc.id">
                                {{ wc.name }}
                            </option>
                        </select>
                        
                        <input type="date" v-model="fechaSeleccionada" @change="cambiarFecha"
                               :class="isTVMode() ? 'px-4 py-3 text-base 2xl:px-5 2xl:py-4 2xl:text-lg' : 'px-3 py-2 text-sm'"
                               class="border border-[#d4dee8] rounded-md font-bold text-[#0c1c28]">
                        
                        <select v-model="turnoSeleccionado" @change="cambiarTurno"
                               :class="isTVMode() ? 'px-4 py-3 text-base 2xl:px-5 2xl:py-4 2xl:text-lg' : 'px-3 py-2 text-sm'"
                               class="border border-[#d4dee8] rounded-md font-bold text-[#0c1c28] bg-white">
                            <option value="matutino">Matutino</option>
                            <option value="vespertino">Vespertino</option>
                        </select>
                        
                        <CurrentTimeDisplay />
                    </div>
                </div>
            </div>
            
            <!-- Información del Centro -->
            <div v-if="selectedWorkCenterData" :class="isTVMode() ? 'p-8 2xl:p-12' : 'p-6'" class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <h2 :class="isTVMode() ? 'text-3xl 2xl:text-5xl' : 'text-xl'" class="font-extrabold text-[#0b2a40] mb-4">{{ selectedWorkCenterData.name }}</h2>
                
                <!-- Tarjetas de KPIs del Centro (igual que supervisor) -->
                <div v-if="centerKPIsData" :class="isTVMode() ? 'mb-8 p-6 2xl:mb-10 2xl:p-8' : 'mb-6 p-4'" class="bg-[#f8f9fb] border border-[#d4dee8] rounded-lg">
                    <div class="flex items-center justify-between mb-3">
                        <h3 :class="isTVMode() ? 'text-lg 2xl:text-xl' : 'text-sm'" class="font-bold text-[#0b2a40]">Programa del Turno</h3>
                        <div class="flex items-center gap-3">
                            <span v-if="dailyProgramData?.program?.codigo" :class="isTVMode() ? 'text-sm 2xl:text-base' : 'text-xs'" class="font-bold text-[#174060] bg-[#e8f4f8] px-2 py-1 rounded">
                                {{ dailyProgramData.program.codigo }}
                            </span>
                            <span :class="isTVMode() ? 'text-sm 2xl:text-base' : 'text-xs'" class="font-semibold text-[#6a8090]">{{ turnoLabel }} - {{ fechaFormateada }}</span>
                            <button 
                                v-if="turnoSeleccionado === 'matutino' && dailyProgramData"
                                @click="showExtendModal = true"
                                :class="isTVMode() ? 'px-4 py-2 text-base 2xl:px-5 2xl:py-3 2xl:text-lg' : 'px-3 py-1 text-xs'"
                                class="bg-[#174060] text-white rounded-md font-bold hover:opacity-85 transition">
                                🌙 Extender a Vespertino
                            </button>
                        </div>
                    </div>
                    
                    <div :class="isTVMode() ? 'gap-4 2xl:gap-6' : 'gap-2'" class="grid grid-cols-2 md:grid-cols-5 lg:grid-cols-10 2xl:grid-cols-12">
                        <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold tracking-wider uppercase text-[#4e6070] mb-1">Programado</div>
                            <div :class="isTVMode() ? 'text-4xl' : 'text-2xl'" class="font-extrabold text-[#0b2a40]">{{ formatNumber(centerKPIsData.programmed) }}</div>
                        </div>
                        
                        <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold tracking-wider uppercase text-[#4e6070] mb-1">Atraso</div>
                            <div :class="isTVMode() ? 'text-4xl' : 'text-2xl'" class="font-extrabold text-[#ba2418]">{{ formatNumber(centerKPIsData.backwardness) }}</div>
                        </div>
                        
                        <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold tracking-wider uppercase text-[#4e6070] mb-1">Adelantadas</div>
                            <div :class="isTVMode() ? 'text-4xl' : 'text-2xl'" class="font-extrabold text-[#0b8a3d]">{{ formatNumber(centerKPIsData.advanced) }}</div>
                        </div>
                        
                        <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold tracking-wider uppercase text-[#4e6070] mb-1">Total a Producir</div>
                            <div :class="isTVMode() ? 'text-4xl' : 'text-2xl'" class="font-extrabold text-[#0b2a40]">{{ formatNumber(centerKPIsData.total_to_produce) }}</div>
                        </div>
                        
                        <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold tracking-wider uppercase text-[#4e6070] mb-1">Fabricadas</div>
                            <div :class="isTVMode() ? 'text-4xl' : 'text-2xl'" class="font-extrabold text-[#0b2a40]">{{ formatNumber(centerKPIsData.fabricated) }}</div>
                        </div>
                        
                        <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold tracking-wider uppercase text-[#4e6070] mb-1">Diferencia</div>
                            <div :class="[isTVMode() ? 'text-4xl' : 'text-2xl', 'font-extrabold', centerKPIsData.difference >= 0 ? 'text-[#0b8a3d]' : 'text-[#ba2418]']">
                                {{ centerKPIsData.difference >= 0 ? '+' : '' }}{{ formatNumber(centerKPIsData.difference) }}
                            </div>
                        </div>
                        
                        <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold tracking-wider uppercase text-[#4e6070] mb-1">Cumplimiento</div>
                            <div :class="[isTVMode() ? 'text-4xl' : 'text-2xl', 'font-extrabold', complianceClass]">
                                {{ centerKPIsData.compliance }}%
                            </div>
                        </div>
                        
                        <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold tracking-wider uppercase text-[#4e6070] mb-1">Real vs Ideal</div>
                            <div :class="isTVMode() ? 'text-4xl' : 'text-2xl'" class="font-extrabold text-[#0b2a40]">{{ centerKPIsData.real_vs_ideal }}%</div>
                        </div>
                        
                        <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold tracking-wider uppercase text-[#4e6070] mb-1">Cap. Instalada</div>
                            <div :class="isTVMode() ? 'text-4xl' : 'text-2xl'" class="font-extrabold text-[#0b2a40]">{{ formatNumber(centerKPIsData.installed_capacity) }}</div>
                        </div>
                    </div>
                </div>
                
                <!-- Mensaje cuando no hay programa -->
                <div v-else :class="isTVMode() ? 'mb-8 p-8' : 'mb-6 p-6'" class="bg-[#fff6da] border border-[#e8d488] rounded-lg text-center">
                    <div :class="isTVMode() ? 'text-6xl mb-5' : 'text-4xl mb-3'">📋</div>
                    <h3 :class="isTVMode() ? 'text-2xl mb-3' : 'text-lg mb-2'" class="font-bold text-[#0b2a40]">No hay programa diario registrado</h3>
                    <p :class="isTVMode() ? 'text-base' : 'text-sm'" class="text-[#6a8090]">
                        No existe un programa para <strong>{{ selectedWorkCenterData.name }}</strong> en el turno <strong>{{ turnoLabel }}</strong> del <strong>{{ fechaFormateada }}</strong>.
                    </p>
                    <p :class="isTVMode() ? 'text-sm' : 'text-xs'" class="text-[#6a8090] mt-2">
                        Debe crear el programa diario primero.
                    </p>
                </div>
            </div>
            
            <!-- Líneas de Producción y sus detalles -->
            <div v-if="dailyProgramData && allKPIsData.length > 0" class="flex flex-col gap-4">
                <h3 :class="isTVMode() ? 'text-2xl mb-4' : 'text-xl mb-3'" class="font-extrabold text-[#0b2a40]">Detalle por Línea de Producción</h3>
                
                <div class="grid" :class="isTVMode() ? 'gap-6' : 'gap-4'" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
                    <div v-for="item in allKPIsData" :key="item.line.id" class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                        <div :class="isTVMode() ? 'p-6' : 'p-4'">
                            <h4 :class="isTVMode() ? 'text-2xl mb-4' : 'text-xl mb-3'" class="font-extrabold text-[#0b2a40]">{{ item.line.title }}</h4>
                            
                            <!-- Producción por Hora (Solo lectura) -->
                            <div v-if="item.schedules.length > 0" :class="isTVMode() ? 'mb-6' : 'mb-4'">
                                <h5 :class="isTVMode() ? 'text-base mb-4' : 'text-sm mb-3'" class="font-bold text-[#0b2a40]">📊 Producción por Hora</h5>
                                <div class="overflow-x-auto">
                                    <table class="w-full border-collapse">
                                        <thead>
                                            <tr class="bg-[#0b2a40] text-white">
                                                <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-left font-bold uppercase">Hora</th>
                                                <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-center font-bold uppercase">Producido</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="schedule in item.schedules" :key="schedule.id" class="border-b border-[#d4dee8] hover:bg-[#f8f9fb]">
                                                <td :class="isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm'" class="font-semibold text-[#0b2a40]">
                                                    {{ formatTime(schedule.start_time) }} - {{ formatTime(schedule.end_time) }}
                                                </td>
                                                <td :class="isTVMode() ? 'px-5 py-4' : 'px-4 py-3'" class="text-center">
                                                    <span :class="isTVMode() ? 'text-2xl' : 'text-lg'" class="font-extrabold text-[#0b2a40]">
                                                        {{ formatNumber(schedule.produced) }}
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr class="bg-[#f4f7fa] font-bold">
                                                <td :class="isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm'" class="text-[#0b2a40]">TOTAL</td>
                                                <td :class="isTVMode() ? 'px-5 py-4 text-2xl' : 'px-4 py-3 text-lg'" class="text-center font-extrabold text-[#0b2a40]">
                                                    {{ formatNumber(item.schedules.reduce((sum, s) => sum + (s.produced || 0), 0)) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Paros Registrados (Solo lectura) -->
                            <div v-if="item.strikes.length > 0">
                                <h5 :class="isTVMode() ? 'text-base mb-4' : 'text-sm mb-3'" class="font-bold text-[#0b2a40]">⚠️ Paros Registrados</h5>
                                <div class="overflow-x-auto">
                                    <table class="w-full border-collapse">
                                        <thead>
                                            <tr class="bg-[#0b2a40] text-white">
                                                <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-left font-bold uppercase">Inicio</th>
                                                <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-left font-bold uppercase">Fin</th>
                                                <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-left font-bold uppercase">Descripción</th>
                                                <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-center font-bold uppercase">Duración</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="strike in item.strikes" :key="strike.id" class="border-b border-[#d4dee8]">
                                                <td :class="isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm'" class="text-[#0b2a40]">{{ formatTime(strike.start_time) }}</td>
                                                <td :class="isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm'" class="text-[#0b2a40]">
                                                    <span v-if="strike.end_time">{{ formatTime(strike.end_time) }}</span>
                                                    <span v-else class="text-[#f59e0b] font-bold">En curso...</span>
                                                </td>
                                                <td :class="isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm'" class="text-[#6a8090]">{{ strike.description }}</td>
                                                <td :class="[isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm', 'text-center font-bold', strike.end_time ? 'text-[#ba2418]' : 'text-[#f59e0b]']">
                                                    <span v-if="strike.end_time">{{ strike.minutes }} min</span>
                                                    <span v-else>-</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div v-else :class="isTVMode() ? 'p-8' : 'p-6'" class="bg-[#f0fdf4] border border-[#86efac] rounded-lg text-center">
                                <div :class="isTVMode() ? 'text-5xl mb-3' : 'text-3xl mb-2'">✅</div>
                                <p :class="isTVMode() ? 'text-base' : 'text-sm'" class="text-[#0b8a3d] font-semibold">No hay paros registrados</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
        
        <!-- Modal para Extender a Vespertino -->
        <div v-if="showExtendModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div :class="isTVMode() ? 'p-8 2xl:p-12' : 'p-6'" class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
                <h3 :class="isTVMode() ? 'text-2xl mb-4' : 'text-xl mb-3'" class="font-extrabold text-[#0b2a40]">Extender a Turno Vespertino</h3>
                <p :class="isTVMode() ? 'text-base mb-4' : 'text-sm mb-3'" class="text-[#6a8090]">
                    Indique cuántas piezas del programa matutino desea pasar al turno vespertino (17:00 - 01:40).
                </p>
                
                <div class="mb-4">
                    <label :class="isTVMode() ? 'text-sm font-bold mb-2' : 'text-xs font-bold mb-1'" class="block text-[#4e6070] uppercase tracking-wider">
                        Piezas a extender
                    </label>
                    <input 
                        type="number" 
                        v-model.number="piecesToExtend" 
                        min="0" 
                        :max="maxPiecesToExtend"
                        :class="isTVMode() ? 'px-4 py-3 text-base' : 'px-3 py-2 text-sm'"
                        class="w-full border border-[#d4dee8] rounded-md font-bold text-[#0c1c28] focus:outline-none focus:border-[#174060]"
                    >
                    <p :class="isTVMode() ? 'text-sm mt-2' : 'text-xs mt-1'" class="text-[#6a8090]">
                        Máximo disponible: {{ maxPiecesToExtend }} piezas
                    </p>
                </div>
                
                <div class="flex gap-3 justify-end">
                    <button 
                        @click="showExtendModal = false"
                        :class="isTVMode() ? 'px-4 py-2 text-base' : 'px-3 py-1 text-xs'"
                        class="border border-[#d4dee8] rounded-md font-bold text-[#0c1c28] hover:bg-[#f8f9fb] transition">
                        Cancelar
                    </button>
                    <button 
                        @click="extendToVespertino"
                        :disabled="extending || piecesToExtend <= 0"
                        :class="isTVMode() ? 'px-4 py-2 text-base' : 'px-3 py-1 text-xs'"
                        class="bg-[#174060] text-white rounded-md font-bold hover:opacity-85 transition disabled:opacity-50">
                        {{ extending ? 'Procesando...' : 'Extender' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import SupervisorSidebar from '@/Components/SupervisorSidebar.vue'
import DisplayModeToggle from '@/Components/DisplayModeToggle.vue'
import CurrentTimeDisplay from '@/Components/CurrentTimeDisplay.vue'
import { useDisplayMode } from '@/Composables/useDisplayMode'

const { isTVMode } = useDisplayMode()

const props = defineProps({
    workCenters: {
        type: Array,
        default: () => []
    },
    productionLines: {
        type: Array,
        default: () => []
    },
    selectedWorkCenter: {
        type: Object,
        default: null
    },
    selectedDate: {
        type: String,
        default: ''
    },
    selectedShift: {
        type: String,
        default: 'matutino'
    },
    dailyProgram: {
        type: Object,
        default: null
    },
    productionLinesForCenter: {
        type: Array,
        default: () => []
    },
    allKPIs: {
        type: Array,
        default: () => []
    },
    centerKPIs: {
        type: Object,
        default: null
    }
})

const selectedWorkCenterId = ref(props.selectedWorkCenter?.id || null)
const fechaSeleccionada = ref(props.selectedDate)
const turnoSeleccionado = ref(props.selectedShift)

const workCentersData = computed(() => props.workCenters)
const selectedWorkCenterData = computed(() => props.selectedWorkCenter)
const dailyProgramData = computed(() => props.dailyProgram)
const productionLinesForCenterData = computed(() => props.productionLinesForCenter)
const allKPIsData = computed(() => props.allKPIs)
const centerKPIsData = computed(() => props.centerKPIs)

const turnoLabel = computed(() => {
    const labels = {
        matutino: 'Matutino',
        vespertino: 'Vespertino'
    }
    return labels[turnoSeleccionado.value] || turnoSeleccionado.value
})

// Modal para extender a vespertino
const showExtendModal = ref(false)
const piecesToExtend = ref(0)
const extending = ref(false)

const maxPiecesToExtend = computed(() => {
    if (!centerKPIsData.value) return 0
    return centerKPIsData.value.programmed + centerKPIsData.value.backwardness - centerKPIsData.value.advanced
})

const canExtendToVespertino = computed(() => {
    // Solo permitir extender si es turno matutino y hay piezas disponibles
    return turnoSeleccionado.value === 'matutino' && maxPiecesToExtend.value > 0
})

const fechaFormateada = computed(() => {
    if (!fechaSeleccionada.value) return ''
    const date = new Date(fechaSeleccionada.value)
    return date.toLocaleDateString('es-ES', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    })
})

const complianceClass = computed(() => {
    if (!centerKPIsData.value) return 'text-[#0b2a40]'
    const compliance = centerKPIsData.value.compliance
    if (compliance >= 95) return 'text-[#0b8a3d]'
    if (compliance >= 80) return 'text-[#f59e0b]'
    return 'text-[#ba2418]'
})

function cambiarCentro() {
    router.get(route('supervisor.information-panel'), {
        work_center_id: selectedWorkCenterId.value,
        date: fechaSeleccionada.value,
        shift: turnoSeleccionado.value
    }, { preserveState: true })
}

function cambiarFecha() {
    router.get(route('supervisor.information-panel'), {
        work_center_id: selectedWorkCenterId.value,
        date: fechaSeleccionada.value,
        shift: turnoSeleccionado.value
    }, { preserveState: true })
}

function cambiarTurno() {
    router.get(route('supervisor.information-panel'), {
        work_center_id: selectedWorkCenterId.value,
        date: fechaSeleccionada.value,
        shift: turnoSeleccionado.value
    }, { preserveState: true })
}

async function extendToVespertino() {
    if (!dailyProgramData.value || piecesToExtend.value <= 0) return
    
    extending.value = true
    
    try {
        const response = await axios.post(route('supervisor.extend-to-vespertino'), {
            daily_program_id: dailyProgramData.value.id,
            pieces_to_extend: piecesToExtend.value
        })
        
        if (response.data.success) {
            alert(response.data.message)
            showExtendModal.value = false
            piecesToExtend.value = 0
            // Recargar datos
            refreshData()
        } else {
            alert(response.data.message || 'Error al extender el programa')
        }
    } catch (error) {
        console.error('Error extending to vespertino:', error)
        alert('Error al extender el programa. Por favor intente nuevamente.')
    } finally {
        extending.value = false
    }
}

function refreshData() {
    router.get(route('supervisor.information-panel'), {
        work_center_id: selectedWorkCenterId.value,
        date: fechaSeleccionada.value,
        shift: turnoSeleccionado.value
    }, { 
        preserveState: true,
        preserveScroll: true,
        only: ['workCenters', 'productionLines', 'selectedWorkCenter', 'selectedDate', 'selectedShift', 'dailyProgram', 'productionLinesForCenter', 'allKPIs', 'centerKPIs']
    })
}

let refreshInterval = null

onMounted(() => {
    // Actualizar datos cada 3 minutos (180,000 ms)
    refreshInterval = setInterval(() => {
        refreshData()
    }, 180000)
})

onUnmounted(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval)
    }
})

function formatNumber(num) {
    if (num === null || num === undefined) return '0'
    return new Intl.NumberFormat('es-MX').format(num)
}

function formatTime(time) {
    if (!time) return '-'
    if (time.length > 5) {
        return time.substring(0, 5)
    }
    return time
}
</script>
