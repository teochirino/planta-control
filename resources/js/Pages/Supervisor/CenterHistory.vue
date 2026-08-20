<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <SupervisorSidebar />
        <DisplayModeToggle />
        
        <div :class="isTVMode() ? 'p-8 ml-16 2xl:p-12 2xl:ml-20' : 'p-6 ml-16'" class="flex flex-col gap-2.5">
            <!-- Header -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <div :class="isTVMode() ? 'px-6 py-4 2xl:px-8 2xl:py-5' : 'px-4 py-3'" class="flex items-center justify-between gap-4 flex-wrap">
                    <div>
                        <span :class="isTVMode() ? 'text-xs 2xl:text-sm' : 'text-[10px]'" class="font-bold tracking-widest uppercase text-[#174060]">Historial por Centro</span>
                        <h1 :class="isTVMode() ? 'text-4xl 2xl:text-6xl' : 'text-2xl'" class="font-extrabold text-[#0b2a40] leading-none">{{ workCenter?.name || 'Cargando...' }}</h1>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <select 
                            v-model="selectedWorkCenterId" 
                            @change="cargarHistorial"
                            :class="isTVMode() ? 'px-4 py-3 text-base 2xl:px-5 2xl:py-4 2xl:text-lg' : 'px-3 py-2 text-xs'"
                            class="border border-[#d4dee8] rounded-md font-bold text-[#0c1c28]"
                        >
                            <option v-for="center in workCenters" :key="center.id" :value="center.id">
                                {{ center.name }}
                            </option>
                        </select>
                        <input 
                            type="date" 
                            v-model="startDate" 
                            :class="isTVMode() ? 'px-4 py-3 text-base 2xl:px-5 2xl:py-4 2xl:text-lg' : 'px-3 py-2 text-xs'"
                            class="border border-[#d4dee8] rounded-md font-bold text-[#0c1c28]"
                        >
                        <span :class="isTVMode() ? 'text-sm 2xl:text-base' : 'text-xs'" class="font-bold text-[#4e6070]">a</span>
                        <input 
                            type="date" 
                            v-model="endDate" 
                            :class="isTVMode() ? 'px-4 py-3 text-base 2xl:px-5 2xl:py-4 2xl:text-lg' : 'px-3 py-2 text-xs'"
                            class="border border-[#d4dee8] rounded-md font-bold text-[#0c1c28]"
                        >
                        <button 
                            @click="cargarHistorial"
                            :class="isTVMode() ? 'px-6 py-3 text-base 2xl:px-8 2xl:py-4 2xl:text-lg' : 'px-4 py-2 text-xs'"
                            class="bg-[#0b2a40] text-white rounded-md font-bold hover:opacity-85"
                        >
                            Filtrar
                        </button>
                        <button 
                            @click="limpiarFiltros"
                            :class="isTVMode() ? 'px-6 py-3 text-base 2xl:px-8 2xl:py-4 2xl:text-lg' : 'px-4 py-2 text-xs'"
                            class="bg-[#ba2418] text-white rounded-md font-bold hover:opacity-85"
                        >
                            Limpiar Filtros
                        </button>
                        <button 
                            @click="exportarExcel"
                            :class="isTVMode() ? 'px-6 py-3 text-base 2xl:px-8 2xl:py-4 2xl:text-lg' : 'px-4 py-2 text-xs'"
                            class="bg-[#0b8a3d] text-white rounded-md font-bold hover:opacity-85"
                        >
                            Exportar Excel
                        </button>
                    </div>
                </div>
            </div>

            <!-- Resumen -->
            <div v-if="dailyPrograms.data.length > 0" :class="isTVMode() ? 'gap-4 2xl:gap-6' : 'gap-2'" class="grid grid-cols-1 md:grid-cols-4 2xl:grid-cols-4">
                <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg">
                    <div :class="isTVMode() ? 'text-sm' : 'text-[11px]'" class="font-bold tracking-widest uppercase text-[#4e6070]">Total Registros</div>
                    <div :class="isTVMode() ? 'text-4xl' : 'text-2xl'" class="font-extrabold text-[#0b2a40]">{{ dailyPrograms.total }}</div>
                </div>
                <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg">
                    <div :class="isTVMode() ? 'text-sm' : 'text-[11px]'" class="font-bold tracking-widest uppercase text-[#4e6070]">Total Programado</div>
                    <div :class="isTVMode() ? 'text-4xl' : 'text-2xl'" class="font-extrabold text-[#0b2a40]">{{ formatNumber(totalProgramado) }}</div>
                </div>
                <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg">
                    <div :class="isTVMode() ? 'text-sm' : 'text-[11px]'" class="font-bold tracking-widest uppercase text-[#4e6070]">Total Producido</div>
                    <div :class="isTVMode() ? 'text-4xl' : 'text-2xl'" class="font-extrabold text-[#0b8a3d]">{{ formatNumber(totalProducido) }}</div>
                </div>
                <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg">
                    <div :class="isTVMode() ? 'text-sm' : 'text-[11px]'" class="font-bold tracking-widest uppercase text-[#4e6070]">Total Faltantes</div>
                    <div :class="isTVMode() ? 'text-4xl' : 'text-2xl'" class="font-extrabold text-[#ba2418]">{{ formatNumber(totalFaltantes) }}</div>
                </div>
            </div>

            <!-- Tabla de Historial -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm overflow-hidden">
                <div :class="isTVMode() ? 'px-6 py-4' : 'px-4 py-3'" class="border-b border-[#d4dee8]">
                    <h2 :class="isTVMode() ? 'text-xl' : 'text-base'" class="font-extrabold text-[#0b2a40]">Historial de Producción</h2>
                </div>
                <div v-if="dailyPrograms.data.length > 0" class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-[#0b2a40] text-white">
                                <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-left font-bold uppercase">Fecha</th>
                                <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-left font-bold uppercase">Programa</th>
                                <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-left font-bold uppercase">Turno</th>
                                <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-center font-bold uppercase">Programado</th>
                                <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-center font-bold uppercase">Atrasos</th>
                                <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-center font-bold uppercase">Adelantos</th>
                                <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-center font-bold uppercase">Producción Real</th>
                                <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-center font-bold uppercase">Faltantes a Producir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="program in dailyPrograms.data" :key="program.id || program.date" 
                                :class="[
                                    'border-b hover:bg-[#eef5fa]',
                                    program.is_virtual ? 'border-[#f0f0f0] bg-[#fafafa]' : 'border-[#e8eff4]'
                                ]">
                                <td :class="[isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm', program.is_virtual ? 'text-[#999] italic' : 'font-semibold text-[#0c1c28]']">
                                    {{ formatDate(program.date) }}
                                </td>
                                <td :class="[isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm', 'font-semibold text-[#0c1c28]']">
                                    <button 
                                        v-if="program.program?.codigo"
                                        @click="openAdjustmentModal(program)"
                                        class="hover:text-[#0b5a7a] hover:underline cursor-pointer transition"
                                        :title="'Registrar Ajustes: ' + program.program.codigo"
                                    >
                                        {{ program.program.codigo }}
                                    </button>
                                    <span v-else>-</span>
                                </td>
                                <td :class="isTVMode() ? 'px-5 py-4' : 'px-4 py-3'">
                                    <span v-if="program.is_virtual" 
                                          :class="[isTVMode() ? 'px-3 py-2 text-sm' : 'px-2 py-1 text-xs', 'rounded font-bold bg-[#f0f0f0] text-[#999]']">
                                        N/A
                                    </span>
                                    <span v-else 
                                          :class="[isTVMode() ? 'px-3 py-2 text-sm' : 'px-2 py-1 text-xs', 'rounded font-bold', getTurnoClass(program.shift)]">
                                        {{ getTurnoLabel(program.shift) }}
                                    </span>
                                </td>
                                <td :class="[isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm', program.is_virtual ? 'text-center text-[#999]' : 'text-center font-semibold text-[#0c1c28]']">
                                    {{ formatNumber(program.programmed) }}
                                </td>
                                <td :class="[isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm', program.is_virtual ? 'text-center text-[#999]' : 'text-center font-semibold text-[#f59e0b]']">
                                    {{ formatNumber(program.backwardness) }}
                                </td>
                                <td :class="[isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm', program.is_virtual ? 'text-center text-[#999]' : 'text-center font-semibold text-[#0a7c3e]']">
                                    {{ formatNumber(program.advanced) }}
                                </td>
                                <td :class="[isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm', program.is_virtual ? 'text-center text-[#999]' : 'text-center font-semibold text-[#0b8a3d]']">
                                    {{ formatNumber(program.total_produced) }}
                                </td>
                                <td :class="[isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm', 'text-center font-bold', program.is_virtual ? 'text-[#999]' : (program.missing_to_produce > 0 ? 'text-[#ba2418]' : 'text-[#0b8a3d]')]">
                                    {{ formatNumber(program.missing_to_produce) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else :class="isTVMode() ? 'p-10' : 'p-6'" class="text-center">
                    <div :class="isTVMode() ? 'text-6xl mb-5' : 'text-4xl mb-3'">📋</div>
                    <h3 :class="isTVMode() ? 'text-2xl mb-3' : 'text-lg mb-2'" class="font-bold text-[#0b2a40]">No hay registros</h3>
                    <p :class="isTVMode() ? 'text-base' : 'text-sm'" class="text-[#6a8090]">
                        No se encontraron registros en el período seleccionado.
                    </p>
                </div>

                <!-- Modal de Registro de Ajustes -->
                <div v-if="showAdjustmentModal" class="fixed inset-0 flex items-center justify-center z-50" style="background: rgba(11, 28, 40, 0.5);">
                    <div class="rounded-lg p-6 w-full max-w-2xl mx-4" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 12px 40px rgba(0,0,0,.3);">
                        <h2 class="text-xl font-semibold mb-4" style="color: #0b2a40;">Registrar Ajustes de Producción</h2>

                        <div class="mb-4 p-3 rounded" style="background: #f4f7fa; border: 1px solid #e8eff4;">
                            <p class="text-sm font-semibold" style="color: #6a8090;">
                                {{ formatDate(editingProgram.date) }} - {{ getTurnoLabel(editingProgram.shift) }} - {{ editingProgram.workCenter?.name }}
                            </p>
                            <p class="text-sm font-semibold mt-1" style="color: #0c1c28;">
                                Programa: {{ editingProgram.program?.codigo || '-' }}
                            </p>
                        </div>

                        <form @submit.prevent="saveAdjustment">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-semibold mb-2" style="color: #4e6070;">Programado</label>
                                    <input type="number" v-model.number="editForm.programmed" min="0"
                                           class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none"
                                           style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;">
                                    <p class="text-xs mt-1" style="color: #6a8090;">Valor actual: {{ editingProgram.programmed }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-2" style="color: #4e6070;">Atrasos</label>
                                    <input type="number" v-model.number="editForm.backwardness" min="0"
                                           class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none"
                                           style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;">
                                    <p class="text-xs mt-1" style="color: #6a8090;">Valor actual: {{ editingProgram.backwardness }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-2" style="color: #4e6070;">Adelantos</label>
                                    <input type="number" v-model.number="editForm.advanced" min="0"
                                           class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none"
                                           style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;">
                                    <p class="text-xs mt-1" style="color: #6a8090;">Valor actual: {{ editingProgram.advanced }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-semibold mb-2" style="color: #4e6070;">Total Fabricado</label>
                                    <input type="number" v-model.number="editForm.total_produced" min="0"
                                           class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none"
                                           style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;">
                                    <p class="text-xs mt-1" style="color: #6a8090;">Valor actual: {{ editingProgram.total_produced || 0 }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-2" style="color: #4e6070;">Total Rechazado</label>
                                    <input type="number" v-model.number="editForm.total_rejected" min="0"
                                           class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none"
                                           style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;">
                                    <p class="text-xs mt-1" style="color: #6a8090;">Valor actual: {{ editingProgram.total_rejected || 0 }}</p>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-semibold mb-2" style="color: #4e6070;">Motivo del ajuste *</label>
                                <textarea v-model="editForm.reason" rows="3" required
                                          class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none"
                                          style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;"
                                          placeholder="Explique el motivo del ajuste..."></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-semibold mb-2" style="color: #4e6070;">Notas adicionales</label>
                                <textarea v-model="editForm.notes" rows="2"
                                          class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none"
                                          style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;"
                                          placeholder="Información adicional opcional..."></textarea>
                            </div>

                            <div class="flex space-x-4">
                                <button type="submit"
                                        :disabled="editForm.processing"
                                        class="px-6 py-2 rounded-lg transition font-semibold text-sm disabled:opacity-50"
                                        style="background: #0a7c3e; color: #fff;">
                                    {{ editForm.processing ? 'Guardando...' : 'Guardar Cambios' }}
                                </button>
                                <button type="button" @click="closeAdjustmentModal"
                                        class="px-6 py-2 rounded-lg transition font-semibold text-sm"
                                        style="background: #fff; color: #0b2a40; border: 1px solid #d4dee8;">
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Paginación -->
                <div v-if="dailyPrograms.data.length > 0" :class="isTVMode() ? 'px-6 py-4' : 'px-4 py-3'" class="border-t border-[#d4dee8] flex items-center justify-between gap-4 flex-wrap">
                    <div :class="isTVMode() ? 'text-sm' : 'text-xs'" class="text-[#4e6070]">
                        Mostrando {{ dailyPrograms.from }} a {{ dailyPrograms.to }} de {{ dailyPrograms.total }} registros
                    </div>
                    <div class="flex items-center gap-2">
                        <button 
                            @click="changePage(dailyPrograms.current_page - 1)"
                            :disabled="dailyPrograms.current_page === 1"
                            :class="isTVMode() ? 'px-4 py-2 text-sm' : 'px-3 py-1 text-xs'"
                            class="border border-[#d4dee8] rounded-md font-bold text-[#0c1c28] hover:bg-[#eef5fa] disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Anterior
                        </button>
                        <span 
                            v-for="page in getPageNumbers()" 
                            :key="page"
                            @click="changePage(page)"
                            :class="[
                                isTVMode() ? 'px-4 py-2 text-sm' : 'px-3 py-1 text-xs',
                                'border rounded-md font-bold cursor-pointer',
                                page === dailyPrograms.current_page 
                                    ? 'bg-[#0b2a40] text-white border-[#0b2a40]' 
                                    : 'border-[#d4dee8] text-[#0c1c28] hover:bg-[#eef5fa]'
                            ]"
                        >
                            {{ page }}
                        </span>
                        <button 
                            @click="changePage(dailyPrograms.current_page + 1)"
                            :disabled="dailyPrograms.current_page === dailyPrograms.last_page"
                            :class="isTVMode() ? 'px-4 py-2 text-sm' : 'px-3 py-1 text-xs'"
                            class="border border-[#d4dee8] rounded-md font-bold text-[#0c1c28] hover:bg-[#eef5fa] disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Siguiente
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { router, useForm } from '@inertiajs/vue3'
import SupervisorSidebar from '@/Components/SupervisorSidebar.vue'
import DisplayModeToggle from '@/Components/DisplayModeToggle.vue'
import { useDisplayMode } from '@/Composables/useDisplayMode'

const { isTVMode } = useDisplayMode()

const page = usePage()
const props = page.props

const workCenters = ref(props.workCenters || [])
const workCenter = ref(props.selectedWorkCenter || {})
const dailyPrograms = ref(props.dailyPrograms || { data: [] })
const selectedWorkCenterId = ref(props.filters?.work_center_id || workCenters.value[0]?.id)
const startDate = ref(props.filters?.start_date || '')
const endDate = ref(props.filters?.end_date || '')

// Modal de ajustes
const showAdjustmentModal = ref(false)
const editingProgram = ref(null)

const editForm = useForm({
    programmed: 0,
    backwardness: 0,
    advanced: 0,
    total_produced: 0,
    total_rejected: 0,
    reason: '',
    notes: '',
})

const totalProgramado = computed(() => {
    return dailyPrograms.value.data.reduce((sum, p) => sum + (p.programmed || 0), 0)
})

const totalProducido = computed(() => {
    return dailyPrograms.value.data.reduce((sum, p) => sum + (p.total_produced || 0), 0)
})

const totalFaltantes = computed(() => {
    return dailyPrograms.value.data.reduce((sum, p) => sum + (p.missing_to_produce || 0), 0)
})

const formatNumber = (num) => (num || 0).toLocaleString('es-MX')

const formatDate = (date) => {
    if (!date) return '-'
    // Extraer la fecha sin conversión de zona horaria
    const dateStr = date.toString()
    // Manejar diferentes formatos de fecha
    if (dateStr.includes('T')) {
        // Formato ISO: 2026-08-17T00:00:00.000000Z
        const parts = dateStr.split('T')[0].split('-')
        if (parts.length === 3) {
            const [year, month, day] = parts
            return `${day}/${month}/${year}`
        }
    } else if (dateStr.includes(' ')) {
        // Formato con espacio: 2026-08-17 00:00:00
        const parts = dateStr.split(' ')[0].split('-')
        if (parts.length === 3) {
            const [year, month, day] = parts
            return `${day}/${month}/${year}`
        }
    } else {
        // Formato simple: 2026-08-17
        const parts = dateStr.split('-')
        if (parts.length === 3) {
            const [year, month, day] = parts
            return `${day}/${month}/${year}`
        }
    }
    return new Date(date).toLocaleDateString('es-MX', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    })
}

const getTurnoLabel = (shift) => {
    const labels = {
        'matutino': 'Matutino',
        'vespertino': 'Vespertino',
        'nocturno': 'Nocturno'
    }
    return labels[shift] || shift
}

const getTurnoClass = (shift) => {
    const classes = {
        'matutino': 'bg-[#e4f5ec] text-[#0b8a3d]',
        'vespertino': 'bg-[#fff6da] text-[#f59e0b]',
        'nocturno': 'bg-[#fce9e8] text-[#ba2418]'
    }
    return classes[shift] || 'bg-[#f4f7fa] text-[#4e6070]'
}

const getPageNumbers = () => {
    const pages = []
    const current = dailyPrograms.value.current_page
    const last = dailyPrograms.value.last_page

    if (last <= 7) {
        for (let i = 1; i <= last; i++) {
            pages.push(i)
        }
    } else {
        if (current <= 4) {
            for (let i = 1; i <= 5; i++) pages.push(i)
            pages.push('...')
            pages.push(last)
        } else if (current >= last - 3) {
            pages.push(1)
            pages.push('...')
            for (let i = last - 4; i <= last; i++) pages.push(i)
        } else {
            pages.push(1)
            pages.push('...')
            for (let i = current - 1; i <= current + 1; i++) pages.push(i)
            pages.push('...')
            pages.push(last)
        }
    }

    return pages
}

const changePage = (page) => {
    if (page === '...' || page < 1 || page > dailyPrograms.value.last_page) return
    cargarHistorial(page)
}

const cargarHistorial = (page = 1) => {
    router.get(route('supervisor.center-history'), {
        work_center_id: selectedWorkCenterId.value,
        start_date: startDate.value,
        end_date: endDate.value,
        page: page
    }, {
        preserveState: false,
        preserveScroll: false
    })
}

const limpiarFiltros = () => {
    startDate.value = ''
    endDate.value = ''
    cargarHistorial(1)
}

const exportarExcel = () => {
    const params = {
        work_center_id: selectedWorkCenterId.value,
        start_date: startDate.value,
        end_date: endDate.value
    }

    const queryString = new URLSearchParams(params).toString()
    window.location.href = `/supervisor/center-history/export?${queryString}`
}

// Funciones del modal de ajustes
const openAdjustmentModal = (program) => {
    editingProgram.value = program
    editForm.programmed = program.programmed
    editForm.backwardness = program.backwardness
    editForm.advanced = program.advanced
    editForm.total_produced = program.total_produced || 0
    editForm.total_rejected = program.total_rejected || 0
    editForm.reason = ''
    editForm.notes = ''
    showAdjustmentModal.value = true
}

const closeAdjustmentModal = () => {
    showAdjustmentModal.value = false
    editingProgram.value = null
    editForm.reset()
}

const saveAdjustment = () => {
    editForm.put(route('supervisor.daily-programs.update', editingProgram.value.id), {
        onSuccess: () => {
            closeAdjustmentModal()
            cargarHistorial()
        },
    })
}

onMounted(() => {
    console.log('=== HISTORIAL POR CENTRO ===')
    console.log('Centros:', workCenters.value.length)
    console.log('Programas:', dailyPrograms.value.data.length)
    console.log('Programas completos:', dailyPrograms.value)
    console.log('Fechas:', dailyPrograms.value.data.map(p => ({ date: p.date, is_virtual: p.is_virtual, shift: p.shift })))
})
</script>
