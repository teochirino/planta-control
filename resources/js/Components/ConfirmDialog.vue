<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black bg-opacity-50" @click="cancel"></div>
                
                <!-- Modal -->
                <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                    <div class="mb-4">
                        <h3 class="text-xl font-bold text-gray-900">{{ title }}</h3>
                        <p class="mt-2 text-gray-600">{{ message }}</p>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button
                            @click="cancel"
                            class="px-4 py-2 rounded-lg font-semibold text-gray-700 bg-gray-200 hover:bg-gray-300 transition"
                        >
                            Cancelar
                        </button>
                        <button
                            @click="confirm"
                            class="px-4 py-2 rounded-lg font-semibold text-white bg-red-600 hover:bg-red-700 transition"
                        >
                            {{ confirmText }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    show: Boolean,
    title: {
        type: String,
        default: 'Confirmar acción'
    },
    message: {
        type: String,
        default: '¿Estás seguro de realizar esta acción?'
    },
    confirmText: {
        type: String,
        default: 'Confirmar'
    }
});

const emit = defineEmits(['confirm', 'cancel']);

function confirm() {
    emit('confirm');
}

function cancel() {
    emit('cancel');
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
