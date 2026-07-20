<template>
    <AuthenticatedLayout>
        <DisplayModeToggle />
        <div class="flex flex-col gap-2.5">
            <!-- Selector de Línea de Producción -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <div :class="isTVMode() ? 'px-6 py-4' : 'px-4 py-3'" class="flex items-center gap-4 flex-wrap">
                    <span :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold tracking-widest uppercase text-[#4e6070]">Línea de Producción:</span>
                    
                    <div class="flex items-center gap-3 flex-1">
                        <select v-model="selectedLineId" @change="cambiarLinea"
                                :class="isTVMode() ? 'px-4 py-3 text-base' : 'px-3 py-2 text-sm'"
                                class="border border-[#d4dee8] rounded-md font-bold text-[#0c1c28] bg-white focus:outline-none focus:border-[#174060]">
                            <option v-for="line in productionLinesData" :key="line.id" :value="line.id">
                                {{ line.title }}
                            </option>
                        </select>
                        
                        <input type="date" v-model="fechaSeleccionada" @change="cambiarFecha"
                               :class="isTVMode() ? 'px-4 py-3 text-base' : 'px-3 py-2 text-sm'"
                               class="border border-[#d4dee8] rounded-md font-bold text-[#0c1c28]">
                        
                        <select v-model="turnoSeleccionado" @change="cambiarTurno"
                               :class="isTVMode() ? 'px-4 py-3 text-base' : 'px-3 py-2 text-sm'"
                               class="border border-[#d4dee8] rounded-md font-bold text-[#0c1c28] bg-white">
                            <option value="matutino">Matutino</option>
                            <option value="vespertino">Vespertino</option>
                            <option value="nocturno">Nocturno</option>
                        </select>
                        
                        <CurrentTimeDisplay />
                    </div>
                </div>
            </div>
            
            <!-- Información de la Línea -->
            <div :class="isTVMode() ? 'p-8' : 'p-6'" class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <h2 :class="isTVMode() ? 'text-3xl' : 'text-xl'" class="font-extrabold text-[#0b2a40] mb-2">{{ selectedLineData?.title || 'Cargando...' }}</h2>
                <p :class="isTVMode() ? 'text-base' : 'text-sm'" class="text-[#6a8090] mb-4">
                    Centro de Trabajo: <strong>{{ selectedLineData?.work_center?.name || '-' }}</strong>
                </p>
                
                <!-- KPIs -->
                <div v-if="kpisData" :class="isTVMode() ? 'mb-8 p-6' : 'mb-6 p-4'" class="bg-[#f8f9fb] border border-[#d4dee8] rounded-lg">
                    <div class="flex items-center justify-between mb-3">
                        <h3 :class="isTVMode() ? 'text-base' : 'text-sm'" class="font-bold text-[#0b2a40]">Indicadores de la Línea</h3>
                        <span :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-semibold text-[#6a8090]">{{ turnoLabel }} - {{ fechaFormateada }}</span>
                    </div>
                    
                    <div :class="isTVMode() ? 'gap-6' : 'gap-4'" class="grid grid-cols-1 md:grid-cols-3">
                        <div :class="isTVMode() ? 'p-6' : 'p-4'" class="bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold tracking-wider uppercase text-[#4e6070] mb-2">Fabricadas</div>
                            <div :class="isTVMode() ? 'text-5xl' : 'text-4xl'" class="font-extrabold text-[#0b2a40]">{{ formatNumber(kpisData.fabricated) }}</div>
                            <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="text-[#6a8090] mt-1">piezas</div>
                        </div>
                        
                        <div :class="isTVMode() ? 'p-6' : 'p-4'" class="bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold tracking-wider uppercase text-[#4e6070] mb-2">Min. Paro</div>
                            <div :class="[isTVMode() ? 'text-5xl' : 'text-4xl', 'font-extrabold', strikeMinutesClass]">
                                {{ formatNumber(kpisData.strike_minutes || 0) }}
                            </div>
                            <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="text-[#6a8090] mt-1">minutos</div>
                        </div>
                        
                        <div :class="isTVMode() ? 'p-6' : 'p-4'" class="bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold tracking-wider uppercase text-[#4e6070] mb-2">Costo de Paro</div>
                            <div :class="isTVMode() ? 'text-5xl' : 'text-4xl'" class="font-extrabold text-[#ba2418]">${{ formatNumber(kpisData.strike_cost || 0) }}</div>
                            <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="text-[#6a8090] mt-1">pesos</div>
                        </div>
                    </div>
                </div>
                
                <!-- Mensaje cuando no hay programa -->
                <div v-else-if="!dailyProgramId" :class="isTVMode() ? 'mb-8 p-8' : 'mb-6 p-6'" class="bg-[#fff6da] border border-[#e8d488] rounded-lg text-center">
                    <div :class="isTVMode() ? 'text-6xl mb-5' : 'text-4xl mb-3'">📋</div>
                    <h3 :class="isTVMode() ? 'text-2xl mb-3' : 'text-lg mb-2'" class="font-bold text-[#0b2a40]">No hay programa diario registrado</h3>
                    <p :class="isTVMode() ? 'text-base' : 'text-sm'" class="text-[#6a8090]">
                        No existe un programa para <strong>{{ selectedLineData?.title }}</strong> en el turno <strong>{{ turnoLabel }}</strong> del <strong>{{ fechaFormateada }}</strong>.
                    </p>
                    <p :class="isTVMode() ? 'text-sm' : 'text-xs'" class="text-[#6a8090] mt-2">
                        El supervisor debe crear el programa diario primero.
                    </p>
                </div>
                
                <!-- Tabla de Producción por Hora -->
                <div v-if="dailyProgramId && schedulesData.length > 0" :class="isTVMode() ? 'mb-8' : 'mb-6'">
                    <h3 :class="isTVMode() ? 'text-base mb-4' : 'text-sm mb-3'" class="font-bold text-[#0b2a40]">📊 Producción por Hora</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-[#0b2a40] text-white">
                                    <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-left font-bold uppercase">Hora</th>
                                    <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-center font-bold uppercase">Producido</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="schedule in schedulesData" :key="schedule.id" class="border-b border-[#d4dee8] hover:bg-[#f8f9fb]">
                                    <td :class="isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm'" class="font-semibold text-[#0b2a40]">
                                        {{ formatTime(schedule.start_time) }} - {{ formatTime(schedule.end_time) }}
                                    </td>
                                    <td :class="isTVMode() ? 'px-5 py-4' : 'px-4 py-3'" class="text-center">
                                        <input type="number" 
                                               min="0" 
                                               v-model="produccionValues[schedule.id]"
                                               @input="guardarProduccion(schedule.id)"
                                               :class="isTVMode() ? 'w-32 px-4 py-3 text-base' : 'w-24 px-3 py-2 text-sm'"
                                               class="border border-[#d4dee8] rounded-md text-center font-bold text-[#0b2a40] focus:outline-none focus:border-[#174060]">
                                    </td>
                                </tr>
                                <tr class="bg-[#f4f7fa] font-bold">
                                    <td :class="isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm'" class="text-[#0b2a40]">TOTAL</td>
                                    <td :class="isTVMode() ? 'px-5 py-4 text-2xl' : 'px-4 py-3 text-lg'" class="text-center font-extrabold text-[#0b2a40]">
                                        {{ formatNumber(totalProducido) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Registro de Paros -->
                <div v-if="dailyProgramId">
                    <div class="flex items-center justify-between mb-3">
                        <h3 :class="isTVMode() ? 'text-base' : 'text-sm'" class="font-bold text-[#0b2a40]">⚠️ Registro de Paros</h3>
                        <button @click="abrirModalParo" :class="isTVMode() ? 'px-6 py-3 text-base' : 'px-4 py-2 text-xs'" class="bg-[#ba2418] text-white rounded-md font-bold hover:opacity-85 transition">
                            + Registrar Paro
                        </button>
                    </div>
                    
                    <div v-if="strikesList.length > 0" class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-[#0b2a40] text-white">
                                    <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-left font-bold uppercase">Inicio</th>
                                    <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-left font-bold uppercase">Fin</th>
                                    <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-left font-bold uppercase">Descripción</th>
                                    <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-center font-bold uppercase">Duración</th>
                                    <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-center font-bold uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="strike in strikesList" :key="strike.id" class="border-b border-[#d4dee8]">
                                    <td :class="isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm'" class="text-[#0b2a40]">{{ formatTime(strike.start_time) }}</td>
                                    <td :class="isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm'" class="text-[#0b2a40]">
                                        <span v-if="strike.end_time">{{ formatTime(strike.end_time) }}</span>
                                        <span v-else class="text-[#f59e0b] font-bold">En curso...</span>
                                    </td>
                                    <td :class="isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm'" class="text-[#6a8090]">{{ strike.description }}</td>
                                    <td :class="[isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm', 'text-center font-bold', strike.end_time ? 'text-[#ba2418]' : 'text-[#f59e0b]']">
                                        <span v-if="strike.end_time">{{ strike.minutes || calcularDuracion(strike) }} min</span>
                                        <span v-else>{{ tiempoTranscurrido(strike) }}</span>
                                    </td>
                                    <td :class="isTVMode() ? 'px-5 py-4' : 'px-4 py-3'" class="text-center">
                                        <button v-if="!strike.end_time" 
                                                @click="finalizarParo(strike)"
                                                :class="isTVMode() ? 'px-4 py-2 text-sm' : 'px-3 py-1 text-xs'"
                                                class="bg-[#0b8a3d] text-white rounded font-bold hover:opacity-85">
                                            Finalizar
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else :class="isTVMode() ? 'p-8' : 'p-6'" class="bg-[#f0fdf4] border border-[#86efac] rounded-lg text-center">
                        <div :class="isTVMode() ? 'text-5xl mb-3' : 'text-3xl mb-2'">✅</div>
                        <p :class="isTVMode() ? 'text-base' : 'text-sm'" class="text-[#0b8a3d] font-semibold">No hay paros registrados</p>
                    </div>
                </div>

                <!-- Botón Cerrar Turno -->
                <div v-if="dailyProgramId" :class="isTVMode() ? 'mt-8 p-6' : 'mt-6 p-4'" class="bg-[#f8f9fb] border border-[#d4dee8] rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 :class="isTVMode() ? 'text-base' : 'text-sm'" class="font-bold text-[#0b2a40]">🔒 Cierre de Turno - {{ selectedLineData?.title || 'Línea seleccionada' }}</h3>
                            <p :class="isTVMode() ? 'text-sm' : 'text-xs'" class="text-[#6a8090] mt-1">
                                <span v-if="isLineClosed" class="text-[#0b8a3d] font-semibold">
                                    ✓ Línea cerrada el {{ formatDateTime(lineClosedAt) }}
                                </span>
                                <span v-else>
                                    Esta línea aún no ha sido cerrada. Cierra el turno cuando termines tu jornada en esta línea.
                                </span>
                            </p>
                        </div>
                        <button
                            v-if="!isLineClosed"
                            @click="cerrarTurno"
                            :disabled="cerrandoTurno"
                            :class="isTVMode() ? 'px-6 py-3 text-base' : 'px-4 py-2 text-xs'"
                            class="bg-[#0b2a40] text-white rounded-md font-bold hover:opacity-85 transition disabled:opacity-50">
                            {{ cerrandoTurno ? 'Cerrando...' : 'Cerrar Turno' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal para Registrar Paro -->
        <div v-if="modalVisible" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div :class="isTVMode() ? 'p-8 max-w-2xl' : 'p-6 max-w-md'" class="bg-white rounded-lg shadow-xl w-full">
                <h3 class="text-lg font-bold text-[#0b2a40] mb-4">Registrar Paro</h3>
                <div class="space-y-4">
                    <div v-if="machines && machines.length > 0">
                        <label class="block text-sm font-bold text-[#0b2a40] mb-2">Máquina afectada (opcional)</label>
                        <select v-model="nuevoParo.machine_id" class="w-full px-3 py-2 border border-[#d4dee8] rounded-md">
                            <option :value="null">No afecta a máquina específica</option>
                            <option v-for="machine in machines" :key="machine.id" :value="machine.id">{{ machine.title }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-[#0b2a40] mb-2">Hora de Inicio</label>
                        <input type="time" v-model="nuevoParo.start_time" class="w-full px-3 py-2 border border-[#d4dee8] rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-[#0b2a40] mb-2">Hora de Fin (opcional)</label>
                        <input type="time" v-model="nuevoParo.end_time" class="w-full px-3 py-2 border border-[#d4dee8] rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-[#0b2a40] mb-2">Descripción</label>
                        <textarea v-model="nuevoParo.description" rows="3" class="w-full px-3 py-2 border border-[#d4dee8] rounded-md"></textarea>
                    </div>
                </div>
                <div class="flex gap-2 mt-6">
                    <button @click="registrarParo" :disabled="guardandoParo"
                            class="flex-1 px-4 py-2 bg-[#ba2418] text-white rounded-md font-bold hover:opacity-85 disabled:opacity-50">
                        {{ guardandoParo ? 'Guardando...' : 'Guardar' }}
                    </button>
                    <button @click="cerrarModalParo"
                            class="flex-1 px-4 py-2 bg-[#6a8090] text-white rounded-md font-bold hover:opacity-85">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Modal de Confirmación para Finalizar Paro -->
        <div v-if="showConfirmModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
                <div class="mb-4">
                    <h3 class="text-lg font-bold text-[#0b2a40]">Confirmar Finalización de Paro</h3>
                    <p class="text-[#4e6070] mt-2">¿Finalizar este paro a las {{ confirmEndTime }}?</p>
                </div>
                
                <div class="flex gap-3 justify-end">
                    <button @click="cancelEndStrike" 
                            class="px-4 py-2 bg-[#f4f7fa] text-[#4e6070] border border-[#d4dee8] rounded-md font-bold hover:bg-[#e8edf2]">
                        Cancelar
                    </button>
                    <button @click="confirmEndStrike" 
                            class="px-4 py-2 bg-[#0b2a40] text-white rounded-md font-bold hover:opacity-85">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import DisplayModeToggle from '@/Components/DisplayModeToggle.vue'
import CurrentTimeDisplay from '@/Components/CurrentTimeDisplay.vue'
import { useDisplayMode } from '@/Composables/useDisplayMode'

const { isTVMode } = useDisplayMode()
const toast = useToast()

// Props
const props = defineProps({
    productionLines: {
        type: Array,
        default: () => []
    },
    selectedLine: {
        type: Object,
        default: () => ({})
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
    schedules: {
        type: Array,
        default: () => []
    },
    strikes: {
        type: Array,
        default: () => []
    },
    kpis: {
        type: Object,
        default: null
    },
    lineClosure: {
        type: Object,
        default: null
    },
    machines: {
        type: Array,
        default: () => []
    }
})

// Fecha actual
const hoy = new Date()
const fechaActualStr = hoy.toISOString().split('T')[0]

// Estados
const productionLinesData = ref(props.productionLines || [])
const selectedLineData = ref(props.selectedLine || {})
const kpisData = ref(props.kpis)
const schedulesData = ref(props.schedules || [])
const strikesList = ref(props.strikes || [])
const dailyProgramId = ref(props.dailyProgram?.id || null)
const machines = ref(props.machines || [])

const selectedLineId = ref(props.selectedLine?.id || (props.productionLines?.[0]?.id))
const fechaSeleccionada = ref(props.selectedDate && props.selectedDate !== '' ? props.selectedDate : fechaActualStr)
const turnoSeleccionado = ref(props.selectedShift || 'matutino')

// Producción
const produccionValues = ref({})
const autoSaveTimeouts = {}

// Modal paro
const modalVisible = ref(false)
const guardandoParo = ref(false)
const nuevoParo = ref({
    machine_id: null,
    start_time: '',
    end_time: '',
    description: ''
})

// Modal de confirmación para finalizar paro
const showConfirmModal = ref(false)
const confirmStrike = ref(null)
const confirmEndTime = ref('')

// Cierre de turno
const cerrandoTurno = ref(false)

// Timer para actualizar minutos transcurridos
let timerInterval = null

// Computed
const turnoLabel = computed(() => {
    const shifts = { matutino: 'Matutino', vespertino: 'Vespertino', nocturno: 'Nocturno' }
    return shifts[turnoSeleccionado.value] || turnoSeleccionado.value
})

const fechaFormateada = computed(() => {
    if (!fechaSeleccionada.value) return ''
    const [year, month, day] = fechaSeleccionada.value.split('-')
    return `${parseInt(day)}/${parseInt(month)}/${year}`
})

const isLineClosed = computed(() => props.lineClosure !== null)
const lineClosedAt = computed(() => props.lineClosure?.closed_at || null)

const strikeMinutesClass = computed(() => {
    const minutes = kpisData.value?.strike_minutes || 0
    if (minutes > 30) return 'text-[#ba2418]'
    if (minutes > 15) return 'text-[#f59e0b]'
    return 'text-[#0b8a3d]'
})

const totalProducido = computed(() => {
    let total = 0
    for (const schedule of schedulesData.value) {
        total += produccionValues.value[schedule.id] || 0
    }
    return total
})

// Métodos
const formatNumber = (num) => {
    if (num === undefined || num === null) return '0'
    return num.toLocaleString('es-MX')
}

const formatTime = (time) => {
    if (!time) return '-'
    return time.substring(0, 5)
}

const formatDateTime = (datetime) => {
    if (!datetime) return '-'
    const date = new Date(datetime)
    return date.toLocaleString('es-MX', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const calcularDuracion = (strike) => {
    if (!strike.start_time || !strike.end_time) return '-'
    const start = new Date(`2000-01-01 ${strike.start_time}`)
    const end = new Date(`2000-01-01 ${strike.end_time}`)
    const diff = Math.round((end - start) / 1000 / 60)
    return `${diff} min`
}

const tiempoTranscurrido = (strike) => {
    if (!strike.start_time) return '0 min'
    const now = new Date()
    const [hours, minutes] = strike.start_time.split(':')
    const start = new Date()
    start.setHours(parseInt(hours), parseInt(minutes), 0)
    let diff = Math.floor((now - start) / 1000 / 60)
    if (diff < 0) diff = 0
    return `${diff} min`
}

// Inicializar producción
const initProduccion = () => {
    for (const schedule of schedulesData.value) {
        produccionValues.value[schedule.id] = schedule.produced || 0
    }
}

// Guardar producción
const guardarProduccion = async (scheduleId) => {
    clearTimeout(autoSaveTimeouts[scheduleId])
    autoSaveTimeouts[scheduleId] = setTimeout(async () => {
        try {
            const response = await axios.post(route('operador.schedule.update'), {
                schedule_id: scheduleId,
                produced: produccionValues.value[scheduleId] || 0
            })
            
            if (response.data.success) {
                if (response.data.kpis) {
                    kpisData.value = response.data.kpis
                }
                // Actualizar total producido en la tabla
                const newTotal = schedulesData.value.reduce((sum, s) => {
                    return sum + (produccionValues.value[s.id] || 0)
                }, 0)
                // Forzar actualización reactiva
                schedulesData.value = [...schedulesData.value]
            }
        } catch (error) {
            console.error('Error al guardar:', error)
        }
    }, 1000)
}

// Cargar paros desde el servidor
const cargarParos = async () => {
    if (!dailyProgramId.value) return
    try {
        // 🔧 Usar URL directa
        const response = await axios.get(`/operador/strikes/${dailyProgramId.value}`)
        strikesList.value = response.data || []
    } catch (error) {
        console.error('Error al cargar paros:', error)
    }
}

// Cambios de filtros
const cambiarLinea = () => {
    router.get(route('operador.dashboard'), {
        production_line_id: selectedLineId.value,
        date: fechaSeleccionada.value,
        shift: turnoSeleccionado.value
    })
}

const cambiarFecha = () => {
    router.get(route('operador.dashboard'), {
        production_line_id: selectedLineId.value,
        date: fechaSeleccionada.value,
        shift: turnoSeleccionado.value
    })
}

const cambiarTurno = () => {
    router.get(route('operador.dashboard'), {
        production_line_id: selectedLineId.value,
        date: fechaSeleccionada.value,
        shift: turnoSeleccionado.value
    })
}

// Paros - Modal
const abrirModalParo = () => {
    const now = new Date()
    const hours = String(now.getHours()).padStart(2, '0')
    const minutes = String(now.getMinutes()).padStart(2, '0')
    
    nuevoParo.value = {
        machine_id: null,
        start_time: `${hours}:${minutes}`,
        end_time: '',
        description: ''
    }
    modalVisible.value = true
}

const cerrarModalParo = () => {
    modalVisible.value = false
}

// Registrar paro
const registrarParo = async () => {
    if (!nuevoParo.value.start_time) {
        toast.error('Indique la hora de inicio')
        return
    }
    if (!nuevoParo.value.description) {
        toast.error('Describa el paro')
        return
    }
    
    guardandoParo.value = true
    try {
        // 🔧 Usar URL directa
        const response = await axios.post('/operador/strikes', {
            id_production_line: selectedLineId.value,
            id_daily_program: dailyProgramId.value,
            date: fechaSeleccionada.value,
            start_time: nuevoParo.value.start_time,
            end_time: nuevoParo.value.end_time || null,
            description: nuevoParo.value.description,
            id_machine: nuevoParo.value.machine_id || null
        })
        
        if (response.data.success) {
            toast.success('Paro registrado correctamente')
            cerrarModalParo()
            await cargarParos()
        } else {
            toast.error('Error: ' + response.data.message)
        }
    } catch (error) {
        console.error('Error:', error)
        toast.error('Error al registrar el paro')
    } finally {
        guardandoParo.value = false
    }
}

// Finalizar paro
const finalizarParo = (strike) => {
    const now = new Date()
    const hours = String(now.getHours()).padStart(2, '0')
    const minutes = String(now.getMinutes()).padStart(2, '0')
    const endTime = `${hours}:${minutes}`
    
    confirmStrike.value = strike
    confirmEndTime.value = endTime
    showConfirmModal.value = true
}

const confirmEndStrike = async () => {
    showConfirmModal.value = false
    
    try {
        const response = await axios.put(`/operador/strikes/${confirmStrike.value.id}/end`, {
            end_time: confirmEndTime.value
        }, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        
        if (response.data.success) {
            toast.success('Paro finalizado correctamente')
            await cargarParos()
        } else {
            toast.error('Error: ' + response.data.message)
        }
    } catch (error) {
        console.error('Error en finalizarParo:', error)
        if (error.response) {
            console.error('Respuesta del servidor:', error.response.data)
            toast.error(`Error ${error.response.status}: ${error.response.data.message || 'Error al finalizar el paro'}`)
        } else {
            toast.error('Error de conexión: ' + error.message)
        }
    }
    
    confirmStrike.value = null
    confirmEndTime.value = ''
}

const cancelEndStrike = () => {
    showConfirmModal.value = false
    confirmStrike.value = null
    confirmEndTime.value = ''
}

// Cerrar turno
const cerrarTurno = async () => {
    if (!dailyProgramId.value) {
        toast.error('No hay programa diario para cerrar')
        return
    }

    if (!selectedLineId.value) {
        toast.error('No hay línea de producción seleccionada')
        return
    }

    if (!confirm('¿Estás seguro de cerrar el turno de esta línea? Esta acción notificará al supervisor para que procese el balance.')) {
        return
    }

    cerrandoTurno.value = true
    try {
        const response = await axios.post('/operador/close-shift', {
            daily_program_id: dailyProgramId.value,
            production_line_id: selectedLineId.value
        })

        if (response.data.success) {
            const message = response.data.all_closed
                ? 'Todas las líneas cerradas correctamente. El supervisor revisará el balance.'
                : `Línea cerrada correctamente (${response.data.closed_lines} de ${response.data.total_lines} líneas cerradas).`

            toast.success(message)
            // Recargar la página para actualizar el estado
            router.reload()
        } else {
            toast.error('Error: ' + response.data.message)
        }
    } catch (error) {
        console.error('Error al cerrar turno:', error)
        toast.error('Error al cerrar el turno')
    } finally {
        cerrandoTurno.value = false
    }
}

// Iniciar actualización automática de tiempo (cada minuto)
const iniciarActualizacionTiempo = () => {
    if (timerInterval) clearInterval(timerInterval)
    timerInterval = setInterval(() => {
        // Forzar actualización reactiva de la lista de paros
        strikesList.value = [...strikesList.value]
    }, 60000)
}

// Recargar paros cada 30 segundos para detectar cambios por Gerente de Mantenimiento
let parosInterval = null
const iniciarRecargaParos = () => {
    if (parosInterval) clearInterval(parosInterval)
    parosInterval = setInterval(() => {
        cargarParos()
    }, 30000)
}

// Watch para cuando cambia la línea seleccionada
watch(() => selectedLineId.value, () => {
    // Reiniciar valores de producción
    produccionValues.value = {}
})

onMounted(() => {
    initProduccion()
    iniciarActualizacionTiempo()
    iniciarRecargaParos()
    console.log('=== DASHBOARD OPERADOR ===')
    console.log('Líneas:', productionLinesData.value.length)
    console.log('Fecha:', fechaSeleccionada.value)
})

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval)
    if (parosInterval) clearInterval(parosInterval)
})
</script>