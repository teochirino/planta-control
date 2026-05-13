<template>
    <AuthenticatedLayout>
        <div class="flex flex-col gap-2.5">
            <!-- Selector de Centro de Trabajo -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <div class="px-4 py-3 flex items-center gap-4 flex-wrap">
                    <span class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Centro de Trabajo:</span>
                    
                    <div class="flex items-center gap-3 flex-1">
                        <select v-model="selectedWorkCenterId" @change="cambiarCentro"
                                class="border border-[#d4dee8] rounded-md px-3 py-2 text-sm font-bold text-[#0c1c28] bg-white focus:outline-none focus:border-[#174060]">
                            <option v-for="wc in workCentersData" :key="wc.id" :value="wc.id">
                                {{ wc.name }}
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
                    
                    <div class="flex gap-2">
                        <Link :href="route('supervisor.daily-production', { work_center_id: selectedWorkCenterId, date: fechaSeleccionada, shift: turnoSeleccionado })" 
                              class="px-4 py-2 bg-[#0b2a40] text-white rounded-md text-xs font-bold hover:opacity-85 transition">
                            📝 Registro Diario de Producción
                        </Link>
                    </div>
                </div>
            </div>
            
            <!-- Información del Centro -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-6">
                <h2 class="text-xl font-extrabold text-[#0b2a40] mb-4">{{ selectedWorkCenterData?.name || 'Cargando...' }}</h2>
                
                <!-- Tarjetas de KPIs -->
                <div v-if="kpisData" class="mb-6 p-4 bg-[#f8f9fb] border border-[#d4dee8] rounded-lg">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-bold text-[#0b2a40]">Programa del Turno</h3>
                        <span class="text-xs font-semibold text-[#6a8090]">{{ turnoLabel }} - {{ fechaFormateada }}</span>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-5 lg:grid-cols-10 gap-2">
                        <div class="p-3 bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-1">Programado</div>
                            <div class="text-2xl font-extrabold text-[#0b2a40]">{{ formatNumber(kpisData.programmed) }}</div>
                        </div>
                        
                        <div class="p-3 bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-1">Atraso</div>
                            <div class="text-2xl font-extrabold text-[#ba2418]">{{ formatNumber(kpisData.backwardness) }}</div>
                        </div>
                        
                        <div class="p-3 bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-1">Adelantadas</div>
                            <div class="text-2xl font-extrabold text-[#0b8a3d]">{{ formatNumber(kpisData.advanced) }}</div>
                        </div>
                        
                        <div class="p-3 bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-1">Total a Producir</div>
                            <div class="text-2xl font-extrabold text-[#0b2a40]">{{ formatNumber(kpisData.total_to_produce) }}</div>
                        </div>
                        
                        <div class="p-3 bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-1">Fabricadas</div>
                            <div class="text-2xl font-extrabold text-[#0b2a40]">{{ formatNumber(kpisData.fabricated) }}</div>
                        </div>
                        
                        <div class="p-3 bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-1">Diferencia</div>
                            <div class="text-2xl font-extrabold" :class="kpisData.difference >= 0 ? 'text-[#0b8a3d]' : 'text-[#ba2418]'">
                                {{ kpisData.difference >= 0 ? '+' : '' }}{{ formatNumber(kpisData.difference) }}
                            </div>
                        </div>
                        
                        <div class="p-3 bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-1">Cumplimiento</div>
                            <div class="text-2xl font-extrabold" :class="complianceClass">
                                {{ kpisData.compliance }}%
                            </div>
                        </div>
                        
                        <div class="p-3 bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-1">Real vs Ideal</div>
                            <div class="text-2xl font-extrabold text-[#0b2a40]">{{ kpisData.real_vs_ideal }}%</div>
                        </div>
                        
                        <div class="p-3 bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-1">Ahorro Activos</div>
                            <div class="text-xl font-extrabold text-[#0b8a3d]">${{ formatNumber(kpisData.saved_amount) }}</div>
                        </div>
                        
                        <div class="p-3 bg-white border border-[#d4dee8] rounded-lg text-center">
                            <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-1">Cap. Instalada</div>
                            <div class="text-2xl font-extrabold text-[#0b2a40]">{{ formatNumber(kpisData.installed_capacity) }}</div>
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
                    <Link :href="route('supervisor.daily-production', { work_center_id: selectedWorkCenterId, date: fechaSeleccionada, shift: turnoSeleccionado })" 
                          class="inline-block px-6 py-3 bg-[#0b2a40] text-white rounded-md text-sm font-bold hover:opacity-85 transition">
                        ➕ Crear Programa Diario
                    </Link>
                </div>
                
                <!-- Estadísticas generales -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 bg-[#f4f7fa] border border-[#d4dee8] rounded-lg">
                        <div class="text-xs font-bold tracking-widest uppercase text-[#4e6070] mb-1">Capacidad Instalada</div>
                        <div class="text-3xl font-extrabold text-[#0b2a40]">{{ formatNumber(selectedWorkCenterData?.installed_capacity) }}</div>
                        <div class="text-xs text-[#6a8090] mt-1">piezas/hora</div>
                    </div>
                    
                    <div class="p-4 bg-[#f4f7fa] border border-[#d4dee8] rounded-lg">
                        <div class="text-xs font-bold tracking-widest uppercase text-[#4e6070] mb-1">Líneas de Producción</div>
                        <div class="text-3xl font-extrabold text-[#0b2a40]">{{ selectedWorkCenterData?.production_lines?.length || 0 }}</div>
                        <div class="text-xs text-[#6a8090] mt-1">líneas activas</div>
                    </div>
                    
                    <div class="p-4 bg-[#f4f7fa] border border-[#d4dee8] rounded-lg">
                        <div class="text-xs font-bold tracking-widest uppercase text-[#4e6070] mb-1">Fecha Actual</div>
                        <div class="text-xl font-extrabold text-[#0b2a40]">{{ fechaFormateada }}</div>
                        <div class="text-xs text-[#6a8090] mt-1">{{ nombreDia }}</div>
                    </div>
                </div>
                
                <!-- Líneas de Producción y Semáforos -->
                <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <!-- Líneas de Producción (2/3 del ancho) -->
                    <div class="lg:col-span-2">
                        <h3 class="text-sm font-bold text-[#0b2a40] mb-3">Líneas de Producción</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                            <div v-for="line in (selectedWorkCenterData?.production_lines || [])" :key="line.id" class="p-3 border border-[#d4dee8] rounded-lg bg-white">
                                <div class="font-bold text-[#0b2a40]">{{ line.title }}</div>
                                <div class="text-xs text-[#6a8090] mt-1">
                                    Cap: {{ formatNumber(line.installed_capacity) }} pzs/h | 
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
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Link } from '@inertiajs/vue3'
import SemaforosArea from '@/Components/SemaforosArea.vue'

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

// 🔧 FORZAR FECHA ACTUAL
const hoy = new Date()
const fechaActualStr = hoy.toISOString().split('T')[0]

// Estados - IGNORAR props.selectedDate
const workCentersData = ref(props.workCenters || [])
const selectedWorkCenterData = ref(props.selectedWorkCenter || {})
const kpisData = ref(props.kpis)
const attributesData = ref(props.attributes || [])
const selectedWorkCenterId = ref(props.selectedWorkCenter?.id || (props.workCenters?.[0]?.id))
const fechaSeleccionada = ref(fechaActualStr)  // 🔧 FORZADO a fecha actual
const turnoSeleccionado = ref(props.selectedShift || 'matutino')

// Computed
const turnoLabel = computed(() => {
    const shifts = { matutino: 'Matutino', vespertino: 'Vespertino', nocturno: 'Nocturno' }
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
    const fecha = new Date(fechaSeleccionada.value)
    return fecha.toLocaleDateString('es-MX', { weekday: 'long' })
})

const complianceClass = computed(() => {
    if (!kpisData.value) return ''
    if (kpisData.value.compliance >= 100) return 'text-[#0b8a3d]'
    if (kpisData.value.compliance >= 95) return 'text-[#f59e0b]'
    return 'text-[#ba2418]'
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

onMounted(() => {
    console.log('=== DASHBOARD SUPERVISOR ===')
    console.log('fechaSeleccionada:', fechaSeleccionada.value)
    console.log('fechaFormateada:', fechaFormateada.value)
    console.log('fechaActualStr:', fechaActualStr)
})
</script>