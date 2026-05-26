<template>
    <div class="w-64 bg-gray-900 min-h-screen">
        <div class="p-4">
            <h2 class="text-xl font-bold text-white mb-6">Ingeniero de Procesos</h2>
            <nav class="space-y-2">
                <Link :href="route('ingeniero-procesos.index')" 
                      class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg transition">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Programas
                </Link>
                <Link :href="route('ingeniero-procesos.create')" 
                      class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg transition">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Nuevo Programa
                </Link>
                <Link :href="route('ingeniero-procesos.products.index')" 
                      class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg transition">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    Productos
                </Link>
                <Link :href="route('ingeniero-procesos.products.create')" 
                      class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg transition">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Nuevo Producto
                </Link>
                <Link :href="route('ingeniero-procesos.import.products')" 
                      class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg transition">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    Importar Excel
                </Link>
            </nav>
        </div>
        <div class="absolute bottom-0 w-64 p-4">
            <button @click="logout" class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                Cerrar Sesión
            </button>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';

function logout() {
    router.post(route('logout'));
}
</script>
