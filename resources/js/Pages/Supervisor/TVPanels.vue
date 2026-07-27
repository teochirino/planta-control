<template>
    <div class="min-h-screen" :class="isDarkMode ? 'bg-[#101216]' : 'bg-[#f6f5f2]'">
        <SupervisorSidebar />
        
        <div class="dash-root" :class="{ 'tema-oscuro': isDarkMode }">
            <!-- Header -->
            <header class="topbar">
                <div class="topbar-inner">
                    <img src="/logo-cliente.png" alt="Logo" class="logo" />
                    
                    <div class="clock-wrap">
                        <div class="rlj" id="clock">
                            <div class="rlj__block" data-block="h0"><div class="rlj__group"><span class="rlj__digits"></span><span class="rlj__digits"></span></div></div>
                            <div class="rlj__block" data-block="h1"><div class="rlj__group"><span class="rlj__digits"></span><span class="rlj__digits"></span></div></div>
                            <span class="rlj__colon">:</span>
                            <div class="rlj__block" data-block="m0"><div class="rlj__group"><span class="rlj__digits"></span><span class="rlj__digits"></span></div></div>
                            <div class="rlj__block" data-block="m1"><div class="rlj__group"><span class="rlj__digits"></span><span class="rlj__digits"></span></div></div>
                            <span class="rlj__colon">:</span>
                            <div class="rlj__block" data-block="s0"><div class="rlj__group"><span class="rlj__digits"></span><span class="rlj__digits"></span></div></div>
                            <div class="rlj__block" data-block="s1"><div class="rlj__group"><span class="rlj__digits"></span><span class="rlj__digits"></span></div></div>
                            <div class="rlj__block rlj__block--sm" data-block="ap"><div class="rlj__group"><span class="rlj__digits"></span><span class="rlj__digits"></span></div></div>
                        </div>
                    </div>

                    <div class="topbar-right">
                        <div class="selector-wrap">
                            <select v-model="selectedWorkCenterId" @change="cambiarCentro" class="centro-select">
                                <option v-for="wc in workCentersData" :key="wc.id" :value="wc.id">
                                    {{ wc.name }}
                                </option>
                            </select>
                            <svg class="selector-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6" /></svg>
                        </div>
                        <button class="toggle-tema" :class="{ 'on': isDarkMode }" @click="toggleTheme" aria-label="Cambiar tema claro u oscuro">
                            <span class="toggle-thumb">
                                <svg v-if="!isDarkMode" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" width="8" height="8"><circle cx="12" cy="12" r="4" /><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" /></svg>
                                <svg v-else viewBox="0 0 24 24" fill="currentColor" width="8" height="8"><path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z" /></svg>
                            </span>
                        </button>
                    </div>
                </div>
            </header>

            <main class="dash-main">
                <div class="top-row">
                    <div class="info-cards">
                        <div class="stat-card">
                            <span class="stat-ghost" style="color: var(--mist-600)">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21V10l6 4V10l6 4V6l6 4v11H3Z" /><path d="M7 21v-4M12 21v-4M17 21v-4" /></svg>
                            </span>
                            <div class="stat-body">
                                <p class="stat-label">Capacidad instalada</p>
                                <p class="stat-value tnum">{{ formatNumber(centerKPIsData?.installed_capacity || 0) }}<span class="stat-unit">pzs / hora</span></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-ghost" style="color: var(--ok-600)">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l9 5-9 5-9-5 9-5Z" /><path d="M3 13l9 5 9-5M3 16.5l9 5 9-5" /></svg>
                            </span>
                            <div class="stat-body">
                                <p class="stat-label">Líneas activas</p>
                                <p class="stat-value tnum">{{ productionLinesForCenterData.length }}<span class="stat-unit">en operación</span></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-ghost" style="color: var(--ink-500)">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4.5" width="18" height="16" rx="2" /><path d="M3 9h18M8 2.5v4M16 2.5v4" /><rect x="7" y="12" width="4" height="4" rx="0.5" fill="currentColor" stroke="none" /></svg>
                            </span>
                            <div class="stat-body">
                                <p class="stat-label">Fecha actual</p>
                                <p class="stat-value tnum">{{ fechaFormateada }}<span class="stat-unit">{{ diaSemana }}</span></p>
                            </div>
                        </div>
                    </div>

                    <section class="semaforos-panel">
                        <h2>Semáforos de área</h2>
                        <ul class="semaforos-list">
                            <li v-for="semaforo in semaforosData" :key="semaforo.area" class="semaforo-row" :class="'estado-' + semaforo.estado">
                                <div class="tl-housing">
                                    <span class="lamp" :class="'on-' + semaforo.estado"></span>
                                </div>
                                <div class="semaforo-info">
                                    <p class="semaforo-area">{{ semaforo.area }}</p>
                                    <p class="semaforo-desde">{{ semaforo.desde }}</p>
                                </div>
                            </li>
                        </ul>
                    </section>
                </div>

                <section class="programa-turno">
                    <div class="pt-header">
                        <h2>Programa del Turno</h2>
                        <span class="turno-badge">{{ turnoLabel }} · {{ fechaFormateada }}</span>
                    </div>
                    <div class="kpi-rows">
                        <div class="kpi-row">
                            <div class="kpi-tile" data-vshadow>
                                <span class="kpi-label">Programado</span>
                                <div><span class="kpi-value tnum tone-default">{{ formatNumber(centerKPIsData?.programmed || 0) }}</span><span class="kpi-mini bg-default"></span></div>
                            </div>
                            <div class="kpi-tile" data-vshadow>
                                <span class="kpi-label">Atraso</span>
                                <div><span class="kpi-value tnum" :class="'tone-' + getTone(centerKPIsData?.backwardness || 0, 'bad')">{{ formatNumber(centerKPIsData?.backwardness || 0) }}</span><span class="kpi-mini" :class="'bg-' + getTone(centerKPIsData?.backwardness || 0, 'bad')"></span></div>
                            </div>
                            <div class="kpi-tile" data-vshadow>
                                <span class="kpi-label">Adelantadas</span>
                                <div><span class="kpi-value tnum" :class="'tone-' + getTone(centerKPIsData?.advanced || 0, 'ok')">{{ formatNumber(centerKPIsData?.advanced || 0) }}</span><span class="kpi-mini" :class="'bg-' + getTone(centerKPIsData?.advanced || 0, 'ok')"></span></div>
                            </div>
                            <div class="kpi-tile" data-vshadow>
                                <span class="kpi-label">Total a producir</span>
                                <div><span class="kpi-value tnum tone-default">{{ formatNumber(centerKPIsData?.total_to_produce || 0) }}</span><span class="kpi-mini bg-default"></span></div>
                            </div>
                            <div class="kpi-tile" data-vshadow>
                                <span class="kpi-label">Fabricadas</span>
                                <div><span class="kpi-value tnum tone-default">{{ formatNumber(centerKPIsData?.fabricated || 0) }}</span><span class="kpi-mini bg-default"></span></div>
                            </div>
                        </div>
                        <div class="kpi-divider"></div>
                        <div class="kpi-row">
                            <div class="kpi-tile" data-vshadow>
                                <span class="kpi-label">Diferencia</span>
                                <div><span class="kpi-value tnum" :class="'tone-' + getDifferenceTone(centerKPIsData?.difference || 0)">{{ centerKPIsData?.difference >= 0 ? '+' : '' }}{{ formatNumber(centerKPIsData?.difference || 0) }}</span><span class="kpi-mini" :class="'bg-' + getDifferenceTone(centerKPIsData?.difference || 0)"></span></div>
                            </div>
                            <div class="kpi-tile" data-vshadow>
                                <span class="kpi-label">Cumplimiento</span>
                                <div><span class="kpi-value tnum" :class="'tone-' + getComplianceTone(centerKPIsData?.compliance || 0)">{{ centerKPIsData?.compliance || 0 }}%</span><div class="kpi-bar-track"><div class="kpi-bar-fill" :class="'bg-' + getComplianceTone(centerKPIsData?.compliance || 0)" :style="'width:' + (centerKPIsData?.compliance || 0) + '%'"></div></div></div>
                            </div>
                            <div class="kpi-tile" data-vshadow>
                                <span class="kpi-label">Real vs ideal</span>
                                <div><span class="kpi-value tnum tone-accent">{{ centerKPIsData?.real_vs_ideal || 0 }}%</span><div class="kpi-bar-track"><div class="kpi-bar-fill bg-accent" :style="'width:' + (centerKPIsData?.real_vs_ideal || 0) + '%'"></div></div></div>
                            </div>
                            <div class="kpi-tile" data-vshadow>
                                <span class="kpi-label">Ahorro activos</span>
                                <div><span class="kpi-value tnum tone-ok">${{ formatNumber(centerKPIsData?.saved_amount || 0) }}</span><span class="kpi-mini bg-ok"></span></div>
                            </div>
                            <div class="kpi-tile" data-vshadow>
                                <span class="kpi-label">Cap. instalada</span>
                                <div><span class="kpi-value tnum tone-accent">{{ formatNumber(centerKPIsData?.installed_capacity || 0) }}</span><span class="kpi-mini bg-accent"></span></div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="lineas-produccion">
                    <h2>Líneas de Producción</h2>
                    <div class="lineas-grid">
                        <div v-for="line in productionLinesForCenterData" :key="line.id" class="linea-card">
                            <h3 class="linea-nombre">{{ line.title }}</h3>
                            <div class="stat-chip">
                                <div class="chip-head">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21V10l6 4V10l6 4V6l6 4v11H3Z" /><path d="M7 21v-4M12 21v-4M17 21v-4" /></svg>
                                    <span>Capacidad</span>
                                </div>
                                <p class="chip-value tnum">{{ formatNumber(line.capacity || 0) }}<span class="chip-unit">pzs/h</span></p>
                            </div>
                            <div class="stat-chip">
                                <div class="chip-head">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="2.5" y="6" width="19" height="12" rx="2" /><circle cx="12" cy="12" r="2.5" /><path d="M6 9v6M18 9v6" /></svg>
                                    <span>Costo</span>
                                </div>
                                <p class="chip-value tnum">${{ formatNumber(line.cost || 0) }}<span class="chip-unit">/min</span></p>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import SupervisorSidebar from '@/Components/SupervisorSidebar.vue'
