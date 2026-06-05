<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <IngenieroProcesosSidebar />
        
        <div class="p-6 ml-16">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold" style="color: #0b2a40;">Programas de Fabricación</h1>
                <Link :href="route('ingeniero-procesos.create')" 
                      class="px-4 py-2 rounded-lg transition font-semibold text-sm"
                      style="background: #0b2a40; color: #fff;">
                    + Nuevo Programa
                </Link>
            </div>
            
            <div class="rounded-lg overflow-hidden" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                <table class="w-full">
                    <thead>
                        <tr style="background: #0b2a40;">
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Código</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Fecha Entrega</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Creado Por</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Fecha Creación</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="program in programs" :key="program.id" style="border-bottom: 1px solid #e8eff4;">
                            <td class="px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ program.codigo }}</td>
                            <td class="px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ formatDate(program.fecha_entrega) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ program.creator?.name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ formatDate(program.created_at) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <Link :href="route('ingeniero-procesos.show', program.id)" 
                                      class="font-semibold" style="color: #174060;">
                                    Ver
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <div v-if="programs.length === 0" class="text-center py-8">
                    <p style="color: #6a8090; font-weight: 600;">No hay programas registrados</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';

defineProps({
    programs: Array,
});

function formatDate(date) {
    return new Date(date).toLocaleDateString('es-MX');
}
</script>
