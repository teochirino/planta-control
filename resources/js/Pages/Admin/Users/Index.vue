<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    users: Object,
});

const deleteUser = (user) => {
    if (confirm(`¿Estás seguro de eliminar a ${user.name}?`)) {
        router.delete(route('admin.users.destroy', user.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Gestión de Usuarios" />

    <AuthenticatedLayout>
        <div class="flex flex-col gap-2.5">
            <!-- Header -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <div class="px-4 py-3 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold tracking-widest uppercase text-[#174060]">Módulo Administrador</span>
                        <h1 class="text-2xl font-extrabold text-[#0b2a40] leading-none">Gestión de Usuarios</h1>
                    </div>
                    
                    <div class="flex gap-2">
                        <Link :href="route('admin.users.import')" 
                              class="px-4 py-2 bg-[#0b2a40] text-white rounded-md text-xs font-bold hover:opacity-85">
                            ➕ Importar Usuario
                        </Link>
                        <Link :href="route('admin.work-centers.assign')" 
                              class="px-4 py-2 bg-[#174060] text-white rounded-md text-xs font-bold hover:opacity-85">
                            🏭 Asignar Centros
                        </Link>
                        <Link :href="route('admin.production-lines.assign')" 
                              class="px-4 py-2 bg-[#0b8a3d] text-white rounded-md text-xs font-bold hover:opacity-85">
                            📊 Asignar Líneas
                        </Link>
                    </div>
                </div>
            </div>
            
            <!-- Tabla de Usuarios -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-[#0b2a40] text-white">
                                <th class="px-4 py-3 text-left text-[11px] font-bold tracking-widest uppercase">ID</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold tracking-widest uppercase">Nombre</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold tracking-widest uppercase">Email</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold tracking-widest uppercase">Perfil</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold tracking-widest uppercase">Asignaciones</th>
                                <th class="px-4 py-3 text-center text-[11px] font-bold tracking-widest uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users.data" :key="user.id" class="border-b border-[#e8eff4] hover:bg-[#eef5fa]">
                                <td class="px-4 py-3 text-sm font-bold text-[#0c1c28]">{{ user.id }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-[#0c1c28]">{{ user.name }}</td>
                                <td class="px-4 py-3 text-sm text-[#4e6070]">{{ user.email }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-bold bg-[#f4f7fa] text-[#4e6070] border border-[#d4dee8]">
                                        {{ user.profile?.title || 'Sin perfil' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-[#4e6070]">
                                    <div v-if="user.id_profile === 5 && user.work_centers?.length > 0" class="flex flex-wrap gap-1">
                                        <span v-for="wc in user.work_centers" :key="wc.id"
                                              class="px-2 py-0.5 rounded text-xs font-semibold bg-[#e4f5ec] text-[#0a7c3e] border border-[#aadcc4]">
                                            🏭 {{ wc.name }}
                                        </span>
                                    </div>
                                    <div v-else-if="user.id_profile === 8 && user.production_lines?.length > 0" class="flex flex-wrap gap-1">
                                        <span v-for="line in user.production_lines" :key="line.id"
                                              class="px-2 py-0.5 rounded text-xs font-semibold bg-[#fef3c7] text-[#92400e] border border-[#fde68a]">
                                            📊 {{ line.title }}
                                        </span>
                                    </div>
                                    <span v-else class="text-xs text-[#6a8090]">Sin asignaciones</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link :href="route('admin.users.edit', user.id)" 
                                              class="px-3 py-1 bg-[#174060] text-white rounded text-xs font-bold hover:opacity-85">
                                            ✏️ Editar
                                        </Link>
                                        <button @click="deleteUser(user)"
                                                class="px-3 py-1 bg-[#fce9e8] text-[#ba2418] border border-[#ebbab8] rounded text-xs font-bold hover:bg-[#ba2418] hover:text-white">
                                            🗑️ Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-[#6a8090]">
                                    No hay usuarios registrados. Importa usuarios desde la base de datos italianet_users.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación -->
                <div v-if="users.links.length > 3" class="px-4 py-3 border-t border-[#d4dee8]">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-[#6a8090]">
                            Mostrando {{ users.from }} a {{ users.to }} de {{ users.total }} usuarios
                        </div>
                        <div class="flex gap-1">
                            <Link v-for="(link, index) in users.links" :key="index"
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
    </AuthenticatedLayout>
</template>
