<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';

const props = defineProps({
    supervisors: Array,
    workCenters: Array,
});

const forms = {};

props.supervisors.forEach(supervisor => {
    forms[supervisor.id] = useForm({
        work_centers: supervisor.work_centers?.map(wc => wc.id) || [],
    });
});

const submit = (supervisor) => {
    forms[supervisor.id].post(route('admin.users.work-centers.update', supervisor.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Asignar Centros de Trabajo" />

    <AdminLayout>
        <AdminSidebar />
        
        <div class="flex flex-col gap-2.5 p-6 ml-16">
            <!-- Header -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <div class="px-4 py-3 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold tracking-widest uppercase text-[#174060]">Módulo Administrador</span>
                        <h1 class="text-2xl font-extrabold text-[#0b2a40] leading-none">Asignar Centros de Trabajo</h1>
                        <div class="text-sm text-[#4e6070] font-semibold mt-1">Gestión de accesos para Supervisores de Área</div>
                    </div>
                    
                    <Link :href="route('admin.users.index')" 
                          class="px-4 py-2 bg-[#f4f7fa] text-[#4e6070] border border-[#d4dee8] rounded-md text-xs font-bold hover:bg-[#e8edf2]">
                        ← Volver
                    </Link>
                </div>
            </div>
            
            <!-- Lista de Supervisores -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div v-for="supervisor in supervisors" :key="supervisor.id" class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-5">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-extrabold text-[#0b2a40]">{{ supervisor.name }}</h3>
                            <p class="text-sm text-[#6a8090]">{{ supervisor.email }}</p>
                        </div>
                        <span class="px-3 py-1 rounded-full bg-[#e4f5ec] text-[#0a7c3e] border border-[#aadcc4] text-xs font-bold">
                            Supervisor
                        </span>
                    </div>
                    
                    <form @submit.prevent="submit(supervisor)">
                        <div class="mb-4">
                            <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070] mb-2 block">
                                Centros Asignados
                            </label>
                            <div class="space-y-2 max-h-60 overflow-y-auto p-3 border border-[#d4dee8] rounded-lg bg-[#f4f7fa]">
                                <label v-for="wc in workCenters" :key="wc.id" class="flex items-center gap-2 p-2 hover:bg-white rounded cursor-pointer">
                                    <input v-model="forms[supervisor.id].work_centers" 
                                           type="checkbox" 
                                           :value="wc.id"
                                           class="w-4 h-4 text-[#0b2a40] border-[#d4dee8] rounded focus:ring-[#174060]">
                                    <span class="text-sm font-semibold text-[#0c1c28]">{{ wc.name }}</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between pt-3 border-t border-[#d4dee8]">
                            <div class="text-xs text-[#6a8090]">
                                <strong>{{ forms[supervisor.id].work_centers.length }}</strong> centro(s) asignado(s)
                            </div>
                            <button type="submit" 
                                    :disabled="forms[supervisor.id].processing"
                                    class="px-4 py-2 bg-[#0b2a40] text-white rounded-md text-xs font-bold hover:opacity-85 disabled:opacity-50">
                                💾 Guardar
                            </button>
                        </div>
                    </form>
                </div>
                
                <div v-if="supervisors.length === 0" class="col-span-2 bg-white border border-[#d4dee8] rounded-xl shadow-sm p-8 text-center">
                    <div class="text-4xl mb-3">👥</div>
                    <p class="text-[#6a8090]">No hay supervisores de área registrados en el sistema.</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
