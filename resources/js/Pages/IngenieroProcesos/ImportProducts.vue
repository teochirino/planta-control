<template>
    <div class="min-h-screen bg-gray-900 flex">
        <IngenieroProcesosSidebar />
        
        <div class="flex-1 p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-white">Importar Productos desde Excel</h1>
                <p class="text-gray-400 mt-2">Sube un archivo Excel (.xlsx) para validar los modelos de productos contra la base de datos.</p>
            </div>
            
            <!-- Formulario de carga -->
            <div class="bg-gray-800 rounded-lg p-6 mb-6">
                <form @submit.prevent="submit" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="block text-white mb-2">Archivo Excel (.xlsx)</label>
                        <input 
                            type="file" 
                            ref="fileInput"
                            @change="handleFileChange"
                            accept=".xlsx,.xls"
                            class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                        >
                        <p class="text-gray-400 text-sm mt-2">
                            El archivo debe tener los modelos en la columna G, cantidad en la columna H y las fechas de vencimiento en la columna P (desde fila 2).
                        </p>
                    </div>
                    
                    <button 
                        type="submit" 
                        :disabled="!file || processing"
                        class="px-6 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-600 text-white rounded-lg transition"
                    >
                        {{ processing ? 'Procesando...' : 'Validar Archivo' }}
                    </button>
                </form>
            </div>
            
            <!-- Resultados de la validación -->
            <div v-if="importData" class="space-y-6">
                <!-- Resumen -->
                <div class="bg-gray-800 rounded-lg p-6">
                    <h2 class="text-xl font-bold text-white mb-4">Resumen de Validación</h2>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-gray-700 rounded-lg p-4 text-center">
                            <p class="text-3xl font-bold text-white">{{ importData.total }}</p>
                            <p class="text-gray-400">Total Registros</p>
                        </div>
                        <div class="bg-green-900 rounded-lg p-4 text-center">
                            <p class="text-3xl font-bold text-green-400">{{ importData.coincidencias }}</p>
                            <p class="text-gray-400">Coincidencias</p>
                        </div>
                        <div class="bg-red-900 rounded-lg p-4 text-center">
                            <p class="text-3xl font-bold text-red-400">{{ importData.no_coincidencias }}</p>
                            <p class="text-gray-400">No Coinciden</p>
                        </div>
                    </div>
                </div>
                
                <!-- Modelos que no existen -->
                <div v-if="importData.modelos_no_existentes.length > 0" class="bg-gray-800 rounded-lg p-6">
                    <h2 class="text-xl font-bold text-white mb-4">Modelos que NO existen en la base de datos</h2>
                    <p class="text-yellow-400 mb-4">
                        Estos modelos deben ser insertados usando la opción "Nuevo Producto":
                    </p>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Fila</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Modelo</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Cantidad</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Fecha Vencimiento</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <tr v-for="item in importData.modelos_no_existentes" :key="item.row" class="hover:bg-gray-750">
                                    <td class="px-4 py-3 text-white">{{ item.row }}</td>
                                    <td class="px-4 py-3 text-red-400 font-bold">{{ item.modelo }}</td>
                                    <td class="px-4 py-3 text-white">{{ item.cantidad }}</td>
                                    <td class="px-4 py-3 text-white">{{ item.fecha_vencimiento }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Botón Crear Programa (solo si todos los productos existen) -->
                <div v-if="importData.no_coincidencias === 0" class="bg-gray-800 rounded-lg p-6">
                    <h2 class="text-xl font-bold text-white mb-4">Todos los productos existen en la base de datos</h2>
                    <p class="text-green-400 mb-4">
                        Puedes crear un programa con estos productos usando la fecha de vencimiento como fecha de entrega.
                    </p>
                    <button 
                        @click="createProgram"
                        :disabled="creatingProgram"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-600 text-white rounded-lg transition"
                    >
                        {{ creatingProgram ? 'Creando programa...' : 'Crear Programa' }}
                    </button>
                </div>
                
                <!-- Mensaje de programa creado -->
                <div v-if="programCreated" class="bg-green-800 rounded-lg p-6">
                    <h2 class="text-xl font-bold text-white mb-4">Programa Creado Exitosamente</h2>
                    <p class="text-green-400 mb-4">
                        Código del programa: <span class="font-bold">{{ programCreated.codigo }}</span>
                    </p>
                    <Link 
                        :href="route('ingeniero-procesos.show', programCreated.id)"
                        class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition inline-block"
                    >
                        Ver Programa
                    </Link>
                </div>
                
                <!-- Todos los datos (opcional) -->
                <details class="bg-gray-800 rounded-lg p-6">
                    <summary class="text-white font-bold cursor-pointer">Ver todos los datos procesados</summary>
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Fila</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Modelo</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Cantidad</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Fecha Vencimiento</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <tr v-for="item in importData.data" :key="item.row" class="hover:bg-gray-750">
                                    <td class="px-4 py-3 text-white">{{ item.row }}</td>
                                    <td class="px-4 py-3 text-white">{{ item.modelo }}</td>
                                    <td class="px-4 py-3 text-white">{{ item.cantidad }}</td>
                                    <td class="px-4 py-3 text-white">{{ item.fecha_vencimiento }}</td>
                                    <td class="px-4 py-3">
                                        <span v-if="item.existe" class="px-2 py-1 bg-green-600 text-white rounded text-sm">
                                            Existe
                                        </span>
                                        <span v-else class="px-2 py-1 bg-red-600 text-white rounded text-sm">
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

// Obtener datos de importación del flash message
const importData = ref(page.props.flash?.import_data || null);

// Watch for changes in flash data
watch(() => page.props.flash, (newFlash) => {
    if (newFlash?.import_data) {
        importData.value = newFlash.import_data;
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
    
    if (!importData.value || !importData.value.data) {
        console.log('No import data or data array');
        return;
    }
    
    creatingProgram.value = true;
    
    // Filtrar solo los productos que existen
    const validProducts = importData.value.data.filter(item => item.existe);
    console.log('Valid products:', validProducts);
    console.log('Sending request to:', route('ingeniero-procesos.import.products.create'));
    
    router.post(route('ingeniero-procesos.import.products.create'), {
        data: validProducts,
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
