<template>
    <div class="min-h-screen bg-gray-100">
        <GerenciaSidebar />
        
        <div class="p-4 md:p-8 ml-16 pt-16">
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
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="wc in workCenters" :key="wc.id" class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                    <div class="bg-gray-50 px-3 py-2 border-b">
                        <h2 class="text-base font-bold text-gray-800">{{ wc.name }}</h2>
                    </div>
                    
                    <!-- KPIs -->
                    <div class="grid grid-cols-4 gap-2 p-3 border-b">
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 text-center">
                            <div class="text-[10px] text-gray-500">Programado</div>
                            <div class="text-sm font-bold">{{ formatNumber(wc.kpis.programmed) }}</div>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 text-center">
                            <div class="text-[10px] text-gray-500">Atraso</div>
                            <div class="text-sm font-bold text-red-600">{{ formatNumber(wc.kpis.backwardness) }}</div>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 text-center">
                            <div class="text-[10px] text-gray-500">Adelanto</div>
                            <div class="text-sm font-bold text-green-600">{{ formatNumber(wc.kpis.advanced) }}</div>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 text-center">
                            <div class="text-[10px] text-gray-500">Producido</div>
                            <div class="text-sm font-bold text-blue-600">{{ formatNumber(wc.kpis.produced) }}</div>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 text-center">
                            <div class="text-[10px] text-gray-500">Eficiencia</div>
                            <div class="text-sm font-bold" :class="getEfficiencyClass(wc.kpis.efficiency)">
                                {{ wc.kpis.efficiency }}%
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 text-center">
                            <div class="text-[10px] text-gray-500">OEE</div>
                            <div class="text-sm font-bold" :class="getOEEClass(wc.kpis.oee)">
                                {{ wc.kpis.oee }}%
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 text-center">
                            <div class="text-[10px] text-gray-500">Total</div>
                            <div class="text-sm font-bold text-purple-600">{{ formatNumber(wc.kpis.total_to_produce) }}</div>
                        </div>
                    </div>
                    
                    <!-- Gráfica de producción por hora (líneas) -->
                    <div v-if="wc.has_data && wc.hourly_data.labels.length > 0" class="p-3">
                        <h3 class="text-[11px] font-semibold text-gray-700 mb-2">Producción por Hora</h3>
                        <div class="bg-gray-50 p-2 rounded">
                            <canvas :id="'line-chart-' + wc.id" class="w-full" style="height: 180px;"></canvas>
                        </div>
                    </div>
                    
                    <!-- Gráfica de producción por línea -->
                    <div v-if="wc.has_data" class="p-3">
                        <h3 class="text-[11px] font-semibold text-gray-700 mb-2">Producción por Línea</h3>
                        <div class="bg-gray-50 p-2 rounded">
                            <canvas :id="'chart-' + wc.id" class="w-full" style="height: 180px;"></canvas>
                        </div>
                    </div>
                    
                    <div v-if="!wc.has_data" class="p-3 text-center bg-yellow-50 border-t">
                        <p class="text-[11px] text-yellow-700">⚠️ No hay información para la fecha seleccionada</p>
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
const lineChartInstances = ref({})
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

const getOEEClass = (oee) => {
    if (oee >= 85) return 'text-green-600'
    if (oee >= 60) return 'text-yellow-600'
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
    
    Object.values(lineChartInstances.value).forEach(chart => {
        if (chart) chart.destroy()
    })
    lineChartInstances.value = {}
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
                                size: 10
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
                                size: 10
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

const createLineCharts = async () => {
    console.log('=== Iniciando createLineCharts ===')
    await nextTick()
    
    workCenters.value.forEach(wc => {
        console.log(`\nCreando gráfica de líneas para: ${wc.name}`)
        console.log('  hourly_data:', wc.hourly_data)
        
        if (!wc.has_data || !wc.hourly_data || wc.hourly_data.labels.length === 0) {
            console.log(`  ❌ Saltando ${wc.name} - no hay datos por hora`)
            return
        }
        
        const canvasId = 'line-chart-' + wc.id
        const canvas = document.getElementById(canvasId)
        
        console.log('  Buscando canvas con ID:', canvasId)
        console.log('  Canvas encontrado:', canvas)
        
        if (!canvas) {
            console.log(`  ❌ Canvas no encontrado para ${wc.name}`)
            return
        }
        
        const ctx = canvas.getContext('2d')
        
        const labels = wc.hourly_data.labels
        const expectedData = wc.hourly_data.expected
        const producedData = wc.hourly_data.produced
        
        console.log('  ✓ Labels:', labels)
        console.log('  ✓ Expected:', expectedData)
        console.log('  ✓ Produced:', producedData)
        
        try {
            lineChartInstances.value[wc.id] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Total a producir (esperado)',
                            data: expectedData,
                            borderColor: 'rgba(147, 51, 234, 1)',
                            backgroundColor: 'rgba(147, 51, 234, 0.1)',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            tension: 0.1,
                            fill: false,
                            pointRadius: 2,
                            pointBackgroundColor: 'rgba(147, 51, 234, 1)'
                        },
                        {
                            label: 'Producido (real)',
                            data: producedData,
                            borderColor: 'rgba(59, 130, 246, 1)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 2,
                            tension: 0.1,
                            fill: false,
                            pointRadius: 2,
                            pointBackgroundColor: 'rgba(59, 130, 246, 1)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 10,
                                font: {
                                    size: 10
                                }
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y.toLocaleString('es-MX')
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Hora',
                                font: {
                                    size: 10
                                }
                            },
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45,
                                font: {
                                    size: 9
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Piezas',
                                font: {
                                    size: 10
                                }
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString('es-MX')
                                },
                                font: {
                                    size: 9
                                }
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            })
            
            console.log(`  ✅ Gráfica de líneas creada exitosamente para ${wc.name}`)
        } catch (error) {
            console.error(`  ❌ Error creando gráfica de líneas para ${wc.name}:`, error)
        }
    })
    
    console.log('=== Fin createLineCharts ===\n')
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
            createLineCharts()
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