import axios from 'axios'

const props = defineProps({
    workCenters: {
        type: Array,
        default: () => []
    },
    productionLines: {
        type: Array,
        default: () => []
    },
    selectedWorkCenter: {
        type: Object,
        default: null
    },
    selectedDate: {
        type: String,
        default: ''
    },
    selectedShift: {
        type: String,
        default: 'matutino'
    },
    dailyProgram: {
        type: Object,
        default: null
    },
    productionLinesForCenter: {
        type: Array,
        default: () => []
    },
    allKPIs: {
        type: Array,
        default: () => []
    },
    centerKPIs: {
        type: Object,
        default: null
    },
    attributes: {
        type: Array,
        default: () => []
    }
})

const selectedWorkCenterId = ref(props.selectedWorkCenter?.id || null)
const isDarkMode = ref(false)

// Detectar zona horaria según entorno
const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
const timeZone = isLocal ? 'America/Caracas' : 'America/Mexico_City'

const workCentersData = computed(() => props.workCenters)
const selectedWorkCenterData = computed(() => props.selectedWorkCenter)
const productionLinesForCenterData = computed(() => props.productionLinesForCenter)
const centerKPIsData = computed(() => props.centerKPIs)

const turnoLabel = computed(() => {
    const labels = {
        matutino: 'Matutino',
        vespertino: 'Vespertino',
        nocturno: 'Nocturno'
    }
    return labels[props.selectedShift] || props.selectedShift
})

