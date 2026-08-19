<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <IngenieroProcesosSidebar />
        
        <div class="p-6 ml-16">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold" style="color: #0b2a40;">Historial de Programas</h1>
            </div>
            
            <!-- Filtros -->
            <div class="rounded-lg overflow-hidden mb-6" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                <div class="p-6">
                    <h3 class="mb-4 text-lg font-semibold" style="color: #0b2a40;">Filtros de Consulta</h3>
                    
                    <!-- Tipo de filtro -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" style="color: #0c1c28;">Tipo de Consulta</label>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <button
                                @click="setFilterType('program')"
                                :class="[
                                    'flex items-center justify-center gap-2 rounded-lg border px-4 py-3 transition-colors font-semibold text-sm',
                                    filterType === 'program' 
                                        ? 'border-[#0a7c3e] bg-[#e6f4ec] text-[#0a7c3e]' 
                                        : 'border-[#d4dee8] bg-white text-[#0c1c28] hover:bg-[#f5f8fa]'
                                ]"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                Por Programa
                            </button>
                            <button
                                @click="setFilterType('work_center')"
                                :class="[
                                    'flex items-center justify-center gap-2 rounded-lg border px-4 py-3 transition-colors font-semibold text-sm',
                                    filterType === 'work_center' 
                                        ? 'border-[#0a7c3e] bg-[#e6f4ec] text-[#0a7c3e]' 
                                        : 'border-[#d4dee8] bg-white text-[#0c1c28] hover:bg-[#f5f8fa]'
                                ]"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                Por Centro y Fecha
                            </button>
                            <button
                                @click="setFilterType('date')"
                                :class="[
                                    'flex items-center justify-center gap-2 rounded-lg border px-4 py-3 transition-colors font-semibold text-sm',
                                    filterType === 'date' 
                                        ? 'border-[#0a7c3e] bg-[#e6f4ec] text-[#0a7c3e]' 
                                        : 'border-[#d4dee8] bg-white text-[#0c1c28] hover:bg-[#f5f8fa]'
                                ]"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Por Fecha Global
                            </button>
                        </div>
                    </div>

                    <!-- Filtro por programa -->
                    <div v-if="filterType === 'program'" class="mb-4">
                        <label class="block text-sm font-medium mb-2" style="color: #0c1c28;">Seleccionar Programa</label>
                        <select
                            v-model="selectedProgram"
                            class="block w-full rounded-md border-[#d4dee8] shadow-sm focus:border-[#0a7c3e] focus:ring-[#0a7c3e]"
                        >
                            <option value="">-- Seleccione un programa --</option>
                            <option v-for="program in programs" :key="program.id" :value="program.id">
                                {{ program.codigo }} - {{ program.fecha_entrega }} ({{ program.total_piezas }} piezas)
                            </option>
                        </select>
                    </div>

                    <!-- Filtro por centro y fecha -->
                    <div v-if="filterType === 'work_center'" class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium mb-2" style="color: #0c1c28;">Centro de Trabajo</label>
                            <select
                                v-model="selectedWorkCenter"
                                class="block w-full rounded-md border-[#d4dee8] shadow-sm focus:border-[#0a7c3e] focus:ring-[#0a7c3e]"
                            >
                                <option value="">-- Seleccione un centro --</option>
                                <option v-for="wc in workCenters" :key="wc.id" :value="wc.id">
                                    {{ wc.name }} (Fase {{ wc.phase }})
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2" style="color: #0c1c28;">Fecha</label>
                            <input
                                type="date"
                                v-model="selectedDate"
                                class="block w-full rounded-md border-[#d4dee8] shadow-sm focus:border-[#0a7c3e] focus:ring-[#0a7c3e]"
                            >
                        </div>
                    </div>

                    <!-- Filtro por fecha global -->
                    <div v-if="filterType === 'date'" class="mb-4">
                        <label class="block text-sm font-medium mb-2" style="color: #0c1c28;">Fecha</label>
                        <input
                            type="date"
                            v-model="selectedDate"
                            class="block w-full rounded-md border-[#d4dee8] shadow-sm focus:border-[#0a7c3e] focus:ring-[#0a7c3e]"
                        >
                    </div>

                    <!-- Botón de búsqueda -->
                    <div class="flex justify-end">
                        <button
                            @click="search"
                            :disabled="!canSearch"
                            :class="[
                                'flex items-center gap-2 rounded-lg px-6 py-2 font-semibold text-sm transition-colors',
                                canSearch 
                                    ? 'bg-[#0b2a40] text-white hover:bg-[#174060]' 
                                    : 'cursor-not-allowed bg-gray-300 text-gray-500'
                            ]"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Consultar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Resultados -->
            <div v-if="results" class="rounded-lg overflow-hidden" style="background: #fff; border: 1px solid #d4dee8; box-shadow: 0 2px 12px rgba(11,28,40,.08);">
                <div class="p-6">
                    <h3 class="mb-4 text-lg font-semibold" style="color: #0b2a40;">Resultados</h3>

                    <!-- Resultados por programa -->
                    <div v-if="filterType === 'program' && results.program">
                        <div class="mb-6 rounded-lg p-4" style="background: #f5f8fa; border: 1px solid #d4dee8;">
                            <h4 class="text-base font-semibold" style="color: #0b2a40;">{{ results.program.codigo }}</h4>
                            <div class="mt-2 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                                <div>
                                    <span style="color: #6a8090;">Fecha Entrega:</span>
                                    <span class="ml-1 font-medium" style="color: #0c1c28;">{{ results.program.fecha_entrega }}</span>
                                </div>
                                <div>
                                    <span style="color: #6a8090;">Total Piezas:</span>
                                    <span class="ml-1 font-medium" style="color: #0c1c28;">{{ results.program.total_piezas }}</span>
                                </div>
                                <div>
                                    <span style="color: #6a8090;">Creado por:</span>
                                    <span class="ml-1 font-medium" style="color: #0c1c28;">{{ results.program.creator }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[800px]">
                                <thead>
                                    <tr style="background: #0b2a40;">
                                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Fecha</th>
                                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Turno</th>
                                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Centro</th>
                                        <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium uppercase tracking-wider" style="color: #fff;">Programado</th>
                                        <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium uppercase tracking-wider" style="color: #fff;">Fabricado</th>
                                        <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium uppercase tracking-wider" style="color: #fff;">Atrasos</th>
                                        <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium uppercase tracking-wider" style="color: #fff;">Adelantos</th>
                                        <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium uppercase tracking-wider" style="color: #fff;">Eficiencia</th>
                                        <th class="px-4 sm:px-6 py-3 text-center text-xs font-medium uppercase tracking-wider" style="color: #fff;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in results.history" :key="item.id" style="border-bottom: 1px solid #e8eff4;">
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ item.date }}</td>
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600; text-transform: capitalize;">{{ item.shift }}</td>
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ item.work_center }}</td>
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right" style="color: #0c1c28; font-weight: 600;">{{ item.programmed }}</td>
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right" style="color: #0c1c28; font-weight: 600;">{{ item.produced }}</td>
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right" :style="item.backwardness > 0 ? 'color: #dc2626; font-weight: 600;' : 'color: #0c1c28; font-weight: 600;'">{{ item.backwardness }}</td>
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right" :style="item.advanced > 0 ? 'color: #16a34a; font-weight: 600;' : 'color: #0c1c28; font-weight: 600;'">{{ item.advanced }}</td>
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right" :style="getEfficiencyStyle(item.efficiency)">{{ item.efficiency }}%</td>
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-center">
                                            <button
                                                @click="showLinesDetails(item)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#174060] text-white rounded-md text-xs font-bold shadow-sm hover:bg-[#0f2c47] transition-colors whitespace-nowrap"
                                                title="Ver líneas"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                                </svg>
                                                Líneas
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Resultados por centro y fecha -->
                    <div v-if="filterType === 'work_center' && results.work_center">
                        <div class="mb-6 rounded-lg p-4" style="background: #f5f8fa; border: 1px solid #d4dee8;">
                            <h4 class="text-base font-semibold" style="color: #0b2a40;">{{ results.work_center.name }}</h4>
                            <div class="mt-2 grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
                                <div>
                                    <span style="color: #6a8090;">Fase:</span>
                                    <span class="ml-1 font-medium" style="color: #0c1c28;">{{ results.work_center.phase }}</span>
                                </div>
                                <div>
                                    <span style="color: #6a8090;">Fecha:</span>
                                    <span class="ml-1 font-medium" style="color: #0c1c28;">{{ results.date }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[800px]">
                                <thead>
                                    <tr style="background: #0b2a40;">
                                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Turno</th>
                                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Programa</th>
                                        <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium uppercase tracking-wider" style="color: #fff;">Programado</th>
                                        <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium uppercase tracking-wider" style="color: #fff;">Fabricado</th>
                                        <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium uppercase tracking-wider" style="color: #fff;">Atrasos</th>
                                        <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium uppercase tracking-wider" style="color: #fff;">Adelantos</th>
                                        <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium uppercase tracking-wider" style="color: #fff;">Eficiencia</th>
                                        <th class="px-4 sm:px-6 py-3 text-center text-xs font-medium uppercase tracking-wider" style="color: #fff;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in results.history" :key="item.id" style="border-bottom: 1px solid #e8eff4;">
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600; text-transform: capitalize;">{{ item.shift }}</td>
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ item.program_code }}</td>
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right" style="color: #0c1c28; font-weight: 600;">{{ item.programmed }}</td>
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right" style="color: #0c1c28; font-weight: 600;">{{ item.produced }}</td>
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right" :style="item.backwardness > 0 ? 'color: #dc2626; font-weight: 600;' : 'color: #0c1c28; font-weight: 600;'">{{ item.backwardness }}</td>
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right" :style="item.advanced > 0 ? 'color: #16a34a; font-weight: 600;' : 'color: #0c1c28; font-weight: 600;'">{{ item.advanced }}</td>
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right" :style="getEfficiencyStyle(item.efficiency)">{{ item.efficiency }}%</td>
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-center">
                                            <button
                                                @click="showLinesDetails(item)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#174060] text-white rounded-md text-xs font-bold shadow-sm hover:bg-[#0f2c47] transition-colors whitespace-nowrap"
                                                title="Ver líneas"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                                </svg>
                                                Líneas
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Resultados por fecha global -->
                    <div v-if="filterType === 'date' && results.date">
                        <div class="mb-6 rounded-lg p-4" style="background: #f5f8fa; border: 1px solid #d4dee8;">
                            <h4 class="text-base font-semibold" style="color: #0b2a40;">Fecha: {{ results.date }}</h4>
                        </div>

                        <div v-if="results.history && results.history.length > 0">
                            <div v-for="wcGroup in results.history" :key="wcGroup.work_center?.id || wcGroup.id" class="mb-6">
                                <h5 class="mb-3 font-semibold" style="color: #0b2a40;">{{ wcGroup.work_center?.name || 'Centro' }} (Fase {{ wcGroup.work_center?.phase || 'N/A' }})</h5>
                            
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[800px]">
                                    <thead>
                                        <tr style="background: #0b2a40;">
                                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Turno</th>
                                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Programa</th>
                                            <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium uppercase tracking-wider" style="color: #fff;">Programado</th>
                                            <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium uppercase tracking-wider" style="color: #fff;">Fabricado</th>
                                            <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium uppercase tracking-wider" style="color: #fff;">Atrasos</th>
                                            <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium uppercase tracking-wider" style="color: #fff;">Adelantos</th>
                                            <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium uppercase tracking-wider" style="color: #fff;">Eficiencia</th>
                                            <th class="px-4 sm:px-6 py-3 text-center text-xs font-medium uppercase tracking-wider" style="color: #fff;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in wcGroup.programs" :key="item.id" style="border-bottom: 1px solid #e8eff4;">
                                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600; text-transform: capitalize;">{{ item.shift }}</td>
                                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ item.program_code }}</td>
                                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right" style="color: #0c1c28; font-weight: 600;">{{ item.programmed }}</td>
                                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right" style="color: #0c1c28; font-weight: 600;">{{ item.produced }}</td>
                                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right" :style="item.backwardness > 0 ? 'color: #dc2626; font-weight: 600;' : 'color: #0c1c28; font-weight: 600;'">{{ item.backwardness }}</td>
                                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right" :style="item.advanced > 0 ? 'color: #16a34a; font-weight: 600;' : 'color: #0c1c28; font-weight: 600;'">{{ item.advanced }}</td>
                                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right" :style="getEfficiencyStyle(item.efficiency)">{{ item.efficiency }}%</td>
                                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-center">
                                                <button
                                                    @click="showLinesDetails(item)"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#174060] text-white rounded-md text-xs font-bold shadow-sm hover:bg-[#0f2c47] transition-colors whitespace-nowrap"
                                                    title="Ver líneas"
                                                >
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                                    </svg>
                                                    Líneas
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        </div>
                    </div>

                    <!-- Mensaje cuando no hay resultados -->
                    <div v-if="!results || (results.history && results.history.length === 0)" class="text-center py-8" style="color: #6a8090;">
                        <p>No se encontraron resultados para la consulta.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de detalles de líneas -->
        <Modal :show="showLinesModal" @close="showLinesModal = false" maxWidth="2xl">
            <div class="p-6">
                <h3 class="mb-4 text-lg font-semibold" style="color: #0b2a40;">Detalles por Línea de Producción</h3>
                
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[600px]">
                        <thead>
                            <tr style="background: #0b2a40;">
                                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Línea</th>
                                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Hora Inicio</th>
                                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: #fff;">Hora Fin</th>
                                <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium uppercase tracking-wider" style="color: #fff;">Producido</th>
                                <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium uppercase tracking-wider" style="color: #fff;">Rechazado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="line in selectedItemLines" :key="line.line" style="border-bottom: 1px solid #e8eff4;">
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ line.line }}</td>
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ line.start_time }}</td>
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap" style="color: #0c1c28; font-weight: 600;">{{ line.end_time }}</td>
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right" style="color: #0c1c28; font-weight: 600;">{{ line.produced }}</td>
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right" :style="line.rejected > 0 ? 'color: #dc2626; font-weight: 600;' : 'color: #0c1c28; font-weight: 600;'">{{ line.rejected }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-end">
                    <button
                        @click="showLinesModal = false"
                        class="rounded-lg px-4 py-2 font-semibold text-sm transition-colors"
                        style="background: #d4dee8; color: #0c1c28; hover: background: #c4d0dc;"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </Modal>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import IngenieroProcesosSidebar from '@/Components/IngenieroProcesosSidebar.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    programs: Array,
    workCenters: Array,
    results: Object,
    filters: Object,
});

