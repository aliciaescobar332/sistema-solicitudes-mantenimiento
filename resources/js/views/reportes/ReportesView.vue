<template>
  <div class="dashboard-page">
    <header class="dashboard-header">
      <div class="header-brand">
        <div class="header-logo">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <rect x="3" y="7" width="18" height="13" rx="2" stroke="white" stroke-width="2"/>
            <path d="M12 11v4M10 13h4" stroke="white" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
        <div>
          <h1 class="header-title">Hospital Salud Integral</h1>
          <p class="header-subtitle">EL SALVADOR</p>
        </div>
      </div>

      <nav class="header-nav">
        <router-link to="/dashboard" class="nav-link">Panel</router-link>
        <router-link to="/tareas" class="nav-link">Mis Solicitudes</router-link>
        <a href="#" class="nav-link">Notificaciones</a>
        <router-link to="/reportes" class="nav-link active">Reportes</router-link>
      </nav>

      <div class="header-user">
        <div class="user-info">
          <p class="user-name">{{ authStore.user?.name ?? 'Usuario' }}</p>
          <p class="user-role">{{ authStore.user?.rol ?? 'Solicitante' }}</p>
        </div>
        <div class="user-avatar" title="Cerrar sesión" @click="cerrarSesion">{{ userInitial }}</div>
      </div>
    </header>

    <main class="dashboard-main">
      <div class="welcome-row">
        <div>
          <h2 class="welcome-title">Reportes</h2>
          <p class="welcome-subtitle">Resumen general de solicitudes de mantenimiento.</p>
        </div>
        <button
          class="btn-export"
          :disabled="loading || !!error"
          @click="exportarPDF"
        >
          Exportar PDF
        </button>
      </div>

      <p v-if="loading" class="table-loading">Cargando reporte...</p>
      <p v-else-if="error" class="table-error">{{ error }}</p>

      <template v-else>
        <section class="metrics-row">
          <div class="metric-card">
            <p class="metric-label">PENDIENTES</p>
            <p class="metric-value">{{ resumen.pendientes }}</p>
          </div>
          <div class="metric-card">
            <p class="metric-label">EN PROCESO</p>
            <p class="metric-value">{{ resumen.en_proceso }}</p>
          </div>
          <div class="metric-card">
            <p class="metric-label">COMPLETADAS</p>
            <p class="metric-value">{{ resumen.completadas }}</p>
          </div>
          <div class="metric-card">
            <p class="metric-label">TOTAL</p>
            <p class="metric-value">{{ resumen.total }}</p>
          </div>
        </section>

        <section class="table-card">
          <div class="table-header">
            <h3>Distribución por Estado</h3>
          </div>
          <div class="bar-chart">
            <div class="bar-row" v-for="item in barras" :key="item.label">
              <span class="bar-label">{{ item.label }}</span>
              <div class="bar-track">
                <div class="bar-fill" :style="{ width: item.porcentaje + '%', background: item.color }"></div>
              </div>
              <span class="bar-value">{{ item.valor }}</span>
            </div>
          </div>
        </section>

        <section class="table-card">
          <div class="table-header">
            <h3>Detalle de Solicitudes (últimas 10)</h3>
          </div>
          <table class="solicitudes-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>SERVICIO</th>
                <th>UBICACIÓN</th>
                <th>FECHA</th>
                <th>ESTADO</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in solicitudes" :key="s.id">
                <td class="cell-id">#SOL-{{ s.id }}</td>
                <td>{{ s.titulo }}</td>
                <td class="cell-ubicacion">{{ s.ubicacion }}</td>
                <td>{{ formatFecha(s.fecha) }}</td>
                <td>
                  <span class="status-badge" :class="estadoClase(s.estado)">{{ s.estado }}</span>
                </td>
              </tr>
              <tr v-if="solicitudes.length === 0">
                <td colspan="5" class="cell-empty">No hay solicitudes registradas todavía.</td>
              </tr>
            </tbody>
          </table>
        </section>
      </template>
    </main>

    <footer class="dashboard-footer">
      © 2024 Hospital Salud Integral - Sistema de Gestión de Servicios Técnicos.
    </footer>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import solicitudesService from '../../services/solicitudesService';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

