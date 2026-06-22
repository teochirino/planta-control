<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <IngenieroProcesosSidebar />
        
        <div class="p-6 ml-16">
            <div class="mb-6">
                <Link :href="route('ingeniero-procesos.production-lines.index')" 
                      class="px-4 py-2 rounded-lg font-semibold transition text-sm"
                      style="background: #fff; color: #0b2a40; border: 1px solid #d4dee8;">
                    ← Volver a Líneas de Producción
                </Link>
            </div>
            
            <h1 class="text-3xl font-bold mb-6" style="color: #0b2a40;">Nueva Línea de Producción</h1>
            
            <div v-if="$page.props.flash.success" class="mb-4 p-4 rounded-lg" style="background: #d4edda; color: #155724;">
                {{ $page.props.flash.success }}
            </div>
            
            <div v-if="$page.props.flash.error" class="mb-4 p-4 rounded-lg" style="background: #f8d7da; color: #721c24;">
                {{ $page.props.flash.error }}
            </div>
            
            <div class="rounded-lg p-6" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                <form @submit.prevent="submit">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2" style="color: #0b2a40;">
                            Centro de Trabajo *
                        </label>
                        <select 
                            v-model="form.id_work_center"
                            class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-blue-500"
                            style="border-color: #d4dee8;"
                            required
                        >
                            <option value="">Seleccione un centro de trabajo</option>
                            <option 
                                v-for="center in workCenters" 
                                :key="center.id" 
                                :value="center.id"
                            >
                                {{ center.name }} (Fase {{ center.phase }})
                            </option>
                        </select>
                        <p v-if="errors.id_work_center" class="mt-1 text-sm" style="color: #dc3545;">
                            {{ errors.id_work_center }}
                        </p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2" style="color: #0b2a40;">
                            Título de la Línea *
                        </label>
                        <input 
                            v-model="form.title"
                            type="text"
                            class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-blue-500"
                            style="border-color: #d4dee8;"
                            placeholder="Ej: Línea A, Línea B, Pintura, etc."
                            required
                        >
                        <p v-if="errors.title" class="mt-1 text-sm" style="color: #dc3545;">
                            {{ errors.title }}
                        </p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2" style="color: #0b2a40;">
                            Capacidad Instalada *
                        </label>
                        <input 
                            v-model="form.installed_capacity"
                            type="number"
                            min="1"
                            class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-blue-500"
                            style="border-color: #d4dee8;"
                            placeholder="Ej: 100"
                            required
                        >
                        <p v-if="errors.installed_capacity" class="mt-1 text-sm" style="color: #dc3545;">
                            {{ errors.installed_capacity }}
                        </p>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-semibold mb-2" style="color: #0b2a40;">
                            Costo por Paro *
                        </label>
                        <input 
                            v-model="form.cost"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-blue-500"
                            style="border-color: #d4dee8;"
                            placeholder="Ej: 150.00"
                            required
                        >
                        <p v-if="errors.cost" class="mt-1 text-sm" style="color: #dc3545;">
                            {{ errors.cost }}
                        </p>
                    </div>
                    
                    <div class="flex gap-4">
                        <button 
                            type="submit"
                            :disabled="processing"
                            class="px-6 py-2 rounded-lg font-semibold transition text-sm"
                            style="background: #0b2a40; color: #fff;"
                            :style="{ opacity: processing ? 0.7 : 1 }"
                        >
                            {{ processing ? 'Guardando...' : 'Guardar Línea' }}
                        </button>
                        <Link 
                            :href="route('ingeniero-procesos.production-lines.index')"
                            class="px-6 py-2 rounded-lg font-semibold transition text-sm"
                            style="background: #6c757d; color: #fff;"
                        >
                            Cancelar
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    workCenters: Array,
    errors: Object,
});

const form = useForm({
    id_work_center: '',
    title: '',
    installed_capacity: '',
    cost: '',
});

const processing = computed(() => form.processing);

const submit = () => {
    form.post(route('ingeniero-procesos.production-lines.store'));
};
</script>
