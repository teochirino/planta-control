<template>
    <div class="fixed bottom-6 right-6 z-[9999] flex flex-col items-end gap-2">
        <!-- Botón principal (expandido/colapsado) -->
        <div v-if="!isCollapsed" class="flex flex-col items-end gap-2">
            <button 
                @click="toggleMode"
                class="px-6 py-3 rounded-lg shadow-lg font-bold text-sm transition-all duration-300 hover:scale-105 active:scale-95"
                :class="isTVMode() ? 'bg-[#0b2a40] text-white' : 'bg-white text-[#0b2a40] border-2 border-[#0b2a40]'"
            >
                <span v-if="isTVMode()">📺 Modo TV</span>
                <span v-else>📱 Modo Normal</span>
            </button>
        </div>
        
        <!-- Botón de colapsar/expandir -->
        <button 
            @click="toggleCollapse"
            class="w-10 h-10 rounded-full shadow-lg font-bold text-lg transition-all duration-300 hover:scale-110 active:scale-95 flex items-center justify-center"
            :class="isTVMode() ? 'bg-[#0b2a40] text-white' : 'bg-white text-[#0b2a40] border-2 border-[#0b2a40]'"
        >
            <span v-if="isCollapsed">📺</span>
            <span v-else>−</span>
        </button>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useDisplayMode } from '@/Composables/useDisplayMode'

const { displayMode, toggleMode, isTVMode, isNormalMode } = useDisplayMode()

const isCollapsed = ref(false)

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value
}
</script>
