<!-- resources/js/Pages/Gerencia/PlantOverview.vue -->
<template>
    <div id="app">
        <GerenciaSidebar />

        <header>
            <div class="header-top">
                <div class="brand">
                    <div class="brand-icon">LI</div>
                    <div class="brand-txt">
                        <div class="name">línea italia</div>
                        <div class="sub">MOBILIARIO DE OFICINA</div>
                    </div>
                </div>
                <div class="title-block">
                    <h1>Estado General de Planta</h1>
                    <div class="live"><span class="live-dot"></span>Datos en vivo · todas las áreas</div>
                </div>
                <div class="clock">
                    <div class="time mono">{{ clockTime }}</div>
                    <div class="date">{{ clockDate }}</div>
                </div>
            </div>

            <div class="metrics">
                <div class="metric">
                    <span class="m-label">Cumplimiento promedio</span>
                    <span class="m-value">{{ stats.avg_pct }}<span class="m-unit">%</span></span>
                </div>
                <div class="metric ok">
                    <span class="m-label">Operando en programa</span>
                    <span class="m-value">{{ stats.green }}<span class="m-unit">máq.</span></span>
                </div>
                <div class="metric warn">
                    <span class="m-label">Con atraso</span>
                    <span class="m-value">{{ stats.amber }}<span class="m-unit">máq.</span></span>
                </div>
                <div class="metric crit">
                    <span class="m-label">Paradas</span>
                    <span class="m-value">{{ stats.red }}<span class="m-unit">máq.</span></span>
                </div>
                <div class="metric">
                    <span class="m-label">Áreas monitoreadas</span>
                    <span class="m-value">{{ stats.areas }}<span class="m-unit">áreas</span></span>
                </div>
            </div>
        </header>

        <div id="board">
            <div v-for="group in phaseGroups" :key="group.phase" class="phase-row">
                <div class="phase-label"><span>FASE {{ group.phase }}</span></div>
                <div class="phase-tiles">
                    <div v-for="tile in group.items" :key="`${tile.area}-${tile.name}`" class="tile" :class="tile.status">
                        <div class="row-top">
                            <span class="area-group">
                                <span class="area">{{ tile.area }}</span>
                                <span
                                    class="area-dot"
                                    :class="tile.area_status"
                                    @click.stop="showAreaPopover($event, tile)"
                                    @mouseenter="showAreaPopover($event, tile)"
                                    @mouseleave="hideAreaPopover"
                                ></span>
                            </span>
                            <span class="icon-badge">{{ ICONS[tile.status] }}</span>
                        </div>
                        <div class="mname">{{ tile.name }}</div>
                        <div class="bottom">
                            <div class="pct">{{ tile.pct }}<span>%</span></div>
                            <div class="bar-bg"><div class="bar-fill" :style="{ width: Math.min(tile.pct, 100) + '%' }"></div></div>
                            <div v-if="tile.status !== 'gray'" class="cap-note">Capacidad: {{ tile.pct_capacity }}%</div>
                            <div v-if="tile.reason" class="reason">⚠ {{ tile.reason }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer>
            <div class="ai-badge">🤖</div>
            <div class="ai-text">
                <div class="ai-head">
                    <span class="lbl">Recomendación de mejora</span>
                    <span class="badge-tag">Análisis automático</span>
                    <span class="updated">{{ recommendationUpdated }}</span>
                </div>
                <div class="ai-msg">{{ recommendationMsg }}</div>
            </div>
        </footer>
    </div>

    <Teleport to="body">
        <div v-if="popover.visible" class="area-popover" :style="{ top: popover.top + 'px', left: popover.left + 'px' }">
            <div class="area-popover-title">{{ popover.area }}</div>
            <div v-for="attr in popover.attributes" :key="attr.name" class="area-popover-row">
                <span class="area-popover-dot" :class="attr.color"></span>
                <span class="area-popover-name">{{ attr.name }}</span>
                <span class="area-popover-time">{{ elapsedTime(attr.changed_at) }}</span>
            </div>
            <div v-if="popover.attributes.length === 0" class="area-popover-empty">Sin semáforos configurados</div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import GerenciaSidebar from '@/Components/GerenciaSidebar.vue';

const props = defineProps({
    date: String,
    machines: Array,
    stats: Object,
});

const ICONS = { green: '✓', amber: '!', red: '×', gray: '–' };

// La cuadrícula de tarjetas es responsiva por CSS (auto-fill), no requiere
// calcular columnas por JavaScript: así se adapta igual a un monitor de
// oficina, una tablet o un celular, con scroll normal en pantallas angostas.
const phaseGroups = computed(() =>
    [1, 2, 3, 4]
        .map((phase) => ({ phase, items: props.machines.filter((m) => m.phase === phase) }))
        .filter((g) => g.items.length > 0)
);

// ===== Popover de semáforos de área =====
const popover = ref({ visible: false, top: 0, left: 0, area: '', attributes: [] });

const POPOVER_WIDTH = 200;
const POPOVER_MAX_HEIGHT = 190;

function positionAreaPopover(event, tile) {
    const rect = event.currentTarget.getBoundingClientRect();
    const margin = 8;

    let left = rect.left;
    if (left + POPOVER_WIDTH > window.innerWidth - margin) {
        left = window.innerWidth - margin - POPOVER_WIDTH;
    }

    let top = rect.bottom + 6;
    if (top + POPOVER_MAX_HEIGHT > window.innerHeight - margin) {
        top = rect.top - POPOVER_MAX_HEIGHT - 6;
    }

    popover.value = {
        visible: true,
        top: Math.max(margin, top),
        left: Math.max(margin, left),
        area: tile.area,
        attributes: tile.area_attributes || [],
    };
}

function showAreaPopover(event, tile) {
    positionAreaPopover(event, tile);
}

function hideAreaPopover() {
    popover.value.visible = false;
}

function closeAreaPopoverOnOutsideClick() {
    popover.value.visible = false;
}

function elapsedTime(iso) {
    if (!iso) return 'Sin datos';
    const diffMs = Date.now() - new Date(iso).getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);
    if (diffDays > 0) return `${diffDays}d ${diffHours % 24}h`;
    if (diffHours > 0) return `${diffHours}h ${diffMins % 60}m`;
    return `${diffMins}m`;
}

