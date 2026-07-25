<template>
  <div class="tarea-page">
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

      <div class="header-search">
        <span class="search-icon">🔍</span>
        <input type="text" placeholder="Buscar activos o tareas..." />
      </div>

      <nav class="header-nav">
        <a href="#" class="nav-link">Tablero</a>
        <a href="#" class="nav-link active">Tareas</a>
        <a href="#" class="nav-link">Inventario</a>
        <a href="#" class="nav-link">Reportes</a>
      </nav>

      <div class="header-actions">
        <span class="icon-btn">🔔</span>
        <span class="icon-btn">⚙</span>
        <div class="user-avatar">{{ userInitial }}</div>
      </div>
    </header>

    <main class="page-main">
      <p class="breadcrumbs">Inicio › Tareas Asignadas › #MT-2024-08</p>

      <div class="title-row">
        <h2 class="page-title">Tarea #MT-2024-08: Reparación de Monitor</h2>
        <button class="report-problem-btn">⚠ Reportar Problema</button>
      </div>

      <div class="tag-row">
        <span class="priority-badge priority-alta">PRIORIDAD: ALTA</span>
        <span class="status-badge status-proceso">ESTADO: EN PROCESO</span>
      </div>

      <div class="content-grid">
        <div class="main-column">
          <section class="info-card">
            <h3><span class="card-icon">ℹ</span> Detalles de la Tarea</h3>
            <div class="info-grid">
              <div>
                <p class="info-label">UBICACIÓN</p>
                <p class="info-value">{{ tarea.ubicacion }}</p>
              </div>
              <div>
                <p class="info-label">ACTIVO</p>
                <p class="info-value">{{ tarea.activo }}</p>
              </div>
              <div>
                <p class="info-label">SOLICITANTE</p>
                <p class="info-value">{{ tarea.solicitante }}</p>
              </div>
              <div>
                <p class="info-label">TIPO DE MANTENIMIENTO</p>
                <p class="info-value">{{ tarea.tipo }}</p>
              </div>
            </div>
            <p class="info-label">DESCRIPCIÓN DEL PROBLEMA</p>
            <p class="info-description">{{ tarea.descripcion }}</p>
          </section>

          <section class="info-card">
            <h3><span class="card-icon">📋</span> Bitácora de Trabajo</h3>

            <div class="timeline">
              <div class="timeline-item" v-for="(entrada, i) in bitacora" :key="i">
                <span class="timeline-dot"></span>
                <div class="timeline-content">
                  <div class="timeline-head">
                    <strong>{{ entrada.titulo }}</strong>
                    <span class="timeline-time">{{ entrada.fecha }}</span>
                  </div>
                  <p>{{ entrada.texto }}</p>
                </div>
              </div>
            </div>

            <div class="nueva-nota">
              <p class="info-label">NUEVA ENTRADA DE BITÁCORA</p>
              <textarea
                v-model="nuevaNota"
                rows="3"
                placeholder="Escribe aquí los avances o hallazgos del trabajo..."
              ></textarea>
              <button class="add-note-btn" @click="agregarNota" :disabled="!nuevaNota.trim()">
                + Añadir Nota
              </button>
            </div>
          </section>
        </div>

        <div class="side-column">
          <section class="info-card">
            <h3><span class="card-icon">📷</span> Evidencia de Finalización</h3>

            <label class="upload-box" :class="{ 'upload-box-loading': subiendo }">
              <input
                type="file"
                accept="image/jpeg,image/png,image/jpg,application/pdf"
                class="upload-input"
                :disabled="subiendo"
                @change="subirEvidencia"
              />
              <span class="upload-icon">📷</span>
              <p class="upload-text">{{ subiendo ? 'Subiendo...' : 'Subir Foto de Finalización' }}</p>
              <p class="upload-hint">JPG, PNG o PDF, máx. 10MB</p>
            </label>

            <p v-if="errorEvidencia" class="upload-error">{{ errorEvidencia }}</p>

            <p v-if="cargandoEvidencias" class="state-msg-inline">Cargando evidencia...</p>

            <div class="evidence-preview" v-if="evidencias.length">
              <a
                v-for="ev in evidencias"
                :key="ev.id"
                :href="urlEvidencia(ev)"
                target="_blank"
                class="evidence-item"
              >
                <img v-if="esImagen(ev)" :src="urlEvidencia(ev)" :alt="ev.nombre_archivo" />
                <div v-else class="evidence-doc">📄</div>
              </a>
            </div>
          </section>

          <section class="action-card">
            <label class="checklist-item">
              <input type="checkbox" v-model="confirmado" />
              <span>Asegúrese de haber documentado todas las refacciones utilizadas antes de finalizar.</span>
            </label>

            <button class="finish-btn" :disabled="!confirmado">
              ✓ FINALIZAR TRABAJO
            </button>
            <p class="finish-note">Al finalizar, se notificará al solicitante.</p>

            <button class="pause-btn">⏸ Pausar Tarea (Esperando repuestos)</button>
            <button class="cancel-btn">✕ Cancelar Tarea</button>
          </section>

          <section class="parts-card">
            <h3>🔧 Repuestos Utilizados</h3>
            <div class="part-item" v-for="(parte, i) in repuestos" :key="i">
              <span>{{ parte.nombre }}</span>
              <span class="part-qty">{{ parte.cantidad }}</span>
            </div>
            <button class="add-part-btn">+ Agregar Insumo</button>
          </section>
        </div>
      </div>
    </main>

    <footer class="page-footer">
      © 2024 Hospital Salud Integral - Sistema de Gestión de Mantenimiento Bio-Médico
    </footer>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import evidenciasService from '../../services/evidenciasService';

