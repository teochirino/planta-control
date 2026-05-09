<template>
    <div class="app" :class="{ 'tv-mode': tvMode }">
        <!-- Top Bar -->
        <div class="topbar card">
            <div class="topbar-left">
                <div class="eyebrow">ANDON INDUSTRIAL</div>
                <h1>Panel de Control</h1>
                <div class="topbar-sub">Planta {{ selectedLine || 'General' }}</div>
            </div>
            <div class="topbar-right">
                <div class="chip">{{ currentDateTime }}</div>
                <div class="chip live">LIVE</div>
                <button @click="tvMode = !tvMode" class="chip" :class="{ live: tvMode }">
                    {{ tvMode ? '📺 MODO TV' : '🖥️ NORMAL' }}
                </button>
            </div>
        </div>

        <!-- Tabs de líneas -->
        <div class="tabs">
            <button v-for="line in productionLines" :key="line.id"
                @click="selectedLine = line.id"
                class="tab-btn" :class="{ active: selectedLine === line.id }">
                {{ line.title }}
            </button>
            <button class="primary-btn" style="margin-left: auto;">📊 Reporte</button>
        </div>

        <!-- Contenido principal -->
        <slot />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from 'axios'

const tvMode = ref(false)
const selectedLine = ref(null)
const productionLines = ref([])

const currentDateTime = computed(() => {
    const now = new Date()
    return now.toLocaleDateString('es-ES') + ' ' + now.toLocaleTimeString('es-ES')
})

let interval
onMounted(async () => {
    // Cargar líneas de producción
    const res = await axios.get('/api/production-lines')
    productionLines.value = res.data
    if (productionLines.value.length) selectedLine.value = productionLines.value[0].id
    
    // Reloj en vivo
    interval = setInterval(() => {}, 1000)
})

onUnmounted(() => clearInterval(interval))
</script>

<style scoped>
/* Variables y estilos desde tu HTML */
:root {
    --bg: #eaf0f5;
    --panel: #ffffff;
    --soft: #f4f7fa;
    --text: #0c1c28;
    --muted: #4e6070;
    --border: #d4dee8;
    --navy: #0b2a40;
    --navy2: #174060;
    --green: #0a7c3e;
    --amber: #a87000;
    --red: #ba2418;
}

.app {
    padding: 10px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-height: 100vh;
    background: var(--bg);
    font-family: "Segoe UI", system-ui, Arial, sans-serif;
}

.app.tv-mode {
    height: 100vh;
    overflow: hidden;
}

.card {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(11,28,40,.08);
}

.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 16px;
    flex-shrink: 0;
}

.topbar-left { display: flex; align-items: center; gap: 14px; }
.eyebrow { font-size: 10px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: var(--navy2); }
.topbar h1 { font-size: clamp(22px, 2.4vw, 32px); font-weight: 800; color: var(--navy); }
.topbar-sub { font-size: 14px; color: var(--muted); font-weight: 600; }
.topbar-right { display: flex; gap: 8px; flex-wrap: wrap; }

.chip { 
    padding: 5px 11px; 
    border-radius: 999px; 
    background: var(--soft); 
    border: 1px solid var(--border); 
    font-size: 12px; 
    font-weight: 700; 
    color: var(--muted); 
}
.chip.live { background: var(--navy); color: #fff; border-color: var(--navy); }

.tabs { display: flex; gap: 6px; flex-wrap: wrap; padding: 6px 10px; flex-shrink: 0; }
.tab-btn {
    background: var(--soft);
    color: var(--muted);
    border: 1px solid var(--border);
    padding: 8px 14px;
    border-radius: 6px;
    font-weight: 700;
    cursor: pointer;
}
.tab-btn.active { background: var(--navy); color: #fff; border-color: var(--navy); }
.primary-btn { background: var(--navy); color: #fff; padding: 8px 14px; border-radius: 6px; font-weight: 700; cursor: pointer; }
</style>