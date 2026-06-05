<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';

const props = defineProps({
    recipients: Object,
});

const deleteRecipient = (recipient) => {
    if (confirm(`¿Estás seguro de eliminar a ${recipient.name}?`)) {
        router.delete(route('admin.notification-recipients.destroy', recipient.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Gestión de Destinatarios de Notificaciones" />

    <AdminLayout>
        <AdminSidebar />
        
        <div class="flex flex-col gap-2.5 p-6 ml-16">
            <!-- Header -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <div class="px-4 py-3 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold tracking-widest uppercase text-[#174060]">Módulo Administrador</span>
                        <h1 class="text-2xl font-extrabold text-[#0b2a40] leading-none">Gestión de Destinatarios de Notificaciones</h1>
                    </div>
                    <Link :href="route('admin.notification-recipients.create')" 
                          class="px-4 py-2 bg-[#0b2a40] text-white rounded-md text-xs font-bold hover:opacity-85">
                        ➕ Nuevo Destinatario
                    </Link>
                </div>
            </div>
            
            <!-- Tabla de Destinatarios -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-[#0b2a40] text-white">
                                <th class="px-4 py-3 text-left text-[11px] font-bold tracking-widest uppercase">ID</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold tracking-widest uppercase">Nombre</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold tracking-widest uppercase">Email</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold tracking-widest uppercase">Estado</th>
                                <th class="px-4 py-3 text-center text-[11px] font-bold tracking-widest uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="recipient in recipients.data" :key="recipient.id" class="border-b border-[#e8eff4] hover:bg-[#eef5fa]">
                                <td class="px-4 py-3 text-sm font-bold text-[#0c1c28]">{{ recipient.id }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-[#0c1c28]">{{ recipient.name }}</td>
                                <td class="px-4 py-3 text-sm text-[#4e6070]">{{ recipient.email }}</td>
                                <td class="px-4 py-3">
                                    <span v-if="recipient.is_active" 
                                          class="px-2 py-1 rounded-full text-xs font-bold bg-[#e4f5ec] text-[#0a7c3e] border border-[#aadcc4]">
                                        ✅ Activo
                                    </span>
                                    <span v-else 
                                          class="px-2 py-1 rounded-full text-xs font-bold bg-[#fef3c7] text-[#92400e] border border-[#fde68a]">
                                        ⏸️ Inactivo
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link :href="route('admin.notification-recipients.edit', recipient.id)"
                                              class="px-3 py-1.5 bg-[#174060] text-white border border-[#174060] rounded text-xs font-bold hover:opacity-85">
                                            ✏️ Editar
                                        </Link>
                                        <button @click="deleteRecipient(recipient)"
                                                class="px-3 py-1.5 bg-[#f4f7fa] text-[#ba2418] border border-[#d4dee8] rounded text-xs font-bold hover:bg-[#ba2418] hover:text-white hover:border-[#ba2418]">
                                            🗑️ Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="recipients.data.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-[#6a8090]">
                                    No hay destinatarios registrados. Crea un nuevo destinatario para comenzar.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación -->
                <div v-if="recipients.links.length > 3" class="px-4 py-3 border-t border-[#d4dee8]">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-[#6a8090]">
                            Mostrando {{ recipients.from }} a {{ recipients.to }} de {{ recipients.total }} destinatarios
                        </div>
                        <div class="flex gap-1">
                            <Link v-for="(link, index) in recipients.links" :key="index"
                                  :href="link.url"
                                  :class="[
                                      'px-3 py-1 text-xs font-bold rounded',
                                      link.active 
                                          ? 'bg-[#0b2a40] text-white' 
                                          : 'bg-white text-[#4e6070] border border-[#d4dee8] hover:bg-[#f4f7fa]',
                                      !link.url && 'opacity-50 cursor-not-allowed'
                                  ]"
                                  :disabled="!link.url"
                                  v-html="link.label">
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
