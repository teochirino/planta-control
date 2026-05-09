<template>
    <div class="app" :class="{ 'tv-mode': tvMode }">
        <!-- Top Bar -->
        <div class="topbar card">
            <div class="topbar-left">
                <div class="logo">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                        <rect width="32" height="32" rx="6" fill="#0b2a40"/>
                        <path d="M8 16L12 12L16 16L20 12L24 16" stroke="white" stroke-width="2" fill="none"/>
                        <circle cx="16" cy="16" r="3" fill="white"/>
                    </svg>
                </div>
                <div>
                    <div class="eyebrow">ANDON INDUSTRIAL</div>
                    <h1>Panel de Control</h1>
                    <div class="topbar-sub">Planta General</div>
                </div>
            </div>
            <div class="topbar-right">
                <div class="chip">{{ currentDateTime }}</div>
                <div class="chip live">LIVE</div>
                <button @click="tvMode = !tvMode" class="chip" :class="{ live: tvMode }">
                    {{ tvMode ? '📺 MODO TV' : '🖥️ NORMAL' }}
                </button>
            </div>
        </div>

        <!-- KPIs Row -->
        <div class="principal-top">
            <div class="kpi-box" :class="kpis.oee?.color">
                <div class="lbl">OEE GLOBAL</div>
                <div class="val">{{ kpis.oee?.value || 0 }}%</div>
            </div>
            <div class="kpi-box" :class="kpis.produccion?.color">
                <div class="lbl">PRODUCCIÓN</div>
                <div class="val">{{ formatNumber(kpis.produccion?.value) }}</div>
                <div class="lbl">/ {{ formatNumber(kpis.produccion?.total) }}</div>
            </div>
            <div class="kpi-box" :class="kpis.eficiencia?.color">
                <div class="lbl">EFICIENCIA</div>
                <div class="val">{{ kpis.eficiencia?.value || 0 }}%</div>
            </div>
            <div class="kpi-box" :class="kpis.disponibilidad?.color">
                <div class="lbl">DISPONIBILIDAD</div>
                <div class="val">{{ kpis.disponibilidad?.value || 0 }}%</div>
            </div>
            <div class="kpi-box" :class="kpis.calidad?.color">
                <div class="lbl">CALIDAD</div>
                <div class="val">{{ kpis.calidad?.value || 98 }}%</div>
            </div>
            <div class="kpi-box" :class="kpis.performance?.color">
                <div class="lbl">PERFORMANCE</div>
                <div class="val">{{ kpis.performance?.value || 87 }}%</div>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="principal-body">
            <!-- Machines Grid -->
            <div class="machines-panel card">
                <div class="panel-header">
                    <h2>🏭 ESTADO DE MÁQUINAS</h2>
                    <div class="legend">
                        <span><span class="dot-green"></span> Operativo</span>
                        <span><span class="dot-yellow"></span> Mantenimiento</span>
                        <span><span class="dot-red"></span> Averiado</span>
                    </div>
                </div>
                <div v-if="cargandoMaquinas" class="loading">Cargando máquinas...</div>
                <div v-else class="status-board">
                    <div v-for="machine in machines" :key="machine.id" class="status-card" :class="machine.statusCard">
                        <div class="sc-header">
                            <div class="sc-title">
                                <span class="sc-name">{{ machine.title }}</span>
                                <span class="sc-line">{{ machine.linea }}</span>
                            </div>
                            <div class="traffic-light">
                                <div class="tl-light" :class="machine.trafficRed"></div>
                                <div class="tl-light" :class="machine.trafficYellow"></div>
                                <div class="tl-light" :class="machine.trafficGreen"></div>
                            </div>
                        </div>
                        <div class="sc-img-placeholder">
                            <span>🖨️ {{ machine.title }}</span>
                        </div>
                        <div class="sc-metrics">
                            <div class="sc-metric">
                                <div class="ml">PROD/HORA</div>
                                <div class="mv">{{ machine.prodHora }}</div>
                            </div>
                            <div class="sc-metric">
                                <div class="ml">EFICIENCIA</div>
                                <div class="mv">{{ machine.eficiencia }}%</div>
                            </div>
                            <div class="sc-metric">
                                <div class="ml">DISP.</div>
                                <div class="mv">{{ machine.disponibilidad }}%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel -->
            <div class="right-panel">
                <div class="card chart-card">
                    <h2>📈 PRODUCCIÓN POR HORA</h2>
                    <canvas ref="hourlyChart" style="height: 180px; width: 100%;"></canvas>
                </div>
                <div class="card resumen-card">
                    <h2>📋 RESUMEN DE TURNO</h2>
                    <table class="resumen-table">
                        <tr v-for="item in resumenTurno" :key="item.label">
                            <td>{{ item.label }}</td>
                            <td class="value">{{ item.value }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import axios from 'axios'
import Chart from 'chart.js/auto'

// Estado
const machines = ref([])
const hourlyChart = ref(null)
const cargandoMaquinas = ref(true)
const tvMode = ref(false)
let chartInstance = null
let refreshInterval = null

// KPIs
const kpis = ref({
    oee: { value: 78, color: 'warn' },
    produccion: { value: 1240, total: 1800, color: 'bad' },
    eficiencia: { value: 85, color: 'good' },
    disponibilidad: { value: 92, color: 'good' },
    calidad: { value: 98, color: 'good' },
    performance: { value: 87, color: 'warn' }
})

// Fecha/hora actual
const currentDateTime = computed(() => {
    const now = new Date()
    return now.toLocaleDateString('es-ES') + ' ' + now.toLocaleTimeString('es-ES')
})

// Resumen turno
const resumenTurno = ref([
    { label: 'Programado', value: '1,800 pz' },
    { label: 'Producido', value: '1,240 pz' },
    { label: 'Meta x Hora', value: '225 pz' },
    { label: 'Eficiencia Turno', value: '85%' }
])

// Formatear números
const formatNumber = (num) => {
    if (!num) return '0'
    return num.toLocaleString('es-ES')
}

// Cargar KPIs
const cargarKPIs = async () => {
    try {
        const res = await axios.get('/api/dashboard-kpis')
        kpis.value = { ...kpis.value, ...res.data }
    } catch (error) {
        console.error('Error cargando KPIs:', error)
    }
}

// Cargar máquinas
const cargarMaquinas = async () => {
    cargandoMaquinas.value = true
    try {
        const res = await axios.get('/api/machines/status')
        machines.value = res.data.map(m => {
            let estado = m.estado
            let statusCard = 's-green'
            
            if (estado === 'operativo') statusCard = 's-green'
            else if (estado === 'mantenimiento') statusCard = 's-yellow'
            else statusCard = 's-red'
            
            return {
                ...m,
                statusCard: statusCard,
                trafficRed: { 'on-red': estado === 'averiado' },
                trafficYellow: { 'on-yellow': estado === 'mantenimiento' },
                trafficGreen: { 'on-green': estado === 'operativo' }
            }
        })
    } catch (error) {
        console.error('Error cargando máquinas:', error)
        // Datos de ejemplo
        machines.value = [
            { id: 1, title: 'Ensambladora CNC', linea: 'Línea A', estado: 'operativo', prodHora: 65, eficiencia: 92, disponibilidad: 98 },
            { id: 2, title: 'Selladora', linea: 'Línea A', estado: 'operativo', prodHora: 58, eficiencia: 85, disponibilidad: 95 },
            { id: 3, title: 'Etiquetadora', linea: 'Línea B', estado: 'mantenimiento', prodHora: 0, eficiencia: 0, disponibilidad: 45 },
            { id: 4, title: 'Empacadora', linea: 'Línea B', estado: 'operativo', prodHora: 72, eficiencia: 94, disponibilidad: 97 }
        ].map(m => {
            let estado = m.estado
            return {
                ...m,
                statusCard: estado === 'operativo' ? 's-green' : (estado === 'mantenimiento' ? 's-yellow' : 's-red'),
                trafficRed: { 'on-red': estado === 'averiado' },
                trafficYellow: { 'on-yellow': estado === 'mantenimiento' },
                trafficGreen: { 'on-green': estado === 'operativo' }
            }
        })
    } finally {
        cargandoMaquinas.value = false
    }
}

// Cargar gráfico
const cargarGrafico = async () => {
    try {
        const res = await axios.get('/api/hourly-production')
        
        if (chartInstance) chartInstance.destroy()
        
        const ctx = hourlyChart.value?.getContext('2d')
        if (!ctx) return
        
        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: res.data.labels || ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00'],
                datasets: [{
                    label: 'Piezas Producidas',
                    data: res.data.values || [55, 68, 62, 54, 52, 58, 59, 55],
                    borderColor: '#0b2a40',
                    backgroundColor: 'rgba(11, 42, 64, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true } }
            }
        })
    } catch (error) {
        console.error('Error cargando gráfico:', error)
        const ctx = hourlyChart.value?.getContext('2d')
        if (ctx && !chartInstance) {
            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00'],
                    datasets: [{ label: 'Piezas Producidas', data: [55, 68, 62, 54, 52, 58, 59, 55], borderColor: '#0b2a40', fill: true }]
                },
                options: { responsive: true, maintainAspectRatio: true }
            })
        }
    }
}

