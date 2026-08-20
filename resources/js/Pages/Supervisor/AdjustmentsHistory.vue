<template>
    <AuthenticatedLayout>
        <DisplayModeToggle />
        <div :class="isTVMode() ? 'p-8 2xl:p-12' : 'p-6'" class="flex flex-col gap-2.5">
            <!-- Header -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <div :class="isTVMode() ? 'px-6 py-4 2xl:px-8 2xl:py-5' : 'px-4 py-3'" class="flex items-center justify-between gap-4 flex-wrap">
                    <div>
                        <span :class="isTVMode() ? 'text-xs 2xl:text-sm' : 'text-[10px]'" class="font-bold tracking-widest uppercase text-[#174060]">Historial de Ajustes</span>
                        <h1 :class="isTVMode() ? 'text-4xl 2xl:text-6xl' : 'text-2xl'" class="font-extrabold text-[#0b2a40] leading-none">{{ workCenter?.name || 'Cargando...' }}</h1>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <input type="date" v-model="startDate" @change="cargarHistorial" :class="isTVMode() ? 'px-4 py-3 text-base 2xl:px-5 2xl:py-4 2xl:text-lg' : 'px-3 py-2 text-xs'" class="border border-[#d4dee8] rounded-md font-bold text-[#0c1c28]">
                        <span :class="isTVMode() ? 'text-sm 2xl:text-base' : 'text-xs'" class="font-bold text-[#4e6070]">a</span>
                        <input type="date" v-model="endDate" @change="cargarHistorial" :class="isTVMode() ? 'px-4 py-3 text-base 2xl:px-5 2xl:py-4 2xl:text-lg' : 'px-3 py-2 text-xs'" class="border border-[#d4dee8] rounded-md font-bold text-[#0c1c28]">
                        <button @click="cargarHistorial" :class="isTVMode() ? 'px-6 py-3 text-base 2xl:px-8 2xl:py-4 2xl:text-lg' : 'px-4 py-2 text-xs'" class="bg-[#0b2a40] text-white rounded-md font-bold hover:opacity-85">
                            Actualizar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Resumen -->
            <div v-if="adjustments.length > 0" :class="isTVMode() ? 'gap-4 2xl:gap-6' : 'gap-2'" class="grid grid-cols-1 md:grid-cols-4 2xl:grid-cols-4">
                <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg">
                    <div :class="isTVMode() ? 'text-sm' : 'text-[11px]'" class="font-bold tracking-widest uppercase text-[#4e6070]">Total Ajustes</div>
                    <div :class="isTVMode() ? 'text-4xl' : 'text-2xl'" class="font-extrabold text-[#0b2a40]">{{ adjustments.length }}</div>
                </div>
                <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg">
                    <div :class="isTVMode() ? 'text-sm' : 'text-[11px]'" class="font-bold tracking-widest uppercase text-[#4e6070]">Correcciones</div>
                    <div :class="isTVMode() ? 'text-4xl' : 'text-2xl'" class="font-extrabold text-[#f59e0b]">{{ correctionsCount }}</div>
                </div>
                <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg">
                    <div :class="isTVMode() ? 'text-sm' : 'text-[11px]'" class="font-bold tracking-widest uppercase text-[#4e6070]">Conteos Físicos</div>
                    <div :class="isTVMode() ? 'text-4xl' : 'text-2xl'" class="font-extrabold text-[#0b8a3d]">{{ manualCountCount }}</div>
                </div>
                <div :class="isTVMode() ? 'p-5' : 'p-3'" class="bg-white border border-[#d4dee8] rounded-lg">
                    <div :class="isTVMode() ? 'text-sm' : 'text-[11px]'" class="font-bold tracking-widest uppercase text-[#4e6070]">Ajustes Inventario</div>
                    <div :class="isTVMode() ? 'text-4xl' : 'text-2xl'" class="font-extrabold text-[#ba2418]">{{ inventoryCount }}</div>
                </div>
            </div>

            <!-- Tabla de Ajustes -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm overflow-hidden">
                <div :class="isTVMode() ? 'px-6 py-4' : 'px-4 py-3'" class="border-b border-[#d4dee8]">
                    <h2 :class="isTVMode() ? 'text-xl' : 'text-base'" class="font-extrabold text-[#0b2a40]">Registro de Ajustes</h2>
                </div>
                <div v-if="adjustments.length > 0" class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-[#0b2a40] text-white">
                                <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-left font-bold uppercase">Fecha</th>
                                <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-left font-bold uppercase">Tipo</th>
                                <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-left font-bold uppercase">Campo Ajustado</th>
                                <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-center font-bold uppercase">Valor Anterior</th>
                                <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-center font-bold uppercase">Valor Nuevo</th>
                                <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-center font-bold uppercase">Diferencia</th>
                                <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-left font-bold uppercase">Motivo</th>
                                <th :class="isTVMode() ? 'px-5 py-4 text-sm' : 'px-4 py-3 text-xs'" class="text-left font-bold uppercase">Usuario</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="adjustment in adjustments" :key="adjustment.id" class="border-b border-[#e8eff4] hover:bg-[#eef5fa]">
                                <td :class="isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm'" class="font-semibold text-[#0c1c28]">
                                    {{ formatDateTime(adjustment.created_at) }}
                                </td>
                                <td :class="isTVMode() ? 'px-5 py-4' : 'px-4 py-3'">
                                    <span :class="[isTVMode() ? 'px-3 py-2 text-sm' : 'px-2 py-1 text-xs', 'rounded font-bold', getTipoClass(adjustment.adjustment_type)]">
                                        {{ getTipoLabel(adjustment.adjustment_type) }}
                                    </span>
                                </td>
                                <td :class="isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm'" class="font-semibold text-[#0c1c28]">
                                    {{ getFieldLabel(adjustment.field_adjusted) }}
                                </td>
                                <td :class="isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm'" class="text-center font-semibold text-[#0c1c28]">
                                    {{ formatNumber(adjustment.previous_value) }}
                                </td>
                                <td :class="isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm'" class="text-center font-semibold text-[#0c1c28]">
                                    {{ formatNumber(adjustment.new_value) }}
                                </td>
                                <td :class="[isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm', 'text-center font-bold', adjustment.difference >= 0 ? 'text-[#0b8a3d]' : 'text-[#ba2418]']">
                                    {{ adjustment.difference >= 0 ? '+' : '' }}{{ formatNumber(adjustment.difference) }}
                                </td>
                                <td :class="isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm'" class="text-[#4e6070]">
                                    {{ adjustment.reason }}
                                    <p v-if="adjustment.notes" :class="isTVMode() ? 'text-sm' : 'text-xs'" class="text-[#6a8090] mt-1">{{ adjustment.notes }}</p>
                                </td>
                                <td :class="isTVMode() ? 'px-5 py-4 text-base' : 'px-4 py-3 text-sm'" class="text-[#4e6070]">
                                    {{ adjustment.adjusted_by_name || 'Usuario ' + adjustment.adjusted_by }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else :class="isTVMode() ? 'p-10' : 'p-6'" class="text-center">
                    <div :class="isTVMode() ? 'text-6xl mb-5' : 'text-4xl mb-3'">📋</div>
                    <h3 :class="isTVMode() ? 'text-2xl mb-3' : 'text-lg mb-2'" class="font-bold text-[#0b2a40]">No hay ajustes registrados</h3>
                    <p :class="isTVMode() ? 'text-base' : 'text-sm'" class="text-[#6a8090]">
                        No se encontraron ajustes en el período seleccionado.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import DisplayModeToggle from '@/Components/DisplayModeToggle.vue'
import { useDisplayMode } from '@/Composables/useDisplayMode'

const { isTVMode } = useDisplayMode()

const page = usePage()
const props = page.props

const workCenter = ref(props.workCenter || {})
const adjustments = ref(props.adjustments || [])
const startDate = ref(props.start_date || '')
const endDate = ref(props.end_date || '')

const correctionsCount = computed(() => adjustments.value.filter(a => a.adjustment_type === 'correction').length)
const manualCountCount = computed(() => adjustments.value.filter(a => a.adjustment_type === 'manual_count').length)
const inventoryCount = computed(() => adjustments.value.filter(a => a.adjustment_type === 'inventory_adjustment').length)

const formatNumber = (num) => (num || 0).toLocaleString('es-MX')

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

const getTipoLabel = (type) => {
    const labels = {
        'correction': 'Corrección',
        'manual_count': 'Conteo Físico',
        'inventory_adjustment': 'Ajuste Inventario'
    }
    return labels[type] || type
}

const getFieldLabel = (field) => {
    const labels = {
        'programmed': 'Programado',
        'backwardness': 'Atrasos',
        'advanced': 'Adelantos',
        'total_produced': 'Fabricado',
        'total_rejected': 'Rechazado'
    }
    return labels[field] || field
}

const getTipoClass = (type) => {
    const classes = {
        'correction': 'bg-[#fff6da] text-[#f59e0b]',
        'manual_count': 'bg-[#e4f5ec] text-[#0b8a3d]',
        'inventory_adjustment': 'bg-[#fce9e8] text-[#ba2418]'
    }
    return classes[type] || 'bg-[#f4f7fa] text-[#4e6070]'
}

const cargarHistorial = async () => {
    try {
        const response = await axios.get('/supervisor/adjustments-history', {
            params: {
                work_center_id: workCenter.value.id,
                start_date: startDate.value,
                end_date: endDate.value
            }
        })
        adjustments.value = response.data
    } catch (error) {
        console.error('Error al cargar historial:', error)
        alert('Error al cargar el historial de ajustes')
    }
}

onMounted(() => {
    console.log('=== HISTORIAL DE AJUSTES ===')
    console.log('Ajustes:', adjustments.value.length)
})
</script>
