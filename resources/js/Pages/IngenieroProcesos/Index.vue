<template>
    <div class="min-h-screen bg-gray-900 flex">
        <IngenieroProcesosSidebar />
        
        <div class="flex-1 p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-white">Programas de Fabricación</h1>
                <Link :href="route('ingeniero-procesos.create')" 
                      class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                    + Nuevo Programa
                </Link>
            </div>
            
            <div class="bg-gray-800 rounded-lg overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Código</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Fecha Entrega</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Creado Por</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Fecha Creación</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <tr v-for="program in programs" :key="program.id" class="hover:bg-gray-750">
                            <td class="px-6 py-4 whitespace-nowrap text-white">{{ program.codigo }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-white">{{ formatDate(program.fecha_entrega) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-white">{{ program.creator?.name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-white">{{ formatDate(program.created_at) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <Link :href="route('ingeniero-procesos.show', program.id)" 
                                      class="text-blue-400 hover:text-blue-300">
                                    Ver
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <div v-if="programs.length === 0" class="text-center py-8">
                    <p class="text-gray-400">No hay programas registrados</p>
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
