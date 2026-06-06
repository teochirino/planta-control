<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <IngenieroProcesosSidebar />
        
        <div class="p-6 ml-16">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold" style="color: #0b2a40;">Líneas de Producción</h1>
                <Link :href="route('ingeniero-procesos.production-lines.create')"
                      class="px-4 py-2 rounded-lg transition font-semibold text-sm"
                      style="background: #0b2a40; color: #fff;">
                    + Nueva Línea
                </Link>
            </div>

            <!-- Buscador -->
            <div class="mb-6">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Buscar por nombre de línea o centro de trabajo..."
                    class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-blue-500"
                    style="border-color: #d4dee8;"
                    @input="handleSearch"
                >
            </div>
            
            <div v-if="$page.props.flash.success" class="mb-4 p-4 rounded-lg" style="background: #d4edda; color: #155724;">
                {{ $page.props.flash.success }}
            </div>
            
            <div v-if="$page.props.flash.error" class="mb-4 p-4 rounded-lg" style="background: #f8d7da; color: #721c24;">
                {{ $page.props.flash.error }}
            </div>
            
            <div class="rounded-lg overflow-hidden" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                <table class="w-full">
                    <thead>
                        <tr style="background: #0b2a40;">
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Centro de Trabajo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Línea</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Capacidad Instalada</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Costo por Paro</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="line in productionLines.data" :key="line.id" style="border-bottom: 1px solid #e8eff4;">
                            <td class="px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">
                                {{ line.work_center?.name }} (Fase {{ line.work_center?.phase }})
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ line.title }}</td>
                            <td class="px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ line.installed_capacity }}</td>
                            <td class="px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">${{ line.cost }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <Link :href="route('ingeniero-procesos.production-lines.edit', line.id)" 
                                      class="font-semibold mr-3" style="color: #174060;">
                                    Editar
                                </Link>
                                <button @click="confirmDelete(line)" class="font-semibold" style="color: #dc3545;">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <div v-if="productionLines.data.length === 0" class="text-center py-8">
                    <p style="color: #6a8090; font-weight: 600;">No hay líneas de producción registradas</p>
                </div>
            </div>
            
            <!-- Paginación -->
            <div v-if="productionLinks" class="mt-6 flex justify-center gap-2">
                <template v-for="(link, key) in productionLinks" :key="key">
                    <Link 
                        v-if="link.url" 
                        :href="link.url" 
                        v-html="link.label"
                        class="px-4 py-2 rounded-lg transition text-sm"
                        :class="link.active ? 'font-bold' : ''"
                        :style="link.active ? 'background: #0b2a40; color: #fff;' : 'background: #fff; color: #0b2a40; border: 1px solid #d4dee8;'"
                    />
                    <span 
                        v-else 
                        v-html="link.label"
                        class="px-4 py-2 rounded-lg text-sm"
                        style="background: #e9ecef; color: #6c757d;"
                    />
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    productionLines: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
let searchTimeout = null;

const productionLinks = computed(() => {
    return props.productionLines?.links || null;
});

const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('ingeniero-procesos.production-lines.index'), {
            search: search.value
        }, {
            preserveState: true,
            replace: true
        });
    }, 300);
};

const confirmDelete = (line) => {
    if (confirm(`¿Está seguro de eliminar la línea de producción "${line.title}"?`)) {
        router.delete(route('ingeniero-procesos.production-lines.destroy', line.id));
    }
};
</script>
