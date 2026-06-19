<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <IngenieroProcesosSidebar />
        
        <div class="p-6 ml-16">
            <h1 class="text-3xl font-bold mb-6" style="color: #0b2a40;">Crear Nuevo Programa</h1>
            
            <form @submit.prevent="submit" class="rounded-lg p-6" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                <!-- Fecha de Entrega -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold mb-2" style="color: #4e6070; letter-spacing: 0.1em; text-transform: uppercase;">Fecha de Entrega</label>
                    <input type="date" 
                           v-model="form.fecha_entrega" 
                           :min="minDeliveryDate"
                           class="w-full rounded-lg px-4 py-2 font-semibold focus:outline-none"
                           style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;">
                    <p v-if="errors.fecha_entrega" class="text-sm mt-1 font-semibold" style="color: #ba2418;">{{ errors.fecha_entrega }}</p>
                    <p class="text-sm mt-1" style="color: #6a8090;">Mínimo: {{ minDeliveryDateFormatted }}</p>
                </div>
                
                <!-- Productos -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold mb-2" style="color: #4e6070; letter-spacing: 0.1em; text-transform: uppercase;">Productos</label>
                    
                    <div v-for="(product, index) in form.productos" :key="index" class="flex gap-4 mb-4">
                        <select v-model="product.modelo" 
                                class="flex-1 rounded-lg px-4 py-2 font-semibold focus:outline-none"
                                style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;">
                            <option value="">Seleccionar modelo</option>
                            <option v-for="model in Object.keys(products)" :key="model" :value="model">
                                {{ model }}
                            </option>
                        </select>
                        
                        <input type="number" 
                               v-model="product.cantidad" 
                               min="1"
                               placeholder="Cantidad"
                               class="w-32 rounded-lg px-4 py-2 font-semibold focus:outline-none"
                               style="background: #fff; color: #0c1c28; border: 1px solid #d4dee8;">
                        
                        <button type="button" 
                                @click="removeProduct(index)"
                                v-if="form.productos.length > 1"
                                class="px-4 py-2 rounded-lg transition font-semibold text-sm"
                                style="background: #fce9e8; color: #ba2418; border: 1px solid #ebbab8;">
                            Eliminar
                        </button>
                    </div>
                    
                    <button type="button" 
                            @click="addProduct"
                            class="px-4 py-2 rounded-lg transition font-semibold text-sm"
                            style="background: #0b2a40; color: #fff;">
                        + Agregar Producto
                    </button>
                </div>
                
                <!-- Botones -->
                <div class="flex gap-4">
                    <button type="submit" 
                            :disabled="form.processing"
                            class="px-6 py-2 rounded-lg transition font-semibold text-sm disabled:opacity-50"
                            style="background: #0a7c3e; color: #fff;">
                        {{ form.processing ? 'Guardando...' : 'Crear Programa' }}
                    </button>
                    
                    <Link :href="route('ingeniero-procesos.index')" 
                          class="px-6 py-2 rounded-lg transition font-semibold text-sm"
                          style="background: #fff; color: #0b2a40; border: 1px solid #d4dee8;">
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
</script>
