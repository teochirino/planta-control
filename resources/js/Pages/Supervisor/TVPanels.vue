<template>
    <div>
        <SupervisorSidebar :hide-button="isFullscreen" />
        <div class="dashboard">
        <header class="topbar">
            <div class="brand" aria-label="Línea Italia">
                <div class="brand-mark">LI</div>
                <div class="brand-copy">
                    <div class="brand-name">línea italia</div>
                    <div class="brand-subtitle">Mobiliario de oficina</div>
                </div>
            </div>

            <div class="area-control">
                <span class="area-label">Área</span>
                <div class="select-wrap">
                    <select v-model="selectedWorkCenterId" @change="cambiarCentro" aria-label="Área de producción">
                        <option v-for="wc in workCentersData" :key="wc.id" :value="wc.id">
                            {{ wc.name }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="shift-control">
                <span class="shift-label">Turno</span>
                <div class="select-wrap">
                    <select v-model="selectedShiftLocal" @change="cambiarTurno" aria-label="Turno de producción">
                        <option value="matutino">Matutino</option>
                        <option value="vespertino">Vespertino</option>
                    </select>
                </div>
            </div>

            <div class="program-control" v-if="dailyProgramData?.program?.codigo">
                <span class="program-label">Programa</span>
                <span class="program-value">{{ dailyProgramData.program.codigo }}</span>
            </div>

            <div class="clock" aria-live="polite">
                <span id="clockTime" class="clock-time">{{ currentTime }}</span>
                <span id="clockPeriod" class="clock-period">{{ currentPeriod }}</span>
            </div>

            <div class="top-actions">
                <div class="shift-chip" :class="selectedShiftLocal">
                    <span class="shift-dot"></span>
                    <span id="shiftText">Turno {{ turnoLabel.toLowerCase() }}</span>
                </div>
                <button id="fullscreenBtn" class="icon-btn" type="button" title="Pantalla completa" aria-label="Pantalla completa" @click="toggleFullscreen">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5" />
                    </svg>
                </button>
            </div>
        </header>

        <section class="content">
            <aside class="left-column">
                <section class="panel summary-panel" aria-label="Resumen de capacidad">
                    <div class="summary-main">
                        <div>
                            <div class="summary-label">Capacidad instalada</div>
                            <div class="summary-value">{{ formatNumber(centerKPIsData?.installed_capacity || 0) }}</div>
                            <div class="summary-detail">piezas por día</div>
                        </div>
                        <div class="capacity-ring" :data-value="Math.round(centerKPIsData?.compliance || 0)" :style="'--value: ' + Math.min(centerKPIsData?.compliance || 0, 100)"></div>
                    </div>

                    <div class="summary-block">
                        <div class="summary-label">Líneas activas</div>
                        <div class="summary-value">{{ productionLinesForCenterData.length }}</div>
                        <div class="summary-detail">en operación</div>
                    </div>

                    <div class="summary-block date-block">
                        <div class="summary-label">Fecha actual</div>
                        <div class="summary-value">{{ fechaFormateada }}</div>
                        <div class="summary-detail">{{ diaSemana }}</div>
                    </div>

                    <div class="summary-block difference-block">
                        <div class="summary-label">Diferencia</div>
                        <div class="summary-value" :style="centerKPIsData?.difference >= 0 ? 'color: var(--green)' : 'color: var(--red)'">{{ centerKPIsData?.difference >= 0 ? '+' : '' }}{{ formatNumber(centerKPIsData?.difference || 0) }}</div>
                        <div class="summary-detail">vs. programa</div>
                    </div>
                </section>

                <section class="panel status-panel">
                    <div class="panel-heading">
                        <div>
                            <h2 class="panel-title">Semáforos de área</h2>
                            <span class="panel-kicker">Tiempo de respuesta y disponibilidad</span>
                        </div>
                    </div>
                    <div class="status-list">
                        <article v-for="semaforo in semaforosData" :key="semaforo.area" class="status-item" :class="getStatusClass(semaforo.estado)">
                            <span class="status-light" aria-hidden="true"></span>
                            <div>
                                <div class="status-name">{{ semaforo.area }}</div>
                                <div class="status-time">{{ semaforo.desde }}</div>
                            </div>
                            <span class="status-badge">{{ getStatusLabel(semaforo.estado) }}</span>
                        </article>
                    </div>
                </section>

                <section class="panel line-panel">
                    <div class="panel-heading">
                        <div>
                            <h2 class="panel-title">Líneas de producción</h2>
                            <span class="panel-kicker">Rendimiento actual por equipo</span>
                        </div>
                    </div>
                    <div class="line-list">
                        <article v-for="line in productionLinesForCenterData" :key="line.id" class="line-card">
                            <div>
                                <div class="line-name">{{ line.title }}</div>
                                <div class="line-state" :class="{ 'state-stopped': getLineState(line.id) === 'En paro' }">{{ getLineState(line.id) }}</div>
                            </div>
                            <div class="line-metric">
                                <div class="line-metric-label">Capacidad</div>
                                <div class="line-metric-value">{{ formatNumber(line.installed_capacity || 0) }} <small>pzs/día</small></div>
                            </div>
                            <div class="line-metric">
                                <div class="line-metric-label">Costo</div>
                                <div class="line-metric-value">${{ formatNumber(line.cost || 0) }} <small>/min</small></div>
                            </div>
                        </article>
                    </div>
                </section>

                <section class="panel history-panel">
                    <div class="history-title">
                        <span>Cumplimiento reciente</span>
                        <span class="history-subtitle">Últimos 3 días</span>
                    </div>
                    <div class="history-grid">
                        <article class="history-card" v-for="(history, index) in recentHistory" :key="'history-' + index">
                            <div class="history-date">{{ history.date }}</div>
                            <div class="history-value">{{ history.value }}%</div>
                            <div class="history-bar"><div class="history-fill" :style="'width:' + history.value + '%'"></div></div>
                        </article>
                    </div>
                </section>
            </aside>

            <section class="right-column">
                <section class="panel kpi-panel">
                    <div class="panel-heading">
                        <div>
                            <h1 class="panel-title">Programa del turno</h1>
                            <span class="panel-kicker">{{ turnoLabel }} · {{ fechaFormateada }}</span>
                        </div>
                        <span class="refresh-status">Actualización automática</span>
                    </div>
                    <div class="kpi-grid">
                        <article class="kpi-card">
                            <div class="kpi-label">Programado</div>
                            <div class="kpi-value">{{ formatNumber(centerKPIsData?.programmed || 0) }}</div>
                            <div class="kpi-meta">piezas del turno</div>
                        </article>
                        <article class="kpi-card negative">
                            <div class="kpi-label">Atraso</div>
                            <div class="kpi-value">{{ formatNumber(centerKPIsData?.backwardness || 0) }}</div>
                            <div class="kpi-meta">piezas pendientes</div>
                        </article>
                        <article class="kpi-card positive">
                            <div class="kpi-label">Adelantadas</div>
                            <div class="kpi-value">{{ formatNumber(centerKPIsData?.advanced || 0) }}</div>
                            <div class="kpi-meta">piezas anticipadas</div>
                        </article>
                        <article class="kpi-card">
                            <div class="kpi-label">Total a producir</div>
                            <div class="kpi-value">{{ formatNumber(centerKPIsData?.total_to_produce || 0) }}</div>
                            <div class="kpi-meta">piezas restantes</div>
                        </article>
                        <article class="kpi-card positive">
                            <div class="kpi-label">Fabricadas</div>
                            <div class="kpi-value">{{ formatNumber(centerKPIsData?.fabricated || 0) }}</div>
                            <div class="kpi-meta">piezas acumuladas</div>
                        </article>
                        <article class="kpi-card compliance">
                            <div class="kpi-label">Cumplimiento</div>
                            <div class="kpi-value">{{ (centerKPIsData?.compliance || 0).toFixed(1) }}%</div>
                            <div class="kpi-meta">{{ formatNumber(centerKPIsData?.total_to_produce || 0) }} por fabricar</div>
                            <div class="progress-track"><div class="progress-fill" :style="'width:' + Math.min(centerKPIsData?.compliance || 0, 100) + '%'"></div></div>
                        </article>
                    </div>
                </section>

                <section class="panel table-panel">
                    <div class="panel-heading">
                        <div>
                            <h2 class="panel-title">Producción por hora</h2>
                            <span class="panel-kicker">Avance consolidado por línea y hora</span>
                        </div>
                        <span class="refresh-status">Datos actualizados ahora</span>
                    </div>

                    <div class="table-wrap">
                        <table aria-label="Producción por hora">
                            <colgroup>
                                <col style="width: 13%" />
                                <col style="width: 12%" />
                                <col v-for="line in productionLinesForCenterData" :key="'col-' + line.id" style="width: 1fr" />
                                <col style="width: 10%" />
                                <col style="width: 17%" />
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Hora</th>
                                    <th>Producción esperada</th>
                                    <th v-for="line in productionLinesForCenterData" :key="'th-' + line.id">{{ line.title }}</th>
                                    <th>Producción</th>
                                    <th>Cumplimiento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(hour, index) in productionHours" :key="'hour-' + index" :class="hour.isCurrent ? 'current-row' : ''">
                                    <td>{{ hour.time }}</td>
                                    <td class="expected-cell">{{ formatNumber(hour.expected) }}</td>
                                    <td v-for="line in productionLinesForCenterData" :key="'val-' + line.id + '-' + index">
                                        <span class="value-pill" :class="getHourValueClass(hour.production[line.id], hour.expected)">{{ formatNumber(hour.production[line.id] || 0) }}</span>
                                    </td>
                                    <td><span class="row-total">{{ formatNumber(hour.total) }}</span></td>
                                    <td class="compliance-cell">
                                        <div class="compliance-top">
                                            <span class="compliance-value">{{ hour.compliance.toFixed(1) }}%</span>
                                            <span class="mini-status" :class="getComplianceStatus(hour.compliance)"></span>
                                        </div>
                                        <div class="mini-progress">
                                            <span :class="getComplianceStatus(hour.compliance)" :style="'width:' + Math.min(hour.compliance, 100) + '%'"></span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="table-total" :style="{ gridTemplateColumns: '13% 12% repeat(' + productionLinesForCenterData.length + ', 1fr) 10% 17%' }">
                        <div>TOTAL</div>
                        <div>{{ formatNumber(totalExpected) }}</div>
                        <div v-for="line in productionLinesForCenterData" :key="'total-' + line.id">{{ formatNumber(lineTotals[line.id] || 0) }}</div>
                        <div>{{ formatNumber(totalProduced) }}</div>
                        <div class="total-compliance">{{ totalCompliance.toFixed(1) }}%</div>
                    </div>
                </section>
            </section>
        </section>

        <!-- Video RRHH Modal -->
        <div v-if="showVideoModal && currentVideo" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-90">
            <div class="relative w-full max-w-5xl mx-4">
                <button
                    @click="closeVideoModal"
                    class="absolute -top-12 right-0 text-white hover:text-gray-300 transition"
                >
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                
                <div class="bg-black rounded-lg overflow-hidden shadow-2xl">
                    <video
                        ref="videoPlayerRef"
                        :src="getVideoUrl(currentVideo.ruta_video)"
                        class="w-full"
                        autoplay
                        @ended="onVideoEnded"
                    ></video>
                    
                    <div class="bg-gray-900 text-white p-4">
                        <h3 class="text-xl font-bold">{{ currentVideo.nombre }}</h3>
                        <p class="text-gray-400 text-sm mt-1">
                            Programado: {{ currentVideo.hora_reproduccion }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import SupervisorSidebar from '@/Components/SupervisorSidebar.vue'

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
    },
    hours: {
        type: Array,
        default: () => []
    },
    existingSchedules: {
        type: Object,
        default: () => ({})
    },
    recentHistory: {
        type: Array,
        default: () => []
    }
})

