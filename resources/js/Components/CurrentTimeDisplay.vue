<template>
    <div :class="isTVMode() ? 'px-4 py-3 text-base' : 'px-3 py-2 text-sm'" class="rounded-full bg-[#0b2a40] text-white font-bold flex items-center gap-2">
        <span class="text-lg">🕐</span>
        <span>{{ currentTime }}</span>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useDisplayMode } from '@/Composables/useDisplayMode'

const { isTVMode } = useDisplayMode()

const currentTime = ref('')
let timer = null

// Detectar zona horaria según el entorno
const getTimezone = () => {
    const hostname = window.location.hostname
    
    // En producción (no localhost) usar México
    if (hostname !== 'localhost' && hostname !== '127.0.0.1') {
        return 'America/Mexico_City'
    }
    
    // En local usar Venezuela
    return 'America/Caracas'
}

const updateTime = () => {
    const timezone = getTimezone()
    const now = new Date()
    
    // Formatear hora según la zona horaria
    const options = {
        timeZone: timezone,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    }
    
    currentTime.value = new Intl.DateTimeFormat('es-ES', options).format(now)
}

onMounted(() => {
    updateTime()
    timer = setInterval(updateTime, 1000)
})

onUnmounted(() => {
    if (timer) {
        clearInterval(timer)
    }
})
</script>
