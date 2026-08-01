<template>
  <div class="tareas-page">
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

      <nav class="header-nav">
        <router-link to="/dashboard" class="nav-link">Tablero</router-link>
        <router-link to="/tareas" class="nav-link active">Tareas</router-link>
      </nav>

      <div class="header-actions">
        <div class="user-menu">
          <button class="user-avatar" @click="menuAbierto = !menuAbierto">{{ userInitial }}</button>
          <div v-if="menuAbierto" class="dropdown-menu">
            <button class="dropdown-item" @click="cerrarSesion">Cerrar sesión</button>
          </div>
        </div>
      </div>
    </header>

    <main class="page-main">
      <div class="title-row">
        <div>
          <h2 class="page-title">Mis Solicitudes Asignadas</h2>
          <p class="page-subtitle">Tareas de mantenimiento asignadas a ti</p>
        </div>
        <div class="filter-tabs">
          <button
            v-for="f in filtros"
            :key="f.value"
            class="filter-tab"
            :class="{ active: filtroActivo === f.value }"
            @click="cambiarFiltro(f.value)"
          >
            {{ f.label }}
          </button>
        </div>
      </div>

      <p v-if="loading" class="state-msg">Cargando tareas...</p>
      <p v-else-if="error" class="state-msg error">{{ error }}</p>
      <p v-else-if="tareas.length === 0" class="state-msg">No tienes tareas asignadas en este momento.</p>

      <div v-else class="tareas-grid">
        <div v-for="t in tareas" :key="t.id" class="tarea-card" :class="{ 'card-alta': t.prioridad === 'Alta' }">
          <div class="card-top">
            <span class="cell-id">#SOL-{{ t.id }}</span>
            <span class="priority-badge" :class="prioridadClase(t.prioridad)">{{ t.prioridad }}</span>
          </div>

          <h3 class="card-title">{{ t.titulo }}</h3>
