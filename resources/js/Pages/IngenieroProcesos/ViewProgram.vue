<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <IngenieroProcesosSidebar />
        
        <div class="p-6 ml-16">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold" style="color: #0b2a40;">Programa {{ program.codigo }}</h1>
                    <p class="mt-1" style="color: #6a8090;">Creado por {{ program.creator?.name }}</p>
                </div>
                <Link :href="route('ingeniero-procesos.index')" 
                      class="px-4 py-2 rounded-lg transition font-semibold text-sm"
                      style="background: #fff; color: #0b2a40; border: 1px solid #d4dee8;">
                    Volver
                </Link>
            </div>
            
            <!-- Fechas de Fases -->
            <div class="grid grid-cols-4 gap-4 mb-6">
                <div class="rounded-lg p-4" style="background: #fff; border: 1px solid #d4dee8;">
                    <h3 class="text-sm font-semibold uppercase mb-2" style="color: #4e6070; letter-spacing: 0.1em;">Fase 1</h3>
                    <p class="font-semibold" style="color: #0b2a40;">{{ program.fecha_fase1_formatted }}</p>
                </div>
                <div class="rounded-lg p-4" style="background: #fff; border: 1px solid #d4dee8;">
                    <h3 class="text-sm font-semibold uppercase mb-2" style="color: #4e6070; letter-spacing: 0.1em;">Fase 2</h3>
                    <p class="font-semibold" style="color: #0b2a40;">{{ program.fecha_fase2_formatted }}</p>
                </div>
                <div class="rounded-lg p-4" style="background: #fff; border: 1px solid #d4dee8;">
                    <h3 class="text-sm font-semibold uppercase mb-2" style="color: #4e6070; letter-spacing: 0.1em;">Fase 3</h3>
                    <p class="font-semibold" style="color: #0b2a40;">{{ program.fecha_fase3_formatted }}</p>
                </div>
                <div class="rounded-lg p-4" style="background: #fff; border: 1px solid #d4dee8;">
                    <h3 class="text-sm font-semibold uppercase mb-2" style="color: #4e6070; letter-spacing: 0.1em;">Fase 4 (Entrega)</h3>
                    <p class="font-semibold" style="color: #0b2a40;">{{ program.fecha_fase4_formatted }}</p>
                </div>
            </div>
            
            <!-- Tabla por Fases -->
            <div v-for="(phaseDetails, phase) in details" :key="phase" class="mb-6">
                <h2 class="text-xl font-bold mb-4" style="color: #0b2a40;">Fase {{ phase }}</h2>
                
                <div class="rounded-lg overflow-hidden" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                    <table class="w-full">
                        <thead>
                            <tr style="background: #0b2a40;">
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Centro de Trabajo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Modelo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Cantidad Solicitada</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Piezas Totales</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Tiempo Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="detail in phaseDetails" :key="detail.id" style="border-bottom: 1px solid #e8eff4;">
                                <td class="px-6 py-4" style="color: #0c1c28; font-weight: 600;">{{ detail.work_center }}</td>
                                <td class="px-6 py-4" style="color: #0c1c28; font-weight: 600;">{{ detail.modelo }}</td>
                                <td class="px-6 py-4" style="color: #0c1c28; font-weight: 600;">{{ detail.cantidad_solicitada }}</td>
                                <td class="px-6 py-4" style="color: #0c1c28; font-weight: 600;">{{ detail.total_pieces }}</td>
                                <td class="px-6 py-4" style="color: #0c1c28; font-weight: 600;">{{ detail.total_time }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Totalizador por Fecha y Centro de Trabajo -->
            <div class="mt-8">
                <h2 class="text-2xl font-bold mb-6" style="color: #0b2a40;">Resumen por Fecha y Centro de Trabajo</h2>
                
                <div v-for="(workCenters, date) in totalsByDate" :key="date" class="mb-6">
                    <div class="rounded-lg p-4 mb-3" style="background: #174060;">
                        <h3 class="text-lg font-bold" style="color: #fff;">Fecha: {{ date }}</h3>
                    </div>
                    
                    <div class="rounded-lg overflow-hidden" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                        <table class="w-full">
                            <thead>
                                <tr style="background: #0b2a40;">
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Centro de Trabajo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Piezas Totales</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Tiempo Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(totals, workCenter) in workCenters" :key="workCenter" style="border-bottom: 1px solid #e8eff4;">
                                    <td class="px-6 py-4" style="color: #0c1c28; font-weight: 600;">{{ workCenter }}</td>
                                    <td class="px-6 py-4" style="color: #0c1c28; font-weight: 600;">{{ totals.total_pieces }}</td>
                                    <td class="px-6 py-4" style="color: #0c1c28; font-weight: 600;">{{ totals.total_time }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';

defineProps({
    program: Object,
    details: Object,
    totalsByDate: Object,
});
</script>