const fechaFormateada = computed(() => {
    if (!props.selectedDate) return ''
    const date = new Date(props.selectedDate)
    return date.toLocaleDateString('es-ES', { 
        day: 'numeric', 
        month: 'numeric', 
        year: 'numeric' 
    })
})

const diaSemana = computed(() => {
    if (!props.selectedDate) return ''
    const date = new Date(props.selectedDate)
    return date.toLocaleDateString('es-ES', { weekday: 'long' })
})

const semaforosData = computed(() => {
    return props.attributes.map(attr => {
        const colorMap = {
            'verde': 'ok',
            'amarillo': 'warn',
            'rojo': 'bad',
            'gris': 'default'
        }
        return {
            area: attr.name,
            estado: colorMap[attr.color] || 'default',
            desde: attr.elapsed_time || getElapsedTime(attr.color_changed_at)
        }
    })
})

function getElapsedTime(colorChangedAt) {
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

function cambiarCentro() {
    router.get(route('supervisor.tv-panels'), {
        work_center_id: selectedWorkCenterId.value,
        date: props.selectedDate,
        shift: props.selectedShift
    }, { preserveState: true })
}

function toggleTheme() {
    isDarkMode.value = !isDarkMode.value
}

function formatNumber(num) {
    if (num === null || num === undefined) return '0'
    return new Intl.NumberFormat('es-MX').format(num)
}

function getTone(value, defaultTone) {
    if (value > 0) return defaultTone
    return 'default'
}

function getDifferenceTone(value) {
    if (value < 0) return 'bad'
    if (value > 0) return 'ok'
    return 'default'
}

function getComplianceTone(value) {
    if (value >= 95) return 'ok'
    if (value >= 80) return 'warn'
    return 'bad'
}

// Reloj de bloques
let clockInterval = null
let dataRefreshInterval = null

onMounted(() => {
    initClock()
    clockInterval = setInterval(updateClock, 1000)
    // Actualizar datos completos (incluyendo semáforos) cada 1 minuto
    dataRefreshInterval = setInterval(refreshData, 60000)
})

onUnmounted(() => {
    if (clockInterval) {
        clearInterval(clockInterval)
    }
    if (dataRefreshInterval) {
        clearInterval(dataRefreshInterval)
    }
})

async function refreshData() {
    try {
        await router.reload({
            only: ['dailyProgram', 'allKPIs', 'centerKPIs', 'attributes'],
            preserveState: true,
            preserveScroll: true
        })
    } catch (error) {
        console.error('Error al actualizar datos:', error)
    }
}

function initClock() {
    const t = horaEnBloques()
    paintBlock('h0', t.h0, t.h0)
    paintBlock('h1', t.h1, t.h1)
    paintBlock('m0', t.m0, t.m0)
    paintBlock('m1', t.m1, t.m1)
    paintBlock('s0', t.s0, t.s0)
    paintBlock('s1', t.s1, t.s1)
    paintBlock('ap', t.ap, t.ap)
}

let prevT = horaEnBloques()

function horaEnBloques() {
    const d = new Date()
    // Convertir a la zona horaria correcta
    const options = { timeZone, hour12: false, hour: 'numeric', minute: 'numeric', second: 'numeric' }
    const formatter = new Intl.DateTimeFormat('en-US', options)
    const parts = formatter.formatToParts(d)
    const getPart = (type) => parts.find(p => p.type === type)?.value || '00'
    let h = parseInt(getPart('hour')), m = parseInt(getPart('minute')), s = parseInt(getPart('second'))
    const ap = h < 12 ? 'AM' : 'PM'
    if (h === 0) h = 12
    if (h > 12) h -= 12
    const hh = dosDig(h), mm = dosDig(m), ss = dosDig(s)
    return { h0: hh[0], h1: hh[1], m0: mm[0], m1: mm[1], s0: ss[0], s1: ss[1], ap }
}

function dosDig(n) {
    return n < 10 ? '0' + n : '' + n
}

function paintBlock(key, a, b) {
    const el = document.querySelector('[data-block="' + key + '"]')
    if (!el) return
    const digits = el.querySelectorAll('.rlj__digits')
    digits[0].textContent = a
    digits[1].textContent = b
}

function updateClock() {
    const t = horaEnBloques()
    const keys = ['h0', 'h1', 'm0', 'm1', 's0', 's1', 'ap']
    keys.forEach(key => {
        if (t[key] !== prevT[key]) {
            paintBlock(key, prevT[key], t[key])
            restartAnim(key)
            setTimeout(() => paintBlock(key, t[key], t[key]), 720)
        }
    })
    prevT = t
}

function restartAnim(key) {
    const el = document.querySelector('[data-block="' + key + '"]')
    if (!el) return
    const group = el.querySelector('.rlj__group')
    el.classList.remove('bounce')
    group.classList.remove('roll')
    void el.offsetWidth
    el.classList.add('bounce')
    group.classList.add('roll')
}
</script>

<style scoped>
/* Variables CSS */
.dash-root {
    --mist-500: #4a6c7e;
    --mist-600: #3a5667;
    --mist-700: #2f4654;
    --mist-800: #263844;
    --stone-50: #f6f5f2;
    --stone-100: #ecebe4;
    --ink-400: #9ca3af;
    --ink-500: #6b7280;
    --ink-800: #1f2937;
    --ink-900: #111827;
    --cloud-50: #f8fafc;
    --cloud-100: #f1f5f9;
    --cloud-200: #e2e8f0;
    --cloud-300: #cbd5e1;
    --ok-500: #16a34a;
    --ok-600: #15803d;
    --warn-500: #f59e0b;
    --warn-600: #d97706;
    --bad-500: #dc2626;
    --surface: #ffffff;
    --chip-bg: rgba(248, 250, 252, 0.7);
    height: 100%;
    width: 100%;
    display: flex;
    flex-direction: column;
    background: var(--stone-100);
    color: var(--ink-800);
    overflow: hidden;
}

.dash-root.tema-oscuro {
    --cloud-50: #181b21;
    --cloud-100: #14171c;
    --cloud-200: #2b3038;
    --cloud-300: #3a414b;
    --ink-400: #8a92a0;
    --ink-500: #a6adb8;
    --ink-800: #e7eaee;
    --ink-900: #f4f6f8;
    --stone-50: #14161a;
    --stone-100: #101216;
    --surface: #1e222a;
    --chip-bg: rgba(20, 23, 28, 0.6);
}

.tnum {
    font-variant-numeric: tabular-nums;
}

/* Header */
.topbar {
    position: relative;
    z-index: 5;
    border-bottom: 1px solid var(--cloud-200);
    background: rgba(255, 255, 255, 0.95);
    flex-shrink: 0;
}

.dash-root.tema-oscuro .topbar {
    background: rgba(30, 34, 42, 0.95);
}

.topbar-inner {
    position: relative;
    margin: 0 auto;
    width: 100%;
    max-width: 1024px;
    display: flex;
    flex-wrap: nowrap;
    align-items: center;
    padding: 12px;
}

.logo {
    height: 28px;
    width: auto;
    flex-shrink: 0;
    object-fit: contain;
}

.clock-wrap {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
}

.topbar-right {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 10px;
}

.selector-wrap {
    position: relative;
    display: inline-flex;
    align-items: center;
}

.centro-select {
    appearance: none;
    -webkit-appearance: none;
    cursor: pointer;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.7);
    background: linear-gradient(to bottom, rgba(255, 255, 255, 0.75), rgba(255, 255, 255, 0.35));
    padding: 4px 24px 4px 10px;
    font-size: 11.2px;
    font-weight: 600;
    color: var(--ink-800);
    box-shadow: 0 1px 6px rgba(38, 56, 68, 0.10);
    backdrop-filter: blur(12px);
    outline: none;
    font-family: inherit;
}