const filterType = ref(props.filters?.filter_type || 'program');
const selectedProgram = ref(props.filters?.program_id || '');
const selectedWorkCenter = ref(props.filters?.work_center_id || '');
const selectedDate = ref(props.filters?.date || '');
const showLinesModal = ref(false);
const selectedItemLines = ref([]);

const canSearch = computed(() => {
    if (filterType.value === 'program') {
        return selectedProgram.value !== '';
    }
    if (filterType.value === 'work_center') {
        return selectedWorkCenter.value !== '' && selectedDate.value !== '';
    }
    if (filterType.value === 'date') {
        return selectedDate.value !== '';
    }
    return false;
});

const setFilterType = (type) => {
    filterType.value = type;
    selectedProgram.value = '';
    selectedWorkCenter.value = '';
    selectedDate.value = '';
};

const search = () => {
    router.get(route('ingeniero-procesos.program-history'), {
        filter_type: filterType.value,
        program_id: selectedProgram.value,
        work_center_id: selectedWorkCenter.value,
        date: selectedDate.value,
        filter: true,
    });
};

const showLinesDetails = (item) => {
    selectedItemLines.value = item.lines;
    showLinesModal.value = true;
};

const getEfficiencyStyle = (efficiency) => {
    if (efficiency >= 100) return 'color: #16a34a; font-weight: 600;';
    if (efficiency >= 95) return 'color: #ca8a04; font-weight: 600;';
    return 'color: #dc2626; font-weight: 600;';
};
</script>
