<template>
    <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-3 flex flex-col gap-2">
        <h2 class="text-sm font-extrabold text-[#0b2a40] mb-2">Semáforos de área</h2>
        
        <div class="overflow-y-auto" style="max-height: 400px;">
            <div v-for="attr in localAttributes" :key="attr.id" 
                 :class="['flex items-center gap-2.5 p-2 rounded-lg border bg-white cursor-default mb-2', borderColorClass(attr.color)]">
                
                <div class="flex items-center gap-1 bg-[#181818] rounded-2xl px-1.5 py-1 border-2 border-[#2a2a2a] shadow-lg flex-shrink-0">
                    <div :class="['w-4 h-4 rounded-full border border-[#3a3a3a] transition-all duration-350', lightClass(attr.color, 'rojo')]"></div>
                    <div :class="['w-4 h-4 rounded-full border border-[#3a3a3a] transition-all duration-350', lightClass(attr.color, 'amarillo')]"></div>
                    <div :class="['w-4 h-4 rounded-full border border-[#3a3a3a] transition-all duration-350', lightClass(attr.color, 'verde')]"></div>
                    <div :class="['w-4 h-4 rounded-full border border-[#3a3a3a] transition-all duration-350', lightClass(attr.color, 'gris')]"></div>
                </div>
                
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-extrabold text-[#0b2a40] tracking-tight">{{ attr.name }}</div>
                    <div class="text-xs font-semibold text-[#4e6070]">{{ getElapsedTime(attr.color_changed_at) }}</div>
                </div>
                
                <div class="flex gap-2 flex-shrink-0">
                    <button @click="openChangeModal(attr)" 
                            class="text-xs font-bold text-[#174060] hover:text-[#0b2a40] transition">
                        Cambiar
                    </button>
                    <button @click="openHistoryModal(attr)" 
                            class="text-xs font-bold text-[#6a8090] hover:text-[#0b2a40] transition">
                        Historial
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Desplegable de cambios recientes -->
        <div class="mt-2 border-t border-[#d4dee8] pt-2">
            <button @click="toggleRecentChanges" 
                    class="w-full flex items-center justify-between text-xs font-bold text-[#0b2a40] hover:text-[#174060] transition">
                <span>Cambios recientes</span>
                <span>{{ showRecentChanges ? '▼' : '▶' }}</span>
            </button>
            
            <div v-if="showRecentChanges" class="mt-2 space-y-1 overflow-y-scroll pr-1" style="max-height: 120px; scrollbar-width: thin; scrollbar-color: #9ca3af #f3f4f6;">
                <div v-for="change in recentChanges" :key="change.id" 
                     class="flex items-center gap-2 text-xs p-1.5 rounded bg-gray-50 hover:bg-gray-100 transition">
                    <div :class="['w-2 h-2 rounded-full flex-shrink-0', dotColorClass(change.new_color)]"></div>
                    <span class="font-bold text-[#0b2a40]">{{ change.attribute_name }}</span>
                    <span class="text-[#6a8090]">→ {{ colorLabel(change.new_color) }}</span>
                    <span class="text-[#6a8090] ml-auto">{{ formatTime(change.created_at) }}</span>
                </div>
                <div v-if="recentChanges.length === 0" class="text-xs text-[#6a8090] text-center py-2">
                    Sin cambios recientes
                </div>
            </div>
        </div>
        
        <Modal :show="showChangeModal" @close="closeChangeModal" max-width="md">
            <div class="p-6">
                <h3 class="text-base font-extrabold text-[#0b2a40] mb-1">Cambiar estado</h3>
                <p class="text-sm text-[#4e6070] font-semibold mb-4">▸ {{ selectedAttribute?.name }}</p>
                
                <div class="mb-4">
                    <label class="block text-xs font-bold tracking-widest uppercase text-[#4e6070] mb-2">
                        Selecciona color
                    </label>
                    <div class="grid grid-cols-4 gap-2">
                        <button @click="selectedColor = 'rojo'" 
                                :class="['p-2.5 rounded-lg border-2 text-center font-extrabold text-xs transition-transform hover:scale-105',
                                         selectedColor === 'rojo' ? 'border-[#ba2418] bg-[#fce9e8] text-[#ba2418] shadow-[0_0_0_3px_rgba(186,36,24,0.3)]' : 'border-[#d4dee8] bg-white text-[#4e6070]']">
                            <div class="text-lg mb-1">🔴</div>
                            <div>Rojo</div>
                            <div class="text-[10px] font-semibold">Crítico</div>
                        </button>
                        
                        <button @click="selectedColor = 'amarillo'" 
                                :class="['p-2.5 rounded-lg border-2 text-center font-extrabold text-xs transition-transform hover:scale-105',
                                         selectedColor === 'amarillo' ? 'border-[#a87000] bg-[#fff6da] text-[#a87000] shadow-[0_0_0_3px_rgba(168,112,0,0.3)]' : 'border-[#d4dee8] bg-white text-[#4e6070]']">
                            <div class="text-lg mb-1">🟡</div>
                            <div>Amarillo</div>
                            <div class="text-[10px] font-semibold">Riesgo</div>
                        </button>
                        
                        <button @click="selectedColor = 'verde'" 
                                :class="['p-2.5 rounded-lg border-2 text-center font-extrabold text-xs transition-transform hover:scale-105',
                                         selectedColor === 'verde' ? 'border-[#0a7c3e] bg-[#e4f5ec] text-[#0a7c3e] shadow-[0_0_0_3px_rgba(10,124,62,0.3)]' : 'border-[#d4dee8] bg-white text-[#4e6070]']">
                            <div class="text-lg mb-1">🟢</div>
                            <div>Verde</div>
                            <div class="text-[10px] font-semibold">Normal</div>
                        </button>
                        
                        <button @click="selectedColor = 'gris'" 
                                :class="['p-2.5 rounded-lg border-2 text-center font-extrabold text-xs transition-transform hover:scale-105',
                                         selectedColor === 'gris' ? 'border-[#999] bg-[#f0f0f0] text-[#555] shadow-[0_0_0_3px_rgba(153,153,153,0.3)]' : 'border-[#d4dee8] bg-white text-[#4e6070]']">
                            <div class="text-lg mb-1">⚪</div>
                            <div>Gris</div>
                            <div class="text-[10px] font-semibold">Sin datos</div>
                        </button>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-xs font-bold tracking-widest uppercase text-[#4e6070] mb-2">
                        Motivo del cambio
                    </label>
                    <input v-model="comment" 
                           type="text" 
                           maxlength="100"
                           placeholder="Ej: Falta de material"
                           class="w-full border border-[#d4dee8] rounded-md px-3 py-2 text-sm font-semibold text-[#0c1c28] focus:outline-none focus:border-[#174060]">
                </div>
                
                <div class="flex gap-2 justify-end">
                    <button @click="closeChangeModal" 
                            class="px-4 py-2 bg-white text-[#0b2a40] border border-[#d4dee8] rounded-md text-xs font-bold hover:opacity-85 transition">
                        Cancelar
                    </button>
                    <button @click="confirmChange" 
                            :disabled="!selectedColor"
                            class="px-4 py-2 bg-[#0b2a40] text-white rounded-md text-xs font-bold hover:opacity-85 transition disabled:opacity-50">
                        Confirmar
                    </button>
                </div>
            </div>
        </Modal>
        
        <Modal :show="showHistoryModal" @close="closeHistoryModal" max-width="2xl">
            <div class="p-6">
                <h3 class="text-base font-extrabold text-[#0b2a40] mb-1">Historial de {{ selectedAttribute?.name }}</h3>
                <p class="text-xs text-[#6a8090] font-semibold mb-4">Últimos cambios registrados</p>
                
                <div v-if="loadingHistory" class="text-center py-8">
                    <div class="text-[#6a8090]">Cargando historial...</div>
                </div>
                
                <div v-else-if="history.length === 0" class="text-center py-8 border border-dashed border-[#d4dee8] rounded-lg">
                    <div class="text-4xl mb-2">📋</div>
                    <div class="text-sm font-bold text-[#6a8090]">No hay cambios registrados</div>
                </div>
                
                <div v-else class="overflow-x-auto">
                    <table class="w-full border-collapse bg-white rounded-lg overflow-hidden border border-[#d4dee8]">
                        <thead>
                            <tr class="bg-[#0b2a40]">
                                <th class="px-3 py-2.5 text-left text-[10px] font-bold tracking-widest uppercase text-white">Anterior</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-bold tracking-widest uppercase text-white">Nuevo</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-bold tracking-widest uppercase text-white">Usuario</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-bold tracking-widest uppercase text-white">Comentario</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-bold tracking-widest uppercase text-white">Fecha y Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in history" :key="item.id" class="border-b border-[#e8eff4] hover:bg-[#eef5fa]">
                                <td class="px-3 py-2.5">
                                    <span :class="['inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-extrabold', colorBadgeClass(item.previous_color)]">
                                        <span :class="['w-2 h-2 rounded-full', dotClass(item.previous_color)]"></span>
                                        {{ capitalizeColor(item.previous_color) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5">
                                    <span :class="['inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-extrabold', colorBadgeClass(item.new_color)]">
                                        <span :class="['w-2 h-2 rounded-full', dotClass(item.new_color)]"></span>
                                        {{ capitalizeColor(item.new_color) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-sm font-semibold text-[#0c1c28]">{{ item.user_name }}</td>
                                <td class="px-3 py-2.5 text-xs text-[#4e6070]">{{ item.comment || 'Sin comentario' }}</td>
                                <td class="px-3 py-2.5 text-xs font-semibold text-[#6a8090]">
                                    <div>{{ item.created_at }}</div>
                                    <div class="text-[10px] text-[#6a8090]">{{ item.created_at_human }}</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4 flex justify-end">
                    <button @click="closeHistoryModal" 
                            class="px-4 py-2 bg-[#0b2a40] text-white rounded-md text-xs font-bold hover:opacity-85 transition">
                        Cerrar
                    </button>
                </div>
            </div>
        </Modal>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'
import axios from 'axios'

const props = defineProps({
    attributes: {
        type: Array,
        default: () => []
    }
})

const showChangeModal = ref(false)
const showHistoryModal = ref(false)
const selectedAttribute = ref(null)
const selectedColor = ref(null)
const comment = ref('')
const history = ref([])
const loadingHistory = ref(false)
const localAttributes = ref([...props.attributes])
const showRecentChanges = ref(false)
const recentChanges = ref([])
const timeUpdateInterval = ref(null)

const borderColorClass = (color) => {
    const classes = {
        'rojo': 'border-l-4 border-l-[#ba2418] border-[#d4dee8]',
        'amarillo': 'border-l-4 border-l-[#a87000] border-[#d4dee8]',
        'verde': 'border-l-4 border-l-[#0a7c3e] border-[#d4dee8]',
        'gris': 'border-l-4 border-l-[#aaa] border-[#d4dee8]',
    }
    return classes[color] || 'border-[#d4dee8]'
}

const lightClass = (currentColor, lightColor) => {
    if (currentColor === lightColor) {
        const classes = {
            'rojo': 'bg-[#e0342a] shadow-[0_0_10px_#e0342a,0_0_20px_rgba(224,52,42,0.4)]',
            'amarillo': 'bg-[#d49200] shadow-[0_0_10px_#d49200,0_0_20px_rgba(212,146,0,0.4)]',
            'verde': 'bg-[#14a852] shadow-[0_0_10px_#14a852,0_0_20px_rgba(20,168,82,0.4)]',
            'gris': 'bg-[#888] shadow-[0_0_8px_#888,0_0_16px_rgba(136,136,136,0.35)]',
        }
        return classes[lightColor]
    }
    return 'bg-[#2a2a2a]'
}

const colorBadgeClass = (color) => {
    const classes = {
        'rojo': 'bg-[#fce9e8] text-[#ba2418] border border-[#ebbab8]',
        'amarillo': 'bg-[#fff6da] text-[#a87000] border border-[#e8d488]',
        'verde': 'bg-[#e4f5ec] text-[#0a7c3e] border border-[#aadcc4]',
        'gris': 'bg-[#f0f0f0] text-[#555] border border-[#ccc]',
    }
    return classes[color] || 'bg-gray-100 text-gray-600'
}

const dotClass = (color) => {
    const classes = {
        'rojo': 'bg-[#dc3020]',
        'amarillo': 'bg-[#cf9000]',
        'verde': 'bg-[#14a852]',
        'gris': 'bg-[#999]',
    }
    return classes[color] || 'bg-gray-400'
}

const capitalizeColor = (color) => {
    const names = {
        'rojo': 'Rojo',
        'amarillo': 'Amarillo',
        'verde': 'Verde',
        'gris': 'Gris',
    }
    return names[color] || color
}

const openChangeModal = (attr) => {
    selectedAttribute.value = attr
    selectedColor.value = attr.color
    comment.value = ''
    showChangeModal.value = true
}

const closeChangeModal = () => {
    showChangeModal.value = false
    selectedAttribute.value = null
    selectedColor.value = null
    comment.value = ''
}

const confirmChange = async () => {
    if (!selectedColor.value || !selectedAttribute.value) return
    
    try {
        const response = await axios.post(`/supervisor/attributes/${selectedAttribute.value.id}/change-color`, {
            color: selectedColor.value,
            comment: comment.value
        })
        
        // Actualizar atributo local
        const index = localAttributes.value.findIndex(a => a.id === selectedAttribute.value.id)
        if (index !== -1) {
            localAttributes.value[index] = {
                ...localAttributes.value[index],
                color: response.data.attribute.color,
                color_changed_at: response.data.attribute.color_changed_at
            }
        }
        
        // Agregar a cambios recientes
        loadRecentChanges()
        
        closeChangeModal()
    } catch (error) {
        console.error('Error al cambiar color:', error)
        alert('Error al cambiar el color. Por favor intenta de nuevo.')
    }
}

const openHistoryModal = async (attr) => {
    selectedAttribute.value = attr
    showHistoryModal.value = true
    loadingHistory.value = true
    
    try {
        const response = await axios.get(`/supervisor/attributes/${attr.id}/history`)
        history.value = response.data.history
    } catch (error) {
        console.error('Error al cargar historial:', error)
        history.value = []
    } finally {
        loadingHistory.value = false
    }
}

const closeHistoryModal = () => {
    showHistoryModal.value = false
    selectedAttribute.value = null
    history.value = []
}

const toggleRecentChanges = () => {
    showRecentChanges.value = !showRecentChanges.value
    if (showRecentChanges.value && recentChanges.value.length === 0) {
        loadRecentChanges()
    }
}

const loadRecentChanges = async () => {
    try {
        const response = await axios.get('/supervisor/attributes/recent-changes')
        recentChanges.value = response.data.changes
    } catch (error) {
        console.error('Error al cargar cambios recientes:', error)
        recentChanges.value = []
    }
}

const getElapsedTime = (colorChangedAt) => {
    if (!colorChangedAt) return 'Sin datos'
    
    const now = new Date()
    const changed = new Date(colorChangedAt)
    const diffMs = now - changed
    const diffMins = Math.floor(diffMs / 60000)
    const diffHours = Math.floor(diffMins / 60)
    const diffDays = Math.floor(diffHours / 24)
    
    if (diffDays > 0) {
        const remainingHours = diffHours % 24
        return `${diffDays}d ${remainingHours}h`
    } else if (diffHours > 0) {
        const remainingMins = diffMins % 60
        return `${diffHours}h ${remainingMins}m`
    } else {
        return `${diffMins}m`
    }
}

const dotColorClass = (color) => {
    const classes = {
        'rojo': 'bg-[#dc3020]',
        'amarillo': 'bg-[#cf9000]',
        'verde': 'bg-[#14a852]',
        'gris': 'bg-[#999]',
    }
    return classes[color] || 'bg-gray-400'
}

const colorLabel = (color) => {
    const labels = {
        'rojo': 'Rojo',
        'amarillo': 'Amarillo',
        'verde': 'Verde',
        'gris': 'Gris',
    }
    return labels[color] || color
}

const formatTime = (datetime) => {
    const date = new Date(datetime)
    const hours = date.getHours().toString().padStart(2, '0')
    const minutes = date.getMinutes().toString().padStart(2, '0')
    return `${hours}:${minutes}`
}

onMounted(() => {
    // Actualizar tiempo cada minuto
    timeUpdateInterval.value = setInterval(() => {
        // Forzar re-render actualizando la referencia
        localAttributes.value = [...localAttributes.value]
    }, 60000)
})

onUnmounted(() => {
    if (timeUpdateInterval.value) {
        clearInterval(timeUpdateInterval.value)
    }
})

watch(() => props.attributes, (newAttrs) => {
    localAttributes.value = [...newAttrs]
}, { deep: true })
</script>