const authStore = useAuthStore();
const router = useRouter();

const resumen = ref({ pendientes: 0, en_proceso: 0, completadas: 0, total: 0 });
const solicitudes = ref([]);
const loading = ref(true);
const error = ref('');

const userInitial = computed(() => {
  const nombre = authStore.user?.name ?? 'U';
  return nombre.charAt(0).toUpperCase();
});

const barras = computed(() => {
  const total = resumen.value.total || 1;
  return [
    { label: 'Pendientes', valor: resumen.value.pendientes, color: '#d97706', porcentaje: (resumen.value.pendientes / total) * 100 },
    { label: 'En Proceso', valor: resumen.value.en_proceso, color: '#2563eb', porcentaje: (resumen.value.en_proceso / total) * 100 },
    { label: 'Completadas', valor: resumen.value.completadas, color: '#16a34a', porcentaje: (resumen.value.completadas / total) * 100 },
  ];
});

function formatFecha(fecha) {
  if (!fecha) return '';
  const date = new Date(fecha);
  return date.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
}

function estadoClase(estado) {
  if (estado === 'Pendiente') return 'status-pendiente';
  if (estado === 'En Proceso') return 'status-proceso';
  if (estado === 'Completada') return 'status-completada';
  return '';
}

async function cerrarSesion() {
  await authStore.logout();
  router.push('/login');
}

async function cargarDatos() {
  loading.value = true;
  error.value = '';
  try {
    const [resumenData, listaData] = await Promise.all([
      solicitudesService.resumen(),
      solicitudesService.listar(),
    ]);
    resumen.value = resumenData;
    solicitudes.value = listaData.data ?? [];
  } catch (e) {
    error.value = 'No se pudo cargar el reporte. Intenta de nuevo más tarde.';
  } finally {
    loading.value = false;
  }
}

// NUEVO: exporta el resumen + la tabla de solicitudes a un PDF descargable
function exportarPDF() {
  const doc = new jsPDF();
  const fechaGenerado = new Date().toLocaleDateString('es-ES');

  doc.setFontSize(16);
  doc.text('Hospital Salud Integral', 14, 18);
  doc.setFontSize(11);
  doc.text('Reporte de Solicitudes de Mantenimiento', 14, 25);
  doc.setFontSize(9);
  doc.setTextColor(100);
  doc.text(`Generado: ${fechaGenerado}`, 14, 31);
  doc.setTextColor(0);

  autoTable(doc, {
    startY: 38,
    head: [['Pendientes', 'En Proceso', 'Completadas', 'Total']],
    body: [[
      resumen.value.pendientes,
      resumen.value.en_proceso,
      resumen.value.completadas,
      resumen.value.total,
    ]],
    theme: 'grid',
    headStyles: { fillColor: [37, 99, 235] },
  });

  const finalY = doc.lastAutoTable.finalY + 10;
  doc.setFontSize(12);
  doc.text('Detalle de Solicitudes', 14, finalY);

  autoTable(doc, {
    startY: finalY + 5,
    head: [['ID', 'Servicio', 'Ubicación', 'Fecha', 'Estado']],
    body: solicitudes.value.map((s) => [
      `#SOL-${s.id}`,
      s.titulo,
      s.ubicacion,
      formatFecha(s.fecha),
      s.estado,
    ]),
    theme: 'striped',
    headStyles: { fillColor: [37, 99, 235] },
    styles: { fontSize: 9 },
  });

  doc.save(`reporte-solicitudes-${fechaGenerado.replaceAll('/', '-')}.pdf`);
}

onMounted(cargarDatos);
</script>

