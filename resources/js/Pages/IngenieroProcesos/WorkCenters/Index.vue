<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <IngenieroProcesosSidebar />
        
        <div class="p-6 ml-16">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold" style="color: #0b2a40;">Centros de Trabajo</h1>
                <Link :href="route('ingeniero-procesos.work-centers.create')"
                      class="px-4 py-2 rounded-lg transition font-semibold text-sm"
                      style="background: #0b2a40; color: #fff;">
                    + Nuevo Centro
                </Link>
            </div>

            <!-- Buscador -->
            <div class="mb-6">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Buscar por nombre o fase..."
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
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Fase</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Capacidad Instalada</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Líneas de Producción</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Máquinas</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="center in workCenters.data" :key="center.id" style="border-bottom: 1px solid #e8eff4;">
                            <td class="px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ center.name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ center.phase }}</td>
                            <td class="px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ center.installed_capacity }}</td>
                            <td class="px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ center.production_lines?.length || 0 }}</td>
                            <td class="px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ center.machines?.length || 0 }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <Link :href="route('ingeniero-procesos.work-centers.edit', center.id)" 
                                          class="px-3 py-1.5 bg-[#174060] text-white border border-[#174060] rounded text-xs font-bold hover:opacity-85">
                                        ✏️ Editar
                                    </Link>
                                    <button @click="confirmDelete(center)" 
                                            class="px-3 py-1.5 bg-[#f4f7fa] text-[#ba2418] border border-[#d4dee8] rounded text-xs font-bold hover:bg-[#ba2418] hover:text-white hover:border-[#ba2418]">
                                        🗑️ Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <div v-if="workCenters.data.length === 0" class="text-center py-8">
                    <p style="color: #6a8090; font-weight: 600;">No hay centros de trabajo registrados</p>
                </div>
            </div>
            
            <!-- Paginación -->
            <div v-if="workCenterLinks" class="mt-6 flex justify-center gap-2">
                <template v-for="(link, key) in workCenterLinks" :key="key">
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
    workCenters: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
let searchTimeout = null;

const workCenterLinks = computed(() => {
    return props.workCenters?.links || null;
});

const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('ingeniero-procesos.work-centers.index'), {
            search: search.value
        }, {
            preserveState: true,
            replace: true
        });
    }, 300);
};

const confirmDelete = (center) => {
    if (confirm(`¿Está seguro de eliminar el centro de trabajo "${center.name}"?`)) {
        router.delete(route('ingeniero-procesos.work-centers.destroy', center.id));
    }
};
</script>
