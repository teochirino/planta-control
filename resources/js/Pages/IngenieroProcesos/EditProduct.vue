<template>
    <div class="min-h-screen bg-gray-900 flex">
        <IngenieroProcesosSidebar />
        
        <div class="flex-1 p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-white">Editar Producto</h1>
                <Link :href="route('ingeniero-procesos.products.index')" 
                      class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                    Volver
                </Link>
            </div>
            
            <div class="bg-gray-800 rounded-lg p-6">
                <form @submit.prevent="submit">
                    <div class="mb-6">
                        <label class="block text-gray-300 text-sm font-bold mb-2">
                            Modelo
                        </label>
                        <input 
                            v-model="form.modelo" 
                            type="text" 
                            class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                            required
                        >
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-300 text-sm font-bold mb-2">
                            Centros de Trabajo
                        </label>
                        <div class="space-y-2">
                            <div v-for="workCenter in workCenters" :key="workCenter.id" 
                                 class="flex items-center space-x-4 p-3 bg-gray-700 rounded-lg">
                                <input 
                                    type="checkbox" 
                                    :id="'wc-' + workCenter.id"
                                    :value="workCenter.id"
                                    v-model="selectedWorkCenters"
                                    @change="toggleWorkCenter(workCenter.id)"
                                    class="w-5 h-5 rounded"
                                >
                                <label :for="'wc-' + workCenter.id" class="flex-1 text-white">
                                    {{ workCenter.name }} (Fase {{ workCenter.phase }})
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="form.work_centers.length > 0" class="mb-6">
                        <label class="block text-gray-300 text-sm font-bold mb-2">
                            Configuración por Centro de Trabajo
                        </label>
                        <div class="space-y-4">
                            <div v-for="wc in form.work_centers" :key="wc.id_work_center" 
                                 class="p-4 bg-gray-700 rounded-lg">
                                <h3 class="text-white font-bold mb-3">
                                    {{ getWorkCenterName(wc.id_work_center) }}
                                </h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-gray-300 text-sm mb-1">
                                            Tiempo
                                        </label>
                                        <input 
                                            v-model="wc.tiempo" 
                                            type="number" 
                                            step="0.00001"
                                            min="0"
                                            class="w-full px-3 py-2 bg-gray-600 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                            required
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-gray-300 text-sm mb-1">
                                            Piezas
                                        </label>
                                        <input 
                                            v-model="wc.piezas" 
                                            type="number" 
                                            min="1"
                                            class="w-full px-3 py-2 bg-gray-600 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                            required
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <Link :href="route('ingeniero-procesos.products.index')" 
                              class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                            Cancelar
                        </Link>
                        <button 
                            type="submit" 
                            :disabled="form.work_centers.length === 0"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                            Actualizar Producto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';
import { router } from '@inertiajs/vue3';
import { reactive, ref, onMounted } from 'vue';

const props = defineProps({
    modelo: String,
    products: Array,
    workCenters: Array,
});

const form = reactive({
    modelo: props.modelo,
    work_centers: [],
});

const selectedWorkCenters = ref([]);

onMounted(() => {
    // Inicializar el formulario con los datos existentes
    props.products.forEach(product => {
        form.work_centers.push({
            id_work_center: product.id_work_center,
            tiempo: product.tiempo,
            piezas: product.piezas,
        });
        selectedWorkCenters.value.push(product.id_work_center);
    });
});

function toggleWorkCenter(workCenterId) {
    const index = form.work_centers.findIndex(wc => wc.id_work_center === workCenterId);
    
    if (index === -1) {
        // Agregar centro de trabajo
        form.work_centers.push({
            id_work_center: workCenterId,
            tiempo: 0,
            piezas: 1,
        });
    } else {
        // Eliminar centro de trabajo
        form.work_centers.splice(index, 1);
    }
}

function getWorkCenterName(workCenterId) {
    const wc = props.workCenters.find(w => w.id === workCenterId);
    return wc ? `${wc.name} (Fase ${wc.phase})` : '';
}

function submit() {
    router.put(route('ingeniero-procesos.products.update', props.modelo), form);
}
</script>
