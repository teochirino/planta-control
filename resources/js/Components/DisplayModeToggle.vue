<template>
    <div class="fixed bottom-6 right-6 z-[9999]">
        <button 
            @click="toggleMode"
            class="px-6 py-3 rounded-lg shadow-lg font-bold text-sm transition-all duration-300 hover:scale-105 active:scale-95"
            :class="isTVMode() ? 'bg-[#0b2a40] text-white' : 'bg-white text-[#0b2a40] border-2 border-[#0b2a40]'"
        >
            <span v-if="isTVMode()">📺 Modo TV</span>
            <span v-else>📱 Modo Normal</span>
        </button>
    </div>
</template>

<script setup>
import { useDisplayMode } from '@/Composables/useDisplayMode'

const { displayMode, toggleMode, isTVMode, isNormalMode } = useDisplayMode()
</script>
