<template>
    <div class="min-h-screen" style="background: #eaf0f5;">
        <SupervisorSidebar />
        
        <div class="p-6 ml-16">
            <div class="flex flex-col gap-2.5">
            <!-- Top Bar -->
            <div class="bg-white border border-[#d4dee8] rounded-xl shadow-sm">
                <div class="px-4 py-3 flex items-center justify-between gap-4 flex-wrap">
                    <div>
                        <span class="text-[10px] font-bold tracking-widest uppercase text-[#174060]">Registro Diario de Producción</span>
                        <h1 class="text-2xl font-extrabold text-[#0b2a40] leading-none">{{ workCenter?.name || 'Cargando...' }}</h1>
                        <div class="text-sm text-[#4e6070] font-semibold mt-1">{{ formattedDateLong }}</div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <input type="date" v-model="selectedDate" @change="cambiarFecha" class="px-3 py-2 border border-[#d4dee8] rounded-md text-xs font-bold text-[#0c1c28]">
                        <select v-model="selectedShift" @change="cambiarTurno" class="px-3 py-2 border border-[#d4dee8] rounded-md text-xs font-bold text-[#0c1c28]">
                            <option value="matutino">Matutino</option>
                            <option value="vespertino">Vespertino</option>
                            <option value="nocturno">Nocturno</option>
                        </select>
                        <div class="px-3 py-2 rounded-full bg-[#0b2a40] text-white text-xs font-bold">{{ currentTime }}</div>
                        <Link :href="route('supervisor.dashboard')" class="px-4 py-2 bg-[#174060] text-white rounded-md text-xs font-bold hover:opacity-85">
                            Principal
                        </Link>
                    </div>
                </div>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-2 md:grid-cols-5 lg:grid-cols-10 gap-2">
                <div class="p-3 bg-white border border-[#d4dee8] rounded-lg">
                    <div class="text-[11px] font-bold tracking-widest uppercase text-[#4e6070]">Programado</div>
                    <div class="text-2xl font-extrabold text-[#0b2a40]">{{ formatNumber(programData.programmed) }}</div>
                </div>
                <div class="p-3 bg-white border border-[#d4dee8] rounded-lg">
                    <div class="text-[11px] font-bold tracking-widest uppercase text-[#4e6070]">Atraso</div>
                    <div class="text-2xl font-extrabold text-[#0b2a40]">{{ formatNumber(programData.backwardness) }}</div>
                </div>
                <div class="p-3 bg-white border border-[#d4dee8] rounded-lg">
                    <div class="text-[11px] font-bold tracking-widest uppercase text-[#4e6070]">Adelantadas</div>
                    <div class="text-2xl font-extrabold text-[#0b2a40]">{{ formatNumber(programData.advanced) }}</div>
                </div>
                <div class="p-3 bg-white border border-[#d4dee8] rounded-lg">
                    <div class="text-[11px] font-bold tracking-widest uppercase text-[#4e6070]">Total a Producir</div>
                    <div class="text-2xl font-extrabold text-[#0b2a40]">{{ formatNumber(kpis.total_to_produce) }}</div>
                </div>
                <div class="p-3 border rounded-lg" :class="kpis.compliance >= 100 ? 'bg-[#e4f5ec] border-[#aadcc4]' : (kpis.compliance >= 95 ? 'bg-[#fff6da] border-[#e8d488]' : 'bg-[#fce9e8] border-[#ebbab8]')">
                    <div class="text-[11px] font-bold tracking-widest uppercase text-[#4e6070]">Fabricadas</div>
                    <div class="text-2xl font-extrabold text-[#0b2a40]">{{ formatNumber(kpis.fabricated) }}</div>
                </div>
                <div class="p-3 border rounded-lg" :class="kpis.difference >= 0 ? 'bg-[#e4f5ec] border-[#aadcc4]' : 'bg-[#fce9e8] border-[#ebbab8]'">
                    <div class="text-[11px] font-bold tracking-widest uppercase text-[#4e6070]">Diferencia</div>
                    <div class="text-2xl font-extrabold text-[#0b2a40]">{{ kpis.difference >= 0 ? '+' : '' }}{{ formatNumber(kpis.difference) }}</div>
                </div>
                <div class="p-3 border rounded-lg" :class="kpis.compliance >= 100 ? 'bg-[#e4f5ec] border-[#aadcc4]' : (kpis.compliance >= 95 ? 'bg-[#fff6da] border-[#e8d488]' : 'bg-[#fce9e8] border-[#ebbab8]')">
                    <div class="text-[11px] font-bold tracking-widest uppercase text-[#4e6070]">Cumplimiento</div>
                    <div class="text-2xl font-extrabold text-[#0b2a40]">{{ kpis.compliance }}%</div>
                </div>
                <div class="p-3 bg-white border border-[#d4dee8] rounded-lg">
                    <div class="text-[11px] font-bold tracking-widest uppercase text-[#4e6070]">Min. Paro</div>
                    <div class="text-2xl font-extrabold text-[#0b2a40]">{{ kpis.strike_minutes }}</div>
                </div>
                <div class="p-3 bg-white border border-[#d4dee8] rounded-lg">
                    <div class="text-[11px] font-bold tracking-widest uppercase text-[#4e6070]">Horas Activas</div>
                    <div class="text-2xl font-extrabold text-[#0b2a40]">{{ kpis.hours_active }}</div>
                </div>
                <div class="p-3 bg-white border border-[#d4dee8] rounded-lg">
                    <div class="text-[11px] font-bold tracking-widest uppercase text-[#4e6070]">Cap. Instalada</div>
                    <div class="text-2xl font-extrabold text-[#0b2a40]">{{ formatNumber(workCenter?.installed_capacity) }}</div>
                </div>
            </div>

            <!-- Mensaje cuando no hay programa -->
            <div v-if="!dailyProgramId" class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-6 text-center">
                <div class="text-6xl mb-4">📋</div>
                <h3 class="text-lg font-bold text-[#0b2a40] mb-2">No hay programa diario registrado</h3>
                <p class="text-sm text-[#6a8090] mb-4">No existe un programa para <strong>{{ workCenter?.name }}</strong> en el turno <strong>{{ shiftLabel }}</strong> del <strong>{{ formattedDateLong }}</strong>.</p>
                <!-- Comentado: Los supervisores no pueden crear programas -->
            </div>

            <!-- Formulario de Programa (cuando ya existe) -->
            <div v-else class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-4">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-extrabold text-[#0b2a40]">Encabezado de Turno</h2>
                    <!-- Comentado: Los supervisores no pueden guardar programas -->
                    <!-- <button @click="guardarPrograma" :disabled="savingProgram" class="px-4 py-2 bg-[#0b2a40] text-white rounded-md text-xs font-bold hover:opacity-85 disabled:opacity-50">💾 Guardar Programa</button> -->
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div class="col-span-2 flex items-end space-x-4">
                        <div>
                            <label class="text-[10px] font-bold tracking-widest uppercase text-[#4e6070]">Fecha</label>
                            <input type="text" :value="formattedDate" readonly class="w-full px-3 py-2 border border-[#d4dee8] rounded text-sm font-semibold bg-[#f4f7fa]">
                        </div>
                        <div>
                            <label class="text-[10px] font-bold tracking-widest uppercase text-[#4e6070]">Turno</label>
                            <input type="text" :value="shiftLabel" readonly class="w-full px-3 py-2 border border-[#d4dee8] rounded text-sm font-semibold bg-[#f4f7fa]">
                        </div>
                    </div>
                    <!-- Comentado: Los supervisores no pueden editar estos campos -->
                    <!-- <div>
                        <label class="text-[10px] font-bold tracking-widest uppercase text-[#4e6070]">Programado</label>
                        <input type="number" v-model.number="programData.programmed" min="0" class="w-full px-3 py-2 border border-[#d4dee8] rounded text-sm font-semibold">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold tracking-widest uppercase text-[#4e6070]">Atraso</label>
                        <input type="number" v-model.number="programData.backwardness" min="0" class="w-full px-3 py-2 border border-[#d4dee8] rounded text-sm font-semibold">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold tracking-widest uppercase text-[#4e6070]">Adelantadas</label>
                        <input type="number" v-model.number="programData.advanced" min="0" class="w-full px-3 py-2 border border-[#d4dee8] rounded text-sm font-semibold">
                    </div> -->
                </div>
            </div>

            <!-- Tabla de Producción -->
            <div v-if="dailyProgramId && horas.length > 0 && lineas.length > 0" class="bg-white border border-[#d4dee8] rounded-xl shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-[#d4dee8]">
                    <h2 class="text-base font-extrabold text-[#0b2a40]">Producción por Hora</h2>
                    <p class="text-xs text-[#6a8090] mt-1">Los cambios se guardan automáticamente</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-[#0b2a40] text-white">
                                <th class="px-3 py-3 text-left text-[11px] font-bold tracking-widest uppercase whitespace-nowrap">Hora</th>
                                <th class="px-3 py-3 text-center text-[11px] font-bold tracking-widest uppercase whitespace-nowrap bg-[#174060]">Producción Esperada</th>
                                <th v-for="line in lineas" :key="line.id" class="px-3 py-3 text-center text-[11px] font-bold tracking-widest uppercase whitespace-nowrap">{{ line.title }}</th>
                                <th class="px-3 py-3 text-center text-[11px] font-bold tracking-widest uppercase bg-[#174060] whitespace-nowrap">PPH</th>
                                <th class="px-3 py-3 text-center text-[11px] font-bold tracking-widest uppercase bg-[#174060] whitespace-nowrap">Cumplimiento %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="hora in horas" :key="hora.start" class="border-b border-[#e8eff4] hover:bg-[#eef5fa]">
                                <td class="px-3 py-2 font-bold text-sm text-[#0c1c28] whitespace-nowrap">{{ hora.start }} - {{ hora.end }}</td>
                                <td class="px-3 py-2 text-center font-bold text-sm text-[#174060] bg-[#f4f7fa] whitespace-nowrap">{{ expectedProductionPerHour }}</td>
                                <td v-for="line in lineas" :key="line.id" class="px-2 py-2">
                                    <input type="number" v-model="productionValues[getKey(line.id, hora.start)]" @input="autoSave(line.id, hora.start)" min="0" class="w-full px-2 py-1 border border-[#d4dee8] rounded text-center text-sm font-semibold focus:border-[#174060] focus:outline-none">
                                </td>
                                <td class="px-3 py-2 text-center font-extrabold text-[#0b2a40] bg-[#f4f7fa]">{{ formatNumber(hourTotals[hora.start]) }}</td>
                                <td class="px-3 py-2 text-center font-bold text-sm bg-[#f4f7fa] whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1">
                                        <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: getComplianceColor(hourCompliance[hora.start]) }"></span>
                                        <span>{{ hourCompliance[hora.start] }}%</span>
                                    </div>
                                </td>
                            </tr>
                            <tr class="bg-[#0c1c28] text-white font-extrabold">
                                <td class="px-3 py-3 text-sm tracking-widest uppercase">Total</td>
                                <td class="px-3 py-3 text-center text-sm text-[#8ba4b8]">-</td>
                                <td v-for="line in lineas" :key="line.id" class="px-3 py-3 text-center text-lg">{{ formatNumber(lineTotals[line.id]) }}</td>
                                <td class="px-3 py-3 text-center text-xl bg-[#174060]">{{ formatNumber(grandTotalValue) }}</td>
                                <td class="px-3 py-3 text-center bg-[#174060]">
                                    <div class="flex items-center justify-center gap-1">
                                        <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: getComplianceColor(averageCompliance) }"></span>
                                        <span>{{ averageCompliance }}%</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Paros -->
            <div v-if="dailyProgramId" class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-4">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-extrabold text-[#0b2a40]">Registro de Paros</h2>
                    <button @click="abrirModalParo" class="px-4 py-2 bg-[#ba2418] text-white rounded-md text-xs font-bold hover:opacity-85">⏸ Registrar Paro</button>
                </div>
                <div class="space-y-2">
                    <div v-for="strike in strikesList" :key="strike.id" class="p-3 bg-[#f8f9fb] border border-[#d4dee8] rounded-lg flex justify-between items-center">
                        <div>
                            <span class="font-bold text-[#0c1c28]">{{ strike.production_line?.title || 'Línea ' + strike.id_production_lines }}</span>
                            <span class="text-xs text-[#6a8090] ml-2">{{ strike.start_time }} - {{ strike.end_time || 'en curso' }}</span>
                            <p class="text-sm text-[#4e6070] mt-1">{{ strike.description }}</p>
                        </div>
                        <button v-if="!strike.end_time" @click="finalizarParo(strike)" class="px-3 py-1 bg-[#0b8a3d] text-white rounded text-xs font-bold">Finalizar</button>
                    </div>
                    <div v-if="strikesList.length === 0" class="text-center py-6 text-[#6a8090]">No hay paros registrados</div>
                </div>
            </div>

            <!-- Acciones del Supervisor -->
            <div v-if="dailyProgramId" class="bg-white border border-[#d4dee8] rounded-xl shadow-sm p-4">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-extrabold text-[#0b2a40]">⚙️ Acciones del Supervisor</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Procesar Balance -->
                    <div class="p-4 bg-[#f8f9fb] border border-[#d4dee8] rounded-lg">
                        <h3 class="text-sm font-bold text-[#0b2a40] mb-2">📊 Procesar Balance</h3>
                        <p class="text-xs text-[#6a8090] mb-3">Calcula y guarda el balance de atrasos/adelantos para el siguiente día.</p>
                        <button 
                            @click="procesarBalance"
                            :disabled="procesandoBalance || props.dailyProgram?.balance_processed"
                            class="w-full px-4 py-2 bg-[#0b2a40] text-white rounded-md text-xs font-bold hover:opacity-85 transition disabled:opacity-50">
                            {{ procesandoBalance ? 'Procesando...' : (props.dailyProgram?.balance_processed ? '✓ Balance Procesado' : 'Procesar Balance') }}
                        </button>
                        <p v-if="props.dailyProgram?.balance_processed" class="text-xs text-[#0b8a3d] mt-2">
                            Procesado el {{ formatDateTime(props.dailyProgram.balance_processed_at) }}
                        </p>
                    </div>

                    <!-- Ajuste Manual -->
                    <div class="p-4 bg-[#f8f9fb] border border-[#d4dee8] rounded-lg">
                        <h3 class="text-sm font-bold text-[#0b2a40] mb-2">✏️ Ajuste Manual</h3>
                        <p class="text-xs text-[#6a8090] mb-3">Realiza ajustes manuales a la producción (conteos físicos, correcciones, etc.).</p>
                        <button 
                            @click="abrirModalAjusteManual"
                            class="w-full px-4 py-2 bg-[#f59e0b] text-white rounded-md text-xs font-bold hover:opacity-85 transition">
                            Realizar Ajuste
                        </button>
                    </div>

                    <!-- Historial de Ajustes -->
                    <div class="p-4 bg-[#f8f9fb] border border-[#d4dee8] rounded-lg">
                        <h3 class="text-sm font-bold text-[#0b2a40] mb-2">📜 Historial de Ajustes</h3>
                        <p class="text-xs text-[#6a8090] mb-3">Ver todos los ajustes y correcciones realizados en este programa.</p>
                        <button 
                            @click="verHistorialAjustes"
                            class="w-full px-4 py-2 bg-[#6a8090] text-white rounded-md text-xs font-bold hover:opacity-85 transition">
                            Ver Historial
                        </button>
                    </div>
                </div>

                <!-- Estado de Cierre de Turno -->
                <div class="mt-4 p-3 border rounded-lg" :class="allLinesClosed ? 'bg-[#e4f5ec] border-[#aadcc4]' : 'bg-[#fff6da] border-[#e8d488]'">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <span class="text-xs font-bold text-[#4e6070]">Estado del Turno:</span>
                            <span v-if="allLinesClosed" class="ml-2 text-xs font-bold text-[#0b8a3d]">
                                ✓ Todas las líneas cerradas ({{ closedLinesCount }}/{{ totalLinesCount }})
                            </span>
                            <span v-else class="ml-2 text-xs font-bold text-[#f59e0b]">
                                ⏳ {{ closedLinesCount }} de {{ totalLinesCount }} líneas cerradas
                            </span>
                        </div>
                    </div>
                    <div v-if="closedLinesCount > 0" class="space-y-1">
                        <div v-for="closure in props.closedLines" :key="closure.id" class="text-xs text-[#6a8090]">
                            ✓ {{ closure.production_line?.title || 'Línea ' + closure.id_production_line }} - Cerrado por {{ closure.closed_by?.name || 'Usuario ' + closure.closed_by }} el {{ formatDateTime(closure.closed_at) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Paro -->
        <div v-if="modalVisible" class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
                <div class="px-6 py-4 border-b border-[#d4dee8]">
                    <h3 class="text-lg font-extrabold text-[#0b2a40]">Registrar Paro</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Línea</label>
                        <select v-model="nuevoParo.line_id" class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1">
                            <option v-for="line in lineas" :key="line.id" :value="line.id">{{ line.title }}</option>
                        </select>
                    </div>
                    <div v-if="machines && machines.length > 0">
                        <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Máquina afectada (opcional)</label>
                        <select v-model="nuevoParo.machine_id" class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1">
                            <option :value="null">No afecta a máquina específica</option>
                            <option v-for="machine in machines" :key="machine.id" :value="machine.id">{{ machine.title }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Inicio</label>
                        <input type="time" v-model="nuevoParo.start_time" class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1">
                    </div>
                    <div>
                        <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Fin (opcional)</label>
                        <input type="time" v-model="nuevoParo.end_time" class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1">
                    </div>
                    <div>
                        <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Descripción</label>
                        <textarea v-model="nuevoParo.description" rows="3" class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1"></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-[#d4dee8] flex gap-2 justify-end">
                    <button @click="cerrarModalParo" class="px-4 py-2 bg-[#f4f7fa] text-[#4e6070] border border-[#d4dee8] rounded-md text-xs font-bold">Cancelar</button>
                    <button @click="registrarParo" class="px-4 py-2 bg-[#ba2418] text-white rounded-md text-xs font-bold hover:opacity-85">Guardar</button>
                </div>
            </div>
        </div>

        <!-- Modal Ajuste Manual -->
        <div v-if="modalAjusteVisible" class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
                <div class="px-6 py-4 border-b border-[#d4dee8]">
                    <h3 class="text-lg font-extrabold text-[#0b2a40]">Ajuste Manual de Producción</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Tipo de Ajuste</label>
                        <select v-model="ajusteManual.adjustment_type" class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1">
                            <option value="manual_count">Conteo Físico</option>
                            <option value="correction">Corrección de Datos</option>
                            <option value="inventory_adjustment">Ajuste de Inventario</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Valor Anterior</label>
                        <input type="number" v-model.number="ajusteManual.previous_value" class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1">
                    </div>
                    <div>
                        <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Valor Nuevo</label>
                        <input type="number" v-model.number="ajusteManual.new_value" class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1">
                    </div>
                    <div>
                        <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Motivo</label>
                        <input type="text" v-model="ajusteManual.reason" class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1" placeholder="Ej: Conteo físico adicional">
                    </div>
                    <div>
                        <label class="text-xs font-bold tracking-widest uppercase text-[#4e6070]">Notas (opcional)</label>
                        <textarea v-model="ajusteManual.notes" rows="2" class="w-full px-3 py-2 border border-[#d4dee8] rounded-md text-sm font-semibold mt-1"></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-[#d4dee8] flex gap-2 justify-end">
                    <button @click="cerrarModalAjuste" class="px-4 py-2 bg-[#f4f7fa] text-[#4e6070] border border-[#d4dee8] rounded-md text-xs font-bold">Cancelar</button>
                    <button @click="guardarAjusteManual" :disabled="guardandoAjuste" class="px-4 py-2 bg-[#f59e0b] text-white rounded-md text-xs font-bold hover:opacity-85 disabled:opacity-50">
                        {{ guardandoAjuste ? 'Guardando...' : 'Guardar Ajuste' }}
                    </button>
                </div>
            </div>
        </div>
        </div>
        
        <!-- Modal de Confirmación para Finalizar Paro -->
        <div v-if="showConfirmModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
                <div class="mb-4">
                    <h3 class="text-lg font-bold text-[#0b2a40]">Confirmar Finalización de Paro</h3>
                    <p class="text-[#4e6070] mt-2">¿Finalizar este paro a las {{ confirmEndTime }}?</p>
                </div>
                
                <div class="flex gap-3 justify-end">
                    <button @click="cancelEndStrike" 
                            class="px-4 py-2 bg-[#f4f7fa] text-[#4e6070] border border-[#d4dee8] rounded-md font-bold hover:bg-[#e8edf2]">
                        Cancelar
                    </button>
                    <button @click="confirmEndStrike" 
                            class="px-4 py-2 bg-[#0b2a40] text-white rounded-md font-bold hover:opacity-85">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { router, usePage, Link } from '@inertiajs/vue3'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import SupervisorSidebar from '@/Components/SupervisorSidebar.vue'

const toast = useToast()
const page = usePage()
const props = page.props

// DATOS DEL SERVIDOR
const workCenter = ref(props.workCenter || {})
const lineas = ref(props.productionLines || [])
const closedLinesData = ref(props.closedLines || [])
const machines = ref(props.machines || [])
const horasBase = ref(props.hours || [
    { start: '08:00', end: '09:00' }, { start: '09:00', end: '10:00' }, 
    { start: '10:00', end: '11:00' }, { start: '11:00', end: '12:00' },
    { start: '12:00', end: '13:00' }, { start: '13:00', end: '14:00' },
    { start: '14:00', end: '15:00' }, { start: '15:00', end: '16:00' }, 
    { start: '16:00', end: '17:00' }
])
const schedulesMapRef = ref(props.existingSchedules || {})

// 🔧 FECHA: Siempre usar la fecha actual si no hay props.date
const todayDate = new Date().toISOString().split('T')[0]
const selectedDate = ref(props.date && props.date !== '' ? props.date : todayDate)
const selectedShift = ref(props.shift || 'matutino')
const dailyProgramId = ref(props.dailyProgram?.id || null)
const currentTime = ref('')
const savingProgram = ref(false)
const creandoPrograma = ref(false)
const modalVisible = ref(false)
const strikesList = ref([])
let strikesInterval = null
const showConfirmModal = ref(false)
const confirmStrike = ref(null)
const confirmEndTime = ref('')

const programData = ref({
    programmed: props.dailyProgram?.programmed || 0,
    backwardness: props.dailyProgram?.backwardness || 0,
    advanced: props.dailyProgram?.advanced || 0
})

const nuevoPrograma = ref({
    programmed: 0,
    backwardness: 0,
    advanced: 0
})

const productionValues = ref({})
const autoSaveTimeouts = {}

const nuevoParo = ref({
    line_id: null,
    machine_id: null,
    start_time: '',
    end_time: '',
    description: ''
})

// Ajuste manual
const modalAjusteVisible = ref(false)
const guardandoAjuste = ref(false)
const procesandoBalance = ref(false)
const ajusteManual = ref({
    adjustment_type: 'manual_count',
    previous_value: 0,
    new_value: 0,
    reason: '',
    notes: ''
})

const getKey = (lineId, hourStart) => `${lineId}-${hourStart}`

const horas = computed(() => horasBase.value)
const shiftLabel = computed(() => {
    const shifts = { matutino: 'Matutino', vespertino: 'Vespertino', nocturno: 'Nocturno' }
    return shifts[selectedShift.value] || selectedShift.value
})
const formattedDate = computed(() => {
    if (!selectedDate.value) return ''
    const [year, month, day] = selectedDate.value.split('-')
    return `${parseInt(day)}/${parseInt(month)}/${year}`
})

const formattedDateLong = computed(() => {
    if (!selectedDate.value) return ''
    const [year, month, day] = selectedDate.value.split('-')
    const fecha = new Date(`${year}-${month}-${day}T12:00:00`)
    return fecha.toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
})

const closedLinesCount = computed(() => closedLinesData.value.length)
const totalLinesCount = computed(() => lineas.value.length)
const allLinesClosed = computed(() => closedLinesCount.value >= totalLinesCount.value && totalLinesCount.value > 0)

const hourTotals = computed(() => {
    const totals = {}
    for (const hora of horas.value) {
        let total = 0
        for (const line of lineas.value) {
            const key = getKey(line.id, hora.start)
            total += productionValues.value[key] || 0
        }
        totals[hora.start] = total
    }
    return totals
})

const lineTotals = computed(() => {
    const totals = {}
    for (const line of lineas.value) {
        let total = 0
        for (const hora of horas.value) {
            const key = getKey(line.id, hora.start)
            total += productionValues.value[key] || 0
        }
        totals[line.id] = total
    }
    return totals
})

const grandTotalValue = computed(() => {
    let total = 0
    for (const line of lineas.value) {
        total += lineTotals.value[line.id] || 0
    }
    return total
})

const kpis = computed(() => {
    const totalToProduce = Math.max(programData.value.programmed + programData.value.backwardness - programData.value.advanced, 0)
    const totalProduced = grandTotalValue.value
    const difference = totalProduced - totalToProduce
    const compliance = totalToProduce > 0 ? ((totalProduced / totalToProduce) * 100).toFixed(1) : 0
    return {
        programmed: programData.value.programmed,
        backwardness: programData.value.backwardness,
        advanced: programData.value.advanced,
        total_to_produce: totalToProduce,
        fabricated: totalProduced,
        difference: difference,
        compliance: compliance,
        strike_minutes: strikesList.value.reduce((sum, s) => sum + (s.minutes || 0), 0),
        hours_active: 9
    }
})

const expectedProductionPerHour = computed(() => {
    const hours = kpis.value.hours_active || 9
    const total = kpis.value.total_to_produce || 0
    return hours > 0 ? (total / hours).toFixed(2) : '0.00'
})

const hourCompliance = computed(() => {
    const compliance = {}
    const expected = parseFloat(expectedProductionPerHour.value) || 0
    for (const hora of horas.value) {
        const pph = hourTotals.value[hora.start] || 0
        const value = expected > 0 ? ((pph / expected) * 100).toFixed(1) : '0.0'
        compliance[hora.start] = value
    }
    return compliance
})

const getComplianceColor = (value) => {
    const num = parseFloat(value) || 0
    if (num >= 90) return '#0b8a3d'
    if (num >= 70) return '#f59e0b'
    return '#ba2418'
}

const averageCompliance = computed(() => {
    const values = Object.values(hourCompliance.value).map(v => parseFloat(v) || 0)
    if (values.length === 0) return '0.0'
    const sum = values.reduce((a, b) => a + b, 0)
    return (sum / values.length).toFixed(1)
})

const formatNumber = (num) => (num || 0).toLocaleString('es-MX')

const formatDateTime = (datetime) => {
    if (!datetime) return '-'
    const date = new Date(datetime)
    return date.toLocaleString('es-MX', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const initProduction = () => {
    for (const line of lineas.value) {
        for (const hora of horas.value) {
            const key = getKey(line.id, hora.start)
            const scheduleKey = `${hora.start}-${line.id}`
            const schedule = schedulesMapRef.value[scheduleKey]
            productionValues.value[key] = schedule?.produced || 0
        }
    }
}

const autoSave = (lineId, hourStart) => {
    clearTimeout(autoSaveTimeouts[`${lineId}-${hourStart}`])
    autoSaveTimeouts[`${lineId}-${hourStart}`] = setTimeout(async () => {
        const key = getKey(lineId, hourStart)
        const scheduleKey = `${hourStart}-${lineId}`
        const schedule = schedulesMapRef.value[scheduleKey]
        if (!schedule || !schedule.id) return
        try {
            await axios.post(route('supervisor.production.auto-save'), {
                schedule_id: schedule.id,
                produced: productionValues.value[key] || 0
            })
        } catch (error) {
            console.error('Auto-save error:', error)
        }
    }, 1000)
}

const crearPrograma = async () => {
    creandoPrograma.value = true
    try {
        await axios.post(route('supervisor.daily-program.store'), {
            date: selectedDate.value,
            id_work_center: workCenter.value.id,
            shift: selectedShift.value,
            programmed: nuevoPrograma.value.programmed,
            backwardness: nuevoPrograma.value.backwardness,
            advanced: nuevoPrograma.value.advanced,
            shift_hours: 9
        })
        toast.success('Programa creado correctamente')
        router.reload()
    } catch (error) {
        console.error('Error:', error)
        toast.error('Error al crear el programa: ' + (error.response?.data?.message || error.message))
    } finally {
        creandoPrograma.value = false
    }
}

const guardarPrograma = async () => {
    savingProgram.value = true
    try {
        await axios.post(route('supervisor.daily-program.store'), {
            date: selectedDate.value,
            id_work_center: workCenter.value.id,
            shift: selectedShift.value,
            programmed: programData.value.programmed,
            backwardness: programData.value.backwardness,
            advanced: programData.value.advanced,
            shift_hours: 9
        })
        toast.success('Programa guardado')
        router.reload()
    } catch (error) {
        toast.error('Error al guardar: ' + (error.response?.data?.message || error.message))
    } finally {
        savingProgram.value = false
    }
}

const loadStrikes = async () => {
    if (!dailyProgramId.value) return
    try {
        // Usar la ruta con el prefijo supervisor
        const res = await axios.get(`/supervisor/strikes/${dailyProgramId.value}`)
        strikesList.value = res.data || []
    } catch (error) {
        console.error('Error loading strikes:', error)
    }
}

const abrirModalParo = () => {
    const now = new Date()
    const hours = String(now.getHours()).padStart(2, '0')
    const minutes = String(now.getMinutes()).padStart(2, '0')
    
    nuevoParo.value = {
        line_id: lineas.value[0]?.id || null,
        machine_id: null,
        start_time: `${hours}:${minutes}`,
        end_time: '',
        description: ''
    }
    modalVisible.value = true
}

const cerrarModalParo = () => { modalVisible.value = false }

const registrarParo = async () => {
    if (!nuevoParo.value.description) { toast.error('Describa el paro'); return }
    if (!nuevoParo.value.start_time) { toast.error('Indique la hora de inicio'); return }
    try {
        await axios.post(route('supervisor.strikes.store'), {
            id_production_line: nuevoParo.value.line_id,
            id_daily_program: dailyProgramId.value,
            date: selectedDate.value,
            start_time: nuevoParo.value.start_time,
            end_time: nuevoParo.value.end_time || null,
            description: nuevoParo.value.description,
            id_machine: nuevoParo.value.machine_id || null
        })
        toast.success('Paro registrado correctamente')
        cerrarModalParo()
        loadStrikes()
    } catch (error) { 
        console.error('Error:', error)
        toast.error('Error al registrar: ' + (error.response?.data?.message || error.message))
    }
}

const finalizarParo = (strike) => {
    const now = new Date()
    const hours = String(now.getHours()).padStart(2, '0')
    const minutes = String(now.getMinutes()).padStart(2, '0')
    const endTime = `${hours}:${minutes}`
    
    confirmStrike.value = strike
    confirmEndTime.value = endTime
    showConfirmModal.value = true
}

const confirmEndStrike = async () => {
    showConfirmModal.value = false
    
    try {
        await axios.put(route('supervisor.strikes.end', confirmStrike.value.id), {
            end_time: confirmEndTime.value
        })
        toast.success('Paro finalizado correctamente')
        loadStrikes()
    } catch (error) { 
        toast.error('Error: ' + (error.response?.data?.message || error.message))
    }
    
    confirmStrike.value = null
    confirmEndTime.value = ''
}

const cancelEndStrike = () => {
    showConfirmModal.value = false
    confirmStrike.value = null
    confirmEndTime.value = ''
}

const cambiarFecha = () => {
    router.get(route('supervisor.daily-production'), {
        work_center_id: workCenter.value.id,
        date: selectedDate.value,
        shift: selectedShift.value
    })
}

const cambiarTurno = () => {
    router.get(route('supervisor.daily-production'), {
        work_center_id: workCenter.value.id,
        date: selectedDate.value,
        shift: selectedShift.value
    })
}

// Métodos de ajuste manual
const abrirModalAjusteManual = () => {
    ajusteManual.value = {
        adjustment_type: 'manual_count',
        previous_value: kpis.value?.fabricated || 0,
        new_value: kpis.value?.fabricated || 0,
        reason: '',
        notes: ''
    }
    modalAjusteVisible.value = true
}

const cerrarModalAjuste = () => {
    modalAjusteVisible.value = false
}

const guardarAjusteManual = async () => {
    if (!ajusteManual.value.reason) {
        toast.error('Por favor ingresa el motivo del ajuste')
        return
    }

    guardandoAjuste.value = true
    try {
        await axios.post('/supervisor/manual-adjustment', {
            daily_program_id: dailyProgramId.value,
            adjustment_type: ajusteManual.value.adjustment_type,
            previous_value: ajusteManual.value.previous_value,
            new_value: ajusteManual.value.new_value,
            reason: ajusteManual.value.reason,
            notes: ajusteManual.value.notes
        })

        toast.success('Ajuste registrado correctamente')
        cerrarModalAjuste()
        router.reload()
    } catch (error) {
        console.error('Error al guardar ajuste:', error)
        toast.error('Error al guardar el ajuste: ' + (error.response?.data?.message || error.message))
    } finally {
        guardandoAjuste.value = false
    }
}

// Procesar balance
const procesarBalance = async () => {
    if (!dailyProgramId.value) {
        toast.error('No hay programa diario para procesar')
        return
    }

    if (!confirm('¿Estás seguro de procesar el balance? Esto calculará los atrasos/adelantos para el siguiente día.')) {
        return
    }

    procesandoBalance.value = true
    try {
        const response = await axios.post('/supervisor/process-balance', {
            daily_program_id: dailyProgramId.value
        })

        if (response.data.success) {
            toast.success('Balance procesado correctamente')
            router.reload()
        } else {
            toast.error('Error: ' + response.data.message)
        }
    } catch (error) {
        console.error('Error al procesar balance:', error)
        toast.error('Error al procesar el balance')
    } finally {
        procesandoBalance.value = false
    }
}

// Ver historial de ajustes
const verHistorialAjustes = () => {
    const startDate = new Date(selectedDate.value)
    startDate.setDate(1) // Primer día del mes
    const endDate = new Date(selectedDate.value)
    endDate.setMonth(endDate.getMonth() + 1)
    endDate.setDate(0) // Último día del mes

    const url = `/supervisor/adjustments-history?work_center_id=${workCenter.value.id}&start_date=${startDate.toISOString().split('T')[0]}&end_date=${endDate.toISOString().split('T')[0]}`
    window.open(url, '_blank')
}

// Watcher para sincronizar si props.date cambia después
watch(() => props.date, (newDate) => {
    if (newDate && newDate !== selectedDate.value) {
        selectedDate.value = newDate
    }
})

let clockInterval
onMounted(() => {
    console.log('=== FECHA ACTUAL ===')
    console.log('Fecha del controlador (props.date):', props.date)
    console.log('Fecha seleccionada (selectedDate):', selectedDate.value)
    console.log('Fecha actual del sistema:', new Date().toISOString().split('T')[0])
    
    const updateClock = () => {
        const now = new Date()
        currentTime.value = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`
    }
    updateClock()
    clockInterval = setInterval(updateClock, 1000)
    initProduction()
    loadStrikes()
    
    // Recargar strikes cada 30 segundos para detectar cambios por Gerente de Mantenimiento
    strikesInterval = setInterval(loadStrikes, 30000)
})

onUnmounted(() => { 
    clearInterval(clockInterval)
    if (strikesInterval) clearInterval(strikesInterval)
})
</script>