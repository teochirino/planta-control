<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: String,
    label: {
        type: String,
        default: 'Turno',
    },
});

const emit = defineEmits(['update:modelValue']);

const value = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

const shifts = [
    { value: 'matutino', label: 'Matutino' },
    { value: 'vespertino', label: 'Vespertino' },
];
</script>

<template>
    <div>
        <label v-if="label" class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">
            {{ label }}
        </label>
        <select v-model="value"
                class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-bold text-[#0c1c28] bg-white focus:outline-none focus:border-[#174060]">
            <option v-for="shift in shifts" :key="shift.value" :value="shift.value">
                {{ shift.label }}
            </option>
        </select>
    </div>
</template>
