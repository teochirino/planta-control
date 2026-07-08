<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <IngenieroProcesosSidebar />
        
        <div class="p-4 sm:p-6 ml-16">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <div class="w-full">
                    <h1 class="text-2xl sm:text-3xl font-bold" style="color: #0b2a40;">Programa {{ program.codigo }}</h1>
                    <p class="mt-1 text-sm sm:text-base" style="color: #6a8090;">Creado por {{ program.creator?.name }}</p>
                </div>
                <Link :href="route('ingeniero-procesos.index')" 
                      class="px-4 py-2 rounded-lg transition font-semibold text-sm w-full sm:w-auto text-center"
                      style="background: #fff; color: #0b2a40; border: 1px solid #d4dee8;">
                    Volver
                </Link>
            </div>
            
            <!-- Filtro por Centro de Trabajo -->
            <div class="mb-6">
                <label class="block text-sm font-semibold mb-2" style="color: #0b2a40;">Filtrar por Centro de Trabajo</label>
                <select 
                    v-model="selectedWorkCenterId"
                    @change="applyFilter"
                    class="w-full sm:w-auto px-4 py-2 rounded-lg border focus:outline-none focus:ring-2"
                    style="background: #fff; border-color: #d4dee8; color: #0b2a40;"
                >
                    <option value="">Todos los centros de trabajo</option>
                    <option 
                        v-for="workCenter in workCenters" 
                        :key="workCenter.id" 
                        :value="workCenter.id"
                    >
                        {{ workCenter.name }} (Fase {{ workCenter.phase }})
                    </option>
                </select>
            </div>
            
            <!-- Fechas de Fases -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
                <div class="rounded-lg p-3 sm:p-4" style="background: #fff; border: 1px solid #d4dee8;">
                    <h3 class="text-xs sm:text-sm font-semibold uppercase mb-2" style="color: #4e6070; letter-spacing: 0.1em;">Fase 1</h3>
                    <p class="font-semibold text-sm sm:text-base" style="color: #0b2a40;">{{ program.fecha_fase1_formatted }}</p>
                </div>
                <div class="rounded-lg p-3 sm:p-4" style="background: #fff; border: 1px solid #d4dee8;">
                    <h3 class="text-xs sm:text-sm font-semibold uppercase mb-2" style="color: #4e6070; letter-spacing: 0.1em;">Fase 2</h3>
                    <p class="font-semibold text-sm sm:text-base" style="color: #0b2a40;">{{ program.fecha_fase2_formatted }}</p>
                </div>
                <div class="rounded-lg p-3 sm:p-4" style="background: #fff; border: 1px solid #d4dee8;">
                    <h3 class="text-xs sm:text-sm font-semibold uppercase mb-2" style="color: #4e6070; letter-spacing: 0.1em;">Fase 3</h3>
                    <p class="font-semibold text-sm sm:text-base" style="color: #0b2a40;">{{ program.fecha_fase3_formatted }}</p>
                </div>
                <div class="rounded-lg p-3 sm:p-4" style="background: #fff; border: 1px solid #d4dee8;">
                    <h3 class="text-xs sm:text-sm font-semibold uppercase mb-2" style="color: #4e6070; letter-spacing: 0.1em;">Fase 4 (Entrega)</h3>
                    <p class="font-semibold text-sm sm:text-base" style="color: #0b2a40;">{{ program.fecha_fase4_formatted }}</p>
                </div>
            </div>
            
            <!-- Tabla por Fases -->
            <div v-for="(phaseDetails, phase) in details" :key="phase" class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg sm:text-xl font-bold" style="color: #0b2a40;">Fase {{ phase }}</h2>
                    <button 
                        @click="togglePhaseVisibility(phase)"
                        class="p-2 rounded-lg transition hover:bg-gray-100"
                        style="background: #fff; border: 1px solid #d4dee8;"
                        :title="phaseVisibility[phase] ? 'Ocultar detalles' : 'Mostrar detalles'"
                    >
                        <svg v-if="phaseVisibility[phase]" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" style="color: #0b2a40;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" style="color: #0b2a40;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                        </svg>
                    </button>
                </div>
                
                <div v-show="phaseVisibility[phase]" class="rounded-lg overflow-hidden" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[700px]">
                            <thead>
                                <tr style="background: #0b2a40;">
                                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Centro de Trabajo</th>
                                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Modelo</th>
                                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Cantidad Solicitada</th>
                                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Piezas Totales</th>
                                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Tiempo Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="detail in phaseDetails" :key="detail.id" style="border-bottom: 1px solid #e8eff4;">
                                    <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ detail.work_center }}</td>
                                    <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ detail.modelo }}</td>
                                    <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ detail.cantidad_solicitada }}</td>
                                    <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ detail.total_pieces }}</td>
                                    <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ detail.total_time }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Totalizador por Fecha y Centro de Trabajo -->
            <div class="mt-8">
                <h2 class="text-xl sm:text-2xl font-bold mb-6" style="color: #0b2a40;">Resumen por Fecha y Centro de Trabajo</h2>
                
                <div v-for="(workCenters, date) in totalsByDate" :key="date" class="mb-6">
                    <div class="rounded-lg p-3 sm:p-4 mb-3" style="background: #174060;">
                        <h3 class="text-base sm:text-lg font-bold" style="color: #fff;">Fecha: {{ date }}</h3>
                    </div>
                    
                    <div class="rounded-lg overflow-hidden" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[500px]">
                                <thead>
                                    <tr style="background: #0b2a40;">
                                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Centro de Trabajo</th>
                                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Piezas Totales</th>
                                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Tiempo Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(totals, workCenter) in workCenters" :key="workCenter" style="border-bottom: 1px solid #e8eff4;">
                                        <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ workCenter }}</td>
                                        <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ totals.total_pieces }}</td>
                                        <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ totals.total_time }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';
import { ref, reactive } from 'vue';

const props = defineProps({
    program: Object,
    details: Object,
    totalsByDate: Object,
    workCenters: Object,
    filters: Object,
});

const selectedWorkCenterId = ref(props.filters?.work_center_id || '');

// Estado de visibilidad de las fases (todas visibles por defecto)
const phaseVisibility = reactive({});

// Inicializar visibilidad de fases basado en los detalles disponibles
const initializePhaseVisibility = () => {
    Object.keys(props.details).forEach(phase => {
        if (!(phase in phaseVisibility)) {
            phaseVisibility[phase] = false; // Cerradas por defecto
        }
    });
};

// Inicializar al montar el componente
initializePhaseVisibility();

// Método para alternar la visibilidad de una fase
const togglePhaseVisibility = (phase) => {
    phaseVisibility[phase] = !phaseVisibility[phase];
};

const applyFilter = () => {
    router.get(route('ingeniero-procesos.show', props.program.id), {
        work_center_id: selectedWorkCenterId.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>
