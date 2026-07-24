<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <IngenieroProcesosSidebar />
        
        <div class="p-6 ml-16">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold" style="color: #0b2a40;">Productos</h1>
                <Link :href="route('ingeniero-procesos.products.create')" 
                      class="px-4 py-2 rounded-lg transition font-semibold text-sm"
                      style="background: #0b2a40; color: #fff;">
                    + Nuevo Producto
                </Link>
            </div>
            
            <div class="mb-6">
                <input 
                    v-model="search"
                    type="text" 
                    placeholder="Buscar por modelo..."
                    class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none"
                    style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;"
                >
            </div>
            
            <div v-if="Object.keys(products).length === 0" class="text-center py-8">
                <p style="color: #6a8090; font-weight: 600;">No hay productos registrados</p>
            </div>
            
            <div v-else class="space-y-6">
                <div v-for="(productGroup, modelo) in products" :key="modelo" 
                     class="rounded-lg overflow-hidden" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                    <div class="px-6 py-4 flex justify-between items-center" style="background: #174060;">
                        <h2 class="text-xl font-bold" style="color: #fff;">{{ modelo }}</h2>
                        <div class="space-x-2">
                            <Link :href="route('ingeniero-procesos.products.edit', { modelo: modelo })"
                                  class="px-3 py-1 rounded transition font-semibold text-sm"
                                  style="background: #0b2a40; color: #fff;">
                                Editar
                            </Link>
                            <button @click="confirmDelete(modelo)" 
                                    class="px-3 py-1 rounded transition font-semibold text-sm"
                                    style="background: #fce9e8; color: #ba2418; border: 1px solid #ebbab8;">
                                Eliminar
                            </button>
                        </div>
                    </div>
                    
                    <table class="w-full">
                        <thead>
                            <tr style="background: #f4f7fa;">
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #4e6070;">
                                    Centro de Trabajo
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #4e6070;">
                                    Fase
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #4e6070;">
                                    Tiempo
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #4e6070;">
                                    Piezas
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="product in productGroup" :key="product.id" style="border-bottom: 1px solid #e8eff4;">
                                <td class="px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">
                                    {{ product.workCenter?.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">
                                    {{ product.workCenter?.phase }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">
                                    {{ product.tiempo }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">
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
        router.delete(route('ingeniero-procesos.products.destroy'), {
            data: { modelo: modelo }
        });
    }
}
</script>
