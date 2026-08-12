<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';

const props = defineProps({
    italianetUsers: Object,
    profiles: Array,
    workCenters: Array,
    productionLines: Array,
    search: String,
});

const searchValue = ref(props.search || '');

const importForm = useForm({
    user_main_id: null,
    id_profile: '',
    work_centers: [],
    production_lines: [],
});

const showModal = ref(false);
const selectedUser = ref(null);

const showWorkCenters = computed(() => importForm.id_profile === 5);
const showProductionLines = computed(() => importForm.id_profile === 8);

const openImportModal = (user) => {
    selectedUser.value = user;
    importForm.user_main_id = user.id;
    importForm.id_profile = '';
    importForm.work_centers = [];
    importForm.production_lines = [];
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedUser.value = null;
    importForm.reset();
};

const submitImport = () => {
    importForm.post(route('admin.users.import.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
        },
    });
};

// Custom debounce function
const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

const performSearch = () => {
    router.get(route('admin.users.import'), { search: searchValue.value }, {
        preserveScroll: true,
        preserveState: true,
    });
};

watch(searchValue, debounce(performSearch, 300));

const clearSearch = () => {
    searchValue.value = '';
};
</script>

<template>
    <Head title="Importar Usuario" />

    <AdminLayout>
        <AdminSidebar />
        
        <div class="flex flex-col gap-2.5 p-6 ml-16">
            <!-- Header -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <div class="px-4 py-3 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold tracking-widest uppercase text-[#174060]">Módulo Administrador</span>
                        <h1 class="text-2xl font-extrabold text-[#0b2a40] leading-none">Importar Usuario</h1>
                        <div class="text-sm text-[#4e6070] font-semibold mt-1">Desde base de datos italianet_users</div>
                    </div>
                    
                    <Link :href="route('admin.users.index')" 
                          class="px-4 py-2 bg-[#f4f7fa] text-[#4e6070] border border-[#d4dee8] rounded-md text-xs font-bold hover:bg-[#e8edf2]">
                        ← Volver
                    </Link>
                </div>
            </div>
            
            <!-- Tabla de Usuarios -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-[#d4dee8]">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-extrabold text-[#0b2a40]">Usuarios Disponibles</h2>
                            <p class="text-xs text-[#6a8090] mt-1">Selecciona un usuario para importarlo al sistema de control de planta</p>
                        </div>
                        
                        <div class="flex gap-2">
                            <div class="relative">
                                <input v-model="searchValue"
                                       type="text" 
                                       placeholder="Buscar por nombre o email..."
                                       class="px-4 py-2 pr-10 border border-[#d4dee8] rounded-md text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#174060] w-80">
                                <button v-if="searchValue" 
                                        type="button"
                                        @click="clearSearch"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-[#6a8090] hover:text-[#0b2a40]">
                                    ✕
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-[#0b2a40] text-white">
                                <th class="px-4 py-3 text-left text-[11px] font-bold tracking-widest uppercase">ID</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold tracking-widest uppercase">Nombre</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold tracking-widest uppercase">Email</th>
                                <th class="px-4 py-3 text-center text-[11px] font-bold tracking-widest uppercase">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in italianetUsers.data" :key="user.id" class="border-b border-[#e8eff4] hover:bg-[#eef5fa]">
                                <td class="px-4 py-3 text-sm font-bold text-[#0c1c28]">{{ user.id }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-[#0c1c28]">{{ user.name }}</td>
                                <td class="px-4 py-3 text-sm text-[#4e6070]">{{ user.email }}</td>
                                <td class="px-4 py-3 text-center">
                                    <button @click="openImportModal(user)"
                                            class="px-4 py-1.5 bg-[#0b2a40] text-white rounded text-xs font-bold hover:opacity-85">
                                        ➕ Importar
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!italianetUsers.data || italianetUsers.data.length === 0">
                                <td colspan="4" class="px-4 py-8 text-center text-[#6a8090]">
                                    No hay usuarios disponibles en italianet_users
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación -->
                <div v-if="italianetUsers.links && italianetUsers.links.length > 3" class="px-4 py-3 border-t border-[#d4dee8]">
                    <div class="flex gap-1">
                        <template v-for="(link, index) in italianetUsers.links" :key="index">
                            <Link v-if="link.url"
                                  :href="link.url"
                                  :class="[
                                      'px-3 py-1 text-xs font-bold rounded',
                                      link.active 
                                          ? 'bg-[#0b2a40] text-white' 
                                          : 'bg-white text-[#4e6070] border border-[#d4dee8] hover:bg-[#f4f7fa]',
                                  ]"
                                  v-html="link.label">
                            </Link>
                            <span v-else
                                  class="px-3 py-1 text-xs font-bold rounded bg-white text-[#4e6070] border border-[#d4dee8] opacity-50 cursor-not-allowed"
                                  v-html="link.label">
                            </span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Importación -->
        <div v-if="showModal" class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl">
                <div class="px-6 py-4 border-b border-[#d4dee8]">
                    <h3 class="text-lg font-extrabold text-[#0b2a40]">Importar Usuario</h3>
                    <p v-if="selectedUser" class="text-sm text-[#6a8090] mt-1">
                        {{ selectedUser.name }} ({{ selectedUser.email }})
                    </p>
                </div>
                
                <form @submit.prevent="submitImport" class="p-6">
                    <div class="mb-4 p-3 bg-[#eef2ff] border border-[#c7d2fe] rounded-md">
                        <div class="flex items-start gap-2">
                            <span class="text-lg">🔑</span>
                            <div>
                                <p class="text-xs font-bold text-[#3730a3]">Inicio de sesión</p>
                                <p class="text-xs text-[#3730a3] mt-1">
                                    El usuario iniciará sesión con la misma contraseña que ya usa en el sistema de italianet — no se le asigna ninguna contraseña nueva aquí.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Perfil de Usuario</label>
                            <select v-model="importForm.id_profile" required
                                    class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1">
                                <option value="">Seleccione un perfil</option>
                                <option v-for="profile in profiles" :key="profile.id_profile" :value="profile.id_profile">
                                    {{ profile.title }}
                                </option>
                            </select>
                        </div>
                        
                        <div v-if="showWorkCenters">
                            <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Centros de Trabajo</label>
                            <p class="text-xs text-[#6a8090] mb-2">Selecciona los centros que este supervisor podrá gestionar</p>
                            <div class="grid grid-cols-2 gap-2 max-h-60 overflow-y-auto p-3 border border-[#d4dee8] rounded-md bg-[#f4f7fa]">
                                <label v-for="wc in workCenters" :key="wc.id" class="flex items-center gap-2 p-2 hover:bg-white rounded cursor-pointer">
                                    <input v-model="importForm.work_centers" 
                                           type="checkbox" 
                                           :value="wc.id"
                                           class="w-4 h-4 text-[#0b2a40] border-[#d4dee8] rounded focus:ring-[#174060]">
                                    <span class="text-sm font-semibold text-[#0c1c28]">{{ wc.name }}</span>
                                </label>
                            </div>
                        </div>
                        
                        <div v-if="showProductionLines">
                            <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Líneas de Producción</label>
                            <p class="text-xs text-[#6a8090] mb-2">Selecciona las líneas que este operador podrá gestionar</p>
                            <div class="grid grid-cols-2 gap-2 max-h-60 overflow-y-auto p-3 border border-[#d4dee8] rounded-md bg-[#f4f7fa]">
                                <label v-for="line in productionLines" :key="line.id" class="flex items-center gap-2 p-2 hover:bg-white rounded cursor-pointer">
                                    <input v-model="importForm.production_lines" 
                                           type="checkbox" 
                                           :value="line.id"
                                           class="w-4 h-4 text-[#0b2a40] border-[#d4dee8] rounded focus:ring-[#174060]">
                                    <span class="text-sm font-semibold text-[#0c1c28]">{{ line.title }}</span>
                                    <span v-if="line.work_center" class="text-xs text-[#6a8090]">({{ line.work_center.name }})</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex gap-2 justify-end">
                        <button type="button" @click="closeModal"
                                class="px-4 py-2 bg-[#f4f7fa] text-[#4e6070] border border-[#d4dee8] rounded-md text-xs font-bold hover:bg-[#e8edf2]">
                            Cancelar
                        </button>
                        <button type="submit" 
                                :disabled="importForm.processing"
                                class="px-4 py-2 bg-[#0b2a40] text-white rounded-md text-xs font-bold hover:opacity-85 disabled:opacity-50">
                            Importar Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
