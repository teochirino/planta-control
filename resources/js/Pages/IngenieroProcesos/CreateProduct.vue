<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <IngenieroProcesosSidebar />
        
        <div class="p-6 ml-16">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold" style="color: #0b2a40;">Nuevo Producto</h1>
                <Link :href="route('ingeniero-procesos.products.index')" 
                      class="px-4 py-2 rounded-lg transition font-semibold text-sm"
                      style="background: #fff; color: #0b2a40; border: 1px solid #d4dee8;">
                    Volver
                </Link>
            </div>
            
            <div class="rounded-lg p-6" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                <form @submit.prevent="submit">
                    <div class="mb-6">
                        <label class="block text-sm font-semibold mb-2" style="color: #4e6070; letter-spacing: 0.1em; text-transform: uppercase;">
                            Modelo
                        </label>
                        <input 
                            v-model="form.modelo" 
                            type="text" 
                            class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none"
                            style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;"
                            required
                        >
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-semibold mb-3" style="color: #4e6070; letter-spacing: 0.1em; text-transform: uppercase;">
                            Centros de Trabajo
                        </label>
                        <div class="space-y-2">
                            <div v-for="workCenter in workCenters" :key="workCenter.id" 
                                 class="flex items-center space-x-4 p-3 rounded-lg" style="background: #f4f7fa; border: 1px solid #e8eff4;">
                                <input 
                                    type="checkbox" 
                                    :id="'wc-' + workCenter.id"
                                    :value="workCenter.id"
                                    v-model="selectedWorkCenters"
                                    @change="toggleWorkCenter(workCenter.id)"
                                    class="w-5 h-5 rounded"
                                    style="color: #0b2a40; border-color: #d4dee8;"
                                >
                                <label :for="'wc-' + workCenter.id" class="flex-1 font-semibold" style="color: #0c1c28;">
                                    {{ workCenter.name }} (Fase {{ workCenter.phase }})
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="form.work_centers.length > 0" class="mb-6">
                        <label class="block text-sm font-semibold mb-2" style="color: #4e6070; letter-spacing: 0.1em; text-transform: uppercase;">
                            Configuración por Centro de Trabajo
                        </label>
                        <div class="space-y-4">
                            <div v-for="wc in form.work_centers" :key="wc.id_work_center" 
                                 class="p-4 rounded-lg" style="background: #f4f7fa; border: 1px solid #e8eff4;">
                                <h3 class="font-bold mb-3" style="color: #0b2a40;">
                                    {{ getWorkCenterName(wc.id_work_center) }}
                                </h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold mb-1" style="color: #4e6070;">
                                            Tiempo
                                        </label>
                                        <input 
                                            v-model="wc.tiempo" 
                                            type="number" 
                                            step="0.00001"
                                            min="0"
                                            class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none"
                                            style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;"
                                            required
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold mb-1" style="color: #4e6070;">
                                            Piezas
                                        </label>
                                        <input 
                                            v-model="wc.piezas" 
                                            type="number" 
                                            min="0"
                                            class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none"
                                            style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;"
                                            required
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <Link :href="route('ingeniero-procesos.products.index')" 
                              class="px-4 py-2 rounded-lg transition font-semibold text-sm"
                              style="background: #fff; color: #0b2a40; border: 1px solid #d4dee8;">
                            Cancelar
                        </Link>
                        <button 
                            type="submit" 
                            :disabled="form.work_centers.length === 0"
                            class="px-4 py-2 rounded-lg transition font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                            style="background: #0a7c3e; color: #fff;">
                            Guardar Producto
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
import { reactive, ref } from 'vue';

const props = defineProps({
    workCenters: Array,
});

const form = reactive({
    modelo: '',
    work_centers: [],
});

const selectedWorkCenters = ref([]);

function toggleWorkCenter(workCenterId) {
    const index = form.work_centers.findIndex(wc => wc.id_work_center === workCenterId);
    
    if (index === -1) {
        // Agregar centro de trabajo
        form.work_centers.push({
            id_work_center: workCenterId,
            tiempo: 0,
            piezas: 0,
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
    router.post(route('ingeniero-procesos.products.store'), form);
}
</script>