<style scoped>
.dashboard-page { min-height: 100vh; background: #f4f6fa; font-family: system-ui, -apple-system, sans-serif; color: #1e293b; }
.dashboard-header { display: flex; align-items: center; justify-content: space-between; background: #fff; padding: 1rem 2rem; border-bottom: 1px solid #e5e9f0; flex-wrap: wrap; gap: 1rem; }
.header-brand { display: flex; align-items: center; gap: 0.75rem; }
.header-logo { width: 38px; height: 38px; border-radius: 10px; background: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.header-logo svg { width: 20px; height: 20px; }
.header-title { font-size: 1rem; font-weight: 700; margin: 0; line-height: 1.2; }
.header-subtitle { font-size: 0.7rem; font-weight: 700; color: #2563eb; margin: 0; letter-spacing: 0.05em; }
.header-nav { display: flex; gap: 1.5rem; }
.nav-link { color: #64748b; text-decoration: none; font-size: 0.9rem; font-weight: 600; padding-bottom: 0.25rem; }
.nav-link.active { color: #2563eb; border-bottom: 2px solid #2563eb; }
.header-user { display: flex; align-items: center; gap: 0.75rem; }
.user-info { text-align: right; }
.user-name { font-size: 0.85rem; font-weight: 700; margin: 0; }
.user-role { font-size: 0.75rem; color: #64748b; margin: 0; }
.user-avatar { width: 36px; height: 36px; border-radius: 50%; background: #2563eb; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; cursor: pointer; }
.dashboard-main { max-width: 1100px; margin: 0 auto; padding: 2rem; }
.welcome-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; gap: 1rem; flex-wrap: wrap; }
.welcome-title { font-size: 1.8rem; font-weight: 800; margin: 0 0 0.4rem; }
.welcome-subtitle { color: #64748b; margin: 0; }

/* NUEVO: botón de exportar PDF */
.btn-export {
  background: #2563eb;
  color: #fff;
  border: none;
  padding: 0.6rem 1.1rem;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
}
.btn-export:hover { background: #1d4ed8; }
.btn-export:disabled { background: #cbd5e1; cursor: not-allowed; }

.metrics-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; margin-bottom: 2rem; }
.metric-card { background: #fff; border-radius: 14px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.metric-label { font-size: 0.8rem; color: #64748b; font-weight: 700; letter-spacing: 0.04em; margin: 0 0 0.3rem; }
.metric-value { font-size: 2rem; font-weight: 800; margin: 0; }
.table-card { background: #fff; border-radius: 14px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 2rem; }
.table-header { margin-bottom: 1rem; }
.table-header h3 { margin: 0; font-size: 1.1rem; }
.table-loading, .table-error { padding: 1.5rem 0; }
.table-error { color: #dc2626; }
.bar-chart { display: flex; flex-direction: column; gap: 1rem; }
.bar-row { display: flex; align-items: center; gap: 1rem; }
.bar-label { width: 110px; font-size: 0.85rem; font-weight: 600; color: #475569; flex-shrink: 0; }
.bar-track { flex: 1; background: #f1f5f9; border-radius: 8px; height: 22px; overflow: hidden; }
.bar-fill { height: 100%; border-radius: 8px; transition: width 0.4s ease; }
.bar-value { width: 30px; text-align: right; font-weight: 700; flex-shrink: 0; }
.solicitudes-table { width: 100%; border-collapse: collapse; }
.solicitudes-table th { text-align: left; font-size: 0.75rem; color: #94a3b8; font-weight: 700; letter-spacing: 0.04em; padding: 0.6rem 0.5rem; border-bottom: 1px solid #e5e9f0; }
.solicitudes-table td { padding: 0.9rem 0.5rem; border-bottom: 1px solid #f1f5f9; }
.cell-id { font-weight: 700; }
.cell-ubicacion { font-style: italic; color: #475569; }
.cell-empty { text-align: center; color: #94a3b8; padding: 2rem 0; }
.status-badge { font-size: 0.78rem; font-weight: 700; padding: 0.3rem 0.7rem; border-radius: 20px; }
.status-pendiente { background: #fef3c7; color: #b45309; }
.status-proceso { background: #dbeafe; color: #1d4ed8; }
.status-completada { background: #dcfce7; color: #15803d; }
.dashboard-footer { text-align: center; color: #94a3b8; font-size: 0.8rem; padding: 2rem 0; }
@media (max-width: 768px) {
  .header-nav { display: none; }
  .metrics-row { grid-template-columns: repeat(2, 1fr); }
}
</style>
