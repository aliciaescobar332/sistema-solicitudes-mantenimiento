<template>
  <div class="detalle-page">
    <header class="page-header">
      <div class="header-brand">
        <div class="header-logo">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <rect x="3" y="7" width="18" height="13" rx="2" stroke="white" stroke-width="2"/>
            <path d="M12 11v4M10 13h4" stroke="white" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
        <h1 class="header-title">Hospital Salud Integral</h1>
      </div>
      <div class="header-actions">
        <router-link to="/coordinacion" class="back-link">← Volver al Panel</router-link>
      </div>
    </header>

    <main class="page-main">
      <p v-if="loading" class="state-msg">Cargando solicitud...</p>
      <p v-else-if="error" class="state-msg state-error">{{ error }}</p>

      <template v-else>
        <p class="breadcrumbs">Inicio › Coordinación › Solicitud #SOL-{{ solicitud.id }}</p>

        <div class="title-row">
          <h2 class="page-title">{{ solicitud.titulo }}</h2>
        </div>

        <div class="tag-row">
          <span class="priority-badge" :class="prioridadClase(solicitud.prioridad)">PRIORIDAD: {{ (solicitud.prioridad || '').toUpperCase() }}</span>
          <span class="status-badge" :class="estadoClase(solicitud.estado)">ESTADO: {{ (solicitud.estado || '').toUpperCase() }}</span>
        </div>

        <div class="content-grid">
          <div class="main-column">
            <section class="info-card">
              <h3><span class="card-icon">ℹ</span> Descripción de la Solicitud</h3>
              <div class="info-grid">
                <div>
                  <p class="info-label">UBICACIÓN</p>
                  <p class="info-value">{{ solicitud.ubicacion || '—' }}</p>
                </div>
                <div>
                  <p class="info-label">DEPARTAMENTO</p>
                  <p class="info-value">{{ solicitud.departamento || '—' }}</p>
                </div>
              </div>
              <p class="info-label">DESCRIPCIÓN</p>
              <p class="info-description">
                {{ solicitud.descripcion || 'Esta solicitud no tiene una descripción registrada.' }}
              </p>
            </section>

            <section class="info-card">
              <h3><span class="card-icon">🔧</span> Reasignar Técnico</h3>
              <p class="info-description">
                Cambia el técnico responsable de esta solicitud. El cambio queda registrado
                automáticamente en el historial, indicando quién lo hizo y cuándo.
              </p>

              <div class="reasignar-row">
                <select v-model="tecnicoSeleccionado" class="tecnico-select-lg">
                  <option :value="null">Sin asignar</option>
                  <option v-for="t in tecnicos" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
                <button
                  class="guardar-tecnico-btn"
                  :disabled="!cambioTecnicoPendiente || guardandoTecnico"
                  @click="guardarTecnico"
                >
                  {{ guardandoTecnico ? 'Guardando...' : 'Guardar' }}
                </button>
              </div>
              <p v-if="errorTecnico" class="upload-error">{{ errorTecnico }}</p>
              <p v-if="exitoTecnico" class="exito-msg">Técnico reasignado correctamente.</p>
            </section>

            <section class="info-card">
              <h3><span class="card-icon">📋</span> Historial de la Solicitud</h3>

              <p v-if="cargandoHistorial" class="state-msg-inline">Cargando historial...</p>
              <p v-else-if="errorHistorial" class="state-msg-inline state-error">{{ errorHistorial }}</p>
              <p v-else-if="historial.length === 0" class="state-msg-inline">
                Todavía no hay cambios registrados para esta solicitud.
              </p>

              <div class="timeline" v-else>
                <div class="timeline-item" v-for="item in historial" :key="item.id">
                  <span class="timeline-dot"></span>
                  <div class="timeline-content">
                    <div class="timeline-head">
                      <strong>{{ descripcionCambio(item) }}</strong>
                      <span class="timeline-time">{{ formatFechaHora(item.created_at) }}</span>
                    </div>
                    <p>{{ item.user ? item.user.name : 'Sistema' }}</p>
                  </div>
                </div>
              </div>
            </section>

            <section class="info-card">
              <h3><span class="card-icon">📷</span> Evidencia de la Solicitud</h3>

              <p v-if="cargandoEvidencias" class="state-msg-inline">Cargando evidencia...</p>
              <p v-else-if="errorEvidencias" class="state-msg-inline state-error">{{ errorEvidencias }}</p>
              <p v-else-if="evidencias.length === 0" class="state-msg-inline">
                Todavía no se ha subido evidencia para esta solicitud.
              </p>

              <div class="evidencia-grid" v-else>
                <a
                  v-for="ev in evidencias"
                  :key="ev.id"
                  :href="urlEvidencia(ev)"
                  target="_blank"
                  class="evidencia-item"
                >
                  <img v-if="esImagen(ev)" :src="urlEvidencia(ev)" :alt="ev.nombre_archivo" class="evidencia-thumb" />
                  <div v-else class="evidencia-doc">📄</div>
                  <div class="evidencia-info">
                    <p class="evidencia-nombre">{{ ev.nombre_archivo }}</p>
                    <p class="evidencia-meta">{{ ev.user ? ev.user.name : 'Sistema' }} · {{ formatFechaHora(ev.created_at) }}</p>
                  </div>
                </a>
              </div>
            </section>
          </div>
        </div>
      </template>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import solicitudesService from '../../services/solicitudesService';
