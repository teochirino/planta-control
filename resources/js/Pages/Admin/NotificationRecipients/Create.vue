<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';

const form = useForm({
    name: '',
    email: '',
    is_active: true,
});

const submit = () => {
    form.post(route('admin.notification-recipients.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Crear Destinatario de Notificación" />

    <AdminLayout>
        <AdminSidebar />
        
        <div class="flex flex-col gap-2.5 p-6 ml-16">
            <!-- Header -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <div class="px-4 py-3 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold tracking-widest uppercase text-[#174060]">Módulo Administrador</span>
                        <h1 class="text-2xl font-extrabold text-[#0b2a40] leading-none">Crear Destinatario de Notificación</h1>
                    </div>
                    
                    <Link :href="route('admin.notification-recipients.index')" 
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
                            <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Nombre del Destinatario</label>
                            <input v-model="form.name" 
                                   type="text" 
                                   required
                                   placeholder="Ej: Compras, Mantenimiento, Gerencia"
                                   class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1"
                                   :class="{ 'border-red-500': form.errors.name }">
                            <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                        </div>
                        
                        <div>
                            <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Correo Electrónico</label>
                            <input v-model="form.email" 
                                   type="email" 
                                   required
                                   placeholder="Ej: compras@empresa.com"
                                   class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1"
                                   :class="{ 'border-red-500': form.errors.email }">
                            <p v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</p>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="form.is_active" 
                                       type="checkbox" 
                                       class="w-4 h-4 text-[#0b2a40] border-[#d4dee8] rounded focus:ring-[#174060]">
                                <span class="text-sm font-semibold text-[#0c1c28]">Destinatario Activo</span>
                            </label>
                            <p class="text-xs text-[#6a8090] mt-1 ml-6">Los destinatarios inactivos no recibirán notificaciones</p>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex gap-2 justify-end">
                        <Link :href="route('admin.notification-recipients.index')" 
                              class="px-4 py-2 bg-[#f4f7fa] text-[#4e6070] border border-[#d4dee8] rounded-md text-xs font-bold hover:bg-[#e8edf2]">
                            Cancelar
                        </Link>
                        <button type="submit" 
                                :disabled="form.processing"
                                class="px-4 py-2 bg-[#0b2a40] text-white rounded-md text-xs font-bold hover:opacity-85 disabled:opacity-50">
                            💾 Guardar Destinatario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
