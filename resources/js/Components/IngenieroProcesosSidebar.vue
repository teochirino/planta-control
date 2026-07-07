<template>
    <div>
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
                        <h2 class="text-xl font-bold text-white">Ingeniero de Procesos</h2>
                        <p class="text-sm mt-1" style="color: #6a8090;">Gestión de Programas</p>
                    </div>

                    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                        <!-- Sección Programas -->
                        <div class="mb-4">
                            <p class="text-xs font-semibold uppercase tracking-wider mb-2 px-4" style="color: #6a8090;">Programas</p>
                            <Link 
                                :href="route('ingeniero-procesos.index')" 
                                class="sidebar-link"
                                :class="{ 'active': route().current('ingeniero-procesos.index') }"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <span>Programas</span>
                            </Link>
                            <Link 
                                :href="route('ingeniero-procesos.create')" 
                                class="sidebar-link"
                                :class="{ 'active': route().current('ingeniero-procesos.create') }"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Nuevo Programa</span>
                            </Link>
                            <Link 
                                :href="route('ingeniero-procesos.import.products')" 
                                class="sidebar-link"
                                :class="{ 'active': route().current('ingeniero-procesos.import.products') }"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <span>Importar Excel</span>
                            </Link>
                        </div>

                        <!-- Sección Productos -->
                        <div class="mb-4">
                            <p class="text-xs font-semibold uppercase tracking-wider mb-2 px-4" style="color: #6a8090;">Productos</p>
                            <Link 
                                :href="route('ingeniero-procesos.products.index')" 
                                class="sidebar-link"
                                :class="{ 'active': route().current('ingeniero-procesos.products.index') }"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <span>Productos</span>
                            </Link>
                            <Link 
                                :href="route('ingeniero-procesos.products.create')" 
                                class="sidebar-link"
                                :class="{ 'active': route().current('ingeniero-procesos.products.create') }"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Nuevo Producto</span>
                            </Link>
                            <Link 
                                :href="route('ingeniero-procesos.export.products')" 
                                class="sidebar-link"
                                :class="{ 'active': route().current('ingeniero-procesos.export.products') }"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <span>Exportar Productos</span>
                            </Link>
                        </div>

                        <!-- Sección Líneas de Producción -->
                        <div class="mb-4">
                            <p class="text-xs font-semibold uppercase tracking-wider mb-2 px-4" style="color: #6a8090;">Líneas de Producción</p>
                            <Link 
                                :href="route('ingeniero-procesos.production-lines.index')" 
                                class="sidebar-link"
                                :class="{ 'active': route().current('ingeniero-procesos.production-lines.index') }"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                <span>Líneas de Producción</span>
                            </Link>
                            <Link 
                                :href="route('ingeniero-procesos.production-lines.create')" 
                                class="sidebar-link"
                                :class="{ 'active': route().current('ingeniero-procesos.production-lines.create') }"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Nueva Línea</span>
                            </Link>
                        </div>

                        <!-- Sección Centros de Trabajo -->
                        <div class="mb-4">
                            <p class="text-xs font-semibold uppercase tracking-wider mb-2 px-4" style="color: #6a8090;">Centros de Trabajo</p>
                            <Link 
                                :href="route('ingeniero-procesos.work-centers.index')" 
                                class="sidebar-link"
                                :class="{ 'active': route().current('ingeniero-procesos.work-centers.index') }"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span>Centros de Trabajo</span>
                            </Link>
                            <Link 
                                :href="route('ingeniero-procesos.work-centers.create')" 
                                class="sidebar-link"
                                :class="{ 'active': route().current('ingeniero-procesos.work-centers.create') }"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Nuevo Centro</span>
                            </Link>
                        </div>

                        <!-- Sección Ajustes -->
                        <div class="mb-4">
                            <p class="text-xs font-semibold uppercase tracking-wider mb-2 px-4" style="color: #6a8090;">Ajustes</p>
                            <Link 
                                :href="route('ingeniero-procesos.production-adjustments')" 
                                class="sidebar-link"
                                :class="{ 'active': route().current('ingeniero-procesos.production-adjustments') }"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                <span>Historial de Ajustes</span>
                            </Link>
                            <Link 
                                :href="route('ingeniero-procesos.register-adjustments')" 
                                class="sidebar-link"
                                :class="{ 'active': route().current('ingeniero-procesos.register-adjustments') }"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span>Registrar Ajustes</span>
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