// ===== Reloj =====
const clockTime = ref('--:--:--');
const clockDate = ref('—');
let clockInterval = null;

function tick() {
    const now = new Date();
    clockTime.value = now.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    clockDate.value = now.toLocaleDateString('es-MX', { weekday: 'long', day: '2-digit', month: 'short' });
}

// ===== Recomendación: mismo análisis que el mockup de referencia, sobre datos reales =====
function analyzePlant() {
    const machines = props.machines;
    if (machines.length === 0) return 'Sin datos de planta para hoy.';

    const paradas = machines.filter((m) => m.status === 'red');
    const atrasos = machines.filter((m) => m.status === 'amber');
    const ok = machines.filter((m) => m.status === 'green');
    const avg = props.stats.avg_pct;
    const peor = [...paradas, ...atrasos].sort((a, b) => a.pct - b.pct)[0];
    const mejorArea = ok.length ? [...ok].sort((a, b) => b.pct - a.pct)[0] : null;

    const motivos = {};
    [...paradas, ...atrasos].forEach((m) => {
        if (!m.reason) return;
        motivos[m.reason] = (motivos[m.reason] || 0) + 1;
    });
    const motivoTop = Object.entries(motivos).sort((a, b) => b[1] - a[1])[0];

    const variantes = [];

    if (paradas.length > 0 && peor) {
        variantes.push(
            `Hay ${paradas.length} línea${paradas.length > 1 ? 's' : ''} parada${paradas.length > 1 ? 's' : ''} (${paradas.map((m) => m.name).join(', ')}). Prioridad inmediata: resolver "${peor.reason}" en ${peor.name} — es lo que más está afectando el cumplimiento de hoy (promedio de planta ${avg}%).`
        );
    }
    if (motivoTop && motivoTop[1] > 1) {
        variantes.push(
            `Se repite "${motivoTop[0]}" en ${motivoTop[1]} líneas distintas. Recomendación: investigar la causa raíz en lugar de atender cada paro por separado.`
        );
    }
    if (atrasos.length > 0) {
        variantes.push(
            `${atrasos.length} línea${atrasos.length > 1 ? 's' : ''} con atraso vs. programa (promedio de planta ${avg}%). Sugerencia: revisar si el atraso es puntual o si conviene ajustar el ritmo para no arrastrar el déficit al siguiente turno.`
        );
    }
    if (mejorArea) {
        variantes.push(
            `${ok.length} de ${machines.length} líneas están cumpliendo programa; ${mejorArea.name} (${mejorArea.area}) tiene el mejor desempeño con ${mejorArea.pct}%. Vale la pena revisar qué está haciendo bien esa línea.`
        );
    }
    variantes.push(
        `Cumplimiento promedio de planta: ${avg}%. ${paradas.length + atrasos.length === 0 ? 'Todas las líneas están dentro de programa.' : `Con ${paradas.length} paro(s) y ${atrasos.length} atraso(s) activos, enfocar el seguimiento de esta hora en las áreas con semáforo rojo.`}`
    );

    const idx = new Date().getHours() % variantes.length;
    return variantes[idx];
}

