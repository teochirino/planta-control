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
                            <td class="px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ program.fecha_entrega_formatted }}</td>
                            <td class="px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ program.creator?.name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ program.created_at_formatted }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <Link :href="route('ingeniero-procesos.show', program.id)" 
                                          class="px-3 py-1.5 bg-[#174060] text-white border border-[#174060] rounded text-xs font-bold hover:opacity-85">
                                        👁️ Ver
                                    </Link>
                                    <button 
                                        @click="showDeleteDialog(program)"
                                        class="px-3 py-1.5 bg-[#f4f7fa] text-[#ba2418] border border-[#d4dee8] rounded text-xs font-bold hover:bg-[#ba2418] hover:text-white hover:border-[#ba2418]">
                                        🗑️ Borrar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <div v-if="programs.length === 0" class="text-center py-8">
                    <p style="color: #6a8090; font-weight: 600;">No hay programas registrados</p>
                </div>
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
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useToast } from 'vue-toastification';
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

const page = usePage();
const toast = useToast();

defineProps({
    programs: Array,
});

const dialog = ref({
    show: false,
    title: '',
    message: '',
    confirmText: 'Eliminar',
    programToDelete: null
});

function showDeleteDialog(program) {
    dialog.value = {
        show: true,
        title: 'Eliminar Programa',
        message: `¿Estás seguro de que deseas eliminar el programa "${program.codigo}"? Esta acción no se puede deshacer.`,
        confirmText: 'Eliminar',
        programToDelete: program
    };
}

function handleDelete() {
    if (!dialog.value.programToDelete) return;
    
    dialog.value.show = false;
    
    router.delete(route('ingeniero-procesos.destroy', dialog.value.programToDelete.id), {
        onSuccess: () => {
            toast.success('Programa eliminado exitosamente');
        },
        onError: (errors) => {
            toast.error('Error al eliminar el programa');
        },
    });
}

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
