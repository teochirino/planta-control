<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <IngenieroProcesosSidebar />
        
        <div class="p-6 ml-16">
            <div class="mb-6">
                <h1 class="text-3xl font-bold" style="color: #0b2a40;">Importar Productos desde Excel</h1>
                <p class="mt-2" style="color: #6a8090;">Sube un archivo Excel (.xlsx) para validar los modelos de productos contra la base de datos.</p>
            </div>
            
            <!-- Formulario de carga -->
            <div class="rounded-lg p-6 mb-6" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                <form @submit.prevent="submit" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="block font-semibold mb-2" style="color: #4e6070; letter-spacing: 0.1em; text-transform: uppercase;">Archivo Excel (.xlsx)</label>
                        <input 
                            type="file" 
                            ref="fileInput"
                            @change="handleFileChange"
                            accept=".xlsx,.xls"
                            class="w-full px-4 py-2 rounded-lg font-semibold focus:outline-none"
                            style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;"
                        >
                        <p class="text-sm mt-2" style="color: #6a8090;">
                            El archivo debe tener los modelos en la columna G, cantidad en la columna H y las fechas de vencimiento en la columna P (desde fila 2).
                        </p>
                    </div>
                    
                    <button 
                        type="submit" 
                        :disabled="!file || processing"
                        class="px-6 py-2 rounded-lg transition font-semibold text-sm disabled:opacity-50"
                        style="background: #0a7c3e; color: #fff;"
                    >
                        {{ processing ? 'Procesando...' : 'Validar Archivo' }}
                    </button>
                </form>
            </div>
            
            <!-- Resultados de la validación -->
            <div v-if="importData" class="space-y-6">
                <!-- Resumen -->
                <div class="rounded-lg p-6" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                    <h2 class="text-xl font-bold mb-4" style="color: #0b2a40;">Resumen de Validación</h2>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="rounded-lg p-4 text-center" style="background: #f4f7fa; border: 1px solid #e8eff4;">
                            <p class="text-3xl font-bold" style="color: #0b2a40;">{{ importData.total }}</p>
                            <p style="color: #6a8090;">Total Registros</p>
                        </div>
                        <div class="rounded-lg p-4 text-center" style="background: #e4f5ec; border: 1px solid #aadcc4;">
                            <p class="text-3xl font-bold" style="color: #0a7c3e;">{{ importData.coincidencias }}</p>
                            <p style="color: #6a8090;">Coincidencias</p>
                        </div>
                        <div class="rounded-lg p-4 text-center" style="background: #fce9e8; border: 1px solid #ebbab8;">
                            <p class="text-3xl font-bold" style="color: #ba2418;">{{ importData.no_coincidencias }}</p>
                            <p style="color: #6a8090;">No Coinciden</p>
                        </div>
                    </div>
                </div>
                
                <!-- Modelos que no existen -->
                <div v-if="importData.modelos_no_existentes.length > 0" class="rounded-lg p-6" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                    <h2 class="text-xl font-bold mb-4" style="color: #0b2a40;">Modelos que NO existen en la base de datos</h2>
                    <p class="mb-4 font-semibold" style="color: #a87000;">
                        Estos modelos deben ser insertados usando la opción "Nuevo Producto":
                    </p>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr style="background: #0b2a40;">
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Fila</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Modelo</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Cantidad</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Fecha Vencimiento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in importData.modelos_no_existentes" :key="item.row" style="border-bottom: 1px solid #e8eff4;">
                                    <td class="px-4 py-3" style="color: #0c1c28; font-weight: 600;">{{ item.row }}</td>
                                    <td class="px-4 py-3 font-bold" style="color: #ba2418;">{{ item.modelo }}</td>
                                    <td class="px-4 py-3" style="color: #0c1c28; font-weight: 600;">{{ item.cantidad }}</td>
                                    <td class="px-4 py-3" style="color: #0c1c28; font-weight: 600;">{{ item.fecha_vencimiento }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Botón Crear Programa (solo si todos los productos existen) -->
                <div v-if="importData.no_coincidencias === 0" class="rounded-lg p-6" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                    <h2 class="text-xl font-bold mb-4" style="color: #0b2a40;">Todos los productos existen en la base de datos</h2>
                    <p class="mb-4 font-semibold" style="color: #0a7c3e;">
                        Puedes crear un programa con estos productos usando la fecha de vencimiento como fecha de entrega.
                    </p>
                    
                    <button 
                        @click="createProgram()"
                        :disabled="creatingProgram"
                        class="px-6 py-2 rounded-lg transition font-semibold text-sm disabled:opacity-50"
                        style="background: #0b2a40; color: #fff;"
                    >
                        {{ creatingProgram ? 'Creando programa...' : 'Crear Programa' }}
                    </button>
                </div>
                
                <!-- Modal de confirmación para sábados -->
                <div v-if="showSaturdayModal" class="fixed inset-0 flex items-center justify-center z-50 p-4" style="background: rgba(0,0,0,0.6);">
                    <div class="rounded-xl p-8 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 8px 32px rgba(11,28,40,.2);">
                        <!-- Header con icono -->
                        <div class="flex items-center gap-4 mb-6">
                            <div class="flex items-center justify-center w-16 h-16 rounded-full" style="background: #fff9e6; border: 2px solid #ffd700;">
                                <svg class="w-8 h-8" style="color: #b8860b;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold" style="color: #0b2a40;">Confirmar Inclusión de Sábados</h3>
                                <p class="text-sm" style="color: #6a8090;">Se detectaron sábados en el cálculo de fases</p>
                            </div>
                        </div>
                        
                        <!-- Información de sábados -->
                        <div class="mb-6 rounded-lg p-5" style="background: #fff9e6; border: 1px solid #ffd700;">
                            <p class="mb-4 font-semibold text-lg" style="color: #b8860b;">📅 Sábados detectados en el cálculo de fases:</p>
                            <div v-for="phase in importData.phases_with_saturday" :key="phase.fase" class="mb-4 p-4 rounded-lg" style="background: #fff; border: 1px solid #ffd700;">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="px-3 py-1 rounded-full text-sm font-bold" style="background: #ffd700; color: #0b2a40;">{{ phase.fase }}</span>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="p-3 rounded" style="background: #f4f7fa;">
                                        <p class="text-xs font-semibold uppercase mb-1" style="color: #6a8090;">Sin incluir sábados</p>
                                        <p class="text-lg font-bold" style="color: #0c1c28;">{{ phase.fecha_sin_sabado }}</p>
                                    </div>
                                    <div class="p-3 rounded" style="background: #e4f5ec;">
                                        <p class="text-xs font-semibold uppercase mb-1" style="color: #6a8090;">Incluyendo sábados</p>
                                        <p class="text-lg font-bold" style="color: #0a7c3e;">{{ phase.fecha_con_sabado }}</p>
                                    </div>
                                </div>
                                <div class="mt-3 p-3 rounded" style="background: #fce9e8; border: 1px solid #ebbab8;">
                                    <p class="text-sm font-semibold" style="color: #ba2418;">
                                        🗓️ Sábados que se saltan: <strong>{{ phase.sabados_saltados.join(', ') }}</strong>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pregunta de confirmación -->
                        <div class="mb-6 p-4 rounded-lg text-center" style="background: #f4f7fa; border: 1px solid #e8eff4;">
                            <p class="text-lg font-semibold" style="color: #0b2a40;">
                                ¿Desea incluir los días sábado como días laborables en este programa de producción?
                            </p>
                        </div>
                        
                        <!-- Botones -->
                        <div class="flex gap-4 justify-center">
                            <button 
                                @click="showSaturdayModal = false"
                                class="px-6 py-3 rounded-lg transition font-semibold text-sm flex items-center gap-2"
                                style="background: #e8eff4; color: #0b2a40; border: 1px solid #d4dee8;"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Cancelar
                            </button>
                            <button 
                                @click="includeSaturdays = false; showSaturdayModal = false"
                                class="px-6 py-3 rounded-lg transition font-semibold text-sm flex items-center gap-2"
                                style="background: #ba2418; color: #fff;"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                </svg>
                                No Incluir Sábados
                            </button>
                            <button 
                                @click="includeSaturdays = true; showSaturdayModal = false"
                                class="px-6 py-3 rounded-lg transition font-semibold text-sm flex items-center gap-2"
                                style="background: #0a7c3e; color: #fff;"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Incluir Sábados
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Mensaje de programa creado -->
                <div v-if="programCreated" class="rounded-lg p-6" style="background: #e4f5ec; border: 1px solid #aadcc4;">
                    <h2 class="text-xl font-bold mb-4" style="color: #0a7c3e;">Programa Creado Exitosamente</h2>
                    <p class="mb-4 font-semibold" style="color: #0a7c3e;">
                        Código del programa: <span class="font-bold">{{ programCreated.codigo }}</span>
                    </p>
                    <Link 
                        :href="route('ingeniero-procesos.show', programCreated.id)"
                        class="px-6 py-2 rounded-lg transition font-semibold text-sm inline-block"
                        style="background: #0a7c3e; color: #fff;"
                    >
                        Ver Programa
                    </Link>
                </div>
                
                <!-- Todos los datos (opcional) -->
                <details class="rounded-lg p-6" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                    <summary class="font-bold cursor-pointer" style="color: #0b2a40;">Ver todos los datos procesados</summary>
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr style="background: #0b2a40;">
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Fila</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Modelo</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Cantidad</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Fecha Vencimiento</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in importData.data" :key="item.row" style="border-bottom: 1px solid #e8eff4;">
                                    <td class="px-4 py-3" style="color: #0c1c28; font-weight: 600;">{{ item.row }}</td>
                                    <td class="px-4 py-3" style="color: #0c1c28; font-weight: 600;">{{ item.modelo }}</td>
                                    <td class="px-4 py-3" style="color: #0c1c28; font-weight: 600;">{{ item.cantidad }}</td>
                                    <td class="px-4 py-3" style="color: #0c1c28; font-weight: 600;">{{ item.fecha_vencimiento }}</td>
                                    <td class="px-4 py-3">
                                        <span v-if="item.existe" class="px-2 py-1 rounded text-sm font-semibold" style="background: #e4f5ec; color: #0a7c3e; border: 1px solid #aadcc4;">
                                            Existe
                                        </span>
                                        <span v-else class="px-2 py-1 rounded text-sm font-semibold" style="background: #fce9e8; color: #ba2418; border: 1px solid #ebbab8;">
                                            No existe
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </details>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';
import { ref, watch } from 'vue';

const page = usePage();
const fileInput = ref(null);
const file = ref(null);
const processing = ref(false);
const creatingProgram = ref(false);
const programCreated = ref(page.props.flash?.program_created || null);
const showSaturdayModal = ref(false);
const includeSaturdays = ref(false);

// Obtener datos de importación del flash message
const importData = ref(page.props.flash?.import_data || null);

// Watch for changes in flash data
watch(() => page.props.flash, (newFlash) => {
    if (newFlash?.import_data) {
        importData.value = newFlash.import_data;
        // Mostrar modal automáticamente si hay sábados detectados
        if (newFlash.import_data.has_saturday_in_phases) {
            showSaturdayModal.value = true;
        }
    }
    if (newFlash?.program_created) {
        programCreated.value = newFlash.program_created;
    }
}, { deep: true });

function handleFileChange(event) {
    file.value = event.target.files[0];
}

function submit() {
    console.log('submit called');
    console.log('file.value:', file.value);
    
    if (!file.value) {
        console.log('No file selected');
        return;
    }
    
    processing.value = true;
    
    const formData = new FormData();
    formData.append('archivo', file.value);
    
    console.log('Sending request to:', route('ingeniero-procesos.import.products.store'));
    
    router.post(route('ingeniero-procesos.import.products.store'), formData, {
        onSuccess: () => {
            console.log('Request successful');
            processing.value = false;
            importData.value = page.props.flash?.import_data || null;
            console.log('importData:', importData.value);
            file.value = null;
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
        onError: (errors) => {
            console.log('Request failed with errors:', errors);
            processing.value = false;
        },
    });
}

function createProgram() {
    console.log('createProgram called');
    console.log('importData.value:', importData.value);
    console.log('includeSaturdays:', includeSaturdays.value);
    
    if (!importData.value || !importData.value.data) {
        console.log('No import data or data array');
        return;
    }
    
    creatingProgram.value = true;
    showSaturdayModal.value = false;
    
    // Filtrar solo los productos que existen
    const validProducts = importData.value.data.filter(item => item.existe);
    console.log('Valid products:', validProducts);
    console.log('Sending request to:', route('ingeniero-procesos.import.products.create'));
    
    router.post(route('ingeniero-procesos.import.products.create'), {
        data: validProducts,
        include_saturdays: includeSaturdays.value,
    }, {
        onSuccess: () => {
            console.log('Program created successfully');
            creatingProgram.value = false;
            programCreated.value = page.props.flash?.program_created || null;
            console.log('programCreated:', programCreated.value);
        },
        onError: (errors) => {
            console.log('Failed to create program:', errors);
            creatingProgram.value = false;
        },
    });
}
</script>