import tecnicosService from '../../services/tecnicosService';
import evidenciasService from '../../services/evidenciasService';

const route = useRoute();

const solicitud = ref({});
const loading = ref(true);
const error = ref('');

const historial = ref([]);
const cargandoHistorial = ref(true);
const errorHistorial = ref('');
const tecnicos = ref([]);

const evidencias = ref([]);
const cargandoEvidencias = ref(true);
const errorEvidencias = ref('');

const tecnicoSeleccionado = ref(null);
const guardandoTecnico = ref(false);
const errorTecnico = ref('');
const exitoTecnico = ref(false);

const cambioTecnicoPendiente = computed(() => {
  return tecnicoSeleccionado.value !== (solicitud.value.tecnico_id ?? null);
});

const NOMBRES_CAMPO = {
  estado: 'Estado',
  prioridad: 'Prioridad',
  tecnico_id: 'Técnico asignado',
};

function prioridadClase(prioridad) {
  if (prioridad === 'Alta') return 'priority-alta';
  if (prioridad === 'Media') return 'priority-media';
  return 'priority-baja';
}

function estadoClase(estado) {
  if (estado === 'Pendiente') return 'status-pendiente';
  if (estado === 'En Proceso') return 'status-proceso';
  if (estado === 'Completada') return 'status-completada';
  return '';
}

function formatFechaHora(fecha) {
  if (!fecha) return '';
  const date = new Date(fecha);
  return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' }) +
    ' ' + date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
}

function nombreTecnico(id) {
  if (!id) return 'Sin asignar';
  const tecnico = tecnicos.value.find((t) => String(t.id) === String(id));
  return tecnico ? tecnico.name : `Técnico #${id}`;
}

function valorLegible(campo, valor) {
  if (campo === 'tecnico_id') return nombreTecnico(valor);
  return valor || '—';
}

function descripcionCambio(item) {
  const nombreCampo = NOMBRES_CAMPO[item.campo] || item.campo;
  const anterior = valorLegible(item.campo, item.valor_anterior);
  const nuevo = valorLegible(item.campo, item.valor_nuevo);
  return `${nombreCampo}: ${anterior} → ${nuevo}`;
}

function urlEvidencia(ev) {
  return `/storage/${ev.ruta}`;
}

function esImagen(ev) {
  return (ev.tipo || '').startsWith('image/');
}

async function cargarSolicitud() {
  loading.value = true;
  error.value = '';
  try {
    solicitud.value = await solicitudesService.obtener(route.params.id);
    tecnicoSeleccionado.value = solicitud.value.tecnico_id ?? null;
  } catch (e) {
    error.value = 'No se pudo cargar la solicitud.';
  } finally {
    loading.value = false;
  }
}

async function cargarHistorial() {
  cargandoHistorial.value = true;
  errorHistorial.value = '';
  try {
    historial.value = await solicitudesService.historial(route.params.id);
  } catch (e) {
    errorHistorial.value = 'No se pudo cargar el historial.';
  } finally {
    cargandoHistorial.value = false;
  }
}

async function cargarEvidencias() {
  cargandoEvidencias.value = true;
  errorEvidencias.value = '';
  try {
    evidencias.value = await evidenciasService.listar(route.params.id);
  } catch (e) {
    errorEvidencias.value = 'No se pudo cargar la evidencia.';
  } finally {
    cargandoEvidencias.value = false;
  }
}

async function cargarTecnicos() {
  try {
    tecnicos.value = await tecnicosService.listar();
  } catch (e) {
    console.error('No se pudieron cargar los técnicos', e);
  }
}

async function guardarTecnico() {
  guardandoTecnico.value = true;
  errorTecnico.value = '';
  exitoTecnico.value = false;
  try {
    solicitud.value = await solicitudesService.actualizar(route.params.id, {
      tecnico_id: tecnicoSeleccionado.value,
    });
    exitoTecnico.value = true;
    await cargarHistorial();
    setTimeout(() => { exitoTecnico.value = false; }, 3000);
  } catch (e) {
    errorTecnico.value = 'No se pudo guardar el cambio de técnico.';
  } finally {
    guardandoTecnico.value = false;
  }
}

onMounted(() => {
  cargarSolicitud();
  cargarHistorial();
  cargarEvidencias();
  cargarTecnicos();
});
</script>

