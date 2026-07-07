<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <IngenieroProcesosSidebar />
        
        <div class="p-6 ml-16">
            <div class="mb-6">
                <Link :href="route('ingeniero-procesos.work-centers.index')" 
                      class="px-4 py-2 rounded-lg font-semibold transition text-sm"
                      style="background: #fff; color: #0b2a40; border: 1px solid #d4dee8;">
                    ← Volver a Centros de Trabajo
                </Link>
            </div>
            
            <h1 class="text-3xl font-bold mb-6" style="color: #0b2a40;">Editar Centro de Trabajo</h1>
            
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
                            Nombre del Centro de Trabajo *
                        </label>
                        <input 
                            v-model="form.name"
                            type="text"
                            class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-blue-500"
                            style="border-color: #d4dee8;"
                            placeholder="Ej: Carpintería corte, Corte láser, Pintura, etc."
                            required
                        >
                        <p v-if="errors.name" class="mt-1 text-sm" style="color: #dc3545;">
                            {{ errors.name }}
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
                            placeholder="Ej: 300"
                            required
                        >
                        <p v-if="errors.installed_capacity" class="mt-1 text-sm" style="color: #dc3545;">
                            {{ errors.installed_capacity }}
                        </p>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-semibold mb-2" style="color: #0b2a40;">
                            Fase *
                        </label>
                        <input 
                            v-model="form.phase"
                            type="number"
                            min="1"
                            max="10"
                            class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-blue-500"
                            style="border-color: #d4dee8;"
                            placeholder="Ej: 1, 2, 3, 4"
                            required
                        >
                        <p v-if="errors.phase" class="mt-1 text-sm" style="color: #dc3545;">
                            {{ errors.phase }}
                        </p>
                        <p class="mt-1 text-sm" style="color: #6a8090;">
                            Fase del proceso (1-10). Ej: 1 = Corte, 2 = Estructura, 3 = Acabados, 4 = Ensamble
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
                            {{ processing ? 'Guardando...' : 'Actualizar Centro' }}
                        </button>
                        <Link 
                            :href="route('ingeniero-procesos.work-centers.index')"
                            class="px-6 py-2 rounded-lg font-semibold transition text-sm"
                            style="background: #6c757d; color: #fff;"
                        >
                            Cancelar
                        </Link>
                    </div>
                </form>
            </div>
            
            <!-- Información de relaciones -->
            <div class="mt-6 rounded-lg p-6" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                <h2 class="text-xl font-bold mb-4" style="color: #0b2a40;">Información Relacionada</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-semibold" style="color: #0b2a40;">Líneas de Producción:</p>
                        <p class="text-lg font-bold" style="color: #0c1c28;">{{ workCenter.production_lines?.length || 0 }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold" style="color: #0b2a40;">Máquinas:</p>
                        <p class="text-lg font-bold" style="color: #0c1c28;">{{ workCenter.machines?.length || 0 }}</p>
                    </div>
                </div>
                <p v-if="workCenter.production_lines?.length > 0 || workCenter.machines?.length > 0" 
                   class="mt-4 text-sm" style="color: #6a8090;">
                    Este centro de trabajo tiene relaciones activas. No se puede eliminar hasta que se eliminen las líneas de producción y máquinas asociadas.
                </p>
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
    workCenter: Object,
    errors: Object,
});

const form = useForm({
    name: props.workCenter.name,
    installed_capacity: props.workCenter.installed_capacity,
    phase: props.workCenter.phase,
});

const processing = computed(() => form.processing);

const submit = () => {
    form.put(route('ingeniero-procesos.work-centers.update', props.workCenter.id));
};
</script>
