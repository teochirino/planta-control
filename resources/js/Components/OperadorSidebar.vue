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
                        <h2 class="text-xl font-bold text-white">Operador de Planta</h2>
                        <p class="text-sm mt-1" style="color: #6a8090;">Gestión de Producción</p>
                    </div>

                    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                        <!-- Sección Principal -->
                        <div class="mb-4">
                            <p class="text-xs font-semibold uppercase tracking-wider mb-2 px-4" style="color: #6a8090;">Principal</p>
                            <Link 
                                :href="route('operador.dashboard')" 
                                class="sidebar-link"
                                :class="{ 'active': route().current('operador.dashboard') }"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1H2m10 6a1 1 0 001-1v-4a1 1 0 011-1h3a1 1 0 001 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                <span>Dashboard</span>
                            </Link>
                            <Link 
                                :href="route('operador.information-panel')" 
                                class="sidebar-link"
                                :class="{ 'active': route().current('operador.information-panel') }"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                <span>Panel de Información</span>
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