const recommendationMsg = ref('Generando recomendación…');
const recommendationUpdated = ref('—');
let hourlyTimeout = null;
let hourlyInterval = null;

function updateRecommendation() {
    recommendationMsg.value = analyzePlant();
    recommendationUpdated.value = 'Actualizado ' + new Date().toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
}

function scheduleHourlyUpdate() {
    const now = new Date();
    const msToNextHour = (60 - now.getMinutes()) * 60000 - now.getSeconds() * 1000;
    hourlyTimeout = setTimeout(() => {
        updateRecommendation();
        hourlyInterval = setInterval(updateRecommendation, 3600000);
    }, msToNextHour);
}

// ===== Refresco de datos =====
let refreshInterval = null;

onMounted(() => {
    tick();
    clockInterval = setInterval(tick, 1000);

    updateRecommendation();
    scheduleHourlyUpdate();

    refreshInterval = setInterval(() => {
        router.reload({ only: ['machines', 'stats'], onSuccess: () => updateRecommendation() });
    }, 60000);

    window.addEventListener('click', closeAreaPopoverOnOutsideClick);
});

onUnmounted(() => {
    if (clockInterval) clearInterval(clockInterval);
    if (refreshInterval) clearInterval(refreshInterval);
    if (hourlyTimeout) clearTimeout(hourlyTimeout);
    if (hourlyInterval) clearInterval(hourlyInterval);
    window.removeEventListener('click', closeAreaPopoverOnOutsideClick);
});
</script>

<style scoped>
.mono{font-family:'IBM Plex Mono',monospace;}

#app{
  --bg:#eef1f6;
  --graphite:#12151f;
  --line:#dde1ea;
  --card:#ffffff;
  --text:#1a1f2c;
  --muted:#8992a6;
  --green:#1f8a4c;
  --green-soft:#e6f4ec;
  --amber:#b8790f;
  --amber-soft:#fbf0dd;
  --red:#c23636;
  --red-soft:#fbe6e6;
  --gray:#6b7280;
  --gray-soft:#eceef1;
  min-height:100vh;display:flex;flex-direction:column;font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;background:var(--bg);color:var(--text);
}

