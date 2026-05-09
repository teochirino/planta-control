<!-- resources/js/Pages/Permissions/Index.vue -->
<template>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h1 class="text-2xl font-bold mb-6">Administración de Permisos</h1>
                    
                    <!-- Selector de usuario -->
                    <div class="mb-8 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Seleccionar Usuario</label>
                        <select v-model="selectedUserId" @change="loadUserPermissions" class="w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">-- Seleccione un usuario --</option>
                            <option v-for="user in usuarios" :key="user.id" :value="user.id">
                                {{ user.name }} - {{ user.profile?.title || 'Sin perfil' }} ({{ user.email }})
                            </option>
                        </select>
                        <div v-if="usuarios.length === 0" class="text-sm text-gray-500 mt-2">
                            No hay usuarios cargados. Verifica la conexión con la API.
                        </div>
                    </div>
                    
                    <!-- Contenido de permisos -->
                    <div v-if="selectedUser" class="space-y-8">
                        <!-- Información del usuario -->
                        <div class="p-4 bg-blue-50 rounded-lg">
                            <h3 class="font-semibold text-blue-800">Usuario seleccionado</h3>
                            <p><strong>Nombre:</strong> {{ selectedUser.name }}</p>
                            <p><strong>Email:</strong> {{ selectedUser.email }}</p>
                            <p><strong>Perfil:</strong> {{ selectedUser.profile?.title }}</p>
                            <p class="text-sm text-gray-600 mt-2">
                                <span v-if="selectedUser.id_profile <= 2" class="text-green-600">
                                    ✅ Este usuario tiene ACCESO TOTAL por su perfil
                                </span>
                                <span v-else class="text-orange-600">
                                    ⚠️ Este usuario necesita asignación de permisos
                                </span>
                            </p>
                        </div>
                        
                        <!-- Permisos de Centros de Trabajo -->
                        <div class="border rounded-lg overflow-hidden">
                            <div class="bg-gray-100 px-4 py-3 font-semibold">
                                🏭 Permisos - Centros de Trabajo
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Centro de Trabajo</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ver</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Editar</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Eliminar</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <tr v-for="wc in workCenters" :key="wc.id">
                                            <td class="px-4 py-3 text-sm">{{ wc.name }}</td>
                                            <td class="px-4 py-3 text-center">
                                                <input type="checkbox" v-model="workCenterPerms[wc.id].can_view" 
                                                       class="rounded border-gray-300 text-blue-600">
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <input type="checkbox" v-model="workCenterPerms[wc.id].can_edit" 
                                                       class="rounded border-gray-300 text-blue-600">
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <input type="checkbox" v-model="workCenterPerms[wc.id].can_delete" 
                                                       class="rounded border-gray-300 text-blue-600">
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="px-4 py-3 bg-gray-50 flex justify-end">
                                <button @click="saveWorkCenterPermissions" :disabled="saving" 
                                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                                    {{ saving ? 'Guardando...' : 'Guardar Permisos de Centros' }}
                                </button>
                            </div>
                        </div>
                        
                        <!-- Permisos de Líneas de Producción -->
                        <div class="border rounded-lg overflow-hidden">
                            <div class="bg-gray-100 px-4 py-3 font-semibold">
                                📋 Permisos - Líneas de Producción
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Línea</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Centro</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ver</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Editar</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Eliminar</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <tr v-for="line in productionLines" :key="line.id">
                                            <td class="px-4 py-3 text-sm">{{ line.title }}</td>
                                            <td class="px-4 py-3 text-sm text-center">{{ line.work_center?.name }}</td>
                                            <td class="px-4 py-3 text-center">
                                                <input type="checkbox" v-model="productionLinePerms[line.id].can_view" 
                                                       class="rounded border-gray-300 text-blue-600">
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <input type="checkbox" v-model="productionLinePerms[line.id].can_edit" 
                                                       class="rounded border-gray-300 text-blue-600">
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <input type="checkbox" v-model="productionLinePerms[line.id].can_delete" 
                                                       class="rounded border-gray-300 text-blue-600">
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="px-4 py-3 bg-gray-50 flex justify-end">
                                <button @click="saveProductionLinePermissions" :disabled="saving" 
                                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                                    {{ saving ? 'Guardando...' : 'Guardar Permisos de Líneas' }}
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mensaje cuando no hay usuario seleccionado -->
                    <div v-else class="text-center py-12 text-gray-500">
                        Seleccione un usuario para gestionar sus permisos
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
const usuarios = ref([])  // Cambiado de 'users' a 'usuarios' para evitar confusión
const workCenters = ref([])
const productionLines = ref([])
const selectedUserId = ref('')
const selectedUser = ref(null)
const workCenterPerms = ref({})
const productionLinePerms = ref({})
const saving = ref(false)

