<template>
    <AuthenticatedLayout>
        <CalidadSidebar />
        
        <div class="flex flex-col gap-2.5">
            <!-- Encabezado -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <div class="px-4 py-3">
                    <h1 class="text-xl font-extrabold text-[#0b2a40]">Bitácora de Piezas Rechazadas</h1>
                    <p class="text-sm text-[#6a8090] mt-1">Seguimiento de resolución de piezas rechazadas</p>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <div class="px-4 py-3 flex items-center gap-4 flex-wrap">
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Centro:</label>
                        <select 
                            v-model="filters.work_center_id" 
                            @change="handleFilterChange"
                            class="border border-[#d4dee8] rounded-md px-3 py-2 text-sm font-bold text-[#0c1c28]"
                        >
                            <option value="">Todos</option>
                            <option v-for="wc in workCenters" :key="wc.id" :value="wc.id">
                                {{ wc.name }}
                            </option>
                        </select>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Fecha:</label>
                        <input 
                            type="date" 
                            v-model="filters.date" 
                            @change="handleFilterChange"
                            class="border border-[#d4dee8] rounded-md px-3 py-2 text-sm font-bold text-[#0c1c28]"
                        >
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Estado:</label>
                        <select 
                            v-model="filters.status" 
                            @change="handleFilterChange"
                            class="border border-[#d4dee8] rounded-md px-3 py-2 text-sm font-bold text-[#0c1c28]"
                        >
                            <option value="pendiente">Pendientes</option>
                            <option value="reparada">Reparadas</option>
                            <option value="reemplazada">Reemplazadas</option>
                            <option value="desechada">Desechadas</option>
                            <option value="all">Todas</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Lista de Piezas Rechazadas -->
            <div v-if="rejectedPieces.length > 0" class="space-y-4">
                <div 
                    v-for="piece in rejectedPieces" 
                    :key="piece.id"
                    class="bg-white border border-[#d4dee8] rounded-xl shadow-sm"
                >
                    <!-- Encabezado -->
                    <div class="px-4 py-3 bg-[#f4f7fa] border-b border-[#d4dee8] flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-[#0b2a40]">{{ piece.work_center?.name }}</h2>
                            <p class="text-xs text-[#6a8090] mt-1">
                                {{ piece.production_line?.title }} | {{ formatDateTime(piece.rejected_at) }}
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-extrabold text-[#ba2418]">{{ piece.quantity }}</div>
                            <div class="text-xs text-[#6a8090]">piezas rechazadas</div>
                        </div>
                    </div>
                    
                    <!-- Detalles -->
                    <div class="px-4 py-3 border-b border-[#d4dee8]">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-1">Motivo del Rechazo</div>
                                <div class="text-sm text-[#0c1c28]">{{ piece.rejection_reason || 'No especificado' }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-1">Rechazado Por</div>
                                <div class="text-sm text-[#0c1c28]">{{ piece.rejected_by?.name || 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Estado y Acciones -->
                    <div class="px-4 py-3 flex items-center justify-between">
                        <div>
                            <span 
                                class="px-3 py-1 rounded-full text-xs font-bold"
                                :class="getStatusClass(piece.resolution_status)"
                            >
                                {{ getStatusLabel(piece.resolution_status) }}
                            </span>
                        </div>
                        
                        <div v-if="piece.resolution_status === 'pendiente'" class="flex gap-2">
                            <button 
                                @click="openRepairModal(piece)"
                                class="px-3 py-1.5 bg-[#0b8a3d] hover:bg-[#096b30] text-white rounded-md text-xs font-bold transition"
                            >
                                Reparar
                            </button>
                            <button 
                                @click="openReplaceModal(piece)"
                                class="px-3 py-1.5 bg-[#0b5aa3] hover:bg-[#094480] text-white rounded-md text-xs font-bold transition"
                            >
                                Reemplazar
                            </button>
                            <button 
                                @click="openDiscardModal(piece)"
                                class="px-3 py-1.5 bg-[#ba2418] hover:bg-[#961c14] text-white rounded-md text-xs font-bold transition"
                            >
                                Desechar
                            </button>
                        </div>
                        
                        <div v-else class="text-sm text-[#6a8090]">
                            <div>Resuelto por: {{ piece.resolved_by?.name }}</div>
                            <div class="text-xs">{{ formatDateTime(piece.resolved_at) }}</div>
                        </div>
                    </div>
                    
                    <!-- Notas de resolución -->
                    <div v-if="piece.resolution_notes" class="px-4 py-3 bg-[#f8f9fb] border-t border-[#d4dee8]">
                        <div class="text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-1">Notas de Resolución</div>
                        <div class="text-sm text-[#0c1c28]">{{ piece.resolution_notes }}</div>
                    </div>
                </div>
            </div>
            
            <!-- Mensaje cuando no hay piezas -->
            <div v-else class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-8 text-center">
                <div class="text-4xl mb-3">📋</div>
                <h3 class="text-lg font-bold text-[#0b2a40] mb-2">No hay piezas rechazadas</h3>
                <p class="text-sm text-[#6a8090]">
                    No se encontraron piezas rechazadas con los filtros seleccionados.
                </p>
            </div>
        </div>
        
        <!-- Modal de Reparación -->
        <div v-if="showRepairModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-md mx-4">
                <div class="px-4 py-3 border-b border-[#d4dee8]">
                    <h3 class="text-lg font-bold text-[#0b2a40]">Marcar como Reparada</h3>
                </div>
                <div class="p-4">
                    <div class="mb-4">
                        <label class="block text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-2">
                            Notas de Reparación
                        </label>
                        <textarea 
                            v-model="repairForm.resolution_notes"
                            rows="3"
                            class="w-full border border-[#d4dee8] rounded-md px-3 py-2 text-sm"
                            placeholder="Describa cómo se repararon las piezas..."
                        ></textarea>
                    </div>
                </div>
                <div class="px-4 py-3 border-t border-[#d4dee8] flex justify-end gap-2">
                    <button 
                        @click="showRepairModal = false"
                        class="px-4 py-2 border border-[#d4dee8] rounded-md text-sm font-bold text-[#0c1c28] hover:bg-[#f4f7fa] transition"
                    >
                        Cancelar
                    </button>
                    <button 
                        @click="markAsRepaired"
                        class="px-4 py-2 bg-[#0b8a3d] hover:bg-[#096b30] text-white rounded-md text-sm font-bold transition"
                    >
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Modal de Reemplazo -->
        <div v-if="showReplaceModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-md mx-4">
                <div class="px-4 py-3 border-b border-[#d4dee8]">
                    <h3 class="text-lg font-bold text-[#0b2a40]">Marcar como Reemplazada</h3>
                </div>
                <div class="p-4 space-y-4">
                    <div>
                        <label class="block text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-2">
                            Cantidad de Piezas Nuevas
                        </label>
                        <input 
                            type="number" 
                            v-model="replaceForm.new_pieces_quantity"
                            min="1"
                            class="w-full border border-[#d4dee8] rounded-md px-3 py-2 text-sm"
                            placeholder="Cantidad de piezas nuevas hechas"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-2">
                            Schedule donde se hicieron
                        </label>
                        <select 
                            v-model="replaceForm.new_pieces_schedule_id"
                            class="w-full border border-[#d4dee8] rounded-md px-3 py-2 text-sm"
                        >
                            <option value="">Seleccione...</option>
                            <option v-for="schedule in schedules" :key="schedule.id" :value="schedule.id">
                                {{ schedule.start_time }} - {{ schedule.end_time }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-2">
                            Notas de Reemplazo
                        </label>
                        <textarea 
                            v-model="replaceForm.resolution_notes"
                            rows="2"
                            class="w-full border border-[#d4dee8] rounded-md px-3 py-2 text-sm"
                            placeholder="Notas adicionales..."
                        ></textarea>
                    </div>
                </div>
                <div class="px-4 py-3 border-t border-[#d4dee8] flex justify-end gap-2">
                    <button 
                        @click="showReplaceModal = false"
                        class="px-4 py-2 border border-[#d4dee8] rounded-md text-sm font-bold text-[#0c1c28] hover:bg-[#f4f7fa] transition"
                    >
                        Cancelar
                    </button>
                    <button 
                        @click="markAsReplaced"
                        class="px-4 py-2 bg-[#0b5aa3] hover:bg-[#094480] text-white rounded-md text-sm font-bold transition"
                    >
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Modal de Desecho -->
        <div v-if="showDiscardModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-md mx-4">
                <div class="px-4 py-3 border-b border-[#d4dee8]">
                    <h3 class="text-lg font-bold text-[#0b2a40]">Marcar como Desechada</h3>
                </div>
                <div class="p-4">
                    <div class="mb-4">
                        <label class="block text-xs font-bold tracking-wider uppercase text-[#4e6070] mb-2">
                            Notas de Desecho
                        </label>
                        <textarea 
                            v-model="discardForm.resolution_notes"
                            rows="3"
                            class="w-full border border-[#d4dee8] rounded-md px-3 py-2 text-sm"
                            placeholder="Explique por qué no se pueden recuperar..."
                        ></textarea>
                    </div>
                </div>
                <div class="px-4 py-3 border-t border-[#d4dee8] flex justify-end gap-2">
                    <button 
                        @click="showDiscardModal = false"
                        class="px-4 py-2 border border-[#d4dee8] rounded-md text-sm font-bold text-[#0c1c28] hover:bg-[#f4f7fa] transition"
                    >
                        Cancelar
                    </button>
                    <button 
                        @click="markAsDiscarded"
                        class="px-4 py-2 bg-[#ba2418] hover:bg-[#961c14] text-white rounded-md text-sm font-bold transition"
                    >
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import CalidadSidebar from '@/Components/CalidadSidebar.vue'

const props = defineProps({
    rejectedPieces: {
        type: Array,
        default: () => []
    },
    workCenters: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    }
})

const rejectedPieces = ref(props.rejectedPieces || [])
const workCenters = ref(props.workCenters || [])
const filters = ref({
    work_center_id: props.filters.work_center_id || '',
    date: props.filters.date || new Date().toISOString().split('T')[0],
    status: props.filters.status || 'pendiente'
})

// Modales
const showRepairModal = ref(false)
const showReplaceModal = ref(false)
const showDiscardModal = ref(false)

// Formularios
const selectedPiece = ref(null)
const repairForm = ref({ resolution_notes: '' })
const replaceForm = ref({ new_pieces_quantity: 0, new_pieces_schedule_id: '', resolution_notes: '' })
const discardForm = ref({ resolution_notes: '' })
const schedules = ref([])

const formatDateTime = (dateTime) => {
    if (!dateTime) return 'N/A'
    const date = new Date(dateTime)
    return date.toLocaleString('es-MX', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getStatusClass = (status) => {
    const classes = {
        pendiente: 'bg-[#f59e0b] text-white',
        reparada: 'bg-[#0b8a3d] text-white',
        reemplazada: 'bg-[#0b5aa3] text-white',
        desechada: 'bg-[#ba2418] text-white'
    }
    return classes[status] || 'bg-gray-200 text-gray-700'
}

const getStatusLabel = (status) => {
    const labels = {
        pendiente: 'Pendiente',
        reparada: 'Reparada',
        reemplazada: 'Reemplazada',
        desechada: 'Desechada'
    }
    return labels[status] || status
}

const handleFilterChange = () => {
    router.get(route('calidad.rejected-pieces.index'), filters.value)
}

const openRepairModal = (piece) => {
    selectedPiece.value = piece
    repairForm.value = { resolution_notes: '' }
    showRepairModal.value = true
}

const openReplaceModal = async (piece) => {
    selectedPiece.value = piece
    replaceForm.value = { new_pieces_quantity: piece.quantity, new_pieces_schedule_id: '', resolution_notes: '' }
    
    // Cargar schedules disponibles
    try {
        const response = await fetch(route('calidad.rejected-pieces.schedules', { daily_program_id: piece.id_daily_program }))
        schedules.value = await response.json()
    } catch (error) {
        console.error('Error loading schedules:', error)
    }
    
    showReplaceModal.value = true
}

const openDiscardModal = (piece) => {
    selectedPiece.value = piece
    discardForm.value = { resolution_notes: '' }
    showDiscardModal.value = true
}

const markAsRepaired = async () => {
    try {
        const response = await fetch(route('calidad.rejected-pieces.repaired', selectedPiece.value.id), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify(repairForm.value)
        })
        const data = await response.json()
        
        if (data.success) {
            showRepairModal.value = false
            // Recargar la página
            router.reload()
        }
    } catch (error) {
        console.error('Error marking as repaired:', error)
    }
}

const markAsReplaced = async () => {
    try {
        const response = await fetch(route('calidad.rejected-pieces.replaced', selectedPiece.value.id), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify(replaceForm.value)
        })
        const data = await response.json()
        
        if (data.success) {
            showReplaceModal.value = false
            router.reload()
        }
    } catch (error) {
        console.error('Error marking as replaced:', error)
    }
}

const markAsDiscarded = async () => {
    try {
        const response = await fetch(route('calidad.rejected-pieces.discarded', selectedPiece.value.id), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify(discardForm.value)
        })
        const data = await response.json()
        
        if (data.success) {
            showDiscardModal.value = false
            router.reload()
        }
    } catch (error) {
        console.error('Error marking as discarded:', error)
    }
}
</script>
