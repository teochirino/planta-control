<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <SupervisorSidebar />
        
        <div class="p-6 ml-16">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-[#0b2a40]">Registrar Ajustes de Producción</h1>
            </div>

            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-6">
                <!-- Selección de Programa, Centro y Fase -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-[#4e6070]">Programa *</label>
                        <select v-model="selectedProgramId" @change="onProgramChange"
                                class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none bg-white text-[#0c1c28] border border-[#d4dee8]">
                            <option value="">Seleccione un programa</option>
                            <option v-for="program in programs" :key="program.id" :value="program.id">
                                {{ program.codigo }} - {{ formatDate(program.fecha_entrega) }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-[#4e6070]">Centro de Trabajo *</label>
                        <select v-model="selectedWorkCenterId" @change="loadDailyPrograms"
                                class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none bg-white text-[#0c1c28] border border-[#d4dee8]">
                            <option value="">Seleccione un centro</option>
                            <option v-for="center in workCenters" :key="center.id" :value="center.id">
                                {{ center.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-[#4e6070]">Fase *</label>
                        <select v-model="selectedPhase" @change="loadDailyPrograms"
                                :disabled="!selectedProgramId"
                                class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none disabled:opacity-50 bg-white text-[#0c1c28] border border-[#d4dee8]">
                            <option value="">Seleccione una fase</option>
                            <option v-if="selectedProgram" value="fecha_fase1">Fase 1 - {{ selectedProgram.fecha_fase1_formatted || '' }}</option>
                            <option v-if="selectedProgram" value="fecha_fase2">Fase 2 - {{ selectedProgram.fecha_fase2_formatted || '' }}</option>
                            <option v-if="selectedProgram" value="fecha_fase3">Fase 3 - {{ selectedProgram.fecha_fase3_formatted || '' }}</option>
                            <option v-if="selectedProgram" value="fecha_fase4">Fase 4 - {{ selectedProgram.fecha_fase4_formatted || '' }}</option>
                        </select>
                    </div>
                </div>

                <!-- Lista de Daily Programs del programa y centro seleccionados -->
                <div v-if="dailyPrograms.length > 0" class="mb-6">
                    <h2 class="text-xl font-semibold mb-4 text-[#0b2a40]">Programas Diarios - {{ selectedPhase ? getPhaseLabel(selectedPhase) : '' }}</h2>
                    <div class="space-y-4">
                        <div v-for="dailyProgram in dailyPrograms" :key="dailyProgram.id" 
                             class="rounded p-4 bg-[#f4f7fa] border border-[#e8eff4]">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="font-semibold text-[#0b2a40]">
                                    Turno {{ getShiftLabel(dailyProgram.shift) }}
                                </h3>
                                <button @click="editDailyProgram(dailyProgram)"
                                        class="px-3 py-1 rounded text-sm font-semibold bg-[#0b2a40] text-white">
                                    Editar
                                </button>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                                <div>
                                    <span class="text-[#6a8090]">Programado:</span>
                                    <span class="ml-2 text-[#0c1c28] font-semibold">{{ dailyProgram.programmed }}</span>
                                </div>
                                <div>
                                    <span class="text-[#6a8090]">Atrasos:</span>
                                    <span class="ml-2 text-[#a87000] font-semibold">{{ dailyProgram.backwardness }}</span>
                                </div>
                                <div>
                                    <span class="text-[#6a8090]">Adelantos:</span>
                                    <span class="ml-2 text-[#0a7c3e] font-semibold">{{ dailyProgram.advanced }}</span>
                                </div>
                                <div>
                                    <span class="text-[#6a8090]">Total Fabricado:</span>
                                    <span class="ml-2 text-[#0c1c28] font-semibold">{{ dailyProgram.total_produced || 0 }}</span>
                                </div>
                                <div>
                                    <span class="text-[#6a8090]">Total Rechazado:</span>
                                    <span class="ml-2 text-[#ba2418] font-semibold">{{ dailyProgram.total_rejected || 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else-if="selectedProgramId && selectedWorkCenterId && selectedPhase" class="text-center py-8">
                    <p class="text-[#6a8090] font-semibold">No hay programas diarios para la fecha y centro seleccionados</p>
                </div>

                <!-- Modal de Edición -->
                <div v-if="editingProgram" class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-50">
                    <div class="rounded-lg p-6 w-full max-w-2xl mx-4 bg-white border border-[#d4dee8] shadow-2xl">
                        <h2 class="text-xl font-semibold mb-4 text-[#0b2a40]">Editar Programa Diario</h2>
                        
                        <div class="mb-4">
                            <p class="text-sm font-semibold text-[#6a8090]">
                                {{ formatDate(editingProgram.date) }} - {{ editingProgram.shift }} - {{ editingProgram.work_center?.name }}
                            </p>
                        </div>

                        <form @submit.prevent="saveAdjustment">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-semibold mb-2 text-[#4e6070]">Programado</label>
                                    <input type="number" v-model.number="editForm.programmed" min="0"
                                           class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none bg-white text-[#0c1c28] border border-[#d4dee8]">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-2 text-[#4e6070]">Atrasos</label>
                                    <input type="number" v-model.number="editForm.backwardness" min="0"
                                           class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none bg-white text-[#0c1c28] border border-[#d4dee8]">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-2 text-[#4e6070]">Adelantos</label>
                                    <input type="number" v-model.number="editForm.advanced" min="0"
                                           class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none bg-white text-[#0c1c28] border border-[#d4dee8]">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-semibold mb-2 text-[#4e6070]">Total Fabricado</label>
                                    <input type="number" v-model.number="editForm.total_produced" min="0"
                                           class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none bg-white text-[#0c1c28] border border-[#d4dee8]">
                                    <p class="text-xs mt-1 text-[#6a8090]">Valor actual: {{ editingProgram.total_produced || 0 }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-2 text-[#4e6070]">Total Rechazado</label>
                                    <input type="number" v-model.number="editForm.total_rejected" min="0"
                                           class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none bg-white text-[#0c1c28] border border-[#d4dee8]">
                                    <p class="text-xs mt-1 text-[#6a8090]">Valor actual: {{ editingProgram.total_rejected || 0 }}</p>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-semibold mb-2 text-[#4e6070]">Motivo del ajuste *</label>
                                <textarea v-model="editForm.reason" rows="3" required
                                          class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none bg-white text-[#0c1c28] border border-[#d4dee8]"
                                          placeholder="Explique el motivo del ajuste..."></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-semibold mb-2 text-[#4e6070]">Notas adicionales</label>
                                <textarea v-model="editForm.notes" rows="2"
                                          class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none bg-white text-[#0c1c28] border border-[#d4dee8]"
                                          placeholder="Información adicional opcional..."></textarea>
                            </div>

                            <div class="flex space-x-4">
                                <button type="submit" 
                                        :disabled="editForm.processing"
                                        class="px-6 py-2 rounded-lg transition font-semibold text-sm disabled:opacity-50 bg-[#0a7c3e] text-white">
                                    {{ editForm.processing ? 'Guardando...' : 'Guardar Cambios' }}
                                </button>
                                <button type="button" @click="closeEdit"
                                        class="px-6 py-2 rounded-lg transition font-semibold text-sm bg-white text-[#0b2a40] border border-[#d4dee8]">
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3';
import SupervisorSidebar from '@/Components/SupervisorSidebar.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    programs: Array,
    workCenters: Array,
});

const selectedProgramId = ref('');
const selectedWorkCenterId = ref('');
const selectedPhase = ref('');
const dailyPrograms = ref([]);
const editingProgram = ref(null);

const selectedProgram = computed(() => {
    return props.programs.find(p => p.id === selectedProgramId.value);
});

function onProgramChange() {
    selectedPhase.value = '';
    selectedWorkCenterId.value = '';
    dailyPrograms.value = [];
}

const editForm = useForm({
    programmed: 0,
    backwardness: 0,
    advanced: 0,
    total_produced: 0,
    total_rejected: 0,
    reason: '',
    notes: '',
});

function loadDailyPrograms() {
    if (!selectedProgramId.value || !selectedWorkCenterId.value || !selectedPhase.value) {
        dailyPrograms.value = [];
        return;
    }

    const phaseDate = selectedProgram.value ? selectedProgram.value[selectedPhase.value] : null;

    if (!phaseDate) {
        dailyPrograms.value = [];
        return;
    }

    router.get(route('supervisor.register-adjustments.load'), {
        program_id: selectedProgramId.value,
        work_center_id: selectedWorkCenterId.value,
        phase_date: phaseDate,
    }, {
        preserveState: true,
        onSuccess: (page) => {
            dailyPrograms.value = page.props.dailyPrograms || [];
        },
    });
}

function editDailyProgram(dailyProgram) {
    editingProgram.value = dailyProgram;
    editForm.programmed = dailyProgram.programmed;
    editForm.backwardness = dailyProgram.backwardness;
    editForm.advanced = dailyProgram.advanced;
    editForm.total_produced = dailyProgram.total_produced || 0;
    editForm.total_rejected = dailyProgram.total_rejected || 0;
    editForm.reason = '';
    editForm.notes = '';
}

function closeEdit() {
    editingProgram.value = null;
    editForm.reset();
}

function saveAdjustment() {
    editForm.put(route('supervisor.daily-programs.update', editingProgram.value.id), {
        onSuccess: () => {
            closeEdit();
            loadDailyPrograms();
        },
    });
}

function formatDate(date) {
    if (!date) return '';
    return new Date(date).toLocaleDateString('es-MX');
}

function getPhaseLabel(phase) {
    switch (phase) {
        case 'fecha_fase1': return 'Fase 1';
        case 'fecha_fase2': return 'Fase 2';
        case 'fecha_fase3': return 'Fase 3';
        case 'fecha_fase4': return 'Fase 4';
        default: return phase;
    }
}

function getShiftLabel(shift) {
    switch (shift) {
        case 'morning': return 'Mañana';
        case 'afternoon': return 'Tarde';
        case 'night': return 'Noche';
        case 'matutino': return 'Matutino';
        case 'vespertino': return 'Vespertino';
        case 'nocturno': return 'Nocturno';
        default: return shift;
    }
}
</script>
