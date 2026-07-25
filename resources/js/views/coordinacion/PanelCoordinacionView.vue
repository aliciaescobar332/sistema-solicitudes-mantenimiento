<template>
  <div class="coord-layout">
    <aside class="sidebar">
      <div class="sidebar-brand">
        <div class="brand-logo">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <rect x="3" y="7" width="18" height="13" rx="2" stroke="white" stroke-width="2"/>
            <path d="M12 11v4M10 13h4" stroke="white" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
        <span class="brand-name">Salud Integral</span>
      </div>

      <nav class="sidebar-nav">
        <p class="nav-section">MENÚ PRINCIPAL</p>
        <a href="#" class="nav-item"><span class="nav-icon">📊</span> Tablero</a>
        <a href="#" class="nav-item active"><span class="nav-icon">📋</span> Solicitudes</a>
        <a href="#" class="nav-item"><span class="nav-icon">🔧</span> Técnicos</a>
        <a href="#" class="nav-item"><span class="nav-icon">📈</span> Reportes</a>
        <p class="nav-section">CONFIGURACIÓN</p>
        <a href="#" class="nav-item"><span class="nav-icon">⚙️</span> Ajustes</a>
      </nav>

      <div class="sidebar-user">
        <div class="user-avatar" title="Cerrar sesión" @click="cerrarSesion">{{ userInitial }}</div>
        <div>
          <p class="user-name">{{ displayName }}</p>
          <p class="user-role">Coordinador General</p>
        </div>
      </div>
    </aside>

    <div class="coord-main">
      <header class="coord-header">
        <div class="header-title-row">
          <h1>Panel de Coordinación</h1>
          <span class="header-tag">Mantenimiento</span>
        </div>
        <div class="header-actions">
          <div class="search-box">
            <span class="search-icon">🔍</span>
            <input type="text" v-model="busqueda" placeholder="Buscar solicitud..." @input="buscar" />
          </div>
          <span class="icon-btn">🔔</span>
          <span class="icon-btn">👤</span>
        </div>
      </header>

      <main class="coord-content">
        <section class="metrics-row">
          <div class="metric-card">
            <div class="metric-top">
              <span class="metric-icon icon-amber">📋</span>
              <span class="metric-delta" :class="resumen.delta_pendientes >= 0 ? 'delta-up' : 'delta-down'">
                {{ resumen.delta_pendientes >= 0 ? '+' : '' }}{{ resumen.delta_pendientes }}% hoy
              </span>
            </div>
            <p class="metric-label">Pendientes Hoy</p>
            <p class="metric-value">{{ resumen.pendientes }}</p>
          </div>

          <div class="metric-card">
            <div class="metric-top">
              <span class="metric-icon icon-blue">🔧</span>
              <span class="metric-delta delta-down">{{ resumen.delta_en_proceso }}% hoy</span>
            </div>
            <p class="metric-label">En Curso</p>
            <p class="metric-value">{{ resumen.en_proceso }}</p>
          </div>

          <div class="metric-card">
            <div class="metric-top">
              <span class="metric-icon icon-green">👥</span>
              <span class="metric-delta delta-neutral">{{ resumen.delta_personal }}% hoy</span>
            </div>
            <p class="metric-label">Personal Disponible</p>
            <p class="metric-value">{{ resumen.personal_disponible }}</p>
          </div>
        </section>

        <section class="table-card">
          <div class="table-card-header">
            <div>
              <h3>Solicitudes Pendientes</h3>
              <p class="table-subtitle">Gestión de tareas de mantenimiento entrantes</p>
            </div>
            <div class="table-actions">
              <button class="filter-btn">🔽 Filtrar</button>
              <router-link to="/solicitudes/nueva" class="new-btn">+ Nueva Solicitud</router-link>
            </div>
          </div>

          <p v-if="loading" class="table-loading">Cargando solicitudes...</p>
          <p v-else-if="error" class="table-error">{{ error }}</p>

          <table v-else class="coord-table">
            <thead>
              <tr>
                <th>ID SOLICITUD</th>
                <th>PRIORIDAD</th>
                <th>DEPARTAMENTO</th>
                <th>FECHA SOLICITUD</th>
                <th>ESTADO</th>
                <th>ACCIÓN</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in solicitudes" :key="s.id" :class="{ 'row-alta': s.prioridad === 'Alta' }">
                <td class="cell-id">
                  <router-link :to="`/solicitudes/${s.id}`" class="id-link">#SOL-{{ s.id }}</router-link>
                </td>

                <td>
                  <select
                    v-model="s.prioridad"
                    @change="registrarCambio(s, 'prioridad', s.prioridad)"
                    :class="prioridadClase(s.prioridad)"
                    class="priority-select"
                  >
                    <option value="Alta">Alta</option>
                    <option value="Media">Media</option>
                    <option value="Baja">Baja</option>
                  </select>
                </td>

                <td>{{ s.departamento ?? '—' }}</td>
                <td>{{ formatFechaHora(s.fecha) }}</td>

                <td>
                  <span class="status-badge" :class="estadoClase(s.estado)">{{ s.estado }}</span>
                </td>

                <td class="accion-cell">
                  <select
                    v-model="s.tecnico_id"
                    @change="registrarCambio(s, 'tecnico_id', s.tecnico_id)"
                    class="tecnico-select"
                  >
                    <option :value="null">Sin asignar</option>
                    <option v-for="t in tecnicos" :key="t.id" :value="t.id">{{ t.name }}</option>
                  </select>
                  <button
                    class="assign-btn"
                    :disabled="!cambiosPendientes[s.id]"
                    @click="guardarCambios(s)"
                  >
                    Guardar
                  </button>
                </td>
              </tr>
              <tr v-if="solicitudes.length === 0">
                <td colspan="6" class="cell-empty">No hay solicitudes registradas.</td>
              </tr>
            </tbody>
          </table>

          <div class="table-footer">
            <span>Mostrando {{ solicitudes.length }} de {{ totalSolicitudes }} solicitudes</span>
            <div class="pagination">
              <button v-for="p in totalPaginas" :key="p" class="page-btn" :class="{ active: p === paginaActual }" @click="cambiarPagina(p)">{{ p }}</button>
              <button class="page-btn" :disabled="paginaActual >= totalPaginas" @click="cambiarPagina(paginaActual + 1)">›</button>
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import solicitudesService from '../../services/solicitudesService';
import tecnicosService from '../../services/tecnicosService';

