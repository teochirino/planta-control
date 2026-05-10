<template>
    <AuthenticatedLayout>
        <div class="flex flex-col gap-2.5">
            <!-- Selector de Línea de Producción -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <div class="px-4 py-3 flex items-center gap-4 flex-wrap">
                    <span class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Línea de Producción:</span>
                    
                    <div class="flex items-center gap-3 flex-1">
                        <select v-model="selectedLineId" @change="cambiarLinea"
                                class="border border-[#d4dee8] rounded-md px-3 py-2 text-sm font-bold text-[#0c1c28] bg-white focus:outline-none focus:border-[#174060]">
                            <option v-for="line in productionLinesData" :key="line.id" :value="line.id">
                                {{ line.title }}
                            </option>
                        </select>
                        
                        <input type="date" v-model="fechaSeleccionada" @change="cambiarFecha"
                               class="border border-[#d4dee8] rounded-md px-3 py-2 text-sm font-bold text-[#0c1c28]">
                        
                        <select v-model="turnoSeleccionado" @change="cambiarTurno"
                                class="border border-[#d4dee8] rounded-md px-3 py-2 text-sm font-bold text-[#0c1c28] bg-white">
                            <option value="matutino">Matutino</option>
                            <option value="vespertino">Vespertino</option>
                            <option value="nocturno">Nocturno</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Información de la Línea -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-6">
                <h2 class="text-xl font-extrabold text-[#0b2a40] mb-2">{{ selectedLineData?.title || 'Cargando...' }}</h2>
                <p class="text-sm text-[#6a8090] mb-4">
                    Centro de Trabajo: <strong>{{ selectedLineData?.work_center?.name || '-' }}</strong>
                </p>
                
                <!-- KPIs -->
                <div v-if="kpisData" class="mb-6 p-4 bg-[#f8f9fb] border border-[#d4dee8] rounded-lg">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-bold text-[#0b2a40]">Indicadores de la Línea</h3>
                        <span class="text-xs font-semibold text-[#6a8090]">{{ turnoLabel }} - {{ fechaFormateada }}</span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-2">Fabricadas</div>
                            <div class="text-4xl font-extrabold text-[#0b2a40]">{{ formatNumber(kpisData.fabricated) }}</div>
                            <div class="text-xs text-[#6a8090] mt-1">piezas</div>
                        </div>
                        
                        <div class="p-4 bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-2">Min. Paro</div>
                            <div class="text-4xl font-extrabold" :class="strikeMinutesClass">
                                {{ formatNumber(kpisData.strike_minutes || 0) }}
                            </div>
                            <div class="text-xs text-[#6a8090] mt-1">minutos</div>
                        </div>
                        
                        <div class="p-4 bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-2">Costo de Paro</div>
                            <div class="text-4xl font-extrabold text-[#ba2418]">${{ formatNumber(kpisData.strike_cost || 0) }}</div>
                            <div class="text-xs text-[#6a8090] mt-1">pesos</div>
                        </div>
                    </div>
                </div>
                
                <!-- Mensaje cuando no hay programa -->
                <div v-else-if="!dailyProgramId" class="mb-6 p-6 bg-[#fff6da] border border-[#e8d488] rounded-lg text-center">
                    <div class="text-4xl mb-3">📋</div>
                    <h3 class="text-lg font-bold text-[#0b2a40] mb-2">No hay programa diario registrado</h3>
                    <p class="text-sm text-[#6a8090]">
                        No existe un programa para <strong>{{ selectedLineData?.title }}</strong> en el turno <strong>{{ turnoLabel }}</strong> del <strong>{{ fechaFormateada }}</strong>.
                    </p>
                    <p class="text-xs text-[#6a8090] mt-2">
                        El supervisor debe crear el programa diario primero.
                    </p>
                </div>
                
                <!-- Tabla de Producción por Hora -->
                <div v-if="dailyProgramId && schedulesData.length > 0" class="mb-6">
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
                                <tr v-for="schedule in schedulesData" :key="schedule.id" class="border-b border-[#d4dee8] hover:bg-[#f8f9fb]">
                                    <td class="px-4 py-3 text-sm font-semibold text-[#0b2a40]">
                                        {{ formatTime(schedule.start_time) }} - {{ formatTime(schedule.end_time) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <input type="number" 
                                               min="0" 
                                               v-model="produccionValues[schedule.id]"
                                               @blur="guardarProduccion(schedule.id)"
                                               class="w-24 px-3 py-2 border border-[#d4dee8] rounded-md text-center font-bold text-[#0b2a40] focus:outline-none focus:border-[#174060]">
                                    </td>
                                </tr>
                                <tr class="bg-[#f4f7fa] font-bold">
                                    <td class="px-4 py-3 text-sm text-[#0b2a40]">TOTAL</td>
                                    <td class="px-4 py-3 text-center text-lg font-extrabold text-[#0b2a40]">
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
                        <h3 class="text-sm font-bold text-[#0b2a40]">⚠️ Registro de Paros</h3>
                        <button @click="abrirModalParo" class="px-4 py-2 bg-[#ba2418] text-white rounded-md text-xs font-bold hover:opacity-85 transition">
                            + Registrar Paro
                        </button>
                    </div>
                    
                    <div v-if="strikesList.length > 0" class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-[#0b2a40] text-white">
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase">Inicio</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase">Fin</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase">Descripción</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold uppercase">Duración</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="strike in strikesList" :key="strike.id" class="border-b border-[#d4dee8]">
                                    <td class="px-4 py-3 text-sm text-[#0b2a40]">{{ formatTime(strike.start_time) }}</td>
                                    <td class="px-4 py-3 text-sm text-[#0b2a40]">
                                        <span v-if="strike.end_time">{{ formatTime(strike.end_time) }}</span>
                                        <span v-else class="text-[#f59e0b] font-bold">En curso...</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-[#6a8090]">{{ strike.description }}</td>
                                    <td class="px-4 py-3 text-center text-sm font-bold" :class="strike.end_time ? 'text-[#ba2418]' : 'text-[#f59e0b]'">
                                        <span v-if="strike.end_time">{{ strike.minutes || calcularDuracion(strike) }} min</span>
                                        <span v-else>{{ tiempoTranscurrido(strike) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button v-if="!strike.end_time" 
                                                @click="finalizarParo(strike)"
                                                class="px-3 py-1 bg-[#0b8a3d] text-white rounded text-xs font-bold hover:opacity-85">
                                            Finalizar
                                        </button>
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
        
        <!-- Modal para Registrar Paro -->
        <div v-if="modalVisible" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
                <h3 class="text-lg font-bold text-[#0b2a40] mb-4">Registrar Paro</h3>
                <div class="space-y-4">
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
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

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
    start_time: '',
    end_time: '',
    description: ''
})

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
    nuevoParo.value = {
        start_time: new Date().toLocaleTimeString().slice(0, 5),
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
        alert('Indique la hora de inicio')
        return
    }
    if (!nuevoParo.value.description) {
        alert('Describa el paro')
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
            description: nuevoParo.value.description
        })
        
        if (response.data.success) {
            alert('Paro registrado correctamente')
            cerrarModalParo()
            await cargarParos()
        } else {
            alert('Error: ' + response.data.message)
        }
    } catch (error) {
        console.error('Error:', error)
        alert('Error al registrar el paro')
    } finally {
        guardandoParo.value = false
    }
}

// Finalizar paro
const finalizarParo = async (strike) => {
    if (!confirm('¿Finalizar este paro?')) return
    
    const now = new Date()
    const hours = String(now.getHours()).padStart(2, '0')
    const minutes = String(now.getMinutes()).padStart(2, '0')
    const endTime = `${hours}:${minutes}`
    
    try {
        // Usar axios.put explícitamente
        const response = await axios.put(`/operador/strikes/${strike.id}/end`, {
            end_time: endTime
        }, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        
        if (response.data.success) {
            alert('Paro finalizado correctamente')
            await cargarParos()
        } else {
            alert('Error: ' + response.data.message)
        }
    } catch (error) {
        console.error('Error en finalizarParo:', error)
        if (error.response) {
            console.error('Respuesta del servidor:', error.response.data)
            alert(`Error ${error.response.status}: ${error.response.data.message || 'Error al finalizar el paro'}`)
        } else {
            alert('Error de conexión: ' + error.message)
        }
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

// Watch para cuando cambia la línea seleccionada
watch(() => selectedLineId.value, () => {
    // Reiniciar valores de producción
    produccionValues.value = {}
})

onMounted(() => {
    initProduccion()
    iniciarActualizacionTiempo()
    console.log('=== DASHBOARD OPERADOR ===')
    console.log('Líneas:', productionLinesData.value.length)
    console.log('Fecha:', fechaSeleccionada.value)
})

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval)
})
</script>