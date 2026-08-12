<template>
    <div>
        <ImpersonationBanner />
        <button
            @click="toggleSidebar" 
            class="fixed top-4 left-4 z-50 p-2 rounded-lg shadow-lg transition-colors"
            style="background: #0b2a40; color: #fff;"
            :class="{ 'left-64': isOpen }"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path v-if="!isOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <transition name="sidebar">
            <div 
                v-if="isOpen" 
                class="fixed inset-y-0 left-0 w-64 shadow-xl z-40 transform transition-transform duration-300"
                style="background: #0b2a40;"
            >
                <div class="flex flex-col h-full">
                    <div class="p-6 border-b" style="border-color: #174060;">
                        <h2 class="text-xl font-bold text-white">Administrador</h2>
                        <p class="text-sm mt-1" style="color: #6a8090;">Gestión del Sistema</p>
                    </div>

                    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                        <!-- Sección Usuarios -->
                        <div class="mb-4">
                            <p class="text-xs font-semibold uppercase tracking-wider mb-2 px-4" style="color: #6a8090;">Usuarios</p>
                            <Link 
                                :href="route('admin.users.index')" 
                                class="sidebar-link"
                                :class="{ 'active': route().current('admin.users.index') }"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span>Gestión de Usuarios</span>
                            </Link>
                            <Link 
                                :href="route('admin.users.import')" 
                                class="sidebar-link"
                                :class="{ 'active': route().current('admin.users.import') }"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <span>Importar Usuarios</span>
                            </Link>
                        </div>

                        <!-- Sección Asignaciones -->
                        <div class="mb-4">
                            <p class="text-xs font-semibold uppercase tracking-wider mb-2 px-4" style="color: #6a8090;">Asignaciones</p>
                            <Link 
                                :href="route('admin.work-centers.assign')" 
                                class="sidebar-link"
                                :class="{ 'active': route().current('admin.work-centers.assign') }"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span>Asignar Centros</span>
                            </Link>
                            <Link 
                                :href="route('admin.production-lines.assign')" 
                                class="sidebar-link"
                                :class="{ 'active': route().current('admin.production-lines.assign') }"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                                </svg>
                                <span>Asignar Líneas</span>
                            </Link>
                        </div>

                        <!-- Sección Notificaciones -->
                        <div class="mb-4">
                            <p class="text-xs font-semibold uppercase tracking-wider mb-2 px-4" style="color: #6a8090;">Notificaciones</p>
                            <Link 
                                :href="route('admin.notification-recipients.index')" 
                                class="sidebar-link"
                                :class="{ 'active': route().current('admin.notification-recipients.*') }"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span>Destinatarios</span>
                            </Link>
                        </div>

                        <!-- Sección RRHH -->
                        <div class="mb-4">
                            <p class="text-xs font-semibold uppercase tracking-wider mb-2 px-4" style="color: #6a8090;">RRHH</p>
                            <Link 
                                :href="route('admin.videos.index')" 
                                class="sidebar-link"
                                :class="{ 'active': route().current('admin.videos.index') }"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <span>Videos Programados</span>
                            </Link>
                        </div>
                    </nav>

                    <div class="p-4 border-t" style="border-color: #174060;">
                        <Link 
                            :href="route('logout')" 
                            method="post" 
                            as="button"
                            class="sidebar-link w-full"
                            style="color: #fce9e8;"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span>Cerrar Sesión</span>
                        </Link>
                    </div>
                </div>
            </div>
        </transition>

        <div 
            v-if="isOpen" 
            @click="closeSidebar" 
            class="fixed inset-0 z-30 transition-opacity duration-300"
            style="background: rgba(11, 28, 40, 0.5);"
        ></div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import ImpersonationBanner from '@/Components/ImpersonationBanner.vue'

const isOpen = ref(false)

const toggleSidebar = () => {
    isOpen.value = !isOpen.value
}

const closeSidebar = () => {
    isOpen.value = false
}
</script>

<style scoped>
.sidebar-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    color: #eaf0f5;
    border-radius: 10px;
    transition: all 0.15s ease;
    font-weight: 600;
    font-size: 13px;
}

.sidebar-link:hover {
    background: #174060;
    color: #fff;
}

.sidebar-link.active {
    background: #0a7c3e;
    color: #fff;
}

.sidebar-enter-active,
.sidebar-leave-active {
    transition: transform 0.3s ease;
}

.sidebar-enter-from {
    transform: translateX(-100%);
}

.sidebar-leave-to {
    transform: translateX(-100%);
}
</style>