const authStore = useAuthStore();
const router = useRouter();

const solicitudes = ref([]);
const tecnicos = ref([]);
const cambiosPendientes = ref({});
const totalSolicitudes = ref(0);
const paginaActual = ref(1);
const totalPaginas = ref(1);
const busqueda = ref('');
const loading = ref(true);
const error = ref('');

const resumen = ref({
  pendientes: 0,
  en_proceso: 0,
  personal_disponible: 8,
  delta_pendientes: 5,
  delta_en_proceso: -2,
  delta_personal: 0,
});

const displayName = computed(() => authStore.user?.name ?? 'Usuario');
const userInitial = computed(() => displayName.value.charAt(0).toUpperCase());

async function cerrarSesion() {
  await authStore.logout();
  router.push('/login');
}

function formatFechaHora(fecha) {
  if (!fecha) return '';
  const date = new Date(fecha);
  return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

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

async function cargarSolicitudes(pagina = 1) {
  loading.value = true;
  error.value = '';
  try {
    const params = { page: pagina };
    if (busqueda.value) params.buscar = busqueda.value;
    const [resumenData, listaData] = await Promise.all([
      solicitudesService.resumen(),
      solicitudesService.listar(params),
    ]);
    resumen.value.pendientes = resumenData.pendientes;
    resumen.value.en_proceso = resumenData.en_proceso;
    solicitudes.value = listaData.data ?? [];
    totalSolicitudes.value = listaData.total ?? solicitudes.value.length;
    totalPaginas.value = listaData.last_page ?? 1;
    paginaActual.value = listaData.current_page ?? 1;
  } catch (e) {
    error.value = 'No se pudieron cargar las solicitudes.';
  } finally {
    loading.value = false;
  }
}

async function cargarTecnicos() {
  try {
    tecnicos.value = await tecnicosService.listar();
  } catch (e) {
    console.error('No se pudieron cargar los técnicos', e);
  }
}

function registrarCambio(solicitud, campo, valor) {
  if (!cambiosPendientes.value[solicitud.id]) {
    cambiosPendientes.value[solicitud.id] = {};
  }
  cambiosPendientes.value[solicitud.id][campo] = valor;
}

async function guardarCambios(solicitud) {
  const cambios = cambiosPendientes.value[solicitud.id];
  if (!cambios) return;

  try {
    await solicitudesService.actualizar(solicitud.id, cambios);
    delete cambiosPendientes.value[solicitud.id];
    await cargarSolicitudes(paginaActual.value);
  } catch (e) {
    error.value = 'No se pudo guardar el cambio.';
  }
}

function cambiarPagina(pagina) {
  if (pagina < 1 || pagina > totalPaginas.value) return;
  cargarSolicitudes(pagina);
}

let buscarTimeout = null;
function buscar() {
  clearTimeout(buscarTimeout);
  buscarTimeout = setTimeout(() => cargarSolicitudes(1), 400);
}

onMounted(() => {
  cargarSolicitudes(1);
  cargarTecnicos();
});
</script>

<style scoped>
.coord-layout { display: flex; min-height: 100vh; background: #f4f6fa; font-family: system-ui, -apple-system, sans-serif; color: #1e293b; }
.sidebar { width: 220px; background: #fff; border-right: 1px solid #e5e9f0; display: flex; flex-direction: column; padding: 1.5rem 1rem; flex-shrink: 0; }
.sidebar-brand { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 2rem; padding: 0 0.5rem; }
.brand-logo svg { width: 18px; height: 18px; }
.brand-name { font-weight: 700; font-size: 0.95rem; }
.sidebar-nav { flex: 1; }
.nav-section { font-size: 0.7rem; color: #94a3b8; font-weight: 700; letter-spacing: 0.05em; margin: 1rem 0.5rem 0.5rem; }
.nav-item { display: flex; align-items: center; gap: 0.6rem; padding: 0.6rem 0.5rem; border-radius: 8px; color: #475569; text-decoration: none; font-size: 0.9rem; font-weight: 600; background: #f1f5f9; }
.nav-item:hover { background: #f1f5f9; }
.nav-item.active { background: #eff6ff; color: #2563eb; }
.nav-icon { font-size: 1rem; width: 20px; text-align: center; }
.sidebar-user { display: flex; align-items: center; gap: 0.6rem; padding: 0.75rem 0.5rem; border-top: 1px solid #e5e9f0; }
.user-avatar { width: 32px; height: 32px; border-radius: 50%; background: #2563eb; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; flex-shrink: 0; cursor: pointer; }
.user-avatar:hover { background: #1d4ed8; }
.user-name { font-weight: 700; margin: 0; font-size: 0.85rem; }
.user-role { font-size: 0.75rem; color: #94a3b8; margin: 0; }
.coord-main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
.coord-header { display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 1rem 1.5rem; border-bottom: 1px solid #e5e9f0; flex-wrap: wrap; gap: 1rem; }
.header-title-row { display: flex; align-items: center; gap: 0.6rem; }
.header-title-row h1 { font-size: 1.2rem; margin: 0; }
.header-tag { background: #f1f5f9; color: #64748b; font-size: 0.75rem; font-weight: 600; padding: 0.2rem 0.6rem; border-radius: 6px; }
.header-actions { display: flex; align-items: center; gap: 0.75rem; }
.search-box { display: flex; align-items: center; gap: 0.4rem; background: #f1f5f9; border-radius: 8px; padding: 0.45rem 0.75rem; }
.search-box input { border: none; outline: none; background: none; font-size: 0.88rem; width: 180px; }
.icon-btn { cursor: pointer; font-size: 1.1rem; }
.coord-content { padding: 1.5rem; flex: 1; }
.metrics-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
.metric-card { background: #fff; border-radius: 12px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.metric-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
.metric-icon { width: 36px; height: 36px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
.icon-amber { background: #fef3c7; }
.icon-blue { background: #dbeafe; }
.icon-green { background: #dcfce7; }
.metric-delta { font-size: 0.75rem; font-weight: 700; }
.delta-up { color: #16a34a; }
.delta-down { color: #dc2626; }
.delta-neutral { color: #94a3b8; margin: 0 0 0.25rem; }
.metric-label { font-size: 0.85rem; color: #64748b; margin: 0 0 0.25rem; }
.metric-value { font-size: 1.8rem; font-weight: 800; margin: 0; }
.table-card { background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.table-card-header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem; }
.table-card-header h3 { margin: 0 0 0.2rem; font-size: 1.05rem; }
.table-subtitle { margin: 0; font-size: 0.85rem; color: #94a3b8; }
.table-actions { display: flex; gap: 0.6rem; }
.filter-btn { background: #fff; border: 1px solid #e2e8f0; padding: 0.5rem 0.9rem; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; color: #475569; }
.new-btn { background: #2563eb; color: #fff; border: none; padding: 0.5rem 0.9rem; border-radius: 8px; font-size: 0.85rem; font-weight: 700; text-decoration: none; cursor: pointer; }
.table-loading, .table-error { padding: 1.5rem 0; color: #64748b; }
.table-error { color: #dc2626; }
.coord-table { width: 100%; border-collapse: collapse; }
.coord-table th { text-align: left; font-size: 0.72rem; color: #94a3b8; font-weight: 700; letter-spacing: 0.03em; padding: 0.6rem 0.5rem; border-bottom: 1px solid #e5e9f0; }
.coord-table td { padding: 0.85rem 0.5rem; border-bottom: 1px solid #f1f5f9; font-size: 0.88rem; }
.row-alta td:first-child { border-left: 3px solid #dc2626; }
.cell-id { font-weight: 700; }
.id-link { color: #2563eb; text-decoration: none; font-weight: 700; }
.id-link:hover { text-decoration: underline; }
.cell-empty { text-align: center; color: #94a3b8; padding: 2rem 0; }
.priority-badge, .status-badge { display: inline-block; width: 90px; text-align: center; font-size: 0.74rem; font-weight: 700; padding: 0.25rem 0.6rem; border-radius: 14px; }
.priority-alta { background: #fef2f2; color: #dc2626; }
.priority-media { background: #fff7ed; color: #d97706; }
.priority-baja { background: #f0fdf4; color: #16a34a; }
.status-pendiente { background: #fef3c7; color: #b45309; }
.status-proceso { background: #dbeafe; color: #1d4ed8; }
.status-completada { background: #dcfce7; color: #15803d; }
.priority-select {
  font-size: 0.8rem;
  font-weight: 700;
  padding: 0.3rem 0.5rem;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  cursor: pointer;
}
.tecnico-select {
  font-size: 0.82rem;
  padding: 0.4rem 0.5rem;
  border-radius: 7px;
  border: 1px solid #e2e8f0;
  margin-right: 0.4rem;
  width: 130px;
}
.accion-cell { display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; }
.assign-btn { background: #2563eb; color: #fff; border: none; padding: 0.4rem 0.8rem; border-radius: 7px; font-size: 0.8rem; font-weight: 700; cursor: pointer; }
.assign-btn:disabled { background: #cbd5e1; cursor: not-allowed; }
.table-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; font-size: 0.85rem; color: #94a3b8; flex-wrap: wrap; gap: 0.75rem; }
.pagination { display: flex; gap: 0.3rem; }
.page-btn { width: 28px; height: 28px; border: 1px solid #e2e8f0; background: #fff; border-radius: 6px; cursor: pointer; font-size: 0.8rem; }
.page-btn.active { background: #2563eb; color: #fff; border-color: #2563eb; }
@media (max-width: 900px) {
  .sidebar { display: none; }
  .metrics-row { grid-template-columns: 1fr; }
}
</style>