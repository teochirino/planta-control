<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <IngenieroProcesosSidebar />
        
        <div class="p-6 ml-16">
            <div class="mb-6">
                <h1 class="text-3xl font-bold" style="color: #0b2a40;">Exportar Productos a Excel</h1>
                <p class="mt-2" style="color: #6a8090;">Descarga un archivo Excel con todos los productos de la base de datos.</p>
            </div>
            
            <!-- Información de exportación -->
            <div class="rounded-lg p-6 mb-6" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                <h2 class="text-xl font-bold mb-4" style="color: #0b2a40;">Información de la Exportación</h2>
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="rounded-lg p-4" style="background: #f4f7fa; border: 1px solid #e8eff4;">
                        <p class="text-sm font-semibold mb-2" style="color: #6a8090;">Columnas del Excel:</p>
                        <ul class="text-sm space-y-1" style="color: #0c1c28;">
                            <li>• Modelo</li>
                            <li>• id_centro_trabajo</li>
                            <li>• Nombre Centro de trabajo</li>
                            <li>• Tiempo</li>
                            <li>• Piezas</li>
                        </ul>
                    </div>
                    <div class="rounded-lg p-4" style="background: #f4f7fa; border: 1px solid #e8eff4;">
                        <p class="text-sm font-semibold mb-2" style="color: #6a8090;">Ordenamiento:</p>
                        <p class="text-sm" style="color: #0c1c28;">Los productos están ordenados por ID de forma ascendente.</p>
                    </div>
                </div>
                
                <button 
                    @click="downloadExcel"
                    :disabled="downloading"
                    class="px-6 py-3 rounded-lg transition font-semibold text-sm disabled:opacity-50 flex items-center gap-2"
                    style="background: #0a7c3e; color: #fff;"
                >
                    <svg v-if="!downloading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ downloading ? 'Generando archivo...' : 'Descargar Excel' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';
import { ref } from 'vue';

const downloading = ref(false);

function downloadExcel() {
    downloading.value = true;
    
    // Crear un enlace temporal para descargar el archivo
    const link = document.createElement('a');
    link.href = route('ingeniero-procesos.export.products.download');
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Simular un pequeño delay para la UX
    setTimeout(() => {
        downloading.value = false;
    }, 1000);
}
</script>
