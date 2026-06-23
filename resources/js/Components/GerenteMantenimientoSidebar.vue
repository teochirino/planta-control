<template>
    <div>
        <button 
            @click="toggleSidebar" 
            class="fixed top-4 left-4 z-50 p-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors shadow-lg"
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
                class="fixed inset-y-0 left-0 w-64 bg-gray-800 shadow-xl z-40 transform transition-transform duration-300"
            >
                <div class="flex flex-col h-full">
                    <div class="p-6 border-b border-gray-700">
                        <h2 class="text-xl font-bold text-white">Gerente de Mantenimiento</h2>
                        <p class="text-sm text-gray-400 mt-1">Panel de Control</p>
                    </div>

                    <nav class="flex-1 px-4 py-6 space-y-2">
                        <Link 
                            :href="route('gerente-mantenimiento.dashboard')" 
                            class="sidebar-link"
                            :class="{ 'active': route().current('gerente-mantenimiento.dashboard') }"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span>Dashboard</span>
                        </Link>

                        <Link 
                            :href="route('gerente-mantenimiento.machines')" 
                            class="sidebar-link"
                            :class="{ 'active': route().current('gerente-mantenimiento.machines') }"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                            </svg>
                            <span>Estado de Máquinas</span>
                        </Link>

                        <Link 
                            :href="route('gerente-mantenimiento.reports')" 
                            class="sidebar-link"
                            :class="{ 'active': route().current('gerente-mantenimiento.reports') }"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Reportes</span>
                        </Link>
                    </nav>

                    <div class="p-4 border-t border-gray-700">
                        <Link 
                            :href="route('logout')" 
                            method="post" 
                            as="button"
                            class="sidebar-link w-full text-red-400 hover:bg-red-900/20"
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
            class="fixed inset-0 bg-black bg-opacity-50 z-30 transition-opacity duration-300"
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
    @apply flex items-center gap-3 px-4 py-3 text-gray-300 rounded-lg transition-colors hover:bg-gray-700 hover:text-white;
}

.sidebar-link.active {
    @apply bg-orange-600 text-white;
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
