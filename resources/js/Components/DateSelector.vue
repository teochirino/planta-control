<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: String,
    label: {
        type: String,
        default: 'Fecha',
    },
});

const emit = defineEmits(['update:modelValue']);

const value = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});
</script>

<template>
    <div>
        <label v-if="label" class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">
            {{ label }}
        </label>
        <input v-model="value"
               type="date" 
               class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-bold text-[#0c1c28] focus:outline-none focus:border-[#174060]">
    </div>
</template>
