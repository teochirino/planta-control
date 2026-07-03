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
                                        @click="showEditDateDialog(program)"
                                        class="px-3 py-1.5 bg-[#f4f7fa] text-[#0a7c3e] border border-[#d4dee8] rounded text-xs font-bold hover:bg-[#0a7c3e] hover:text-white hover:border-[#0a7c3e]">
                                        📅 Editar Fecha
                                    </button>
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

        <!-- Modal para editar fecha de entrega -->
        <div v-if="editDateDialog.show" class="fixed inset-0 flex items-center justify-center z-50" style="background: rgba(0,0,0,0.6);">
            <div class="rounded-xl p-8 max-w-md w-full mx-4" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 8px 32px rgba(11,28,40,.2);">
                <h3 class="text-2xl font-bold mb-4" style="color: #0b2a40;">Editar Fecha de Entrega</h3>
                <p class="mb-4" style="color: #6a8090;">
                    Programa: <strong>{{ editDateDialog.program?.codigo }}</strong>
                </p>
                <p class="mb-4" style="color: #6a8090;">
                    Fecha actual: <strong>{{ editDateDialog.program?.fecha_entrega_formatted }}</strong>
                </p>
                <div class="mb-6">
                    <label class="block font-semibold mb-2" style="color: #4e6070;">Nueva Fecha de Entrega</label>
                    <input
                        v-model="editDateDialog.newDate"
                        type="date"
                        class="w-full px-4 py-2 rounded-lg focus:outline-none"
                        style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;"
                    >
                </div>
                <div class="flex gap-3 justify-end">
                    <button
                        @click="editDateDialog.show = false"
                        class="px-4 py-2 rounded-lg transition font-semibold text-sm"
                        style="background: #e8eff4; color: #0b2a40; border: 1px solid #d4dee8;"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="checkSaturdaysAndUpdate"
                        :disabled="editDateDialog.updating"
                        class="px-4 py-2 rounded-lg transition font-semibold text-sm disabled:opacity-50"
                        style="background: #0b2a40; color: #fff;"
                    >
                        {{ editDateDialog.updating ? 'Verificando...' : 'Continuar' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal de confirmación para sábados -->
        <div v-if="saturdayModal.show" class="fixed inset-0 flex items-center justify-center z-50 p-4" style="background: rgba(0,0,0,0.6); width: 100vw; height: 100vh; box-sizing: border-box;">
            <div class="rounded-xl flex flex-col" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 8px 32px rgba(11,28,40,.2); max-height: calc(100vh - 40px); width: 90%; max-width: 600px; box-sizing: border-box;">
                <!-- Header fijo -->
                <div class="p-6 flex-shrink-0" style="border-bottom: 1px solid #e8eff4;">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="flex items-center justify-center w-14 h-14 rounded-full flex-shrink-0" style="background: #fff9e6; border: 2px solid #ffd700;">
                            <svg class="w-7 h-7" style="color: #b8860b;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold" style="color: #0b2a40;">Confirmar Inclusión de Sábados</h3>
                            <p class="text-sm" style="color: #6a8090;">Se detectaron sábados en el cálculo de fases</p>
                        </div>
                    </div>
                </div>

                <!-- Body con scroll -->
                <div class="flex-1 overflow-y-auto p-6" style="box-sizing: border-box;">
                    <div class="rounded-lg p-4" style="background: #fff9e6; border: 1px solid #ffd700;">
                        <p class="mb-3 font-semibold text-base" style="color: #b8860b;">📅 Sábados detectados en el cálculo de fases:</p>
                        <div v-for="phase in saturdayModal.phases" :key="phase.fase" class="mb-3 p-3 rounded-lg" style="background: #fff; border: 1px solid #ffd700;">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold" style="background: #ffd700; color: #0b2a40;">{{ phase.fase }}</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-2 rounded" style="background: #f4f7fa;">
                                    <p class="text-xs font-semibold uppercase mb-1" style="color: #6a8090;">Sin incluir sábados</p>
                                    <p class="text-base font-bold" style="color: #0c1c28;">{{ phase.fecha_sin_sabado }}</p>
                                </div>
                                <div class="p-2 rounded" style="background: #e4f5ec;">
                                    <p class="text-xs font-semibold uppercase mb-1" style="color: #6a8090;">Incluyendo sábados</p>
                                    <p class="text-base font-bold" style="color: #0a7c3e;">{{ phase.fecha_con_sabado }}</p>
                                </div>
                            </div>
                            <div class="mt-2 p-2 rounded" style="background: #fce9e8; border: 1px solid #ebbab8;">
                                <p class="text-xs font-semibold" style="color: #ba2418;">
                                    🗓️ Sábados que se saltan: <strong>{{ phase.sabados_saltados.join(', ') }}</strong>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer fijo -->
                <div class="flex-shrink-0 p-6" style="border-top: 1px solid #e8eff4; box-sizing: border-box;">
                    <div class="mb-4 p-3 rounded-lg text-center" style="background: #f4f7fa; border: 1px solid #e8eff4;">
                        <p class="text-base font-semibold" style="color: #0b2a40;">
                            ¿Desea incluir los días sábado como días laborables en este programa de producción?
                        </p>
                    </div>

                    <div class="flex gap-3 justify-center flex-wrap">
                        <button
                            @click="saturdayModal.show = false"
                            class="px-4 py-2 rounded-lg transition font-semibold text-sm flex items-center gap-2"
                            style="background: #e8eff4; color: #0b2a40; border: 1px solid #d4dee8;"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Cancelar
                        </button>
                        <button
                            @click="updateDeliveryDate(false)"
                            :disabled="saturdayModal.updating"
                            class="px-4 py-2 rounded-lg transition font-semibold text-sm flex items-center gap-2 disabled:opacity-50"
                            style="background: #ba2418; color: #fff;"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                            </svg>
                            {{ saturdayModal.updating ? 'Actualizando...' : 'No Incluir Sábados' }}
                        </button>
                        <button
                            @click="updateDeliveryDate(true)"
                            :disabled="saturdayModal.updating"
                            class="px-4 py-2 rounded-lg transition font-semibold text-sm flex items-center gap-2 disabled:opacity-50"
                            style="background: #0a7c3e; color: #fff;"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            {{ saturdayModal.updating ? 'Actualizando...' : 'Incluir Sábados' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
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

const editDateDialog = ref({
    show: false,
    program: null,
    newDate: '',
    updating: false
});

const saturdayModal = ref({
    show: false,
    phases: [],
    updating: false,
    includeSaturdays: false
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

function showEditDateDialog(program) {
    editDateDialog.value = {
        show: true,
        program: program,
        newDate: program.fecha_entrega,
        updating: false
    };
}

function handleDelete() {
    if (!dialog.value.programToDelete) return;

    dialog.value.show = false;

    router.delete(route('ingeniero-procesos.destroy', dialog.value.programToDelete.id), {
        onError: (errors) => {
            toast.error('Error al eliminar el programa');
        },
    });
}

async function checkSaturdaysAndUpdate() {
    if (!editDateDialog.value.newDate) {
        toast.error('Por favor seleccione una fecha');
        return;
    }

    editDateDialog.value.updating = true;

    try {
        const response = await fetch(route('ingeniero-procesos.check-saturdays'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                fecha_entrega: editDateDialog.value.newDate
            })
        });

        console.log('Response status:', response.status);

        if (!response.ok) {
            const errorData = await response.json();
            console.error('Error response:', errorData);
            throw new Error(errorData.error || errorData.message || 'Error en la respuesta del servidor');
        }

        const data = await response.json();
        console.log('Success response:', data);

        editDateDialog.value.updating = false;
        editDateDialog.value.show = false;

        if (data.has_saturday_in_phases) {
            saturdayModal.value = {
                show: true,
                phases: data.phases_with_saturday,
                updating: false,
                includeSaturdays: false
            };
        } else {
            // No hay sábados, actualizar directamente
            updateDeliveryDate(false);
        }
    } catch (error) {
        editDateDialog.value.updating = false;
        console.error('Error al verificar sábados:', error);
        toast.error('Error al verificar sábados: ' + error.message);
    }
}

function updateDeliveryDate(includeSaturdays) {
    saturdayModal.value.updating = true;

    router.put(route('ingeniero-procesos.update-delivery-date', editDateDialog.value.program.id), {
        fecha_entrega: editDateDialog.value.newDate,
        include_saturdays: includeSaturdays
    }, {
        onSuccess: () => {
            saturdayModal.value.show = false;
            saturdayModal.value.updating = false;
            editDateDialog.value.show = false;
        },
        onError: (errors) => {
            saturdayModal.value.updating = false;
        }
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