const route = useRoute();
const authStore = useAuthStore();
const userInitial = computed(() => (authStore.user?.name ?? 'U').charAt(0).toUpperCase());

// Datos de ejemplo (mock) — pendiente conectar a backend real
const tarea = ref({
  ubicacion: 'Unidad de Cuidados Intensivos (UCI) - Cama 4',
  activo: 'Monitor de Signos Vitales B40 (GE Healthcare)',
  solicitante: 'Dr. Roberto Gómez (Jefe de Piso)',
  tipo: 'Correctivo / Eléctrico',
  descripcion: 'Fallo intermitente en el encendido y conexión de energía. El personal de enfermería reporta que el cable parece tener un falso contacto en la entrada posterior del equipo.',
});

const bitacora = ref([
  { titulo: 'Diagnóstico Inicial', fecha: 'Hoy, 09:15 AM', texto: 'Se procedió a desmontar la cubierta trasera. Se observa acumulación de polvo y desgaste en el pin central del conector de alimentación.' },
  { titulo: 'Tarea Iniciada', fecha: 'Hoy, 08:30 AM', texto: 'Equipo recibido en taller para revisión profunda.' },
]);

const repuestos = ref([
  { nombre: 'Conector DC Hembra 12V', cantidad: '1x' },
  { nombre: 'Soldadura de Plata (gr)', cantidad: '5g' },
]);

const nuevaNota = ref('');
const confirmado = ref(false);

// Evidencia (conectada al backend real)
const evidencias = ref([]);
const cargandoEvidencias = ref(true);
const subiendo = ref(false);
const errorEvidencia = ref('');

function urlEvidencia(ev) {
  return `/storage/${ev.ruta}`;
}

function esImagen(ev) {
  return (ev.tipo || '').startsWith('image/');
}

async function cargarEvidencias() {
  cargandoEvidencias.value = true;
  try {
    evidencias.value = await evidenciasService.listar(route.params.id);
  } catch (e) {
    console.error('No se pudo cargar la evidencia', e);
  } finally {
    cargandoEvidencias.value = false;
  }
}

