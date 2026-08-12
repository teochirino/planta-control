<!-- resources/js/Components/ImpersonationBanner.vue -->
<template>
    <div v-if="isImpersonating" class="fixed top-4 right-4 z-[60] flex items-center gap-2 text-xs">
        <span class="text-[#92400e] whitespace-nowrap">Suplantando a <strong>{{ userName }}</strong></span>
        <button
            @click="leaveImpersonation"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-[#d4a86a] text-[#92400e] rounded-full text-xs font-bold shadow-sm hover:bg-[#fff8ee] transition-colors whitespace-nowrap"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0L4.586 11H16a1 1 0 100-2H4.586l3.707-3.707a1 1 0 00-1.414-1.414l-5 5a1 1 0 000 1.414l5 5a1 1 0 001.414-1.414z" clip-rule="evenodd" />
            </svg>
            Volver a Administrador
        </button>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const isImpersonating = computed(() => page.props.impersonating);
const userName = computed(() => page.props.auth?.user?.name || '');

const leaveImpersonation = () => {
    // Navegación normal (no router.visit de Inertia): esta acción cambia el usuario
    // autenticado a mitad de camino, e Inertia no sigue bien esa cadena de redirecciones.
    window.location.href = route('impersonate.leave');
};
</script>
