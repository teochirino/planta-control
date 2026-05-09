<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    user: Object,
    profiles: Array,
    workCenters: Array,
    productionLines: Array,
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    id_profile: props.user.id_profile,
    work_centers: props.user.work_centers?.map(wc => wc.id) || [],
    production_lines: props.user.production_lines?.map(line => line.id) || [],
});

const showWorkCenters = computed(() => form.id_profile === 5);
const showProductionLines = computed(() => form.id_profile === 8);

const submit = () => {
    form.put(route('admin.users.update', props.user.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Editar Usuario" />

    <AuthenticatedLayout>
        <div class="flex flex-col gap-2.5">
            <!-- Header -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <div class="px-4 py-3 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold tracking-widest uppercase text-[#174060]">Módulo Administrador</span>
                        <h1 class="text-2xl font-extrabold text-[#0b2a40] leading-none">Editar Usuario</h1>
                    </div>
                    
                    <Link :href="route('admin.users.index')" 
                          class="px-4 py-2 bg-[#f4f7fa] text-[#4e6070] border border-[#d4dee8] rounded-md text-xs font-bold hover:bg-[#e8edf2]">
                        ← Volver
                    </Link>
                </div>
            </div>
            
            <!-- Formulario -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-6">
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Nombre</label>
                            <input v-model="form.name" 
                                   type="text" 
                                   required
                                   class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1"
                                   :class="{ 'border-red-500': form.errors.name }">
                            <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                        </div>
                        
                        <div>
                            <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Email</label>
                            <input v-model="form.email" 
                                   type="email" 
                                   required
                                   class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1"
                                   :class="{ 'border-red-500': form.errors.email }">
                            <p v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</p>
                        </div>
                        
                        <div>
                            <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Perfil de Usuario</label>
                            <select v-model="form.id_profile" 
                                    required
                                    class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1">
                                <option v-for="profile in profiles" :key="profile.id_profile" :value="profile.id_profile">
                                    {{ profile.title }}
                                </option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">ID Usuario Principal</label>
                            <input type="text" 
                                   :value="user.user_main_id || 'N/A'" 
                                   disabled
                                   class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1 bg-[#f4f7fa]">
                        </div>
                    </div>
                    
                    <div v-if="showWorkCenters" class="mt-6">
                        <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Centros de Trabajo</label>
                        <p class="text-xs text-[#6a8090] mb-3">Selecciona los centros que este supervisor podrá gestionar</p>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 p-4 border border-[#d4dee8] rounded-lg bg-[#f4f7fa]">
                            <label v-for="wc in workCenters" :key="wc.id" class="flex items-center gap-2 p-2 hover:bg-white rounded cursor-pointer">
                                <input v-model="form.work_centers" 
                                       type="checkbox" 
                                       :value="wc.id"
                                       class="w-4 h-4 text-[#0b2a40] border-[#d4dee8] rounded focus:ring-[#174060]">
                                <span class="text-sm font-semibold text-[#0c1c28]">{{ wc.name }}</span>
                            </label>
                        </div>
                    </div>
                    
                    <div v-if="showProductionLines" class="mt-6">
                        <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Líneas de Producción</label>
                        <p class="text-xs text-[#6a8090] mb-3">Selecciona las líneas que este operador podrá gestionar</p>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 p-4 border border-[#d4dee8] rounded-lg bg-[#f4f7fa]">
                            <label v-for="line in productionLines" :key="line.id" class="flex items-center gap-2 p-2 hover:bg-white rounded cursor-pointer">
                                <input v-model="form.production_lines" 
                                       type="checkbox" 
                                       :value="line.id"
                                       class="w-4 h-4 text-[#0b2a40] border-[#d4dee8] rounded focus:ring-[#174060]">
                                <span class="text-sm font-semibold text-[#0c1c28]">{{ line.title }}</span>
                                <span v-if="line.work_center" class="text-xs text-[#6a8090]">({{ line.work_center.name }})</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex gap-2 justify-end">
                        <Link :href="route('admin.users.index')" 
                              class="px-4 py-2 bg-[#f4f7fa] text-[#4e6070] border border-[#d4dee8] rounded-md text-xs font-bold hover:bg-[#e8edf2]">
                            Cancelar
                        </Link>
                        <button type="submit" 
                                :disabled="form.processing"
                                class="px-4 py-2 bg-[#0b2a40] text-white rounded-md text-xs font-bold hover:opacity-85 disabled:opacity-50">
                            💾 Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
