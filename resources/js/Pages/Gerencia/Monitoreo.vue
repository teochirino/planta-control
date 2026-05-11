<template>
    <div class="min-h-screen bg-gray-100">
        <GerenciaSidebar />
        
        <div class="p-4 md:p-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-4">Monitoreo General de Producción</h1>
            
            <!-- Selector de fecha -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
                <input type="date" v-model="fecha" @change="cargarDatos" 
                       class="border border-gray-300 rounded-lg px-3 py-2">
            </div>
            
            <!-- Loading -->
            <div v-if="cargando" class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2 text-gray-500">Cargando datos...</p>
            </div>
            
            <!-- Centros de Trabajo -->
            <div v-else class="space-y-6">
                <div v-for="wc in workCenters" :key="wc.id" class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3 border-b">
                        <h2 class="text-lg font-bold text-gray-800">{{ wc.name }}</h2>
                    </div>
                    
                    <!-- KPIs -->
                    <div class="grid grid-cols-5 gap-4 p-4 border-b">
                        <div class="text-center">
                            <div class="text-xs text-gray-500">Programado</div>
                            <div class="text-xl font-bold">{{ formatNumber(wc.kpis.programmed) }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xs text-gray-500">Atraso</div>
                            <div class="text-xl font-bold text-red-600">{{ formatNumber(wc.kpis.backwardness) }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xs text-gray-500">Adelanto</div>
                            <div class="text-xl font-bold text-green-600">{{ formatNumber(wc.kpis.advanced) }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xs text-gray-500">Producido</div>
                            <div class="text-xl font-bold text-blue-600">{{ formatNumber(wc.kpis.produced) }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xs text-gray-500">Eficiencia</div>
                            <div class="text-xl font-bold" :class="getEfficiencyClass(wc.kpis.efficiency)">
                                {{ wc.kpis.efficiency }}%
                            </div>
                        </div>
                    </div>
                    
                    <!-- Gráfica de producción por línea -->
                    <div v-if="wc.has_data" class="p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Producción por Línea</h3>
                        <div class="bg-gray-50 p-4 rounded">
                            <canvas :id="'chart-' + wc.id" class="w-full" style="height: 300px;"></canvas>
                        </div>
                    </div>
                    
                    <div v-if="!wc.has_data" class="p-4 text-center bg-yellow-50 border-t">
                        <p class="text-sm text-yellow-700">⚠️ No hay información para la fecha seleccionada</p>
                    </div>
                </div>
                
                <div v-if="workCenters.length === 0" class="text-center py-8 text-gray-400">
                    No hay centros de trabajo registrados
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import axios from 'axios'
import { Chart, registerables } from 'chart.js'
import ChartDataLabels from 'chartjs-plugin-datalabels'
import GerenciaSidebar from '@/Components/GerenciaSidebar.vue'

Chart.register(...registerables, ChartDataLabels)

const fecha = ref(new Date().toISOString().split('T')[0])
const workCenters = ref([])
const cargando = ref(false)
const chartRefs = ref({})
const chartInstances = ref({})
let refreshInterval = null

const formatNumber = (num) => {
    if (!num && num !== 0) return '0'
    return num.toLocaleString('es-MX')
}

const getEfficiencyClass = (efficiency) => {
    if (efficiency >= 95) return 'text-green-600'
    if (efficiency >= 80) return 'text-yellow-600'
    return 'text-red-600'
}

const hasProductionLines = (wc) => {
    if (!wc.production_lines) return false
    try {
        const plainLines = JSON.parse(JSON.stringify(wc.production_lines))
        if (Array.isArray(plainLines)) {
            return plainLines.length > 0
        }
        return Object.keys(plainLines).length > 0
    } catch (e) {
        return false
    }
}

const destroyCharts = () => {
    Object.values(chartInstances.value).forEach(chart => {
        if (chart) chart.destroy()
    })
    chartInstances.value = {}
}

const createCharts = async () => {
    console.log('=== Iniciando createCharts ===')
    await nextTick()
    
    destroyCharts()
    
    workCenters.value.forEach(wc => {
        console.log(`\nCreando gráfica para: ${wc.name}`)
        console.log('  has_data:', wc.has_data)
        console.log('  production_lines:', wc.production_lines)
        console.log('  production_lines type:', typeof wc.production_lines)
        console.log('  production_lines is Array:', Array.isArray(wc.production_lines))
        
        // Convertir a array real - forzar deserialización del Proxy
        let lines = []
        try {
            // Convertir Proxy a objeto plano
            const plainLines = JSON.parse(JSON.stringify(wc.production_lines))
            if (Array.isArray(plainLines)) {
                lines = plainLines
            } else if (plainLines && typeof plainLines === 'object') {
                lines = Object.values(plainLines)
            }
        } catch (e) {
            console.error('  Error convirtiendo production_lines:', e)
        }
        
        console.log('  lines after conversion:', lines)
        console.log('  lines length:', lines.length)
        if (lines.length > 0) {
            console.log('  lines[0]:', lines[0])
            console.log('  lines[1]:', lines[1])
            console.log('  lines[2]:', lines[2])
        }
        
        if (!wc.has_data || !lines || lines.length === 0) {
            console.log(`  ❌ Saltando ${wc.name} - no hay datos o líneas`)
            return
        }
        
        const canvasId = 'chart-' + wc.id
        const canvas = document.getElementById(canvasId)
        
        console.log('  Buscando canvas con ID:', canvasId)
        console.log('  Canvas encontrado:', canvas)
        
        if (!canvas) {
            console.log(`  ❌ Canvas no encontrado para ${wc.name}`)
            return
        }
        
        const ctx = canvas.getContext('2d')
        
        const labels = lines.map(line => line.title)
        const data = lines.map(line => line.produced)
        
        console.log('  ✓ Labels:', labels)
        console.log('  ✓ Data:', data)
        
        try {
            chartInstances.value[wc.id] = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Piezas Producidas',
                        data: data,
                        backgroundColor: 'rgba(59, 130, 246, 0.8)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1,
                        datalabels: {
                            anchor: 'end',
                            align: 'end',
                            color: '#1f2937',
                            font: {
                                weight: 'bold',
                                size: 12
                            },
                            formatter: function(value) {
                                return value.toLocaleString('es-MX')
                            }
                        }
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Producido: ' + context.parsed.x.toLocaleString('es-MX')
                                }
                            }
                        },
                        datalabels: {
                            display: true,
                            anchor: 'end',
                            align: 'end',
                            color: '#1f2937',
                            font: {
                                weight: 'bold',
                                size: 12
                            },
                            formatter: function(value) {
                                return value.toLocaleString('es-MX')
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString('es-MX')
                                }
                            }
                        }
                    }
                }
            })
            
            console.log(`  ✅ Gráfica creada exitosamente para ${wc.name}`)
        } catch (error) {
            console.error(`  ❌ Error creando gráfica para ${wc.name}:`, error)
        }
    })
    
    console.log('=== Fin createCharts ===\n')
}

const cargarDatos = async () => {
    cargando.value = true
    try {
        const response = await axios.get('/gerencia/monitoreo-data', {
            params: { date: fecha.value }
        })
        console.log('Response data:', response.data)
        console.log('WorkCenters:', response.data.workCenters)
        workCenters.value = response.data.workCenters
        
        // Debug: mostrar datos de producción por línea
        workCenters.value.forEach(wc => {
            console.log(`Centro: ${wc.name}`)
            console.log('  production_lines:', wc.production_lines)
            wc.production_lines?.forEach(line => {
                console.log(`    Línea ${line.id} (${line.title}): ${line.produced} producidas`)
            })
        })
        
        cargando.value = false
        
        // Esperar a que el DOM se actualice completamente
        await nextTick()
        setTimeout(() => {
            console.log('Llamando a createCharts después del timeout')
            createCharts()
        }, 100)
    } catch (error) {
        console.error('Error:', error)
        cargando.value = false
    }
}

onMounted(() => {
    cargarDatos()
    
    refreshInterval = setInterval(() => {
        cargarDatos()
    }, 300000) // 5 minutos
})

onUnmounted(() => {
    destroyCharts()
    if (refreshInterval) {
        clearInterval(refreshInterval)
    }
})
</script>