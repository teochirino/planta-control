<template>
    <div class="min-h-screen bg-gray-900 flex">
        <IngenieroProcesosSidebar />
        
        <div class="flex-1 p-6">
            <h1 class="text-3xl font-bold text-white mb-6">Crear Nuevo Programa</h1>
            
            <form @submit.prevent="submit" class="bg-gray-800 rounded-lg p-6">
                <!-- Fecha de Entrega -->
                <div class="mb-6">
                    <label class="block text-gray-300 text-sm font-medium mb-2">Fecha de Entrega</label>
                    <input type="date" 
                           v-model="form.fecha_entrega" 
                           :min="minDeliveryDate"
                           class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                    <p v-if="errors.fecha_entrega" class="text-red-400 text-sm mt-1">{{ errors.fecha_entrega }}</p>
                    <p class="text-gray-400 text-sm mt-1">Mínimo: {{ formatDate(minDeliveryDate) }}</p>
                </div>
                
                <!-- Productos -->
                <div class="mb-6">
                    <label class="block text-gray-300 text-sm font-medium mb-2">Productos</label>
                    
                    <div v-for="(product, index) in form.productos" :key="index" class="flex gap-4 mb-4">
                        <select v-model="product.modelo" 
                                class="flex-1 bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="">Seleccionar modelo</option>
                            <option v-for="model in Object.keys(products)" :key="model" :value="model">
                                {{ model }}
                            </option>
                        </select>
                        
                        <input type="number" 
                               v-model="product.cantidad" 
                               min="1"
                               placeholder="Cantidad"
                               class="w-32 bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                        
                        <button type="button" 
                                @click="removeProduct(index)"
                                v-if="form.productos.length > 1"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                            Eliminar
                        </button>
                    </div>
                    
                    <button type="button" 
                            @click="addProduct"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        + Agregar Producto
                    </button>
                </div>
                
                <!-- Botones -->
                <div class="flex gap-4">
                    <button type="submit" 
                            :disabled="form.processing"
                            class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition disabled:opacity-50">
                        {{ form.processing ? 'Guardando...' : 'Crear Programa' }}
                    </button>
                    
                    <Link :href="route('ingeniero-procesos.index')" 
                          class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                        Cancelar
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';

const props = defineProps({
    products: Object,
    minDeliveryDate: String,
});

const form = useForm({
    fecha_entrega: '',
    productos: [{ modelo: '', cantidad: 1 }],
});

const errors = ref({});

function addProduct() {
    form.productos.push({ modelo: '', cantidad: 1 });
}

function removeProduct(index) {
    form.productos.splice(index, 1);
}

function submit() {
    form.post(route('ingeniero-procesos.store'), {
        onError: (errs) => {
            errors.value = errs;
        },
    });
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('es-MX');
}
</script>
