<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';
import { ref, watch } from 'vue';

const props = defineProps({
    users: Object,
    profiles: Object,
    filters: Object,
});

const profileFilter = ref(props.filters?.profile || null);
const search = ref(props.filters?.search || '');

// Custom debounce function
const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

watch(profileFilter, (value) => {
    router.get(route('admin.users.index'), { profile: value, search: search.value }, {
        preserveState: true,
        preserveScroll: true,
    });
});

watch(search, debounce((value) => {
    router.get(route('admin.users.index'), { profile: profileFilter.value, search: value }, {
        preserveState: true,
        preserveScroll: true,
    });
}, 300));

const deleteUser = (user) => {
    if (confirm(`¿Estás seguro de eliminar a ${user.name}?`)) {
        router.delete(route('admin.users.destroy', user.id), {
            preserveScroll: true,
        });
    }
};

const impersonate = (user) => {
    // Navegación normal (no router.visit de Inertia): esta acción cambia el usuario
    // autenticado a mitad de camino, e Inertia no sigue bien esa cadena de redirecciones.
    window.location.href = route('impersonate', user.id);
};
</script>

<template>
    <Head title="Gestión de Usuarios" />

    <AdminLayout>
        <AdminSidebar />
        
        <div class="flex flex-col gap-2.5 p-6 ml-16">
            <!-- Header -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <div class="px-4 py-3 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold tracking-widest uppercase text-[#174060]">Módulo Administrador</span>
                        <h1 class="text-2xl font-extrabold text-[#0b2a40] leading-none">Gestión de Usuarios</h1>
                    </div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <div class="px-4 py-3 flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-[#174060] uppercase tracking-widest">Filtrar por Perfil:</label>
                        <select v-model="profileFilter" class="px-3 py-1.5 text-sm border border-[#d4dee8] rounded-lg bg-white text-[#0c1c28] focus:outline-none focus:ring-2 focus:ring-[#174060]">
                            <option :value="null">Todos los perfiles</option>
                            <option v-for="profile in profiles" :key="profile.id_profile" :value="profile.id_profile">
                                {{ profile.title }}
                            </option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-[#174060] uppercase tracking-widest">Buscar por nombre:</label>
                        <input 
                            v-model="search" 
                            type="text" 
                            placeholder="Escribe para buscar..." 
                            class="px-3 py-1.5 text-sm border border-[#d4dee8] rounded-lg bg-white text-[#0c1c28] focus:outline-none focus:ring-2 focus:ring-[#174060] w-64"
                        >
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
                                              class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-[#e4f5ec] text-[#0a7c3e] border border-[#aadcc4]">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M4 3a1 1 0 011-1h4a1 1 0 011 1v3l3.4-1.7a.6.6 0 01.86.54V16a1 1 0 01-1 1H4a1 1 0 01-1-1V4a1 1 0 011-1zm2 3h1v1H6V6zm0 3h1v1H6V9zm0 3h1v1H6v-1zm3-6h1v1H9V6zm0 3h1v1H9V9zm0 3h1v1H9v-1z" />
                                            </svg>
                                            {{ wc.name }}
                                        </span>
                                    </div>
                                    <div v-else-if="user.id_profile === 8 && user.production_lines?.length > 0" class="flex flex-wrap gap-1">
                                        <span v-for="line in user.production_lines" :key="line.id"
                                              class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-[#fef3c7] text-[#92400e] border border-[#fde68a]">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M2 11a1 1 0 011-1h1a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h1a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h1a1 1 0 011 1v12a1 1 0 01-1 1h-1a1 1 0 01-1-1V4z" />
                                            </svg>
                                            {{ line.title }}
                                        </span>
                                    </div>
                                    <span v-else class="text-xs text-[#6a8090]">Sin asignaciones</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <button v-if="user.id_profile !== 7"
                                                @click="impersonate(user)"
                                                title="Suplantar"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#92400e] text-white rounded-md text-xs font-bold shadow-sm hover:bg-[#78350f] transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                            </svg>
                                            Suplantar
                                        </button>
                                        <Link :href="route('admin.users.edit', user.id)"
                                              title="Editar"
                                              class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#174060] text-white rounded-md text-xs font-bold shadow-sm hover:bg-[#0f2c47] transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                            </svg>
                                            Editar
                                        </Link>
                                        <button @click="deleteUser(user)"
                                                title="Eliminar"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#fbe6e6] text-[#ba2418] border border-[#ebbab8] rounded-md text-xs font-bold shadow-sm hover:bg-[#ba2418] hover:text-white hover:border-[#ba2418] transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            Eliminar
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
    </AdminLayout>
</template>