<style scoped>
.detalle-page {
  min-height: 100vh;
  background: #f4f6fa;
  font-family: system-ui, -apple-system, sans-serif;
  color: #1e293b;
}

.page-header {
  display: flex; align-items: center; justify-content: space-between; gap: 1.5rem;
  background: #fff; padding: 0.85rem 1.5rem; border-bottom: 1px solid #e5e9f0;
  flex-wrap: wrap;
}
.header-brand { display: flex; align-items: center; gap: 0.6rem; }
.header-logo { width: 32px; height: 32px; border-radius: 8px; background: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.header-logo svg { width: 18px; height: 18px; }
.header-title { font-size: 0.95rem; font-weight: 700; margin: 0; }
.back-link { color: #2563eb; text-decoration: none; font-size: 0.85rem; font-weight: 600; }

.page-main { max-width: 1100px; margin: 0 auto; padding: 1.5rem; }
.state-msg { color: #64748b; padding: 2rem 0; }
.state-error { color: #dc2626; }
.state-msg-inline { color: #94a3b8; font-size: 0.85rem; margin: 0; }

.breadcrumbs { font-size: 0.8rem; color: #94a3b8; margin: 0 0 0.75rem; }

.title-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; }
.page-title { font-size: 1.4rem; font-weight: 800; margin: 0; }

.tag-row { display: flex; gap: 0.6rem; margin: 0.75rem 0 1.5rem; }
.priority-badge, .status-badge { font-size: 0.75rem; font-weight: 700; padding: 0.3rem 0.7rem; border-radius: 14px; }
.priority-alta { background: #fef2f2; color: #dc2626; }
.priority-media { background: #fff7ed; color: #d97706; }
.priority-baja { background: #f0fdf4; color: #16a34a; }
.status-pendiente { background: #fef3c7; color: #b45309; }
.status-proceso { background: #dbeafe; color: #1d4ed8; }
.status-completada { background: #dcfce7; color: #15803d; }

.content-grid { display: grid; grid-template-columns: 1fr; gap: 1.25rem; align-items: start; }
.main-column { display: flex; flex-direction: column; gap: 1.25rem; }

.info-card { background: #fff; border-radius: 12px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.info-card h3 { font-size: 0.95rem; margin: 0 0 1rem; display: flex; align-items: center; gap: 0.4rem; }
.card-icon { font-size: 0.9rem; }

.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
.info-label { font-size: 0.72rem; color: #94a3b8; font-weight: 700; letter-spacing: 0.03em; margin: 0 0 0.2rem; }
.info-value { font-size: 0.9rem; font-weight: 600; margin: 0; }
.info-description { font-size: 0.88rem; color: #475569; margin: 0; line-height: 1.5; }

.reasignar-row { display: flex; gap: 0.6rem; margin-top: 1rem; flex-wrap: wrap; }
.tecnico-select-lg { flex: 1; min-width: 180px; padding: 0.55rem 0.7rem; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 0.88rem; }
.guardar-tecnico-btn { background: #2563eb; color: #fff; border: none; padding: 0.55rem 1.1rem; border-radius: 8px; font-size: 0.85rem; font-weight: 700; cursor: pointer; }
.guardar-tecnico-btn:disabled { background: #cbd5e1; cursor: not-allowed; }
.upload-error { color: #dc2626; font-size: 0.8rem; margin: 0.6rem 0 0; }
.exito-msg { color: #16a34a; font-size: 0.8rem; margin: 0.6rem 0 0; font-weight: 600; }

.timeline { display: flex; flex-direction: column; gap: 1rem; }
.timeline-item { display: flex; gap: 0.75rem; }
.timeline-dot { width: 9px; height: 9px; border-radius: 50%; background: #2563eb; margin-top: 0.4rem; flex-shrink: 0; }
.timeline-content { flex: 1; }
.timeline-head { display: flex; justify-content: space-between; gap: 0.75rem; font-size: 0.88rem; margin-bottom: 0.15rem; flex-wrap: wrap; }
.timeline-time { color: #94a3b8; font-size: 0.78rem; font-weight: 500; white-space: nowrap; }
.timeline-content p { margin: 0; font-size: 0.83rem; color: #64748b; line-height: 1.4; }

.evidencia-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.75rem; }
.evidencia-item { display: flex; flex-direction: column; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; text-decoration: none; color: inherit; background: #fff; }
.evidencia-thumb { width: 100%; height: 100px; object-fit: cover; display: block; }
.evidencia-doc { width: 100%; height: 100px; display: flex; align-items: center; justify-content: center; font-size: 2rem; background: #f1f5f9; }
.evidencia-info { padding: 0.5rem 0.6rem; }
.evidencia-nombre { font-size: 0.78rem; font-weight: 600; margin: 0 0 0.15rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.evidencia-meta { font-size: 0.7rem; color: #94a3b8; margin: 0; }
</style>