<template>
    <div class="min-h-screen bg-gray-900 flex">
        <IngenieroProcesosSidebar />
        
        <div class="flex-1 p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-white">Registrar Ajustes de Producción</h1>
            </div>

            <div class="bg-gray-800 rounded-lg p-6">
                <!-- Selección de Programa, Centro y Fase -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label class="block text-gray-300 text-sm mb-2">Programa *</label>
                        <select v-model="selectedProgramId" @change="onProgramChange"
                                class="w-full bg-gray-700 text-white rounded px-3 py-2">
                            <option value="">Seleccione un programa</option>
                            <option v-for="program in programs" :key="program.id" :value="program.id">
                                {{ program.codigo }} - {{ formatDate(program.fecha_entrega) }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-300 text-sm mb-2">Centro de Trabajo *</label>
                        <select v-model="selectedWorkCenterId" @change="loadDailyPrograms"
                                class="w-full bg-gray-700 text-white rounded px-3 py-2">
                            <option value="">Seleccione un centro</option>
                            <option v-for="center in workCenters" :key="center.id" :value="center.id">
                                {{ center.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-300 text-sm mb-2">Fase *</label>
                        <select v-model="selectedPhase" @change="loadDailyPrograms"
                                :disabled="!selectedProgramId"
                                class="w-full bg-gray-700 text-white rounded px-3 py-2 disabled:opacity-50">
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
                    <h2 class="text-xl font-semibold text-white mb-4">Programas Diarios - {{ selectedPhase ? getPhaseLabel(selectedPhase) : '' }}</h2>
                    <div class="space-y-4">
                        <div v-for="dailyProgram in dailyPrograms" :key="dailyProgram.id" 
                             class="bg-gray-700 rounded p-4">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="text-white font-semibold">
                                    Turno {{ getShiftLabel(dailyProgram.shift) }}
                                </h3>
                                <button @click="editDailyProgram(dailyProgram)"
                                        class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm">
                                    Editar
                                </button>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-400">Programado:</span>
                                    <span class="text-white ml-2">{{ dailyProgram.programmed }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-400">Atrasos:</span>
                                    <span class="text-yellow-400 ml-2">{{ dailyProgram.backwardness }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-400">Adelantos:</span>
                                    <span class="text-green-400 ml-2">{{ dailyProgram.advanced }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-400">Total Fabricado:</span>
                                    <span class="text-white ml-2">{{ dailyProgram.total_produced || 0 }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-400">Total Rechazado:</span>
                                    <span class="text-red-400 ml-2">{{ dailyProgram.total_rejected || 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else-if="selectedProgramId && selectedWorkCenterId && selectedPhase" class="text-center py-8">
                    <p class="text-gray-400">No hay programas diarios para la fecha y centro seleccionados</p>
                </div>

                <!-- Modal de Edición -->
                <div v-if="editingProgram" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-gray-800 rounded-lg p-6 w-full max-w-2xl mx-4">
                        <h2 class="text-xl font-semibold text-white mb-4">Editar Programa Diario</h2>
                        
                        <div class="mb-4">
                            <p class="text-gray-300 text-sm">
                                {{ formatDate(editingProgram.date) }} - {{ editingProgram.shift }} - {{ editingProgram.work_center?.name }}
                            </p>
                        </div>

                        <form @submit.prevent="saveAdjustment">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label class="block text-gray-300 text-sm mb-2">Programado</label>
                                    <input type="number" v-model.number="editForm.programmed" min="0"
                                           class="w-full bg-gray-700 text-white rounded px-3 py-2">
                                </div>
                                <div>
                                    <label class="block text-gray-300 text-sm mb-2">Atrasos</label>
                                    <input type="number" v-model.number="editForm.backwardness" min="0"
                                           class="w-full bg-gray-700 text-white rounded px-3 py-2">
                                </div>
                                <div>
                                    <label class="block text-gray-300 text-sm mb-2">Adelantos</label>
                                    <input type="number" v-model.number="editForm.advanced" min="0"
                                           class="w-full bg-gray-700 text-white rounded px-3 py-2">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-gray-300 text-sm mb-2">Total Fabricado</label>
                                    <input type="number" v-model.number="editForm.total_produced" min="0"
                                           class="w-full bg-gray-700 text-white rounded px-3 py-2">
                                    <p class="text-gray-400 text-xs mt-1">Valor actual: {{ editingProgram.total_produced || 0 }}</p>
                                </div>
                                <div>
                                    <label class="block text-gray-300 text-sm mb-2">Total Rechazado</label>
                                    <input type="number" v-model.number="editForm.total_rejected" min="0"
                                           class="w-full bg-gray-700 text-white rounded px-3 py-2">
                                    <p class="text-gray-400 text-xs mt-1">Valor actual: {{ editingProgram.total_rejected || 0 }}</p>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-gray-300 text-sm mb-2">Motivo del ajuste *</label>
                                <textarea v-model="editForm.reason" rows="3" required
                                          class="w-full bg-gray-700 text-white rounded px-3 py-2"
                                          placeholder="Explique el motivo del ajuste..."></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="block text-gray-300 text-sm mb-2">Notas adicionales</label>
                                <textarea v-model="editForm.notes" rows="2"
                                          class="w-full bg-gray-700 text-white rounded px-3 py-2"
                                          placeholder="Información adicional opcional..."></textarea>
                            </div>

                            <div class="flex space-x-4">
                                <button type="submit" 
                                        :disabled="editForm.processing"
                                        class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded transition disabled:opacity-50">
                                    {{ editForm.processing ? 'Guardando...' : 'Guardar Cambios' }}
                                </button>
                                <button type="button" @click="closeEdit"
                                        class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded transition">
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
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';
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

    router.get(route('ingeniero-procesos.register-adjustments.load'), {
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
    editForm.put(route('ingeniero-procesos.daily-programs.update', editingProgram.value.id), {
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
        default: return shift;
    }
}
</script>
