<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <SupervisorSidebar />
        <DisplayModeToggle />
        
        <div :class="isTVMode() ? 'p-8 ml-16 2xl:p-12 2xl:ml-20' : 'p-6 ml-16'">
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
                    
                    <div class="flex gap-2">
                        <Link v-if="kpisData" :href="route('supervisor.daily-production', { work_center_id: selectedWorkCenterId, date: fechaSeleccionada, shift: turnoSeleccionado })"
                              :class="isTVMode() ? 'px-6 py-3 text-base 2xl:px-8 2xl:py-4 2xl:text-lg' : 'px-4 py-2 text-xs'"
                              class="bg-[#0b2a40] text-white rounded-md font-bold hover:opacity-85 transition">
                            📝 Registro Diario de Producción
                        </Link>
                        <button v-else disabled
                                :class="isTVMode() ? 'px-6 py-3 text-base 2xl:px-8 2xl:py-4 2xl:text-lg' : 'px-4 py-2 text-xs'"
                                class="bg-gray-300 text-gray-500 rounded-md font-bold cursor-not-allowed">
                            📝 Registro Diario de Producción
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Información del Centro -->
            <div :class="isTVMode() ? 'p-8 2xl:p-12' : 'p-6'" class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <h2 :class="isTVMode() ? 'text-3xl 2xl:text-5xl' : 'text-xl'" class="font-extrabold text-[#0b2a40] mb-4">{{ selectedWorkCenterData?.name || 'Cargando...' }}</h2>
                
                <!-- Tarjetas de KPIs -->
                <div v-if="kpisData" :class="isTVMode() ? 'mb-8 p-6' : 'mb-6 p-4'" class="bg-[#f8f9fb] border border-[#d4dee8] rounded-lg">
                    <div class="flex items-center justify-between mb-3">
                        <h3 :class="isTVMode() ? 'text-lg' : 'text-sm'" class="font-bold text-[#0b2a40]">Programa del Turno</h3>
                        <div class="flex items-center gap-3">
                            <span v-if="dailyProgramData?.program?.codigo" :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold text-[#174060] bg-[#e8f4f8] px-2 py-1 rounded">
                                {{ dailyProgramData.program.codigo }}
                            </span>
                            <span :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-semibold text-[#6a8090]">{{ turnoLabel }} - {{ fechaFormateada }}</span>
                        </div>
                    </div>
                    
                    <div :class="isTVMode() ? 'gap-4' : 'gap-2'" class="grid grid-cols-2 md:grid-cols-5 lg:grid-cols-10">
                        <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold tracking-wider uppercase text-[#4e6070] mb-1">Programado</div>
                            <div :class="isTVMode() ? 'text-4xl' : 'text-2xl'" class="font-extrabold text-[#0b2a40]">{{ formatNumber(kpisData.programmed) }}</div>
                        </div>
                        
                        <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold tracking-wider uppercase text-[#4e6070] mb-1">Atraso</div>
                            <div :class="isTVMode() ? 'text-4xl' : 'text-2xl'" class="font-extrabold text-[#ba2418]">{{ formatNumber(kpisData.backwardness) }}</div>
                        </div>
                        
                        <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold tracking-wider uppercase text-[#4e6070] mb-1">Adelantadas</div>
                            <div :class="isTVMode() ? 'text-4xl' : 'text-2xl'" class="font-extrabold text-[#0b8a3d]">{{ formatNumber(kpisData.advanced) }}</div>
                        </div>
                        
                        <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold tracking-wider uppercase text-[#4e6070] mb-1">Total a Producir</div>
                            <div :class="isTVMode() ? 'text-4xl' : 'text-2xl'" class="font-extrabold text-[#0b2a40]">{{ formatNumber(kpisData.total_to_produce) }}</div>
                        </div>
                        
                        <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold tracking-wider uppercase text-[#4e6070] mb-1">Fabricadas</div>
                            <div :class="isTVMode() ? 'text-4xl' : 'text-2xl'" class="font-extrabold text-[#0b2a40]">{{ formatNumber(kpisData.fabricated) }}</div>
                        </div>
                        
                        <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold tracking-wider uppercase text-[#4e6070] mb-1">Diferencia</div>
                            <div :class="[isTVMode() ? 'text-4xl' : 'text-2xl', 'font-extrabold', kpisData.difference >= 0 ? 'text-[#0b8a3d]' : 'text-[#ba2418]']">
                                {{ kpisData.difference >= 0 ? '+' : '' }}{{ formatNumber(kpisData.difference) }}
                            </div>
                        </div>
                        
                        <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold tracking-wider uppercase text-[#4e6070] mb-1">Cumplimiento</div>
                            <div :class="[isTVMode() ? 'text-4xl' : 'text-2xl', 'font-extrabold', complianceClass]">
                                {{ kpisData.compliance }}%
                            </div>
                        </div>
                        
                        <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold tracking-wider uppercase text-[#4e6070] mb-1">Real vs Ideal</div>
                            <div :class="isTVMode() ? 'text-4xl' : 'text-2xl'" class="font-extrabold text-[#0b2a40]">{{ kpisData.real_vs_ideal }}%</div>
                        </div>
                        
                        <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold tracking-wider uppercase text-[#4e6070] mb-1">Cap. Instalada</div>
                            <div :class="isTVMode() ? 'text-4xl' : 'text-2xl'" class="font-extrabold text-[#0b2a40]">{{ formatNumber(kpisData.installed_capacity) }}</div>
                        </div>
                    </div>
                </div>
                
                <!-- Mensaje cuando no hay programa -->
                <div v-else class="mb-6 p-6 bg-[#fff6da] border border-[#e8d488] rounded-lg text-center">
                    <div class="text-4xl mb-3">📋</div>
                    <h3 class="text-lg font-bold text-[#0b2a40] mb-2">No hay programa diario registrado</h3>
                    <p class="text-sm text-[#6a8090] mb-4">
                        No existe un programa para <strong>{{ selectedWorkCenterData?.name }}</strong> en el turno <strong>{{ turnoLabel }}</strong> del <strong>{{ fechaFormateada }}</strong>.
                    </p>
                    <!-- Comentado: Los supervisores no pueden crear programas
                    <Link :href="route('supervisor.daily-production', { work_center_id: selectedWorkCenterId, date: fechaSeleccionada, shift: turnoSeleccionado })" 
                          class="inline-block px-6 py-3 bg-[#0b2a40] text-white rounded-md text-sm font-bold hover:opacity-85 transition">
                        ➕ Crear Programa Diario
                    </Link>
                    -->
                </div>
                
                <!-- Estadísticas generales -->
                <div :class="isTVMode() ? 'gap-6' : 'gap-4'" class="grid grid-cols-1 md:grid-cols-3">
                    <div :class="isTVMode() ? 'p-6' : 'p-4'" class="bg-[#f4f7fa] border border-[#d4dee8] rounded-lg">
                        <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold tracking-widest uppercase text-[#4e6070] mb-1">Capacidad Instalada</div>
                        <div :class="isTVMode() ? 'text-4xl' : 'text-3xl'" class="font-extrabold text-[#0b2a40]">{{ formatNumber(selectedWorkCenterData?.installed_capacity) }}</div>
                        <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="text-[#6a8090] mt-1">piezas/día</div>
                    </div>
                    
                    <div :class="isTVMode() ? 'p-6' : 'p-4'" class="bg-[#f4f7fa] border border-[#d4dee8] rounded-lg">
                        <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold tracking-widest uppercase text-[#4e6070] mb-1">Líneas de Producción</div>
                        <div :class="isTVMode() ? 'text-4xl' : 'text-3xl'" class="font-extrabold text-[#0b2a40]">{{ selectedWorkCenterData?.production_lines?.length || 0 }}</div>
                        <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="text-[#6a8090] mt-1">líneas activas</div>
                    </div>
                    
                    <div :class="isTVMode() ? 'p-6' : 'p-4'" class="bg-[#f4f7fa] border border-[#d4dee8] rounded-lg">
                        <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="font-bold tracking-widest uppercase text-[#4e6070] mb-1">Fecha Actual</div>
                        <div :class="isTVMode() ? 'text-2xl' : 'text-xl'" class="font-extrabold text-[#0b2a40]">{{ fechaFormateada }}</div>
                        <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="text-[#6a8090] mt-1">{{ nombreDia }}</div>
                    </div>
                </div>
                
                <!-- Líneas de Producción y Semáforos -->
                <div :class="isTVMode() ? 'mt-8 gap-6' : 'mt-6 gap-4'" class="grid grid-cols-1 lg:grid-cols-3">
                    <!-- Líneas de Producción (2/3 del ancho) -->
                    <div class="lg:col-span-2">
                        <h3 :class="isTVMode() ? 'text-lg mb-4' : 'text-sm mb-3'" class="font-bold text-[#0b2a40]">Líneas de Producción</h3>
                        <div :class="isTVMode() ? 'gap-4' : 'gap-3'" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
                            <div v-for="line in (selectedWorkCenterData?.production_lines || [])" :key="line.id" :class="isTVMode() ? 'p-5' : 'p-3'" class="border border-[#d4dee8] rounded-lg bg-white">
                                <div :class="isTVMode() ? 'text-lg' : ''" class="font-bold text-[#0b2a40]">{{ line.title }}</div>
                                <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="text-[#6a8090] mt-1">
                                    Cap: {{ formatNumber(line.installed_capacity) }} pzs/día | 
                                    Costo: ${{ formatNumber(line.cost) }}/min
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Semáforos del Área (1/3 del ancho) -->
                    <div class="lg:col-span-1">
                        <SemaforosArea :attributes="attributesData" />
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'
import SupervisorSidebar from '@/Components/SupervisorSidebar.vue'
import SemaforosArea from '@/Components/SemaforosArea.vue'
import DisplayModeToggle from '@/Components/DisplayModeToggle.vue'
import CurrentTimeDisplay from '@/Components/CurrentTimeDisplay.vue'
import { useDisplayMode } from '@/Composables/useDisplayMode'

const { isTVMode } = useDisplayMode()

// Props
const props = defineProps({
    workCenters: {
        type: Array,
        default: () => []
    },
    selectedWorkCenter: {
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
    kpis: {
        type: Object,
        default: null
    },
    attributes: {
        type: Array,
        default: () => []
    }
})

// El servidor recalcula "hoy" en cada request (hora de la planta, no la del navegador),
// así que basta con usar lo que llega en selectedDate en vez de recalcularlo aquí.
function fechaLocalDeHoy() {
    const hoy = new Date()
    const mes = String(hoy.getMonth() + 1).padStart(2, '0')
    const dia = String(hoy.getDate()).padStart(2, '0')
    return `${hoy.getFullYear()}-${mes}-${dia}`
}

// Estados
const workCentersData = ref(props.workCenters || [])
const selectedWorkCenterData = ref(props.selectedWorkCenter || {})
const kpisData = ref(props.kpis)
const attributesData = ref(props.attributes || [])
const dailyProgramData = ref(props.dailyProgram)
const selectedWorkCenterId = ref(props.selectedWorkCenter?.id || (props.workCenters?.[0]?.id))
const fechaSeleccionada = ref(props.selectedDate || fechaLocalDeHoy())
const turnoSeleccionado = ref(props.selectedShift || 'matutino')

// Computed
const turnoLabel = computed(() => {
    const shifts = { matutino: 'Matutino', vespertino: 'Vespertino' }
    return shifts[turnoSeleccionado.value] || turnoSeleccionado.value
})

const fechaFormateada = computed(() => {
    if (!fechaSeleccionada.value) return ''
    //const fecha = new Date(fechaSeleccionada.value)
    //return fecha.toLocaleDateString('es-MX')
    const [year, month, day] = fechaSeleccionada.value.split('-')
    return `${parseInt(day)}/${parseInt(month)}/${year}`
})

const nombreDia = computed(() => {
    if (!fechaSeleccionada.value) return ''
    const [year, month, day] = fechaSeleccionada.value.split('-')
    const fecha = new Date(year, month - 1, day)
    return fecha.toLocaleDateString('es-MX', { weekday: 'long' })
})

const complianceClass = computed(() => {
    if (!kpisData.value) return ''
    if (kpisData.value.compliance >= 100) return 'text-[#0b8a3d]'
    if (kpisData.value.compliance >= 95) return 'text-[#f59e0b]'
    return 'text-[#ba2418]'
})

// Watch para actualizar dailyProgramData cuando cambian los props
watch(() => props.dailyProgram, (newValue) => {
    dailyProgramData.value = newValue
})

// Métodos
const formatNumber = (num) => {
    if (num === undefined || num === null) return '0'
    return num.toLocaleString('es-MX')
}

const cambiarCentro = () => {
    router.get(route('supervisor.dashboard'), {
        work_center_id: selectedWorkCenterId.value,
        date: fechaSeleccionada.value,
        shift: turnoSeleccionado.value
    })
}

const cambiarFecha = () => {
    router.get(route('supervisor.dashboard'), {
        work_center_id: selectedWorkCenterId.value,
        date: fechaSeleccionada.value,
        shift: turnoSeleccionado.value
    })
}

const cambiarTurno = () => {
    router.get(route('supervisor.dashboard'), {
        work_center_id: selectedWorkCenterId.value,
        date: fechaSeleccionada.value,
        shift: turnoSeleccionado.value
    })
}

</script>