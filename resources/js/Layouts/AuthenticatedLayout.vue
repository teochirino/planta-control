<script setup>
import { usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import NavLink from '@/Components/NavLink.vue';
import { Link } from '@inertiajs/vue3';

const page = usePage();
const sidebarCollapsed = ref(false);

// Depuración - ver qué hay en las props
console.log('Page props:', page.props);
console.log('Auth:', page.props.auth);
console.log('User:', page.props.auth?.user);

const toggleSidebar = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-100">
        <!-- Sidebar -->
        <aside 
            class="bg-[#0b2a40] text-white flex flex-col fixed h-full transition-all duration-300"
            :class="sidebarCollapsed ? 'w-16' : 'w-64'"
        >
            <!-- Logo & Toggle Button -->
            <div class="p-4 border-b border-[#174060] flex items-center justify-between">
                <Link :href="route('dashboard')" class="flex items-center gap-3">
                    <ApplicationLogo class="block h-8 w-auto fill-current text-white" />
                    <span v-if="!sidebarCollapsed" class="text-xl font-bold">Planta Control</span>
                </Link>
                <button 
                    @click="toggleSidebar"
                    class="p-2 rounded-lg hover:bg-[#174060] transition-colors"
                >
                    <svg v-if="sidebarCollapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                    </svg>
                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                    </svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 p-4 space-y-2">
                <NavLink
                    :href="route('dashboard')"
                    :active="route().current('dashboard')"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors hover:bg-[#174060]"
                >
                    🏠
                    <span v-if="!sidebarCollapsed">Dashboard</span>
                </NavLink>

                <!-- Ajustes de Producción (para Supervisor e Ingeniero de Procesos) -->
                <NavLink
                    v-if="$page.props.auth.user.id_profile === 5 || $page.props.auth.user.id_profile === 2"
                    :href="route('supervisor.register-adjustments')"
                    :active="route().current('supervisor.register-adjustments')"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors hover:bg-[#174060]"
                >
                    ✏️
                    <span v-if="!sidebarCollapsed">Registrar Ajustes</span>
                </NavLink>

                <NavLink
                    v-if="$page.props.auth.user.id_profile === 5 || $page.props.auth.user.id_profile === 2"
                    :href="route('supervisor.production-adjustments')"
                    :active="route().current('supervisor.production-adjustments')"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors hover:bg-[#174060]"
                >
                    📜
                    <span v-if="!sidebarCollapsed">Historial de Ajustes</span>
                </NavLink>

                <NavLink
                    v-if="$page.props.auth.user.id_profile <= 2"
                    :href="route('permisos')"
                    :active="route().current('permisos')"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors hover:bg-[#174060]"
                >
                    🔐
                    <span v-if="!sidebarCollapsed">Administrar Permisos</span>
                </NavLink>
            </nav>

            <!-- User Info & Logout -->
            <div class="p-4 border-t border-[#174060]">
                <div v-if="!sidebarCollapsed" class="mb-3 px-4">
                    <div class="text-sm font-medium">{{ $page.props.auth.user.name }}</div>
                    <div class="text-xs text-gray-400">{{ $page.props.auth.user.email }}</div>
                </div>
                <NavLink
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors hover:bg-[#ba2418] w-full"
                >
                    🚪
                    <span v-if="!sidebarCollapsed">Cerrar Sesión</span>
                </NavLink>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 transition-all duration-300" :class="sidebarCollapsed ? 'ml-16' : 'ml-64'">
            <!-- Page Heading -->
            <header
                class="bg-white shadow"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>