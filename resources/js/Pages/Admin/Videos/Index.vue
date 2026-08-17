<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';
import { ref, onMounted } from 'vue';

const videos = ref([]);
const loading = ref(false);
const showForm = ref(false);
const editingVideo = ref(null);
const formData = ref({
    nombre: '',
    video: null,
    hora_reproduccion: '',
    dias_semana: [],
    activo: true,
});

const daysOfWeek = [
    { value: 0, label: 'Domingo' },
    { value: 1, label: 'Lunes' },
    { value: 2, label: 'Martes' },
    { value: 3, label: 'Miércoles' },
    { value: 4, label: 'Jueves' },
    { value: 5, label: 'Viernes' },
    { value: 6, label: 'Sábado' },
];

const loadVideos = async () => {
    loading.value = true;
    try {
        const response = await window.axios.get('/api/videos-programados');
        videos.value = response.data;
    } catch (error) {
        console.error('Error loading videos:', error);
    } finally {
        loading.value = false;
    }
};

const openCreateForm = () => {
    editingVideo.value = null;
    formData.value = {
        nombre: '',
        video: null,
        hora_reproduccion: '',
        dias_semana: [],
        activo: true,
    };
    showForm.value = true;
};

const openEditForm = (video) => {
    editingVideo.value = video;
    formData.value = {
        nombre: video.nombre,
        video: null,
        hora_reproduccion: video.hora_reproduccion ? video.hora_reproduccion.substring(0, 5) : '',
        dias_semana: video.dias_semana || [],
        activo: video.activo,
    };
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    editingVideo.value = null;
    formData.value = {
        nombre: '',
        video: null,
        hora_reproduccion: '',
        dias_semana: [],
        activo: true,
    };
};

const handleFileChange = (event) => {
    formData.value.video = event.target.files[0];
};

const toggleDay = (dayValue) => {
    const index = formData.value.dias_semana.indexOf(dayValue);
    if (index > -1) {
        formData.value.dias_semana.splice(index, 1);
    } else {
        formData.value.dias_semana.push(dayValue);
    }
};

const saveVideo = async () => {
    console.log('Saving video with data:', formData.value);
    
    const data = new FormData();
    data.append('_method', editingVideo.value ? 'PUT' : 'POST');
    data.append('nombre', formData.value.nombre);
    data.append('hora_reproduccion', formData.value.hora_reproduccion);
    data.append('dias_semana', JSON.stringify(formData.value.dias_semana));
    data.append('activo', formData.value.activo ? '1' : '0');
    
    if (formData.value.video) {
        data.append('video', formData.value.video);
    }

    try {
        if (editingVideo.value) {
            console.log('Updating video ID:', editingVideo.value.id);
            await window.axios.post(`/api/videos-programados/${editingVideo.value.id}`, data, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        } else {
            await window.axios.post('/api/videos-programados', data, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        }
        closeForm();
        loadVideos();
    } catch (error) {
        console.error('Error saving video:', error);
        alert('Error al guardar el video');
    }
};

const deleteVideo = async (video) => {
    if (confirm(`¿Estás seguro de eliminar el video "${video.nombre}"?`)) {
        try {
            await window.axios.delete(`/api/videos-programados/${video.id}`);
            loadVideos();
        } catch (error) {
            console.error('Error deleting video:', error);
            alert('Error al eliminar el video');
        }
    }
};

const toggleActive = async (video) => {
    try {
        await window.axios.put(`/api/videos-programados/${video.id}`, {
            activo: !video.activo
        });
        loadVideos();
    } catch (error) {
        console.error('Error toggling video:', error);
    }
};

const getDayNames = (days) => {
    if (!days || days.length === 0) return 'Sin días';
    return days.map(day => daysOfWeek.find(d => d.value === day)?.label).join(', ');
};

const getVideoUrl = (path) => {
    return `/storage/${path}`;
};

onMounted(() => {
    loadVideos();
});
</script>

<template>
    <Head title="Gestión de Videos RRHH" />

    <AdminLayout>
        <div class="flex h-screen bg-gray-100">
            <AdminSidebar />
            
            <div class="flex-1 overflow-auto p-8">
                <div class="max-w-7xl mx-auto">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-3xl font-bold text-gray-900">Gestión de Videos RRHH</h1>
                        <button
                            @click="openCreateForm"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
                        >
                            + Nuevo Video
                        </button>
                    </div>

                    <!-- Loading state -->
                    <div v-if="loading" class="text-center py-8">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        <p class="mt-2 text-gray-600">Cargando videos...</p>
                    </div>

                    <!-- Videos list -->
                    <div v-else class="bg-white rounded-lg shadow overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Nombre
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Hora Reproducción
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Días
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estado
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="videos.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        No hay videos programados
                                    </td>
                                </tr>
                                <tr v-for="video in videos" :key="video.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ video.nombre }}</div>
                                        <div class="text-sm text-gray-500">
                                            <a :href="getVideoUrl(video.ruta_video)" target="_blank" class="text-blue-600 hover:text-blue-800">
                                                Ver video
                                            </a>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ video.hora_reproduccion }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ getDayNames(video.dias_semana) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button
                                            @click="toggleActive(video)"
                                            :class="video.activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                            class="px-2 py-1 rounded-full text-xs font-medium"
                                        >
                                            {{ video.activo ? 'Activo' : 'Inactivo' }}
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button
                                            @click="openEditForm(video)"
                                            class="text-blue-600 hover:text-blue-900 mr-3"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            @click="deleteVideo(video)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Eliminar
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Modal -->
        <div v-if="showForm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-bold text-gray-900">
                            {{ editingVideo ? 'Editar Video' : 'Nuevo Video' }}
                        </h2>
                        <button
                            @click="closeForm"
                            class="text-gray-400 hover:text-gray-600"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="saveVideo" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Nombre del Video
                            </label>
                            <input
                                v-model="formData.nombre"
                                type="text"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Archivo de Video
                            </label>
                            <input
                                type="file"
                                accept="video/*"
                                @change="handleFileChange"
                                :required="!editingVideo"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                            <p v-if="editingVideo" class="text-sm text-gray-500 mt-1">
                                Deja vacío para mantener el video actual
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Hora de Reproducción
                            </label>
                            <input
                                v-model="formData.hora_reproduccion"
                                type="time"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Días de la Semana
                            </label>
                            <div class="grid grid-cols-4 gap-2">
                                <button
                                    v-for="day in daysOfWeek"
                                    :key="day.value"
                                    type="button"
                                    @click="toggleDay(day.value)"
                                    :class="formData.dias_semana.includes(day.value) 
                                        ? 'bg-blue-600 text-white' 
                                        : 'bg-gray-200 text-gray-700'"
                                    class="px-3 py-2 rounded-lg text-sm font-medium transition"
                                >
                                    {{ day.label }}
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <input
                                v-model="formData.activo"
                                type="checkbox"
                                id="activo"
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            />
                            <label for="activo" class="ml-2 text-sm text-gray-700">
                                Video activo
                            </label>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <button
                                type="button"
                                @click="closeForm"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                            >
                                {{ editingVideo ? 'Actualizar' : 'Guardar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
