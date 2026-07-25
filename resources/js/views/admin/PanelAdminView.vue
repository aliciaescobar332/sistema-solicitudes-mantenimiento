<template>
  <div class="flex h-screen w-full bg-slate-50 font-sans">
    <aside class="hidden w-64 shrink-0 flex-col border-r border-slate-200 bg-white px-4 py-6 md:flex">
      <div class="mb-8 flex items-center gap-2 px-2">
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-600 text-white">🛡</div>
        <div>
          <p class="text-[15px] font-semibold leading-tight text-slate-800">Salud Integral</p>
          <p class="text-xs leading-tight text-slate-400">Panel de Control</p>
        </div>
      </div>

      <nav class="mb-8 flex flex-col gap-1">
        <button v-for="item in navItems" :key="item.label"
          :class="['flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors',
            item.active ? 'bg-blue-50 font-medium text-blue-600' : 'text-slate-500 hover:bg-slate-50']">
          <span class="text-base">{{ item.emoji }}</span>{{ item.label }}
        </button>
      </nav>

      <p class="mb-2 px-2 text-[11px] font-medium tracking-wide text-slate-400">SISTEMA</p>
      <nav class="flex flex-col gap-1">
        <button class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-500 hover:bg-slate-50">
          ⚙️ Configuración
        </button>
      </nav>

      <div class="mt-auto flex items-center gap-3 rounded-lg px-2 pt-6">
        <div @click="cerrarSesion" title="Cerrar sesión"
          class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-600 hover:bg-red-100 hover:text-red-600">
          {{ userInitial }}
        </div>
        <div>
          <p class="text-sm font-medium text-slate-700">{{ authStore.user?.name ?? 'Admin' }}</p>
          <p class="text-xs text-slate-400">Administrador</p>
        </div>
      </div>
    </aside>

    <div class="flex flex-1 flex-col overflow-y-auto">
      <header class="flex items-center justify-between border-b border-slate-200 bg-white px-6 py-4">
        <h1 class="text-lg font-semibold text-slate-800">Panel de Administración y Reportes</h1>
        <div class="flex items-center gap-4">
          <input type="text" placeholder="Buscar solicitud..."
            class="hidden w-56 rounded-lg border border-slate-200 bg-slate-50 py-2 px-3 text-sm text-slate-600 placeholder:text-slate-400 focus:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-100 sm:block"/>
          <button class="relative rounded-full p-2 text-slate-500 hover:bg-slate-50">🔔</button>
          <button class="rounded-lg bg-blue-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-blue-700">
            + Nueva Solicitud
          </button>
        </div>
      </header>

      <main class="flex flex-col gap-6 p-6">
        <div class="flex flex-col gap-1">
          <h1 class="text-2xl font-bold text-slate-800">Dashboard Principal</h1>
          <div class="flex items-center justify-between">
            <p class="text-sm text-slate-400">Hospital Salud Integral — Monitoreo en tiempo real de mantenimiento.</p>
            <div class="flex items-center gap-2">
              <button class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">📅 Últimos 30 días</button>
              <button class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">⬇️ Exportar</button>
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-4 sm:flex-row">
          <div v-for="s in stats" :key="s.label" class="flex-1 rounded-2xl border border-slate-200 bg-white p-5">
            <div class="mb-1 flex items-start justify-between">
              <p class="text-[11px] font-medium tracking-wide text-slate-400">{{ s.label }}</p>
              <div :class="['flex h-9 w-9 items-center justify-center rounded-xl text-base', s.iconBg]">{{ s.icon }}</div>
            </div>
            <p class="mb-4 text-xs text-slate-400">{{ s.description }}</p>
            <p class="mb-2 text-3xl font-semibold text-slate-800">{{ s.value }}</p>
            <div :class="['flex items-center gap-1 text-xs font-medium', s.trendColor]">
              <span>{{ s.trend }}</span>
              <span class="font-normal text-slate-400">{{ s.trendLabel }}</span>
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-6 lg:flex-row">
          <div class="flex-1 rounded-2xl border border-slate-200 bg-white p-6">
            <div class="mb-6 flex items-start justify-between">
              <div>
                <h2 class="text-base font-semibold text-slate-800">Solicitudes por Mes</h2>
                <p class="mt-0.5 text-sm text-slate-400">Histórico de volumen de trabajo</p>
              </div>
              <div class="flex items-center gap-4 text-xs text-slate-500">
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-blue-600"></span> Mantenimiento</span>
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-slate-300"></span> Reparaciones</span>
              </div>
            </div>
            <div style="height: 220px"><Bar :data="barData" :options="barOptions" /></div>
          </div>

          <div class="w-full max-w-xs shrink-0 rounded-2xl border border-slate-200 bg-white p-6">
            <h2 class="text-base font-semibold text-slate-800">Eficiencia del Equipo</h2>
            <p class="mt-0.5 mb-4 text-sm text-slate-400">Distribución de estados hoy</p>
            <div class="relative mx-auto h-40 w-40">
              <Doughnut :data="doughnutData" :options="doughnutOptions" />
              <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                <span class="text-2xl font-semibold text-slate-800">94%</span>
                <span class="text-[10px] tracking-wide text-slate-400">GENERAL</span>
              </div>
            </div>
            <div class="mt-5 flex flex-col gap-2.5">
              <div v-for="e in efficiencyData" :key="e.name" class="flex items-center justify-between text-sm">
                <span class="flex items-center gap-2 text-slate-500">
                  <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: e.color }"></span>{{ e.name }}
                </span>
                <span class="font-medium text-slate-700">{{ e.value }}%</span>
              </div>
            </div>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white">
          <div class="flex items-center justify-between px-6 pt-5 pb-4">
            <h2 class="text-base font-semibold text-slate-800">Actividad Reciente</h2>
            <button class="text-sm font-medium text-blue-600 hover:underline">Ver todas →</button>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-sm">
              <thead>
                <tr class="border-y border-slate-100 bg-slate-50/60 text-xs font-medium tracking-wide text-slate-400">
                  <th class="px-6 py-3">ID</th>
                  <th class="px-3 py-3">DEPARTAMENTO</th>
                  <th class="px-3 py-3">SERVICIO</th>
                  <th class="px-3 py-3">ESTADO</th>
                  <th class="px-3 py-3">PRIORIDAD</th>
                  <th class="px-6 py-3">FECHA</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="a in activity" :key="a.id" class="border-b border-slate-100 last:border-0">
                  <td class="px-6 py-4 font-medium text-slate-700">{{ a.id }}</td>
                  <td class="px-3 py-4 text-slate-600">{{ a.dept }}</td>
                  <td class="px-3 py-4 text-slate-600">{{ a.service }}</td>
                  <td class="px-3 py-4">
                    <span :class="['rounded-full px-2.5 py-1 text-xs font-medium', statusStyles[a.status]]">{{ a.status }}</span>
                  </td>
                  <td class="px-3 py-4">
                    <span class="flex items-center gap-1.5 text-xs font-medium text-slate-600">
                      <span :class="['h-2 w-2 rounded-full', priorityDot[a.priority]]"></span>{{ a.priority }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-slate-400">{{ a.date }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>
<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { Bar, Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);

const authStore = useAuthStore();
const router = useRouter();

const userInitial = computed(() => (authStore.user?.name ?? 'A').charAt(0).toUpperCase());

async function cerrarSesion() {
  await authStore.logout();
  router.push('/login');
}

const navItems = [
  { emoji: '🏠', label: 'Dashboard', active: true },
  { emoji: '👥', label: 'Usuarios' },
  { emoji: '🏢', label: 'Departamentos' },
  { emoji: '📊', label: 'Reportes' },
];

const stats = [
  {
    icon: '📋',
    iconBg: 'bg-blue-50',
    label: 'SOLICITUDES TOTALES',
    description: 'Total de solicitudes registradas en el sistema',
    value: '1,240',
    trend: '↗ +12.5%',
    trendLabel: 'vs mes anterior',
    trendColor: 'text-emerald-500',
  },
  {
    icon: '⚠️',
    iconBg: 'bg-rose-50',
    label: 'PENDIENTES CRÍTICAS',
    description: 'Solicitudes de prioridad alta sin resolver',
    value: '45',
    trend: '↗ +5%',
    trendLabel: 'Atención requerida',
    trendColor: 'text-rose-500',
  },
  {
    icon: '⏱',
    iconBg: 'bg-amber-50',
    label: 'TIEMPO DE RESPUESTA',
    description: 'Promedio desde creación hasta primera atención',
    value: '2.4 hrs',
    trend: '↘ -10%',
    trendLabel: 'Más rápido',
    trendColor: 'text-emerald-500',
  },
  {
    icon: '✅',
    iconBg: 'bg-indigo-50',
    label: 'EFICIENCIA OPERATIVA',
    description: 'Porcentaje de solicitudes completadas a tiempo',
    value: '94%',
    trend: '↗ +2.1%',
    trendLabel: 'Nivel óptimo',
    trendColor: 'text-emerald-500',
  },
];

const efficiencyData = [
  { name: 'Completadas', value: 75, color: '#2563eb' },
  { name: 'En Proceso', value: 15, color: '#f59e0b' },
  { name: 'Pendientes', value: 10, color: '#e2e8f0' },
];

const priorityDot = { Alta: 'bg-rose-500', Media: 'bg-amber-500', Baja: 'bg-blue-500' };
const statusStyles = { 'En Proceso': 'bg-blue-50 text-blue-600', Completada: 'bg-emerald-50 text-emerald-600', Pendiente: 'bg-slate-100 text-slate-500' };

const activity = [
  { id: '#REQ-8291', dept: 'Cardiología', service: 'Calibración de Monitor', status: 'En Proceso', priority: 'Alta', date: 'Hoy, 09:15 AM' },
  { id: '#REQ-8290', dept: 'Radiología', service: 'Falla Aire Acondicionado', status: 'Completada', priority: 'Media', date: 'Ayer, 04:30 PM' },
  { id: '#REQ-8289', dept: 'Gastroenterología', service: 'Revisión Endoscopio', status: 'Pendiente', priority: 'Baja', date: 'Ayer, 02:10 PM' },
];

const barData = {
  labels: ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN'],
  datasets: [
    { label: 'Mantenimiento', data: [38, 45, 52, 40, 58, 49], backgroundColor: '#2563eb', borderRadius: 4, barThickness: 14 },
    { label: 'Reparaciones', data: [22, 18, 28, 24, 30, 20], backgroundColor: '#cbd5e1', borderRadius: 4, barThickness: 14 },
  ],
};

const barOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { display: false } } };
const doughnutData = { labels: efficiencyData.map(e => e.name), datasets: [{ data: efficiencyData.map(e => e.value), backgroundColor: efficiencyData.map(e => e.color), borderWidth: 0 }] };
const doughnutOptions = { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { display: false } } };
</script>