header{background:var(--graphite);color:#fff;flex:0 0 auto;padding:12px 26px 12px 64px;}
.header-top{display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;row-gap:10px;}
.brand{display:flex;align-items:center;gap:12px;}
.brand-icon{width:34px;height:34px;border-radius:8px;background:#3355d8;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:12px;color:#fff;flex:0 0 auto;}
.brand-txt .name{font-size:15px;font-weight:700;letter-spacing:.2px;}
.brand-txt .sub{font-size:8.5px;letter-spacing:1.6px;color:#8992b8;margin-top:1px;}

.title-block{text-align:center;}
.title-block h1{font-size:14px;font-weight:600;letter-spacing:.3px;color:#fff;}
.title-block .live{font-size:9.5px;color:#7fd39c;margin-top:2px;display:flex;align-items:center;justify-content:center;gap:5px;}
.live-dot{width:6px;height:6px;background:#22c55e;border-radius:50%;display:inline-block;animation:blink 1.8s infinite;}
@keyframes blink{0%,100%{opacity:1;}50%{opacity:.25;}}

.clock{text-align:right;}
.clock .time{font-size:19px;font-weight:700;font-family:'IBM Plex Mono',monospace;letter-spacing:.5px;}
.clock .date{font-size:9px;color:#8992b8;text-transform:capitalize;margin-top:1px;}

.metrics{display:flex;flex-wrap:wrap;row-gap:10px;margin-top:12px;border-top:1px solid rgba(255,255,255,.08);padding-top:10px;}
.metric{flex:1 1 150px;padding:0 20px;border-right:1px solid rgba(255,255,255,.08);display:flex;flex-direction:column;gap:2px;}
.metric:last-child{border-right:none;}
.metric .m-label{font-size:9.5px;text-transform:uppercase;letter-spacing:.8px;color:#8992b8;font-weight:600;}
.metric .m-value{font-size:24px;font-weight:700;font-family:'IBM Plex Mono',monospace;display:flex;align-items:baseline;gap:6px;}
.metric .m-value .m-unit{font-size:12px;color:#8992b8;font-weight:500;}
.metric.ok .m-value{color:#4ade80;}
.metric.warn .m-value{color:#f5b942;}
.metric.crit .m-value{color:#f27373;}

#board{flex:1 1 auto;display:flex;flex-direction:column;gap:16px;padding:16px 16px 16px 64px;}
.phase-row{display:flex;gap:10px;align-items:stretch;}
.phase-label{flex:0 0 auto;width:22px;border-radius:8px;background:var(--graphite);display:flex;align-items:center;justify-content:center;}
.phase-label span{writing-mode:vertical-rl;transform:rotate(180deg);font-size:10px;font-weight:700;letter-spacing:1.5px;color:#9fb0e8;text-transform:uppercase;white-space:nowrap;}
.phase-tiles{flex:1;display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:10px;align-items:start;}
.tile{background:var(--card);border:1px solid var(--line);border-radius:9px;border-left:4px solid var(--green);padding:12px 14px 11px;display:flex;flex-direction:column;justify-content:space-between;overflow:hidden;position:relative;}
.tile.amber{border-left-color:var(--amber);}
.tile.red{border-left-color:var(--red);}
.tile.gray{border-left-color:var(--gray);}

.tile .row-top{display:flex;align-items:flex-start;justify-content:space-between;gap:6px;}
.tile .area-group{display:flex;align-items:center;gap:5px;min-width:0;}
.tile .area{font-size:8.6px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:0;}
.tile .area-dot{width:7px;height:7px;border-radius:50%;flex:0 0 auto;cursor:pointer;}
.tile .area-dot.green{background:var(--green);}
.tile .area-dot.amber{background:var(--amber);}
.tile .area-dot.red{background:var(--red);}
.tile .area-dot.gray{background:var(--gray);}
.tile .icon-badge{width:18px;height:18px;border-radius:50%;flex:0 0 auto;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;}
.tile.green .icon-badge{background:var(--green-soft);color:var(--green);}
.tile.amber .icon-badge{background:var(--amber-soft);color:var(--amber);}
.tile.red .icon-badge{background:var(--red-soft);color:var(--red);}
.tile.gray .icon-badge{background:var(--gray-soft);color:var(--gray);}

.tile .mname{font-size:clamp(11px,1.3vw,14px);font-weight:700;color:var(--text);line-height:1.15;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}

.tile .bottom{margin-top:auto;}
.tile .pct{font-family:'IBM Plex Mono',monospace;font-weight:700;font-size:clamp(18px,2.6vw,26px);line-height:1;}
.tile.green .pct{color:var(--green);}
.tile.amber .pct{color:var(--amber);}
.tile.red .pct{color:var(--red);}
.tile.gray .pct{color:var(--gray);}
.tile .pct span{font-size:.5em;color:var(--muted);font-weight:600;}

.cap-note{font-size:9.5px;color:var(--muted);margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}

.bar-bg{height:4px;background:#eef0f4;border-radius:3px;margin-top:5px;overflow:hidden;}
.bar-fill{height:100%;border-radius:3px;}
.tile.green .bar-fill{background:var(--green);}
.tile.amber .bar-fill{background:var(--amber);}
.tile.red .bar-fill{background:var(--red);}
.tile.gray .bar-fill{background:var(--gray);}

.tile .reason{font-size:11.5px;font-weight:700;margin-top:6px;padding:3px 8px;border-radius:6px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;line-height:1.25;}
.tile.amber .reason{background:var(--amber-soft);color:var(--amber);}
.tile.red .reason{background:var(--red-soft);color:var(--red);}
.tile.gray .reason{background:var(--gray-soft);color:var(--gray);}

.tile.red{box-shadow:0 0 0 1px rgba(194,54,54,.15);}

footer{flex:0 0 auto;background:var(--graphite);border-top:1px solid rgba(255,255,255,.08);padding:12px 22px 12px 64px;display:flex;align-items:center;gap:14px;}
.ai-badge{flex:0 0 auto;width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg,#3355d8,#6a7ef0);display:flex;align-items:center;justify-content:center;font-size:13px;}
.ai-text{flex:1;min-width:0;}
.ai-head{display:flex;align-items:center;gap:8px;margin-bottom:2px;}
.ai-head .lbl{font-size:10px;font-weight:700;color:#fff;letter-spacing:.3px;}
.ai-head .badge-tag{font-size:8px;font-weight:700;color:#9fb0e8;background:rgba(255,255,255,.08);padding:1px 6px;border-radius:8px;letter-spacing:.4px;text-transform:uppercase;}
.ai-head .updated{font-size:8.5px;color:#6b7695;margin-left:auto;font-family:'IBM Plex Mono',monospace;flex:0 0 auto;}
.ai-msg{font-size:12px;line-height:1.35;color:#d7dbec;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}

.area-popover{position:fixed;z-index:9999;background:#ffffff;border:1px solid #dde1ea;border-radius:8px;box-shadow:0 10px 30px rgba(11,21,31,.2);padding:10px 12px;width:200px;font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;}
.area-popover-title{font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#8992a6;margin-bottom:6px;}
.area-popover-row{display:flex;align-items:center;gap:6px;padding:3px 0;font-size:11.5px;color:#1a1f2c;}
.area-popover-dot{width:8px;height:8px;border-radius:50%;flex:0 0 auto;}
.area-popover-dot.green{background:#1f8a4c;}
.area-popover-dot.amber{background:#b8790f;}
.area-popover-dot.red{background:#c23636;}
.area-popover-dot.gray{background:#6b7280;}
.area-popover-name{flex:1;font-weight:600;}
.area-popover-time{color:#8992a6;font-size:9.5px;font-family:'IBM Plex Mono',monospace;}
.area-popover-empty{font-size:11px;color:#8992a6;}

@media (max-width: 480px){
  header{padding-left:48px;}
  #board{padding-left:48px;}
  footer{padding-left:48px;flex-wrap:wrap;}
  .metric{padding:0 12px;flex-basis:calc(50% - 1px);}
  .metric .m-value{font-size:20px;}
  .title-block{width:100%;order:3;text-align:left;}
  .clock{text-align:left;}
}
</style>