const selectedWorkCenterId = ref(props.selectedWorkCenter?.id || null)
const selectedShiftLocal = ref(props.selectedShift || 'matutino')
const isDarkMode = ref(false)
const isFullscreen = ref(false)
const currentTime = ref('')
const currentPeriod = ref('')

// Video RRHH Modal
const showVideoModal = ref(false)
const currentVideo = ref(null)
const videoPlayerRef = ref(null)
const videosReproducedToday = ref([])
const videoCheckInterval = ref(null)

// Detectar zona horaria según entorno
const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
const timeZone = isLocal ? 'America/Caracas' : 'America/Mexico_City'

const workCentersData = computed(() => props.workCenters)
const selectedWorkCenterData = computed(() => props.selectedWorkCenter)
const productionLinesForCenterData = computed(() => props.productionLinesForCenter)
const centerKPIsData = computed(() => props.centerKPIs)
const dailyProgramData = computed(() => props.dailyProgram)

const turnoLabel = computed(() => {
    const labels = {
        matutino: 'Matutino',
        vespertino: 'Vespertino'
    }
    return labels[selectedShiftLocal.value] || selectedShiftLocal.value
})

const fechaFormateada = computed(() => {
    // Usar la fecha actual del sistema
    const date = new Date()
    return date.toLocaleDateString('es-ES', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric'
    })
})