async function subirEvidencia(event) {
  const archivo = event.target.files[0];
  if (!archivo) return;

  subiendo.value = true;
  errorEvidencia.value = '';
  try {
    const nueva = await evidenciasService.subir(route.params.id, archivo);
    evidencias.value.unshift(nueva);
  } catch (e) {
    errorEvidencia.value = 'No se pudo subir el archivo. Verifica el formato y tamaño.';
  } finally {
    subiendo.value = false;
    event.target.value = '';
  }
}

function agregarNota() {
  if (!nuevaNota.value.trim()) return;
  bitacora.value.unshift({
    titulo: 'Nueva entrada',
    fecha: 'Ahora',
    texto: nuevaNota.value.trim(),
  });
  nuevaNota.value = '';
}

onMounted(() => {
  cargarEvidencias();
});
</script>

<style scoped>
.tarea-page {
  min-height: 100vh;
  background: #f4f6fa;
  font-family: system-ui, -apple-system, sans-serif;
  color: #1e293b;
}

.page-header {
  display: flex; align-items: center; gap: 1.5rem;
  background: #fff; padding: 0.85rem 1.5rem; border-bottom: 1px solid #e5e9f0;
  flex-wrap: wrap;
}
.header-brand { display: flex; align-items: center; gap: 0.6rem; }
.header-logo { width: 32px; height: 32px; border-radius: 8px; background: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.header-logo svg { width: 18px; height: 18px; }
.header-title { font-size: 0.95rem; font-weight: 700; margin: 0; }

.header-search { display: flex; align-items: center; gap: 0.4rem; background: #f1f5f9; border-radius: 8px; padding: 0.4rem 0.75rem; flex: 1; max-width: 320px; }
.header-search input { border: none; background: none; outline: none; font-size: 0.85rem; width: 100%; }

.header-nav { display: flex; gap: 1.25rem; margin-left: auto; }
.nav-link { color: #64748b; text-decoration: none; font-size: 0.88rem; font-weight: 600; }
.nav-link.active { color: #2563eb; }

.header-actions { display: flex; align-items: center; gap: 0.75rem; }
.icon-btn { cursor: pointer; color: #64748b; }
.user-avatar { width: 32px; height: 32px; border-radius: 50%; background: #2563eb; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; }

.page-main { max-width: 1100px; margin: 0 auto; padding: 1.5rem; }
.breadcrumbs { font-size: 0.8rem; color: #94a3b8; margin: 0 0 0.75rem; }

.title-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; }
.page-title { font-size: 1.4rem; font-weight: 800; margin: 0; }
.report-problem-btn { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 0.5rem 0.9rem; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer; }

.tag-row { display: flex; gap: 0.6rem; margin: 0.75rem 0 1.5rem; }
.priority-badge, .status-badge { font-size: 0.75rem; font-weight: 700; padding: 0.3rem 0.7rem; border-radius: 14px; }
.priority-alta { background: #fef2f2; color: #dc2626; }
.status-proceso { background: #dbeafe; color: #1d4ed8; }

.content-grid { display: grid; grid-template-columns: 1.6fr 1fr; gap: 1.25rem; align-items: start; }
.main-column, .side-column { display: flex; flex-direction: column; gap: 1.25rem; }

.info-card, .action-card, .parts-card { background: #fff; border-radius: 12px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.info-card h3, .parts-card h3 { font-size: 0.95rem; margin: 0 0 1rem; display: flex; align-items: center; gap: 0.4rem; }
.card-icon { font-size: 0.9rem; }

.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
.info-label { font-size: 0.72rem; color: #94a3b8; font-weight: 700; letter-spacing: 0.03em; margin: 0 0 0.2rem; }
.info-value { font-size: 0.9rem; font-weight: 600; margin: 0; }
.info-description { font-size: 0.88rem; color: #475569; margin: 0; line-height: 1.5; }

.timeline { display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.25rem; }
.timeline-item { display: flex; gap: 0.75rem; }
.timeline-dot { width: 9px; height: 9px; border-radius: 50%; background: #2563eb; margin-top: 0.4rem; flex-shrink: 0; }
.timeline-content { flex: 1; }
.timeline-head { display: flex; justify-content: space-between; font-size: 0.88rem; margin-bottom: 0.15rem; }
.timeline-time { color: #94a3b8; font-size: 0.78rem; font-weight: 500; }
.timeline-content p { margin: 0; font-size: 0.85rem; color: #64748b; line-height: 1.4; }

.nueva-nota textarea {
  width: 100%; padding: 0.65rem; border: 1px solid #e2e8f0; border-radius: 8px;
  font-family: inherit; font-size: 0.88rem; resize: vertical; box-sizing: border-box; margin-bottom: 0.5rem;
}
.add-note-btn { background: none; border: none; color: #2563eb; font-weight: 700; font-size: 0.85rem; cursor: pointer; padding: 0; }
.add-note-btn:disabled { color: #cbd5e1; cursor: not-allowed; }

.upload-box { position: relative; display: block; border: 2px dashed #cbd5e1; border-radius: 10px; padding: 1.5rem; text-align: center; background: #f8fafc; cursor: pointer; }
.upload-box-loading { opacity: 0.6; cursor: wait; }
.upload-input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.upload-icon { font-size: 1.5rem; display: block; margin-bottom: 0.4rem; }
.upload-text { margin: 0; font-size: 0.85rem; font-weight: 600; color: #475569; }
.upload-hint { margin: 0.2rem 0 0; font-size: 0.75rem; color: #94a3b8; }
.upload-error { color: #dc2626; font-size: 0.8rem; margin: 0.5rem 0 0; }
.state-msg-inline { color: #94a3b8; font-size: 0.8rem; margin: 0.5rem 0 0; }

.evidence-preview { display: flex; gap: 0.5rem; margin-top: 0.75rem; flex-wrap: wrap; }
.evidence-item { width: 70px; height: 70px; display: block; border-radius: 8px; overflow: hidden; }
.evidence-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
.evidence-doc { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; background: #f1f5f9; }

.checklist-item { display: flex; align-items: flex-start; gap: 0.6rem; font-size: 0.83rem; color: #475569; margin-bottom: 1rem; cursor: pointer; }
.checklist-item input { margin-top: 0.2rem; }

.finish-btn {
  width: 100%; background: #2563eb; color: #fff; border: none; padding: 0.8rem;
  border-radius: 10px; font-weight: 700; font-size: 0.95rem; cursor: pointer; margin-bottom: 0.4rem;
}
.finish-btn:disabled { background: #cbd5e1; cursor: not-allowed; }
.finish-note { text-align: center; font-size: 0.75rem; color: #94a3b8; margin: 0 0 1rem; }

.pause-btn, .cancel-btn {
  width: 100%; background: #fff; border: 1px solid #e2e8f0; padding: 0.65rem;
  border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; margin-bottom: 0.5rem; color: #475569;
}
.cancel-btn { color: #dc2626; border-color: #fecaca; }

.parts-card { background: #1e293b; color: #fff; }
.parts-card h3 { color: #fff; }
.part-item { display: flex; justify-content: space-between; font-size: 0.85rem; padding: 0.5rem 0; border-bottom: 1px solid #334155; }
.part-qty { color: #94a3b8; }
.add-part-btn { width: 100%; background: #334155; color: #fff; border: none; padding: 0.6rem; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; margin-top: 0.75rem; }

.page-footer { text-align: center; color: #94a3b8; font-size: 0.8rem; padding: 2rem 0; }

@media (max-width: 900px) {
  .content-grid { grid-template-columns: 1fr; }
  .header-nav, .header-search { display: none; }
}
</style>