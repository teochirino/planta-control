<template>
    <div class="min-h-screen bg-gray-900 flex">
        <IngenieroProcesosSidebar />
        
        <div class="flex-1 p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-white">Productos</h1>
                <Link :href="route('ingeniero-procesos.products.create')" 
                      class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                    + Nuevo Producto
                </Link>
            </div>
            
            <div class="mb-6">
                <input 
                    v-model="search"
                    type="text" 
                    placeholder="Buscar por modelo..."
                    class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                >
            </div>
            
            <div v-if="Object.keys(products).length === 0" class="text-center py-8">
                <p class="text-gray-400">No hay productos registrados</p>
            </div>
            
            <div v-else class="space-y-6">
                <div v-for="(productGroup, modelo) in products" :key="modelo" 
                     class="bg-gray-800 rounded-lg overflow-hidden">
                    <div class="bg-gray-700 px-6 py-4 flex justify-between items-center">
                        <h2 class="text-xl font-bold text-white">{{ modelo }}</h2>
                        <div class="space-x-2">
                            <Link :href="route('ingeniero-procesos.products.edit', modelo)" 
                                  class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded transition">
                                Editar
                            </Link>
                            <button @click="confirmDelete(modelo)" 
                                    class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded transition">
                                Eliminar
                            </button>
                        </div>
                    </div>
                    
                    <table class="w-full">
                        <thead class="bg-gray-750">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    Centro de Trabajo
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    Fase
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    Tiempo
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    Piezas
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <tr v-for="product in productGroup" :key="product.id" class="hover:bg-gray-750">
                                <td class="px-6 py-4 whitespace-nowrap text-white">
                                    {{ product.workCenter?.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-white">
                                    {{ product.workCenter?.phase }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-white">
                                    {{ product.tiempo }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-white">
                                    {{ product.piezas }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    products: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

watch(search, (value) => {
    router.get(route('ingeniero-procesos.products.index'), { search: value }, {
        preserveState: true,
        replace: true,
    });
});

function confirmDelete(modelo) {
    if (confirm(`¿Estás seguro de que deseas eliminar el producto ${modelo}?`)) {
        router.delete(route('ingeniero-procesos.products.destroy', modelo));
    }
}
</script>
