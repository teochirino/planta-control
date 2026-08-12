<template>
    <div>
        <ImpersonationBanner />
        <button
            @click="toggleSidebar" 
            class="fixed top-4 left-4 z-50 p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors shadow-lg"
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
                        <h2 class="text-xl font-bold text-white">Gerente de Producción</h2>
                        <p class="text-sm text-gray-400 mt-1">Panel de Control</p>
                    </div>

                    <nav class="flex-1 px-4 py-6 space-y-2">
                        <Link 
                            :href="route('gerencia.monitoreo')" 
                            class="sidebar-link"
                            :class="{ 'active': route().current('gerencia.monitoreo') }"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <span>Monitoreo General</span>
                        </Link>

                        <Link 
                            :href="route('gerente-produccion.machines')" 
                            class="sidebar-link"
                            :class="{ 'active': route().current('gerente-produccion.machines') }"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                            </svg>
                            <span>Estado de Máquinas</span>
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
    @apply flex items-center gap-3 px-4 py-3 text-gray-300 rounded-lg transition-colors hover:bg-gray-700 hover:text-white;
}

.sidebar-link.active {
    @apply bg-blue-600 text-white;
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