const diaSemana = computed(() => {
    // Usar la fecha actual del sistema
    const date = new Date()
    return date.toLocaleDateString('es-ES', { 
        weekday: 'long'
    })
})

// Historial de cumplimiento de los últimos 3 días (del backend)
const recentHistory = computed(() => {
    // Usar datos del backend si están disponibles, sino usar valores por defecto
    if (props.recentHistory && props.recentHistory.length > 0) {
        return props.recentHistory
    }
    
    // Valores por defecto si no hay datos del backend
    return [
        { date: '29/07/2026', value: 98 },
        { date: '30/07/2026', value: 85 },
        { date: '31/07/2026', value: 92 }
    ]
})

function updateClock() {
    const now = new Date()
    const hours24 = now.getHours()
    const period = hours24 >= 12 ? 'PM' : 'AM'
    const hours12 = hours24 % 12 || 12
    const time = [hours12, now.getMinutes(), now.getSeconds()]
        .map((value) => String(value).padStart(2, '0'))
        .join(':')
    
    currentTime.value = time
    currentPeriod.value = period
}

function toggleFullscreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(err => {
            console.warn('No fue posible activar pantalla completa', err)
        })
        isFullscreen.value = true
    } else {
        document.exitFullscreen().catch(err => {
            console.warn('No fue posible salir de pantalla completa', err)
        })
        isFullscreen.value = false
    }
}

function getStatusClass(estado) {
    if (estado === 'warn') return 'warning'
    if (estado === 'bad') return 'danger'
    return ''
}

function getStatusLabel(estado) {
    const labels = {
        'ok': 'Disponible',
        'warn': 'Atención',
        'bad': 'Crítico',
        'default': 'Sin datos'
    }
    return labels[estado] || 'Sin datos'
}

// Datos de producción por hora usando datos reales del backend
const productionHours = computed(() => {
    const hours = []
    const now = new Date()
    const currentHour = now.getHours()
    
    // Usar las horas del backend o generar horarios por defecto
    const hoursData = props.hours.length > 0 ? props.hours : [
        { start: '08:00', end: '09:00' }, { start: '09:00', end: '10:00' },
        { start: '10:00', end: '11:00' }, { start: '11:00', end: '12:00' },
        { start: '12:00', end: '13:00' }, { start: '13:00', end: '14:00' },
        { start: '14:00', end: '15:00' }, { start: '15:00', end: '16:00' },
        { start: '16:00', end: '17:00' }
    ]
    
    const expectedPerHour = centerKPIsData.value?.total_to_produce > 0 
        ? (centerKPIsData.value.total_to_produce / hoursData.length).toFixed(2) 
        : 0
    
    hoursData.forEach(hora => {
        const hourStart = parseInt(hora.start.split(':')[0])
        const isCurrent = hourStart === currentHour
        
        // Obtener producción real de cada línea desde existingSchedules
        const production = {}
        let total = 0
        
        productionLinesForCenterData.value.forEach(line => {
            const scheduleKey = `${hora.start}-${line.id}`
            const schedule = props.existingSchedules[scheduleKey]
            const lineProduction = schedule?.produced || 0
            production[line.id] = lineProduction
            total += lineProduction
        })
        
        const expected = parseFloat(expectedPerHour) || 0
        const compliance = expected > 0 ? (total / expected) * 100 : 0
        
        hours.push({
            time: hora.start,
            expected: Math.round(expected),
            production,
            total,
            compliance,
            isCurrent
        })
    })
    
    return hours
})

const totalExpected = computed(() => {
    return productionHours.value.reduce((sum, hour) => sum + hour.expected, 0)
})

const totalProduced = computed(() => {
    return productionHours.value.reduce((sum, hour) => sum + hour.total, 0)
})