// Cargar todos los datos
const cargarDatos = async () => {
    await Promise.all([cargarKPIs(), cargarMaquinas(), cargarGrafico()])
}

// Auto-refresh
const iniciarAutoRefresh = () => {
    refreshInterval = setInterval(() => cargarDatos(), 30000)
}

onMounted(() => {
    cargarDatos()
    iniciarAutoRefresh()
})

onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval)
    if (chartInstance) chartInstance.destroy()
})
</script>

<style scoped>
:root {
    --bg: #eaf0f5;
    --panel: #ffffff;
    --soft: #f4f7fa;
    --border: #d4dee8;
    --navy: #0b2a40;
    --green: #0a7c3e;
    --amber: #a87000;
    --red: #ba2418;
}

.app {
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-height: 100vh;
    background: var(--bg);
    font-family: 'Segoe UI', system-ui, sans-serif;
}

.app.tv-mode {
    height: 100vh;
    overflow: hidden;
    padding: 8px;
    gap: 8px;
}

.card {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(11, 28, 40, 0.06);
}

/* Top Bar */
.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    flex-shrink: 0;
}

.topbar-left { display: flex; align-items: center; gap: 16px; }
.eyebrow { font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: #174060; }
.topbar h1 { font-size: clamp(20px, 2.2vw, 28px); font-weight: 800; color: var(--navy); margin: 2px 0; }
.topbar-sub { font-size: 13px; color: #4e6070; font-weight: 500; }
.topbar-right { display: flex; gap: 10px; flex-wrap: wrap; }

.chip { 
    padding: 6px 14px; 
    border-radius: 40px; 
    background: var(--soft); 
    border: 1px solid var(--border); 
    font-size: 12px; 
    font-weight: 600; 
    color: #4e6070; 
    cursor: pointer;
}
.chip.live { background: var(--navy); color: #fff; border-color: var(--navy); }

/* KPIs */
.principal-top {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 12px;
}

.kpi-box {
    padding: 14px 16px;
    border-radius: 16px;
    border: 1px solid var(--border);
    background: var(--panel);
}
.kpi-box .lbl { font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #6a8090; margin-bottom: 6px; }
.kpi-box .val { font-size: clamp(28px, 2.5vw, 42px); font-weight: 800; line-height: 1.1; color: var(--navy); }

.kpi-box.good { background: #e4f5ec; border-left: 4px solid var(--green); }
.kpi-box.warn { background: #fff6da; border-left: 4px solid var(--amber); }
.kpi-box.bad { background: #fce9e8; border-left: 4px solid var(--red); }
.kpi-box.good .val { color: var(--green); }
.kpi-box.warn .val { color: var(--amber); }
.kpi-box.bad .val { color: var(--red); }

/* Main Grid */
.principal-body {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 12px;
    min-height: 0;
    flex: 1;
}

/* Machines Panel */
.machines-panel {
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
    min-height: 0;
    overflow: hidden;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
    flex-shrink: 0;
}
.machines-panel h2 { font-size: 16px; font-weight: 800; color: var(--navy); margin: 0; }
.legend { display: flex; gap: 16px; font-size: 11px; }
.dot-green { display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: var(--green); margin-right: 6px; }
.dot-yellow { display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: var(--amber); margin-right: 6px; }
.dot-red { display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: var(--red); margin-right: 6px; }

.loading { text-align: center; padding: 40px; color: #6a8090; }

.status-board {
    flex: 1;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    overflow-y: auto;
    align-content: start;
    min-height: 0;
}

.status-card {
    border-radius: 14px;
    border: 1px solid var(--border);
    background: #fff;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.status-card.s-green { border-top: 3px solid var(--green); }
.status-card.s-yellow { border-top: 3px solid var(--amber); }
.status-card.s-red { border-top: 3px solid var(--red); }

.sc-header {
    display: flex;
    justify-content: space-between;
    padding: 10px 12px 6px;
    gap: 8px;
}
.sc-name { font-size: 14px; font-weight: 800; color: var(--navy); }
.sc-line { font-size: 9px; color: #6a8090; background: var(--soft); padding: 2px 6px; border-radius: 8px; display: inline-block; margin-top: 4px; }

.traffic-light { display: flex; gap: 5px; background: #1a1a2e; border-radius: 20px; padding: 5px 8px; }
.tl-light { width: 16px; height: 16px; border-radius: 50%; background: #3a3a4e; }
.tl-light.on-red { background: #ff4444; box-shadow: 0 0 10px #ff4444; }
.tl-light.on-yellow { background: #ffcc00; box-shadow: 0 0 10px #ffcc00; }
.tl-light.on-green { background: #00cc66; box-shadow: 0 0 10px #00cc66; }

.sc-img-placeholder {
    height: 65px;
    background: var(--soft);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #6a8090;
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
}

.sc-metrics {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    padding: 8px 0;
}
.sc-metric { text-align: center; border-right: 1px solid var(--border); }
.sc-metric:last-child { border-right: none; }
.sc-metric .ml { font-size: 8px; font-weight: 700; text-transform: uppercase; color: #6a8090; margin-bottom: 4px; }
.sc-metric .mv { font-size: 15px; font-weight: 800; color: var(--navy); }

/* Right Panel */
.right-panel { display: flex; flex-direction: column; gap: 12px; min-height: 0; }
.chart-card, .resumen-card { padding: 14px 16px; }
.chart-card h2, .resumen-card h2 { font-size: 13px; font-weight: 800; color: var(--navy); margin-bottom: 12px; }
.resumen-table { width: 100%; font-size: 13px; }
.resumen-table td { padding: 8px 0; border-bottom: 1px solid var(--border); }
.resumen-table td.value { text-align: right; font-weight: 800; color: var(--navy); }

/* Responsive */
@media (max-width: 1100px) {
    .principal-top { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 900px) {
    .principal-body { grid-template-columns: 1fr; }
    .principal-top { grid-template-columns: repeat(2, 1fr); }
}
</style>