// Cargar listas iniciales
const loadInitialData = async () => {
    try {
        console.log('Cargando datos iniciales...')
        const response = await axios.get('/api/permissions/data')
        
        usuarios.value = response.data.users || []
        workCenters.value = response.data.workCenters || []
        productionLines.value = response.data.productionLines || []
        
        console.log('Usuarios cargados:', usuarios.value.length)
        console.log('WorkCenters cargados:', workCenters.value.length)
        console.log('ProductionLines cargados:', productionLines.value.length)
        
        // Inicializar estructuras de permisos
        workCenters.value.forEach(wc => {
            workCenterPerms.value[wc.id] = {
                work_center_id: wc.id,
                can_view: false,
                can_edit: false,
                can_delete: false
            }
        })
        
        productionLines.value.forEach(line => {
            productionLinePerms.value[line.id] = {
                production_line_id: line.id,
                can_view: false,
                can_edit: false,
                can_delete: false
            }
        })
        
    } catch (error) {
        console.error('Error loading data:', error)
        alert('Error al cargar los datos: ' + (error.response?.data?.message || error.message))
    }
}

// Cargar permisos de un usuario
const loadUserPermissions = async () => {
    if (!selectedUserId.value) {
        selectedUser.value = null
        return
    }
    
    try {
        console.log('Cargando permisos para usuario:', selectedUserId.value)
        const response = await axios.get(`/api/permissions/user/${selectedUserId.value}`)
        selectedUser.value = response.data.user
        
        // Resetear permisos
        workCenters.value.forEach(wc => {
            workCenterPerms.value[wc.id] = {
                work_center_id: wc.id,
                can_view: false,
                can_edit: false,
                can_delete: false
            }
        })
        
        productionLines.value.forEach(line => {
            productionLinePerms.value[line.id] = {
                production_line_id: line.id,
                can_view: false,
                can_edit: false,
                can_delete: false
            }
        })
        
        // Cargar permisos existentes
        Object.values(response.data.work_centers).forEach(perm => {
            if (workCenterPerms.value[perm.work_center_id]) {
                workCenterPerms.value[perm.work_center_id] = perm
            }
        })
        
        Object.values(response.data.production_lines).forEach(perm => {
            if (productionLinePerms.value[perm.production_line_id]) {
                productionLinePerms.value[perm.production_line_id] = perm
            }
        })
        
        console.log('Permisos cargados correctamente')
        
    } catch (error) {
        console.error('Error loading user permissions:', error)
        alert('Error al cargar los permisos del usuario')
    }
}

// Guardar permisos de centros de trabajo
const saveWorkCenterPermissions = async () => {
    if (!selectedUserId.value) return
    
    saving.value = true
    try {
        const permissions = Object.values(workCenterPerms.value)
        await axios.post(`/api/permissions/work-centers/${selectedUserId.value}`, {
            permissions: permissions
        })
        alert('Permisos de centros de trabajo guardados correctamente')
    } catch (error) {
        console.error('Error saving:', error)
        alert('Error al guardar los permisos')
    } finally {
        saving.value = false
    }
}

// Guardar permisos de líneas de producción
const saveProductionLinePermissions = async () => {
    if (!selectedUserId.value) return
    
    saving.value = true
    try {
        const permissions = Object.values(productionLinePerms.value)
        await axios.post(`/api/permissions/production-lines/${selectedUserId.value}`, {
            permissions: permissions
        })
        alert('Permisos de líneas de producción guardados correctamente')
    } catch (error) {
        console.error('Error saving:', error)
        alert('Error al guardar los permisos')
    } finally {
        saving.value = false
    }
}

// Inicializar al montar el componente
onMounted(() => {
    loadInitialData()
})
</script>