const lineTotals = computed(() => {
    const totals = {}
    productionLinesForCenterData.value.forEach(line => {
        totals[line.id] = productionHours.value.reduce((sum, hour) => sum + (hour.production[line.id] || 0), 0)
    })
    return totals
})

const totalCompliance = computed(() => {
    return totalExpected.value > 0 ? (totalProduced.value / totalExpected.value) * 100 : 0
})

function getHourValueClass(value, expected) {
    if (value >= expected) return 'best'
    return ''
}

function getComplianceStatus(compliance) {
    if (compliance >= 95) return 'ok'
    if (compliance >= 80) return 'warning'
    return ''
}

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
        shift: selectedShiftLocal.value
    }, { preserveState: true })
}

function cambiarTurno() {
    router.get(route('supervisor.tv-panels'), {
        work_center_id: selectedWorkCenterId.value,
        date: props.selectedDate,
        shift: selectedShiftLocal.value
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

function getLineState(lineId) {
    // Buscar strikes activos para esta línea
    const lineKPIs = props.allKPIs.find(kpi => kpi.line.id === lineId)
    if (lineKPIs && lineKPIs.strikes) {
        const activeStrike = lineKPIs.strikes.find(strike => !strike.end_time)
        if (activeStrike) {
            return 'En paro'
        }
    }
    return 'Operando'
}

// Reloj de bloques
let clockInterval = null
let dataRefreshInterval = null

onMounted(() => {
    updateClock()
    clockInterval = setInterval(updateClock, 1000)
    // Actualizar datos completos (incluyendo semáforos) cada 1 minuto
    dataRefreshInterval = setInterval(refreshData, 60000)
    
    // Initialize video RRHH system
    loadVideosReproducedToday()
    // Check for videos immediately on mount
    checkForScheduledVideos()
    // Check for videos every 5 minutes
    videoCheckInterval.value = setInterval(checkForScheduledVideos, 300000)
    
    // ESC key listener for fullscreen
    document.addEventListener('keydown', handleEscKey)
    // Listener para detectar cambios en el estado de pantalla completa
    document.addEventListener('fullscreenchange', handleFullscreenChange)
})

onUnmounted(() => {
    if (clockInterval) {
        clearInterval(clockInterval)
    }
    if (dataRefreshInterval) {
        clearInterval(dataRefreshInterval)
    }
    if (videoCheckInterval.value) {
        clearInterval(videoCheckInterval.value)
    }
    document.removeEventListener('keydown', handleEscKey)
    document.removeEventListener('fullscreenchange', handleFullscreenChange)
})

function handleEscKey(event) {
    if (event.key === 'Escape' && document.fullscreenElement) {
        document.exitFullscreen().catch(err => {
            console.warn('No fue posible salir de pantalla completa', err)
        })
        isFullscreen.value = false
    }
}

function handleFullscreenChange() {
    isFullscreen.value = !!document.fullscreenElement
}

async function refreshData() {
    try {
        await router.reload({
            only: ['dailyProgram', 'allKPIs', 'centerKPIs', 'attributes', 'existingSchedules', 'recentHistory'],
            preserveState: true,
            preserveScroll: true
        })
    } catch (error) {
        console.error('Error al actualizar datos:', error)
    }
}


// Video RRHH Functions
function loadVideosReproducedToday() {
    const today = new Date().toDateString()
    const stored = localStorage.getItem('videos_reproduced_' + today)
    videosReproducedToday.value = stored ? JSON.parse(stored) : []
}

function saveVideosReproducedToday() {
    const today = new Date().toDateString()
    localStorage.setItem('videos_reproduced_' + today, JSON.stringify(videosReproducedToday.value))
}

async function checkForScheduledVideos() {
    try {
        const now = new Date()
        console.log('Checking for scheduled videos at:', now.toLocaleTimeString())
        
        const response = await window.axios.get('/api/videos-programados/scheduled')
        const videos = response.data
        
        console.log('Scheduled videos from API:', videos)
        console.log('Videos already reproduced today:', videosReproducedToday.value)
        
        // Filter out videos already reproduced today
        const newVideos = videos.filter(video => {
            const notInLocalStorage = !videosReproducedToday.value.includes(video.id)
            const notReproducedToday = !wasReproducedToday(video.ultima_reproduccion)
            console.log(`Video ${video.id}: localStorage=${notInLocalStorage}, backend=${notReproducedToday}, ultima_reproduccion=${video.ultima_reproduccion}`)
            return notInLocalStorage && notReproducedToday
        })
        
        console.log('New videos to play:', newVideos)
        
        if (newVideos.length > 0) {
            // Play the first video
            await playVideo(newVideos[0])
        }
    } catch (error) {
        console.error('Error checking for scheduled videos:', error)
    }
}

function wasReproducedToday(ultimaReproduccion) {
    if (!ultimaReproduccion) return false
    const lastPlayback = new Date(ultimaReproduccion)
    const today = new Date()
    return lastPlayback.toDateString() === today.toDateString()
}

async function playVideo(video) {
    try {
        // Register playback before starting
        await window.axios.post(`/api/videos-programados/${video.id}/register-playback`)
        
        // Update localStorage
        videosReproducedToday.value.push(video.id)
        saveVideosReproducedToday()
        
        // Set current video and show modal
        currentVideo.value = video
        showVideoModal.value = true
        
        // Auto-play video
        setTimeout(() => {
            if (videoPlayerRef.value) {
                videoPlayerRef.value.play().catch(error => {
                    console.error('Autoplay failed:', error)
                })
            }
        }, 100)
    } catch (error) {
        console.error('Error playing video:', error)
    }
}

function onVideoEnded() {
    showVideoModal.value = false
    currentVideo.value = null
}

function closeVideoModal() {
    if (videoPlayerRef.value) {
        videoPlayerRef.value.pause()
        videoPlayerRef.value.currentTime = 0
    }
    showVideoModal.value = false
    currentVideo.value = null
}

function getVideoUrl(path) {
    return '/storage/' + path
}
</script>

<style scoped>
.dashboard {
  --bg: #edf2f6;
  --panel: #ffffff;
  --panel-soft: #f7fafc;
  --navy: #0d2b3e;
  --navy-2: #143d58;
  --blue: #1f6f9c;
  --blue-soft: #dcecf5;
  --text: #193142;
  --muted: #6f8190;
  --line: #d9e2e9;
  --green: #18a66a;
  --green-soft: #e4f6ee;
  --yellow: #d59a19;
  --yellow-soft: #fff4d9;
  --red: #db4b55;
  --red-soft: #fdecee;
  --shadow: 0 10px 30px rgba(13, 43, 62, 0.08);
  --radius: 18px;
  
  padding: 18px;
  display: grid;
  grid-template-rows: 74px 1fr;
  gap: 14px;
}

.topbar {
  min-height: 0;
  display: grid;
  grid-template-columns: 320px minmax(260px, 1fr) auto auto auto auto;
  align-items: center;
  gap: 14px;
  padding: 0 20px;
  background: var(--panel);
  border: 1px solid rgba(217, 226, 233, 0.9);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.brand-mark {
  width: 44px;
  height: 44px;
  display: grid;
  place-items: center;
  border: 3px solid var(--navy);
  border-radius: 7px;
  color: var(--navy);
  font-size: 17px;
  font-weight: 900;
  letter-spacing: -1px;
}

.brand-copy {
  line-height: 1;
}

.brand-name {
  font-size: 23px;
  font-weight: 770;
  letter-spacing: -0.6px;
  white-space: nowrap;
}

.brand-subtitle {
  margin-top: 6px;
  color: var(--muted);
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 2px;
  text-transform: uppercase;
}

.area-control {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.area-label {
  color: var(--muted);
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 1.2px;
  text-transform: uppercase;
}

.select-wrap {
  position: relative;
  min-width: 250px;
  max-width: 390px;
  flex: 1;
}

.select-wrap select {
  width: 100%;
  height: 46px;
  appearance: none;
  padding: 0 42px 0 16px;
  border: 1px solid var(--line);
  border-radius: 13px;
  outline: none;
  background: var(--panel-soft);
  color: var(--text);
  font-weight: 750;
  cursor: pointer;
}

.select-wrap::after {
  content: "⌄";
  position: absolute;
  right: 16px;
  top: 50%;
  transform: translateY(-56%);
  color: var(--muted);
  pointer-events: none;
  font-size: 20px;
}

.shift-control {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.shift-label {
  color: var(--muted);
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 1.2px;
  text-transform: uppercase;
}

.program-control {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.program-label {
  color: var(--muted);
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 1.2px;
  text-transform: uppercase;
}

.program-value {
  color: var(--navy);
  font-size: 14px;
  font-weight: 750;
  background: var(--blue-soft);
  padding: 6px 12px;
  border-radius: 8px;
}

.clock {
  display: flex;
  align-items: baseline;
  gap: 8px;
  min-width: 190px;
  justify-content: flex-end;
  font-variant-numeric: tabular-nums;
}

.clock-time {
  font-size: clamp(28px, 2.1vw, 38px);
  font-weight: 820;
  letter-spacing: 1px;
  color: var(--navy);
}

.clock-period {
  color: var(--muted);
  font-size: 12px;
  font-weight: 850;
  letter-spacing: 1px;
}

.top-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.shift-chip {
  display: flex;
  align-items: center;
  gap: 9px;
  height: 42px;
  padding: 0 14px;
  border-radius: 12px;
  background: var(--navy);
  color: white;
  font-size: 12px;
  font-weight: 780;
  white-space: nowrap;
}

.shift-chip.matutino {
  background: var(--navy);
}

.shift-chip.matutino .shift-dot {
  background: #ffd36e;
  box-shadow: 0 0 0 5px rgba(255, 211, 110, 0.16);
}

.shift-chip.vespertino {
  background: #6b21a8;
}

.shift-chip.vespertino .shift-dot {
  background: #a78bfa;
  box-shadow: 0 0 0 5px rgba(167, 139, 250, 0.16);
}

.shift-dot {
  width: 9px;
  height: 9px;
  border-radius: 999px;
  background: #ffd36e;
  box-shadow: 0 0 0 5px rgba(255, 211, 110, 0.16);
}

.icon-btn {
  width: 42px;
  height: 42px;
  display: grid;
  place-items: center;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: var(--panel-soft);
  color: var(--navy);
  cursor: pointer;
  transition: transform 0.18s ease, background 0.18s ease;
}

.icon-btn:hover {
  transform: translateY(-1px);
  background: var(--blue-soft);
}

.icon-btn svg {
  width: 19px;
  height: 19px;
  stroke: currentColor;
  fill: none;
  stroke-width: 2;
}

.content {
  min-height: 0;
  display: grid;
  grid-template-columns: minmax(355px, 28%) minmax(0, 72%);
  gap: 14px;
}

.left-column,
.right-column {
  min-height: 0;
  display: grid;
  gap: 12px;
}

.left-column {
  grid-template-rows: 170px minmax(250px, 1.15fr) minmax(180px, 0.85fr) 126px;
}

.right-column {
  grid-template-rows: auto minmax(0, 1fr);
}

.panel {
  min-height: 0;
  background: var(--panel);
  border: 1px solid rgba(217, 226, 233, 0.92);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
}

.panel-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 16px 18px 12px;
}

.panel-title {
  margin: 0;
  font-size: clamp(15px, 1.05vw, 19px);
  font-weight: 820;
  letter-spacing: -0.25px;
  color: var(--navy);
}

.panel-kicker {
  display: block;
  margin-top: 4px;
  color: var(--muted);
  font-size: 10px;
  font-weight: 650;
}

.refresh-status {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--muted);
  font-size: 10px;
  font-weight: 700;
  white-space: nowrap;
}

.refresh-status::before {
  content: "";
  width: 8px;
  height: 8px;
  border-radius: 999px;
  background: var(--green);
  box-shadow: 0 0 0 5px rgba(24, 166, 106, 0.11);
}

.kpi-panel {
  padding-bottom: 14px;
}

.kpi-grid {
  padding: 0 14px;
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 10px;
}

.kpi-card {
  position: relative;
  min-height: 96px;
  overflow: hidden;
  padding: 14px;
  border: 1px solid var(--line);
  border-radius: 15px;
  background: linear-gradient(150deg, #ffffff 0%, #f7fafc 100%);
}

.kpi-card::after {
  content: "";
  position: absolute;
  right: -20px;
  bottom: -26px;
  width: 78px;
  height: 78px;
  border-radius: 50%;
  background: rgba(31, 111, 156, 0.07);
}

.kpi-label {
  color: var(--muted);
  font-size: 9px;
  font-weight: 850;
  letter-spacing: 1.1px;
  text-transform: uppercase;
}

.kpi-value {
  margin-top: 8px;
  color: var(--navy);
  font-size: clamp(24px, 1.85vw, 34px);
  font-weight: 850;
  letter-spacing: -1px;
  line-height: 1;
  font-variant-numeric: tabular-nums;
}

.kpi-meta {
  margin-top: 9px;
  color: var(--muted);
  font-size: 10px;
  font-weight: 650;
}

.kpi-card.positive .kpi-value,
.kpi-card.compliance .kpi-value {
  color: var(--green);
}

.kpi-card.negative .kpi-value {
  color: var(--red);
}

.progress-track {
  height: 5px;
  margin-top: 9px;
  overflow: hidden;
  border-radius: 999px;
  background: #e8eef2;
}

.progress-fill {
  height: 100%;
  width: 0;
  border-radius: inherit;
  background: var(--green);
  transition: width 0.5s ease;
}

.summary-panel {
  padding: 10px;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  grid-template-rows: minmax(72px, 1fr) auto;
  gap: 8px;
}

.summary-main {
  grid-column: 1 / -1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 72px;
  padding: 12px 14px;
  border-radius: 15px;
  background: linear-gradient(130deg, var(--navy), var(--navy-2));
  color: white;
}

.summary-main .summary-label {
  color: rgba(255, 255, 255, 0.7);
}

.summary-main .summary-value {
  color: white;
}

.summary-block {
  min-width: 0;
  min-height: 55px;
  padding: 9px 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--line);
  border-radius: 14px;
  background: var(--panel-soft);
  text-align: center;
}

.summary-block.date-block .summary-value {
  font-size: clamp(16px, 1.15vw, 21px);
  letter-spacing: -0.55px;
  white-space: nowrap;
}

.summary-block.difference-block {
  background: linear-gradient(145deg, #fff 0%, var(--red-soft) 100%);
  border-color: #f2cdd1;
}

.summary-label {
  color: var(--muted);
  font-size: 9px;
  font-weight: 850;
  letter-spacing: 1px;
  text-transform: uppercase;
}

.summary-value {
  margin-top: 5px;
  color: var(--navy);
  font-size: clamp(20px, 1.55vw, 28px);
  font-weight: 840;
  line-height: 1;
  font-variant-numeric: tabular-nums;
}

.summary-detail {
  margin-top: 5px;
  color: var(--muted);
  font-size: 9px;
  font-weight: 650;
}

.summary-main .summary-detail {
  color: rgba(255, 255, 255, 0.7);
}

.capacity-ring {
  --value: 84;
  width: 62px;
  height: 62px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: conic-gradient(#38c58a calc(var(--value) * 1%), rgba(255, 255, 255, 0.17) 0);
}

.capacity-ring::before {
  content: attr(data-value) "%";
  width: 46px;
  height: 46px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: var(--navy-2);
  color: white;
  font-size: 12px;
  font-weight: 820;
}

.status-panel {
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.status-list {
  min-height: 0;
  padding: 0 14px 14px;
  display: grid;
  gap: 7px;
  align-content: start;
}

.status-item {
  min-height: 44px;
  display: grid;
  grid-template-columns: 24px minmax(0, 1fr) auto;
  align-items: center;
  gap: 10px;
  padding: 8px 11px;
  border: 1px solid var(--line);
  border-radius: 13px;
  background: var(--panel-soft);
}

.status-light {
  width: 13px;
  height: 13px;
  border-radius: 999px;
  background: var(--green);
  box-shadow: 0 0 0 5px rgba(24, 166, 106, 0.11);
}

.status-item.warning .status-light {
  background: var(--yellow);
  box-shadow: 0 0 0 5px rgba(213, 154, 25, 0.12);
}

.status-item.danger .status-light {
  background: var(--red);
  box-shadow: 0 0 0 5px rgba(219, 75, 85, 0.12);
}

.status-name {
  color: var(--text);
  font-size: 12px;
  font-weight: 800;
  line-height: 1.05;
}

.status-time {
  margin-top: 4px;
  color: var(--muted);
  font-size: 9px;
  font-weight: 650;
}

.status-badge {
  padding: 5px 8px;
  border-radius: 999px;
  background: var(--green-soft);
  color: var(--green);
  font-size: 9px;
  font-weight: 850;
  text-transform: uppercase;
}

.status-item.warning .status-badge {
  background: var(--yellow-soft);
  color: #9f7008;
}

.status-item.danger .status-badge {
  background: var(--red-soft);
  color: var(--red);
}

.line-panel {
  padding-bottom: 14px;
}

.line-list {
  padding: 0 14px;
  display: grid;
  gap: 8px;
}

.line-card {
  display: grid;
  grid-template-columns: minmax(120px, 1fr) minmax(72px, 0.7fr) minmax(82px, 0.85fr);
  align-items: center;
  gap: 8px;
  min-height: 54px;
  padding: 9px 11px;
  border: 1px solid var(--line);
  border-radius: 14px;
  background: var(--panel-soft);
}

.line-name {
  font-size: 12px;
  font-weight: 820;
  white-space: nowrap;
}

.line-state {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  margin-top: 5px;
  color: var(--muted);
  font-size: 9px;
  font-weight: 700;
}

.line-state::before {
  content: "";
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--green);
}

.line-state.state-stopped {
  color: var(--red);
}

.line-state.state-stopped::before {
  background: var(--red);
}

.line-metric {
  min-width: 0;
  padding-left: 9px;
  border-left: 1px solid var(--line);
}

.line-metric-label {
  color: var(--muted);
  font-size: 8px;
  font-weight: 850;
  letter-spacing: 0.9px;
  text-transform: uppercase;
}

.line-metric-value {
  margin-top: 3px;
  color: var(--navy);
  font-size: 14px;
  font-weight: 840;
  white-space: nowrap;
  font-variant-numeric: tabular-nums;
}

.line-metric-value small {
  color: var(--muted);
  font-size: 8px;
  font-weight: 700;
}

.history-panel {
  padding: 14px;
}

.history-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
  color: var(--navy);
  font-size: 13px;
  font-weight: 820;
}

.history-subtitle {
  color: var(--muted);
  font-size: 9px;
  font-weight: 700;
}

.history-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}

.history-card {
  min-width: 0;
  padding: 10px;
  border: 1px solid var(--line);
  border-radius: 13px;
  background: var(--panel-soft);
  text-align: center;
}

.history-date {
  color: var(--muted);
  font-size: 9px;
  font-weight: 760;
  white-space: nowrap;
}

.history-value {
  margin-top: 4px;
  color: var(--navy);
  font-size: clamp(23px, 2vw, 34px);
  font-weight: 850;
  line-height: 1;
  letter-spacing: -1px;
}

.history-bar {
  height: 4px;
  margin-top: 8px;
  overflow: hidden;
  border-radius: 999px;
  background: #e5ecef;
}

.history-fill {
  height: 100%;
  border-radius: inherit;
  background: var(--green);
}

.table-panel {
  min-height: 0;
  overflow: hidden;
  display: grid;
  grid-template-rows: auto minmax(0, 1fr) auto;
}

.table-wrap {
  min-height: 0;
  overflow: hidden;
  padding: 0 14px;
}

table {
  width: 100%;
  height: 100%;
  border-collapse: separate;
  border-spacing: 0;
  table-layout: fixed;
  font-variant-numeric: tabular-nums;
}

thead th {
  height: 48px;
  padding: 8px 10px;
  background: var(--navy);
  color: white;
  border-right: 1px solid rgba(255, 255, 255, 0.08);
  font-size: 9px;
  font-weight: 820;
  letter-spacing: 0.85px;
  text-align: center;
  text-transform: uppercase;
  white-space: nowrap;
}

thead th:first-child {
  border-top-left-radius: 12px;
  text-align: left;
}

thead th:last-child {
  border-top-right-radius: 12px;
  border-right: none;
}

tbody tr {
  height: calc((100% - 48px) / 9);
  transition: background 0.2s ease;
}

tbody tr:nth-child(even) {
  background: #f7fafc;
}

tbody tr.current-row {
  background: #eaf4fa;
  box-shadow: inset 4px 0 0 var(--blue);
}

tbody td {
  padding: 7px 10px;
  border-right: 1px solid var(--line);
  border-bottom: 1px solid var(--line);
  color: var(--text);
  font-size: clamp(10px, 0.75vw, 13px);
  font-weight: 680;
  text-align: center;
  vertical-align: middle;
}

tbody td:first-child {
  text-align: left;
  font-weight: 800;
  color: var(--navy);
}

tbody td:last-child {
  border-right: none;
}

.expected-cell {
  color: var(--blue);
  font-weight: 820;
}

.value-pill {
  min-width: 54px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 7px 10px;
  border-radius: 10px;
  background: white;
  border: 1px solid var(--line);
  color: var(--navy);
  font-weight: 840;
  box-shadow: 0 2px 5px rgba(13, 43, 62, 0.035);
}

.value-pill.best {
  border-color: #a9e0c8;
  background: var(--green-soft);
  color: #0c8956;
}

.row-total {
  color: var(--navy);
  font-size: clamp(15px, 1.1vw, 21px);
  font-weight: 850;
}

.compliance-cell {
  min-width: 112px;
}

.compliance-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 6px;
}

.compliance-value {
  color: var(--text);
  font-weight: 840;
}

.mini-status {
  width: 8px;
  height: 8px;
  border-radius: 999px;
  background: var(--red);
}

.mini-status.ok {
  background: var(--green);
}

.mini-status.warning {
  background: var(--yellow);
}

.mini-progress {
  width: 100%;
  height: 5px;
  overflow: hidden;
  border-radius: 999px;
  background: #e5ecef;
}

.mini-progress > span {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: var(--red);
}

.mini-progress > span.ok {
  background: var(--green);
}

.mini-progress > span.warning {
  background: var(--yellow);
}

.table-total {
  display: grid;
  margin: 0 14px 14px;
  min-height: 50px;
  overflow: hidden;
  border-radius: 0 0 12px 12px;
  background: var(--navy);
  color: white;
}

.table-total > div {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 10px;
  border-right: 1px solid rgba(255, 255, 255, 0.08);
  font-size: clamp(12px, 0.9vw, 16px);
  font-weight: 820;
  font-variant-numeric: tabular-nums;
}

.table-total > div:first-child {
  justify-content: flex-start;
  text-transform: uppercase;
  letter-spacing: 1px;
  font-size: 10px;
}

.table-total > div:last-child {
  border-right: 0;
  background: var(--navy-2);
}

.total-compliance {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.total-compliance::before {
  content: "";
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--green);
  box-shadow: 0 0 0 4px rgba(24, 166, 106, 0.15);
}

@media (max-height: 850px) {
  .dashboard {
    padding: 12px;
    grid-template-rows: 62px 1fr;
    gap: 10px;
  }

  .topbar {
    padding: 0 15px;
  }

  .brand-mark {
    width: 38px;
    height: 38px;
  }

  .brand-name {
    font-size: 20px;
  }

  .left-column,
  .right-column,
  .content {
    gap: 9px;
  }

  .panel-heading {
    padding: 11px 14px 8px;
  }

  .kpi-panel {
    padding-bottom: 10px;
  }

  .kpi-grid {
    padding: 0 10px;
    gap: 7px;
  }

  .kpi-card {
    min-height: 78px;
    padding: 10px;
  }

  .kpi-value {
    margin-top: 6px;
  }

  .kpi-meta {
    margin-top: 6px;
  }

  .left-column {
    grid-template-rows: 158px minmax(210px, 1.1fr) minmax(145px, 0.9fr) 108px;
  }

  .summary-panel,
  .history-panel {
    padding: 8px;
  }

  .summary-panel {
    gap: 6px;
    grid-template-rows: minmax(62px, 1fr) auto;
  }

  .summary-main {
    min-height: 62px;
    padding: 9px 11px;
  }

  .summary-block {
    min-height: 48px;
    padding: 6px 5px;
  }

  .summary-value {
    margin-top: 3px;
    font-size: clamp(17px, 1.35vw, 24px);
  }

  .summary-detail {
    margin-top: 3px;
    font-size: 8px;
  }

  .summary-block.date-block .summary-value {
    font-size: clamp(14px, 1.05vw, 18px);
  }

  .capacity-ring {
    width: 52px;
    height: 52px;
  }

  .capacity-ring::before {
    width: 38px;
    height: 38px;
    font-size: 10px;
  }

  .status-list,
  .line-list {
    padding: 0 10px 10px;
    gap: 5px;
  }

  .status-item {
    min-height: 36px;
    padding: 6px 9px;
  }

  .line-panel {
    padding-bottom: 10px;
  }

  .line-card {
    min-height: 44px;
    padding: 6px 9px;
  }

  .history-title {
    margin-bottom: 6px;
  }

  .history-grid {
    gap: 6px;
  }

  .history-card {
    padding: 5px;
  }

  .history-value {
    margin-top: 3px;
    font-size: clamp(19px, 1.65vw, 28px);
  }

  .history-bar {
    margin-top: 5px;
  }

  thead th {
    height: 40px;
    padding: 6px 8px;
  }

  tbody tr {
    height: calc((100% - 40px) / 9);
  }

  tbody td {
    padding: 5px 8px;
  }

  .value-pill {
    padding: 5px 8px;
  }

  .table-total {
    min-height: 42px;
    margin-bottom: 10px;
  }
}

@media (max-height: 780px) {
  .dashboard {
    padding: 10px;
    grid-template-rows: 58px 1fr;
    gap: 8px;
  }

  .topbar {
    padding: 0 12px;
  }

  .left-column,
  .right-column,
  .content {
    gap: 7px;
  }

  .left-column {
    grid-template-rows: 146px minmax(188px, 1.12fr) minmax(126px, 0.88fr) 92px;
  }

  .panel-heading {
    padding: 8px 11px 6px;
  }

  .panel-kicker {
    margin-top: 2px;
    font-size: 8px;
  }

  .summary-panel {
    padding: 7px;
    gap: 5px;
    grid-template-rows: minmax(57px, 1fr) auto;
  }

  .summary-main {
    min-height: 57px;
  }

  .summary-block {
    min-height: 43px;
  }

  .summary-label {
    font-size: 7px;
    letter-spacing: 0.65px;
  }

  .summary-value {
    font-size: 17px;
  }

  .summary-block.date-block .summary-value {
    font-size: 13px;
  }

  .summary-detail {
    font-size: 7px;
  }

  .capacity-ring {
    width: 46px;
    height: 46px;
  }

  .capacity-ring::before {
    width: 34px;
    height: 34px;
  }

  .status-list,
  .line-list {
    padding: 0 8px 8px;
    gap: 4px;
  }

  .status-item {
    min-height: 31px;
    padding: 4px 8px;
  }

  .status-time {
    margin-top: 2px;
  }

  .line-card {
    min-height: 38px;
    padding: 4px 8px;
  }

  .line-state {
    margin-top: 3px;
  }

  .history-panel {
    padding: 7px;
  }

  .history-title {
    margin-bottom: 4px;
    font-size: 11px;
  }

  .history-card {
    padding: 4px;
  }

  .history-value {
    font-size: 18px;
  }

  .history-bar {
    margin-top: 3px;
  }
}
</style>
