<template>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="text-2xl font-bold mb-4">Producción Diaria</h1>
                    
                    <!-- Selector de fecha -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Seleccionar Fecha</label>
                        <input type="date" v-model="fecha" @change="cargarDatos" 
                               class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 px-3 py-2 border">
                    </div>
                    
                    <!-- Indicador de carga -->
                    <div v-if="cargando" class="text-center py-8">
                        <div class="text-gray-500">Cargando datos...</div>
                    </div>
                    
                    <!-- Contenido principal -->
                    <div v-else>
                        <!-- Tarjetas de resumen -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <p class="text-sm text-blue-600 font-medium">Total Programado</p>
                                <p class="text-2xl font-bold text-blue-800">{{ formatNumber(stats.total_programado) }}</p>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <p class="text-sm text-green-600 font-medium">Total Producido</p>
                                <p class="text-2xl font-bold text-green-800">{{ formatNumber(stats.total_producido) }}</p>
                            </div>
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <p class="text-sm text-purple-600 font-medium">Eficiencia Global</p>
                                <p class="text-2xl font-bold text-purple-800">{{ stats.eficiencia_global || 0 }}%</p>
                            </div>
                        </div>
                        
                        <!-- Mensaje sin datos -->
                        <div v-if="produccion.length === 0" class="text-center py-8 bg-gray-50 rounded-lg">
                            <p class="text-gray-500">No hay datos de producción para esta fecha</p>
                            <p class="text-sm text-gray-400 mt-2">Selecciona otra fecha o genera un programa diario</p>
                        </div>
                        
                        <!-- Producción por línea -->
                        <div v-for="item in produccion" :key="item.id" class="mb-8 border rounded-lg overflow-hidden">
                            <div class="bg-gray-50 px-4 py-3 border-b">
                                <div class="flex justify-between items-center">
                                    <h3 class="text-lg font-semibold text-gray-800">
                                        {{ item.linea }} - Turno {{ item.turno }}
                                    </h3>
                                    <div class="flex items-center space-x-4">
                                        <span class="text-sm text-gray-600">
                                            Progreso: {{ item.producido }} / {{ item.programado }}
                                        </span>
                                        <span :class="item.eficiencia >= 80 ? 'text-green-600' : 'text-red-600'" class="font-bold">
                                            {{ item.eficiencia }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hora</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Producido</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Meta/Hora</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="hora in item.horas" :key="hora.id">
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                {{ hora.hora_inicio }} - {{ hora.hora_fin }}
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <input type="number" v-model.number="hora.producido" 
                                                       :min="0"
                                                       class="w-24 text-center rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 px-2 py-1 border">
                                            </td>
                                            <td class="px-4 py-3 text-center text-sm text-gray-500">
                                                {{ Math.round(item.programado / 8) }}
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <button @click="guardarHora(hora.id, hora.producido, item)" 
                                                        :disabled="guardando"
                                                        class="px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                                    {{ guardando ? 'Guardando...' : 'Guardar' }}
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Barra de progreso -->
                            <div class="bg-gray-50 px-4 py-3 border-t">
                                <div class="flex justify-between text-sm text-gray-600 mb-1">
                                    <span>Eficiencia del turno</span>
                                    <span>{{ item.eficiencia }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 rounded-full h-2 transition-all duration-500" 
                                         :style="{ width: item.eficiencia + '%' }"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

// Estado
const fecha = ref(new Date().toISOString().split('T')[0])
const produccion = ref([])
const stats = ref({
    total_programado: 0,
    total_producido: 0,
    eficiencia_global: 0
})
const cargando = ref(false)
const guardando = ref(false)

// Formatear números
const formatNumber = (num) => {
    if (num === null || num === undefined || isNaN(num)) return '0'
    return Number(num).toLocaleString('es-ES')
}

// Cargar datos
const cargarDatos = async () => {
    cargando.value = true
    try {
        // Cargar estadísticas
        const statsRes = await axios.get(`/api/produccion-stats/${fecha.value}`)
        stats.value = statsRes.data
        
        // Cargar producción
        const prodRes = await axios.get(`/api/produccion/${fecha.value}`)
        produccion.value = prodRes.data
        
        console.log('Datos cargados:', produccion.value.length, 'líneas')
    } catch (error) {
        console.error('Error al cargar datos:', error)
        if (error.response) {
            console.error('Respuesta del servidor:', error.response.data)
            alert(`Error ${error.response.status}: ${error.response.data.message || 'Error al cargar los datos'}`)
        } else if (error.request) {
            alert('Error de conexión. ¿Está el servidor funcionando?')
        } else {
            alert('Error: ' + error.message)
        }
    } finally {
        cargando.value = false
    }
}

// Guardar producción por hora
const guardarHora = async (scheduleId, producido, item) => {
    guardando.value = true
    try {
        const response = await axios.put(`/api/produccion-hora/${scheduleId}`, {
            producido: producido
        })
        
        if (response.data.success) {
            // Actualizar el item local
            item.producido = response.data.total_producido
            item.eficiencia = response.data.eficiencia
            
            // Recargar estadísticas
            const statsRes = await axios.get(`/api/produccion-stats/${fecha.value}`)
            stats.value = statsRes.data
            
            // Mostrar mensaje de éxito
            const mensaje = document.createElement('div')
            mensaje.textContent = '✓ Producción actualizada'
            mensaje.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50'
            document.body.appendChild(mensaje)
            setTimeout(() => mensaje.remove(), 2000)
        }
    } catch (error) {
        console.error('Error al guardar:', error)
        alert('Error al guardar la producción. Por favor intenta de nuevo.')
    } finally {
        guardando.value = false
    }
}

// Cargar datos al montar el componente
onMounted(() => {
    cargarDatos()
})
</script>