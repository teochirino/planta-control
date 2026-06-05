<template>
    <CalidadLayout>
        <CalidadSidebar />
        
        <div class="flex flex-col gap-2.5 p-6 ml-16">
            <!-- Encabezado -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <div class="px-4 py-3">
                    <h1 class="text-xl font-extrabold text-[#0b2a40]">Registrar Rechazo</h1>
                    <p class="text-sm text-[#6a8090] mt-1">Registre las piezas rechazadas por línea de producción</p>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <div class="px-4 py-3 flex items-center gap-4 flex-wrap">
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Fecha:</label>
                        <input 
                            type="date" 
                            v-model="selectedDate" 
                            @change="handleDateChange"
                            class="border border-[#d4dee8] rounded-md px-3 py-2 text-sm font-bold text-[#0c1c28]"
                        >
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Línea de Producción:</label>
                        <select 
                            v-model="selectedProductionLineId" 
                            @change="handleLineChange"
                            class="border border-[#d4dee8] rounded-md px-3 py-2 text-sm font-bold text-[#0c1c28] bg-white focus:outline-none focus:border-[#174060]"
                        >
                            <option value="">Seleccione una línea</option>
                            <option v-for="line in productionLines" :key="line.id" :value="line.id">
                                {{ line.title }} - {{ line.work_center?.name }}
                            </option>
                        </select>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Turno:</label>
                        <select 
                            v-model="selectedShift" 
                            @change="handleShiftChange"
                            class="border border-[#d4dee8] rounded-md px-3 py-2 text-sm font-bold text-[#0c1c28] bg-white"
                        >
                            <option value="matutino">Matutino</option>
                            <option value="vespertino">Vespertino</option>
                            <option value="nocturno">Nocturno</option>
                        </select>
                    </div>
                    
                    <button 
                        @click="loadSchedules"
                        class="px-4 py-2 bg-[#0b2a40] text-white rounded-md text-xs font-bold hover:opacity-85 transition"
                    >
                        Ver
                    </button>
                </div>
            </div>
            
            <!-- Tabla de Horarios -->
            <div v-if="schedules.length > 0" class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <div class="px-4 py-3 border-b border-[#d4dee8]">
                    <h2 class="text-lg font-bold text-[#0b2a40]">Horarios de Producción</h2>
                    <p class="text-xs text-[#6a8090] mt-1">Registre los rechazos para cada horario con producción</p>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-[#f4f7fa]">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold tracking-widest uppercase text-[#4e6070]">Hora</th>
                                <th class="px-4 py-3 text-center text-xs font-bold tracking-widest uppercase text-[#4e6070]">Producidas</th>
                                <th class="px-4 py-3 text-center text-xs font-bold tracking-widest uppercase text-[#4e6070]">Rechazadas</th>
                                <th class="px-4 py-3 text-center text-xs font-bold tracking-widest uppercase text-[#4e6070]">Registrado Por</th>
                                <th class="px-4 py-3 text-center text-xs font-bold tracking-widest uppercase text-[#4e6070]">Fecha Registro</th>
                                <th class="px-4 py-3 text-center text-xs font-bold tracking-widest uppercase text-[#4e6070]">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="schedule in schedules" :key="schedule.id" class="border-b border-[#d4dee8] hover:bg-[#f8f9fb]">
                                <td class="px-4 py-3 text-sm font-bold text-[#0b2a40]">
                                    {{ schedule.time_range }}
                                </td>
                                <td class="px-4 py-3 text-center text-sm font-bold text-[#0b2a40]">
                                    {{ formatNumber(schedule.produced) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <input 
                                        type="number" 
                                        v-model.number="rejectionInputs[schedule.id]"
                                        :placeholder="schedule.rejected || '0'"
                                        min="0"
                                        :max="schedule.produced"
                                        class="w-24 border border-[#d4dee8] rounded-md px-2 py-1 text-sm text-center font-bold text-[#0c1c28]"
                                    >
                                </td>
                                <td class="px-4 py-3 text-center text-xs text-[#6a8090]">
                                    {{ schedule.rejected_by || '-' }}
                                </td>
                                <td class="px-4 py-3 text-center text-xs text-[#6a8090]">
                                    {{ schedule.rejected_at || '-' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button 
                                        @click="registerRejection(schedule.id)"
                                        :disabled="!rejectionInputs[schedule.id] || rejectionInputs[schedule.id] < 0"
                                        class="px-3 py-1 bg-[#0b8a3d] text-white rounded-md text-xs font-bold hover:opacity-85 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Registrar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Mensaje cuando no hay datos -->
            <div v-else-if="selectedProductionLineId" class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-8 text-center">
                <div class="text-4xl mb-3">📋</div>
                <h3 class="text-lg font-bold text-[#0b2a40] mb-2">No hay horarios con producción</h3>
                <p class="text-sm text-[#6a8090]">
                    No se encontraron horarios con producción para la línea seleccionada en la fecha {{ formatDate(selectedDate) }}.
                </p>
            </div>
            
            <!-- Mensaje inicial -->
            <div v-else class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-8 text-center">
                <div class="text-4xl mb-3">🔍</div>
                <h3 class="text-lg font-bold text-[#0b2a40] mb-2">Seleccione una línea de producción</h3>
                <p class="text-sm text-[#6a8090]">
                    Seleccione una fecha, línea de producción y turno para ver los horarios disponibles.
                </p>
            </div>
        </div>
    </CalidadLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import CalidadLayout from '@/Layouts/CalidadLayout.vue'
import CalidadSidebar from '@/Components/CalidadSidebar.vue'
import axios from 'axios'

const props = defineProps({
    productionLines: {
        type: Array,
        default: () => []
    },
    selectedDate: {
        type: String,
        default: ''
    },
    selectedProductionLineId: {
        type: [Number, String],
        default: ''
    },
    selectedShift: {
        type: String,
        default: 'matutino'
    },
    schedules: {
        type: Array,
        default: () => []
    }
})

const selectedDate = ref(props.selectedDate || new Date().toISOString().split('T')[0])
const selectedProductionLineId = ref(props.selectedProductionLineId || '')
const selectedShift = ref(props.selectedShift || 'matutino')
const schedules = ref(props.schedules || [])
const rejectionInputs = reactive({})

const formatNumber = (num) => {
    if (num === undefined || num === null) return '0'
    return num.toLocaleString('es-MX')
}

const formatDate = (dateStr) => {
    if (!dateStr) return ''
    const [year, month, day] = dateStr.split('-')
    return `${parseInt(day)}/${parseInt(month)}/${year}`
}

const loadSchedules = () => {
    router.get(route('calidad.registrar-rechazo'), {
        date: selectedDate.value,
        production_line_id: selectedProductionLineId.value,
        shift: selectedShift.value
    })
}

const handleDateChange = () => {
    if (selectedProductionLineId.value) {
        loadSchedules()
    }
}

const handleLineChange = () => {
    if (selectedProductionLineId.value) {
        loadSchedules()
    }
}

const handleShiftChange = () => {
    if (selectedProductionLineId.value) {
        loadSchedules()
    }
}

const registerRejection = async (scheduleId) => {
    const rejected = rejectionInputs[scheduleId]
    
    if (rejected === undefined || rejected === null || rejected < 0) {
        alert('Por favor ingrese una cantidad válida de piezas rechazadas')
        return
    }
    
    try {
        const response = await axios.post(route('calidad.store-rechazo'), {
            schedule_id: scheduleId,
            rejected: rejected
        })
        
        if (response.data.success) {
            alert('Rechazo registrado correctamente')
            loadSchedules()
        }
    } catch (error) {
        console.error('Error al registrar rechazo:', error)
        alert('Error al registrar el rechazo: ' + (error.response?.data?.message || error.message))
    }
}
</script>