<p class="card-meta">{{ t.ubicacion }} <span v-if="t.departamento"> · {{ t.departamento }}</span></p>
<p v-if="t.descripcion" class="card-descripcion">{{ t.descripcion }}</p>
<p class="card-date">{{ formatFecha(t.fecha) }}</p>
          <div class="card-bottom">
            <span class="status-badge" :class="estadoClase(t.estado)">{{ t.estado }}</span>
            <div class="card-actions">
              <router-link :to="`/tareas/${t.id}`" class="detail-link">Ver detalle</router-link>
              <button
                v-if="siguienteEstado(t.estado)"
                class="status-btn"
                :disabled="cambiandoId === t.id"
                @click="avanzarEstado(t)"
              >
                {{ cambiandoId === t.id ? 'Guardando...' : `Marcar ${siguienteEstado(t.estado)}` }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import solicitudesService from '../../services/solicitudesService';

const authStore = useAuthStore();
const router = useRouter();
const userInitial = computed(() => (authStore.user?.name ?? 'U').charAt(0).toUpperCase());

const tareas = ref([]);
const loading = ref(true);
const error = ref('');
const filtroActivo = ref('todas');
const cambiandoId = ref(null);
const menuAbierto = ref(false);

const filtros = [
  { value: 'todas', label: 'Todas' },
  { value: 'Pendiente', label: 'Pendientes' },
  { value: 'En Proceso', label: 'En Proceso' },
  { value: 'Completada', label: 'Completadas' },
];

function formatFecha(fecha) {
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

function siguienteEstado(estadoActual) {
  if (estadoActual === 'Pendiente') return 'En Proceso';
  if (estadoActual === 'En Proceso') return 'Completada';
  return null;
}

async function cargarTareas() {
  loading.value = true;
  error.value = '';
  try {
    const params = {};
    if (filtroActivo.value !== 'todas') params.estado = filtroActivo.value;

    const data = await solicitudesService.misTareas(params);
    tareas.value = data.data ?? [];
  } catch (e) {
    error.value = 'No se pudieron cargar tus tareas.';
  } finally {
    loading.value = false;
  }
}

function cambiarFiltro(valor) {
  filtroActivo.value = valor;
  cargarTareas();
}

async function avanzarEstado(tarea) {
  const nuevoEstado = siguienteEstado(tarea.estado);
  if (!nuevoEstado) return;

  cambiandoId.value = tarea.id;
  try {
    await solicitudesService.actualizar(tarea.id, { estado: nuevoEstado });
    tarea.estado = nuevoEstado;
  } catch (e) {
    error.value = 'No se pudo actualizar el estado de la tarea.';
  } finally {
    cambiandoId.value = null;
  }
}

async function cerrarSesion() {
  menuAbierto.value = false;
  await authStore.logout();
  router.push({ name: 'login' });
}

onMounted(() => {
  cargarTareas();
});
</script>

<style scoped>
.tareas-page { min-height: 100vh; background: #f4f6fa; font-family: system-ui, -apple-system, sans-serif; color: #1e293b; }
.page-header { display: flex; align-items: center; gap: 1.5rem; background: #fff; padding: 0.85rem 1.5rem; border-bottom: 1px solid #e5e9f0; flex-wrap: wrap; }
.header-brand { display: flex; align-items: center; gap: 0.6rem; }
.header-logo { width: 32px; height: 32px; border-radius: 8px; background: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.header-logo svg { width: 18px; height: 18px; }
.header-title { font-size: 0.95rem; font-weight: 700; margin: 0; }
.header-nav { display: flex; gap: 1.25rem; margin-left: auto; }
.nav-link { color: #64748b; text-decoration: none; font-size: 0.88rem; font-weight: 600; }
.nav-link.active { color: #2563eb; }
.header-actions { display: flex; align-items: center; }

.user-menu { position: relative; }
.user-avatar { width: 32px; height: 32px; border-radius: 50%; background: #2563eb; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; border: none; cursor: pointer; }
.dropdown-menu {
  position: absolute;
  right: 0;
  top: 42px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  min-width: 140px;
  z-index: 20;
  overflow: hidden;
}
.dropdown-item {
  width: 100%;
  text-align: left;
  background: none;
  border: none;
  padding: 0.6rem 0.9rem;
  font-size: 0.85rem;
  color: #dc2626;
  cursor: pointer;
}
.dropdown-item:hover { background: #f8fafc; }

.page-main { max-width: 1100px; margin: 0 auto; padding: 1.5rem; }
.title-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
.page-title { font-size: 1.3rem; font-weight: 800; margin: 0; }
.page-subtitle { margin: 0.2rem 0 0; font-size: 0.85rem; color: #64748b; }

.filter-tabs { display: flex; gap: 0.4rem; }
.filter-tab { background: #fff; border: 1px solid #e2e8f0; padding: 0.4rem 0.8rem; border-radius: 8px; font-size: 0.82rem; font-weight: 600; cursor: pointer; color: #475569; }
.filter-tab.active { background: #2563eb; color: #fff; border-color: #2563eb; }

.state-msg { padding: 2rem 0; text-align: center; color: #64748b; }
.state-msg.error { color: #dc2626; }

.tareas-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; }
.tarea-card { background: #fff; border-radius: 12px; padding: 1.1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 0.5rem; }
.card-alta { border-left: 3px solid #dc2626; }
.card-top { display: flex; justify-content: space-between; align-items: center; }
.cell-id { font-weight: 700; font-size: 0.85rem; color: #94a3b8; }
.card-title { font-size: 1rem; font-weight: 700; margin: 0; }
.card-meta { font-size: 0.85rem; color: #475569; margin: 0; }
.card-date { font-size: 0.78rem; color: #94a3b8; margin: 0; }
.card-descripcion { font-size: 0.82rem; color: #64748b; margin: 0.35rem 0; line-height: 1.35; }
.card-bottom { display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem; flex-wrap: wrap; gap: 0.5rem; }
.card-actions { display: flex; align-items: center; gap: 0.6rem; }
.detail-link { font-size: 0.8rem; font-weight: 600; color: #2563eb; text-decoration: none; }
.status-btn { background: #2563eb; color: #fff; border: none; padding: 0.4rem 0.7rem; border-radius: 7px; font-size: 0.78rem; font-weight: 700; cursor: pointer; }
.status-btn:disabled { background: #cbd5e1; cursor: not-allowed; }

.priority-badge, .status-badge { font-size: 0.72rem; font-weight: 700; padding: 0.25rem 0.6rem; border-radius: 14px; }
.priority-alta { background: #fef2f2; color: #dc2626; }
.priority-media { background: #fff7ed; color: #d97706; }
.priority-baja { background: #f0fdf4; color: #16a34a; }
.status-pendiente { background: #fef3c7; color: #b45309; }
.status-proceso { background: #dbeafe; color: #1d4ed8; }
.status-completada { background: #dcfce7; color: #15803d; }

@media (max-width: 700px) {
  .header-nav { display: none; }
}
</style>