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
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[800px]">
                        <thead>
                            <tr style="background: #0b2a40;">
                                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Código</th>
                                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Fecha Entrega</th>
                                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Creado Por</th>
                                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Fecha Creación</th>
                                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="program in programs" :key="program.id" style="border-bottom: 1px solid #e8eff4;">
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ program.codigo }}</td>
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ program.fecha_entrega_formatted }}</td>
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ program.creator?.name }}</td>
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ program.created_at_formatted }}</td>
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <Link :href="route('ingeniero-procesos.show', program.id)"
                                              title="Ver"
                                              class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#174060] text-white rounded-md text-xs font-bold shadow-sm hover:bg-[#0f2c47] transition-colors whitespace-nowrap">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                            </svg>
                                            Ver
                                        </Link>
                                        <button
                                            @click="showEditDateDialog(program)"
                                            title="Editar Fecha"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#e6f4ec] text-[#0a7c3e] border border-[#aadcc4] rounded-md text-xs font-bold shadow-sm hover:bg-[#0a7c3e] hover:text-white hover:border-[#0a7c3e] transition-colors whitespace-nowrap">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <rect x="3" y="4" width="14" height="13" rx="2" />
                                                <line x1="3" y1="8" x2="17" y2="8" />
                                                <line x1="7" y1="2" x2="7" y2="5" stroke-linecap="round" />
                                                <line x1="13" y1="2" x2="13" y2="5" stroke-linecap="round" />
                                            </svg>
                                            Editar Fecha
                                        </button>
                                        <button
                                            @click="showDeleteDialog(program)"
                                            title="Borrar"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#fbe6e6] text-[#ba2418] border border-[#ebbab8] rounded-md text-xs font-bold shadow-sm hover:bg-[#ba2418] hover:text-white hover:border-[#ba2418] transition-colors whitespace-nowrap">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            Borrar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
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
        <div v-if="editDateDialog.show" class="fixed inset-0 flex items-center justify-center z-50 p-2 sm:p-4" style="background: rgba(0,0,0,0.6);">
            <div class="rounded-xl p-4 sm:p-6 md:p-8 w-full max-w-md mx-2 sm:mx-4" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 8px 32px rgba(11,28,40,.2);">
                <h3 class="text-xl sm:text-2xl font-bold mb-3 sm:mb-4" style="color: #0b2a40;">Editar Fecha de Entrega</h3>
                <p class="mb-3 sm:mb-4 text-sm sm:text-base" style="color: #6a8090;">
                    Programa: <strong>{{ editDateDialog.program?.codigo }}</strong>
                </p>
                <p class="mb-3 sm:mb-4 text-sm sm:text-base" style="color: #6a8090;">
                    Fecha actual: <strong>{{ editDateDialog.program?.fecha_entrega_formatted }}</strong>
                </p>
                <div class="mb-4 sm:mb-6">
                    <label class="block font-semibold mb-2 text-sm sm:text-base" style="color: #4e6070;">Nueva Fecha de Entrega</label>
                    <input
                        v-model="editDateDialog.newDate"
                        type="date"
                        class="w-full px-3 sm:px-4 py-2 rounded-lg focus:outline-none text-sm sm:text-base"
                        style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;"
                    >
                </div>
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 justify-end">
                    <button
                        @click="editDateDialog.show = false"
                        class="px-3 sm:px-4 py-2 rounded-lg transition font-semibold text-xs sm:text-sm"
                        style="background: #e8eff4; color: #0b2a40; border: 1px solid #d4dee8;"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="checkSaturdaysAndUpdate"
                        :disabled="editDateDialog.updating"
                        class="px-3 sm:px-4 py-2 rounded-lg transition font-semibold text-xs sm:text-sm disabled:opacity-50"
                        style="background: #0b2a40; color: #fff;"
                    >
                        {{ editDateDialog.updating ? 'Verificando...' : 'Continuar' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal de confirmación para sábados -->
        <div v-if="saturdayModal.show" class="fixed inset-0 flex items-center justify-center z-50 p-2 sm:p-4" style="background: rgba(0,0,0,0.6);">
            <div class="rounded-xl flex flex-col w-full max-w-full sm:max-w-lg md:max-w-2xl mx-2 sm:mx-4 max-h-[calc(100vh-16px)] sm:max-h-[calc(100vh-32px)]" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 8px 32px rgba(11,28,40,.2);">
                <!-- Header fijo -->
                <div class="p-4 sm:p-6 flex-shrink-0" style="border-bottom: 1px solid #e8eff4;">
                    <div class="flex items-center gap-3 sm:gap-4 mb-2 sm:mb-4">
                        <div class="flex items-center justify-center w-12 h-12 sm:w-14 sm:h-14 rounded-full flex-shrink-0" style="background: #fff9e6; border: 2px solid #ffd700;">
                            <svg class="w-6 h-6 sm:w-7 sm:h-7" style="color: #b8860b;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-lg sm:text-xl font-bold" style="color: #0b2a40;">Confirmar Inclusión de Sábados</h3>
                            <p class="text-xs sm:text-sm" style="color: #6a8090;">Se detectaron sábados en el cálculo de fases</p>
                        </div>
                    </div>
                </div>

                <!-- Body con scroll -->
                <div class="flex-1 overflow-y-auto p-3 sm:p-6">
                    <div class="rounded-lg p-3 sm:p-4" style="background: #fff9e6; border: 1px solid #ffd700;">
                        <p class="mb-2 sm:mb-3 font-semibold text-sm sm:text-base" style="color: #b8860b;">📅 Sábados detectados en el cálculo de fases:</p>
                        <div v-for="phase in saturdayModal.phases" :key="phase.fase" class="mb-2 sm:mb-3 p-2 sm:p-3 rounded-lg" style="background: #fff; border: 1px solid #ffd700;">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold whitespace-nowrap" style="background: #ffd700; color: #0b2a40;">{{ phase.fase }}</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">
                                <div class="p-2 rounded" style="background: #f4f7fa;">
                                    <p class="text-xs font-semibold uppercase mb-1" style="color: #6a8090;">Sin incluir sábados</p>
                                    <p class="text-sm sm:text-base font-bold break-words" style="color: #0c1c28;">{{ phase.fecha_sin_sabado }}</p>
                                </div>
                                <div class="p-2 rounded" style="background: #e4f5ec;">
                                    <p class="text-xs font-semibold uppercase mb-1" style="color: #6a8090;">Incluyendo sábados</p>
                                    <p class="text-sm sm:text-base font-bold break-words" style="color: #0a7c3e;">{{ phase.fecha_con_sabado }}</p>
                                </div>
                            </div>
                            <div class="mt-2 p-2 rounded" style="background: #fce9e8; border: 1px solid #ebbab8;">
                                <p class="text-xs font-semibold break-words" style="color: #ba2418;">
                                    🗓️ Sábados que se saltan: <strong>{{ phase.sabados_saltados.join(', ') }}</strong>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer fijo -->
                <div class="flex-shrink-0 p-3 sm:p-6" style="border-top: 1px solid #e8eff4;">
                    <div class="mb-3 sm:mb-4 p-2 sm:p-3 rounded-lg text-center" style="background: #f4f7fa; border: 1px solid #e8eff4;">
                        <p class="text-sm sm:text-base font-semibold" style="color: #0b2a40;">
                            ¿Desea incluir los días sábado como días laborables en este programa de producción?
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 justify-center">
                        <button
                            @click="saturdayModal.show = false"
                            class="px-3 sm:px-4 py-2 rounded-lg transition font-semibold text-xs sm:text-sm flex items-center justify-center gap-2 w-full sm:w-auto"
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
                            class="px-3 sm:px-4 py-2 rounded-lg transition font-semibold text-xs sm:text-sm flex items-center justify-center gap-2 disabled:opacity-50 w-full sm:w-auto"
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
                            class="px-3 sm:px-4 py-2 rounded-lg transition font-semibold text-xs sm:text-sm flex items-center justify-center gap-2 disabled:opacity-50 w-full sm:w-auto"
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
