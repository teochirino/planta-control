<!-- resources/js/Pages/IngenieroProcesos/RecoveryIndex.vue -->
<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <IngenieroProcesosSidebar />
        
        <div class="p-6 ml-16">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Programas de Recuperación (Atrasos)</h1>
                <Link :href="route('ingeniero-procesos.recuperacion.create')" 
                      class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    Nuevo Programa de Recuperación
                </Link>
            </div>

            <!-- Tabla de programas -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Código
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Fecha Entrega
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Creado Por
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Fecha Creación
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="program in programs" :key="program.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-blue-600">{{ program.codigo }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ program.fecha_entrega_formatted }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ program.creator?.name || 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ program.created_at_formatted }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    <Link :href="route('ingeniero-procesos.recuperacion.show', program.id)" 
                                          class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition text-sm font-medium"
                                          title="Ver">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                        </svg>
                                        Ver
                                    </Link>
                                    <Link :href="route('ingeniero-procesos.recuperacion.edit', program.id)" 
                                          class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-100 text-indigo-700 rounded-md hover:bg-indigo-200 transition text-sm font-medium"
                                          title="Editar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                        </svg>
                                        Editar
                                    </Link>
                                    <button @click="confirmDelete(program)"
                                            :disabled="!program.can_delete"
                                            :title="program.can_delete ? 'Eliminar' : program.cannot_delete_reason"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md transition text-sm font-medium"
                                            :class="program.can_delete
                                                ? 'bg-red-100 text-red-700 hover:bg-red-200'
                                                : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="programs.length === 0">
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                No hay programas de recuperación registrados
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Diálogo de confirmación -->
        <ConfirmDialog
            :show="dialog.show"
            :title="dialog.title"
            :message="dialog.message"
            :confirm-text="dialog.confirmText"
            @confirm="handleDelete"
            @cancel="dialog.show = false"
        />
    </div>
</template>

<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useToast } from 'vue-toastification';
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

const page = usePage();
const toast = useToast();

const props = defineProps({
    programs: Array,
});

const dialog = ref({
    show: false,
    title: '',
    message: '',
    confirmText: 'Eliminar',
    programToDelete: null,
});

const confirmDelete = (program) => {
    if (!program.can_delete) return;

    dialog.value = {
        show: true,
        title: 'Eliminar Programa',
        message: `¿Estás seguro de que deseas eliminar el programa "${program.codigo}"? Esta acción no se puede deshacer.`,
        confirmText: 'Eliminar',
        programToDelete: program,
    };
};

const handleDelete = () => {
    if (!dialog.value.programToDelete) return;

    const program = dialog.value.programToDelete;
    dialog.value.show = false;

    router.delete(route('ingeniero-procesos.recuperacion.destroy', program.id), {
        onSuccess: () => {
            toast.success('Programa eliminado exitosamente');
        },
        onError: () => {
            toast.error('Error al eliminar el programa');
        },
    });
};

// Mostrar notificaciones de flash messages
watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
        toast.success(flash.success);
    }
    if (flash?.error) {
        toast.error(flash.error);
    }
}, { immediate: true });
</script>
