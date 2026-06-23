<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    productionLineId: Number,
    dailyProgramId: Number,
    date: String,
    routeName: String,
    machines: Array,
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    id_production_line: null,
    id_daily_program: null,
    date: null,
    start_time: '',
    end_time: '',
    description: '',
    id_machine: null,
});

watch(() => props.show, (newVal) => {
    if (newVal) {
        form.id_production_line = props.productionLineId;
        form.id_daily_program = props.dailyProgramId;
        form.date = props.date;
        
        const now = new Date();
        form.start_time = now.toTimeString().slice(0, 5);
        form.end_time = '';
        form.description = '';
        form.id_machine = null;
    }
});

const submit = () => {
    form.post(route(props.routeName), {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            emit('close');
            form.reset();
        },
    });
};

const close = () => {
    emit('close');
    form.reset();
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
            <div class="px-6 py-4 border-b border-[#d4dee8]">
                <h3 class="text-lg font-extrabold text-[#0b2a40]">Registrar Paro</h3>
            </div>
            
            <form @submit.prevent="submit" class="p-6 space-y-4">
                <div v-if="machines && machines.length > 0">
                    <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Máquina afectada (opcional)</label>
                    <select v-model="form.id_machine" 
                            class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1">
                        <option :value="null">No afecta a máquina específica</option>
                        <option v-for="machine in machines" :key="machine.id" :value="machine.id">
                            {{ machine.title }}
                        </option>
                    </select>
                </div>
                
                <div>
                    <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Hora de Inicio</label>
                    <input v-model="form.start_time" 
                           type="time" 
                           required
                           class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1">
                </div>
                
                <div>
                    <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Hora de Fin (opcional)</label>
                    <input v-model="form.end_time" 
                           type="time"
                           class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1">
                </div>
                
                <div>
                    <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Descripción</label>
                    <textarea v-model="form.description" 
                              required
                              rows="3"
                              class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1"></textarea>
                </div>
                
                <div class="flex gap-2 justify-end pt-4 border-t border-[#d4dee8]">
                    <button type="button" 
                            @click="close"
                            class="px-4 py-2 bg-[#f4f7fa] text-[#4e6070] border border-[#d4dee8] rounded-md text-xs font-bold hover:bg-[#e8edf2]">
                        Cancelar
                    </button>
                    <button type="submit" 
                            :disabled="form.processing"
                            class="px-4 py-2 bg-[#ba2418] text-white rounded-md text-xs font-bold hover:opacity-85 disabled:opacity-50">
                        Guardar Paro
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
