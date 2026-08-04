<template>
    <CalidadLayout>
        <CalidadSidebar />
        
        <div class="flex flex-col gap-2.5 p-6 ml-16">
            <!-- Encabezado -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <div class="px-4 py-3">
                    <h1 class="text-xl font-extrabold text-[#0b2a40]">Rechazos</h1>
                    <p class="text-sm text-[#6a8090] mt-1">Consulte los rechazos registrados por centro de trabajo</p>
                </div>
            </div>
            
            <!-- Filtro de Fecha -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <div class="px-4 py-3 flex items-center gap-4">
                    <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Fecha:</label>
                    <input 
                        type="date" 
                        v-model="selectedDate" 
                        @change="handleDateChange"
                        class="border border-[#d4dee8] rounded-md px-3 py-2 text-sm font-bold text-[#0c1c28]"
                    >
                    <span class="text-sm text-[#6a8090]">{{ formatDate(selectedDate) }}</span>
                </div>
            </div>
            
            <!-- Lista de Centros de Trabajo con Rechazos -->
            <div v-if="workCentersWithRejections.length > 0" class="space-y-4">
                <div 
                    v-for="(workCenter, index) in workCentersWithRejections" 
                    :key="index"
                    class="bg-white border border-[#d4dee8] rounded-xl shadow-sm"
                >
                    <!-- Encabezado del Centro de Trabajo -->
                    <div class="px-4 py-3 bg-[#f4f7fa] border-b border-[#d4dee8] flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-[#0b2a40]">{{ workCenter.work_center_name }}</h2>
                            <p class="text-xs text-[#6a8090] mt-1">Turno: {{ getTurnoLabel(workCenter.shift) }}</p>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-extrabold text-[#ba2418]">{{ formatNumber(workCenter.total_rejected) }}</div>
                            <div class="text-xs text-[#6a8090]">piezas rechazadas</div>
                        </div>
                    </div>
                    
                    <!-- Métricas -->
                    <div class="px-4 py-3 border-b border-[#d4dee8] grid grid-cols-3 gap-4">
                        <div class="text-center p-3 bg-white border border-[#d4dee8] rounded-lg">
                            <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-1">Producidas</div>
                            <div class="text-xl font-extrabold text-[#0b2a40]">{{ formatNumber(workCenter.total_produced) }}</div>
                        </div>
                        
                        <div class="text-center p-3 bg-white border border-[#d4dee8] rounded-lg">
                            <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-1">Rechazadas</div>
                            <div class="text-xl font-extrabold text-[#ba2418]">{{ formatNumber(workCenter.total_rejected) }}</div>
                        </div>
                        
                        <div class="text-center p-3 bg-white border border-[#d4dee8] rounded-lg">
                            <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-1">% Rechazo</div>
                            <div class="text-xl font-extrabold" :class="getRejectionPercentageClass(workCenter.rejection_percentage)">
                                {{ workCenter.rejection_percentage }}%
                            </div>
                        </div>
                    </div>
                    
                    <!-- Detalles por Línea -->
                    <div class="px-4 py-3">
                        <h3 class="text-sm font-bold text-[#0b2a40] mb-3">Detalles por Línea de Producción</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-[#f4f7fa]">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-bold tracking-widest uppercase text-[#4e6070]">Línea</th>
                                        <th class="px-3 py-2 text-center text-xs font-bold tracking-widest uppercase text-[#4e6070]">Horario</th>
                                        <th class="px-3 py-2 text-center text-xs font-bold tracking-widest uppercase text-[#4e6070]">Producidas</th>
                                        <th class="px-3 py-2 text-center text-xs font-bold tracking-widest uppercase text-[#4e6070]">Rechazadas</th>
                                        <th class="px-3 py-2 text-center text-xs font-bold tracking-widest uppercase text-[#4e6070]">Registrado Por</th>
                                        <th class="px-3 py-2 text-center text-xs font-bold tracking-widest uppercase text-[#4e6070]">Fecha/Hora</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr 
                                        v-for="(detail, detailIndex) in workCenter.rejection_details" 
                                        :key="detailIndex"
                                        class="border-b border-[#d4dee8] hover:bg-[#f8f9fb]"
                                    >
                                        <td class="px-3 py-2 text-sm font-bold text-[#0b2a40]">{{ detail.production_line }}</td>
                                        <td class="px-3 py-2 text-center text-sm text-[#6a8090]">{{ detail.time_range }}</td>
                                        <td class="px-3 py-2 text-center text-sm font-bold text-[#0b2a40]">{{ formatNumber(detail.produced) }}</td>
                                        <td class="px-3 py-2 text-center text-sm font-bold text-[#ba2418]">{{ formatNumber(detail.rejected) }}</td>
                                        <td class="px-3 py-2 text-center text-xs text-[#6a8090]">{{ detail.rejected_by }}</td>
                                        <td class="px-3 py-2 text-center text-xs text-[#6a8090]">{{ detail.rejected_at }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mensaje cuando no hay rechazos -->
            <div v-else class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-8 text-center">
                <div class="text-4xl mb-3">✅</div>
                <h3 class="text-lg font-bold text-[#0b2a40] mb-2">No hay rechazos registrados</h3>
                <p class="text-sm text-[#6a8090]">
                    No se encontraron rechazos para la fecha {{ formatDate(selectedDate) }}.
                </p>
            </div>
        </div>
    </CalidadLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import CalidadLayout from '@/Layouts/CalidadLayout.vue'
import CalidadSidebar from '@/Components/CalidadSidebar.vue'

const props = defineProps({
    selectedDate: {
        type: String,
        default: ''
    },
    workCentersWithRejections: {
        type: Array,
        default: () => []
    }
})

const selectedDate = ref(props.selectedDate || new Date().toISOString().split('T')[0])
const workCentersWithRejections = ref(props.workCentersWithRejections || [])

const formatNumber = (num) => {
    if (num === undefined || num === null) return '0'
    return num.toLocaleString('es-MX')
}

const formatDate = (dateStr) => {
    if (!dateStr) return ''
    const [year, month, day] = dateStr.split('-')
    return `${parseInt(day)}/${parseInt(month)}/${year}`
}

const getTurnoLabel = (shift) => {
    const shifts = {
        matutino: 'Matutino',
        vespertino: 'Vespertino'
    }
    return shifts[shift] || shift
}

const getRejectionPercentageClass = (percentage) => {
    if (percentage >= 10) return 'text-[#ba2418]'
    if (percentage >= 5) return 'text-[#f59e0b]'
    return 'text-[#0b8a3d]'
}

const handleDateChange = () => {
    router.get(route('calidad.rechazos'), {
        date: selectedDate.value
    })
}
</script>