.selector-chevron {
    position: absolute;
    right: 8px;
    width: 12px;
    height: 12px;
    color: var(--ink-500);
    pointer-events: none;
}

.toggle-tema {
    position: relative;
    display: inline-flex;
    align-items: center;
    height: 18px;
    width: 32px;
    border-radius: 9999px;
    background: #e5e7eb;
    border: none;
    cursor: pointer;
    transition: background-color 0.3s;
    padding: 0;
}

.toggle-tema.on {
    background: #334155;
}

.toggle-thumb {
    position: absolute;
    left: 2px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 14px;
    height: 14px;
    border-radius: 9999px;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    transition: transform 0.3s;
    color: #f59e0b;
}

.toggle-tema.on .toggle-thumb {
    transform: translateX(14px);
    color: #334155;
}

/* Reloj de bloques */
.rlj {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.rlj__block {
    position: relative;
    width: 2.05rem;
    height: 2.05rem;
    border-radius: 0.46rem;
    overflow: hidden;
    background: #fff;
    color: #6E7178;
    box-shadow: 0 2px 5px rgba(15, 23, 42, 0.16);
}

.rlj__block.bounce {
    animation: rlj-bounce 0.7s;
}

.rlj__block--sm {
    width: 1.55rem;
    height: 1.55rem;
    border-radius: 0.36rem;
}

.rlj__group {
    display: flex;
    flex-direction: column-reverse;
    height: 200%;
}

.rlj__group.roll {
    animation: rlj-roll 0.7s ease-in-out forwards;
}

.rlj__digits {
    height: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    font-weight: 800;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

.rlj__block--sm .rlj__digits {
    font-size: 0.52rem;
    letter-spacing: 0.01em;
}

.rlj__colon {
    font-size: 0.95rem;
    font-weight: 800;
    color: #0f172a;
    opacity: 0.5;
}

.dash-root.tema-oscuro .rlj__colon {
    color: #e7eaee;
}

@keyframes rlj-bounce {
    from, to { transform: translateY(0); }
    50% { transform: translateY(10%); }
}

@keyframes rlj-roll {
    from { transform: translateY(-50%); }
    to { transform: translateY(0); }
}

/* Main */
.dash-main {
    margin: 0 auto;
    width: 100%;
    max-width: 1024px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 10px;
    min-height: 0;
}

.top-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    align-items: start;
    gap: 8px;
}

.info-cards {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.stat-card {
    position: relative;
    display: flex;
    align-items: center;
    overflow: hidden;
    border-radius: 8px;
    border: 1px solid var(--cloud-200);
    background: var(--surface);
    padding: 6px 10px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.stat-ghost {
    position: absolute;
    bottom: -6px;
    right: -6px;
    opacity: 0.07;
    pointer-events: none;
}

.stat-ghost svg {
    width: 28px;
    height: 28px;
}

.stat-body {
    position: relative;
    min-width: 0;
}

.stat-label {
    font-size: 7.2px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: var(--ink-500);
    white-space: nowrap;
}

.stat-value {
    font-size: 14px;
    font-weight: 800;
    line-height: 1;
    color: var(--ink-900);
}

.stat-unit {
    margin-left: 4px;
    font-size: 8px;
    font-weight: 500;
    color: var(--ink-400);
}

/* Semáforos */
.semaforos-panel {
    display: flex;
    flex-direction: column;
    border-radius: 12px;
    border: 1px solid var(--cloud-200);
    background: var(--surface);
    padding: 6px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.semaforos-panel h2 {
    margin: 0 0 4px;
    font-size: 10.4px;
    font-weight: 700;
    color: var(--ink-800);
}

.semaforos-list {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 2px;
    list-style: none;
    margin: 0;
    padding: 0;
}

.semaforo-row {
    display: flex;
    align-items: center;
    gap: 6px;
    border-radius: 8px;
    border: 1px solid var(--cloud-200);
    background: var(--surface);
    padding: 2px 6px;
}

.semaforo-row.estado-ok {
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05), 0 0 0 2px rgba(22, 163, 74, 0.4);
}

.semaforo-row.estado-warn {
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05), 0 0 0 2px rgba(245, 158, 11, 0.4);
}

.semaforo-row.estado-bad {
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05), 0 0 0 2px rgba(220, 38, 38, 0.4);
}

.semaforo-row.estado-default {
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.tl-housing {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    border-radius: 9999px;
    padding: 4px 8px;
    background: linear-gradient(145deg, #363b43 0%, #1b1e23 55%, #0f1114 100%);
    border: 1px solid rgba(0, 0, 0, 0.45);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.12), inset 0 -3px 6px rgba(0, 0, 0, 0.6), 0 3px 8px -2px rgba(0, 0, 0, 0.35);
    flex-shrink: 0;
}

.lamp {
    width: 6px;
    height: 6px;
    border-radius: 9999px;
    background: radial-gradient(circle at 35% 30%, #2c3037, #1a1c20 75%);
    box-shadow: inset 0 0 3px rgba(0, 0, 0, 0.9), inset 0 1px 1px rgba(255, 255, 255, 0.05);
}

.lamp.on-ok {
    background: radial-gradient(circle at 35% 30%, #4ade80, #16a34a 70%);
    box-shadow: 0 0 10px 2px rgba(34, 197, 94, 0.75), inset 0 0 3px rgba(255, 255, 255, 0.6);
    animation: lampPulse 2.4s ease-in-out infinite;
}

.lamp.on-warn {
    background: radial-gradient(circle at 35% 30%, #fcd34d, #f59e0b 70%);
    box-shadow: 0 0 10px 2px rgba(245, 158, 11, 0.75), inset 0 0 3px rgba(255, 255, 255, 0.6);
    animation: lampPulse 2.4s ease-in-out infinite;
}

.lamp.on-bad {
    background: radial-gradient(circle at 35% 30%, #f87171, #dc2626 70%);
    box-shadow: 0 0 10px 2px rgba(220, 38, 38, 0.75), inset 0 0 3px rgba(255, 255, 255, 0.6);
    animation: lampPulse 2.4s ease-in-out infinite;
}

.lamp.on-default {
    background: radial-gradient(circle at 35% 30%, #9ca3af, #6b7280 70%);
    box-shadow: 0 0 6px 1px rgba(107, 114, 128, 0.5), inset 0 0 3px rgba(255, 255, 255, 0.4);
}

@keyframes lampPulse {
    0%, 100% { filter: brightness(1); }
    50% { filter: brightness(1.35); }
}

.semaforo-info {
    min-width: 0;
    flex: 1;
}

.semaforo-area {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 9.6px;
    font-weight: 700;
    line-height: 1.2;
    color: var(--ink-900);
}

.semaforo-desde {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 8px;
    line-height: 1.2;
    color: var(--ink-400);
}

/* Programa del Turno */
.programa-turno {
    border-radius: 12px;
    border: 1px solid var(--cloud-200);
    background: linear-gradient(to bottom, var(--stone-50), var(--cloud-100));
    padding: 8px;
}

.programa-turno .pt-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 2px;
    margin-bottom: 6px;
}

.programa-turno h2 {
    margin: 0;
    font-size: 11.2px;
    font-weight: 700;
    color: var(--ink-800);
}

.turno-badge {
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.7);
    padding: 2px 6px;
    font-size: 9.6px;
    font-weight: 600;
    color: var(--ink-500);
    box-shadow: 0 0 0 1px var(--cloud-200);
}

.dash-root.tema-oscuro .turno-badge {
    background: rgba(255, 255, 255, 0.08);
}

.kpi-rows {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.kpi-divider {
    border-top: 1px dashed var(--cloud-300);
}

.kpi-row {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 4px;
}

.kpi-tile {
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 38px;
    overflow: hidden;
    border-radius: 8px;
    border: 1px solid rgba(226, 232, 240, 0.8);
    background: linear-gradient(to bottom, var(--surface), var(--cloud-100));
    padding: 4px 8px;
}

.kpi-tile::before {
    content: "";
    position: absolute;
    left: 0;
    right: 0;
    top: 0;
    height: 1px;
    background: rgba(255, 255, 255, 0.7);
}

.kpi-label {
    font-size: 8px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: var(--ink-500);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.kpi-value {
    margin-top: 2px;
    font-size: 14px;
    font-weight: 800;
    line-height: 1;
}

.kpi-mini {
    margin-top: 2px;
    display: block;
    height: 2px;
    width: 16px;
    border-radius: 9999px;
    opacity: 0.7;
}

.kpi-bar-track {
    margin-top: 2px;
    height: 4px;
    border-radius: 9999px;
    background: var(--cloud-200);
    overflow: hidden;
}

.kpi-bar-fill {
    height: 100%;
    border-radius: 9999px;
}

.tone-default { color: var(--ink-900); }
.tone-accent { color: var(--mist-700); }
.tone-ok { color: var(--ok-600); }
.tone-warn { color: var(--warn-600); }
.tone-bad { color: var(--bad-500); }

.bg-default { background: var(--ink-400); }
.bg-accent { background: var(--mist-500); }
.bg-ok { background: var(--ok-500); }
.bg-warn { background: var(--warn-500); }
.bg-bad { background: var(--bad-500); }

/* Líneas de Producción */
.lineas-produccion h2 {
    margin: 0 0 4px;
    font-size: 11.2px;
    font-weight: 700;
    color: var(--ink-800);
}

.lineas-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.linea-card {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    align-items: center;
    gap: 8px;
    border-radius: 12px;
    border: 1px solid var(--cloud-200);
    background: var(--surface);
    padding: 6px 12px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.linea-nombre {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 12px;
    font-weight: 700;
    color: var(--ink-900);
}

.stat-chip {
    border-radius: 8px;
    border: 1px solid var(--cloud-200);
    background: var(--chip-bg);
    padding: 4px 8px;
}

.chip-head {
    display: flex;
    align-items: center;
    gap: 4px;
    color: var(--ink-400);
}

.chip-head svg {
    width: 10px;
    height: 10px;
}

.chip-head span {
    font-size: 8px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.2px;
}

.chip-value {
    font-size: 12px;
    font-weight: 700;
    line-height: 1;
    color: var(--ink-900);
}

.chip-unit {
    margin-left: 4px;
    font-size: 8.8px;
    font-weight: 500;
    color: var(--ink-400);
}
</style>
