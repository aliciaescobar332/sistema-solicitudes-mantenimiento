/**
 * router/index.js
 * Definición de rutas y guard global de autenticación.
 *
 * Convención para módulos nuevos:
 * - meta.requiresAuth = true  -> requiere sesión activa
 * - meta.requiresGuest = true -> solo accesible SIN sesión (ej. login)
 */

import { createRouter, createWebHistory } from 'vue-router';
import LoginView from '../views/auth/LoginView.vue';
import DashboardView from '../views/dashboard/DashboardView.vue';
import NuevaSolicitudView from '../views/solicitudes/NuevaSolicitudView.vue';
import PanelCoordinacionView from '../views/coordinacion/PanelCoordinacionView.vue';
import DetalleSolicitudView from '../views/coordinacion/DetalleSolicitudView.vue';
import MisTareasView from '../views/tecnico/MisTareasView.vue';
import DetalleTareaView from '../views/tecnico/DetalleTareaView.vue';
import PanelAdminView from '../views/admin/PanelAdminView.vue';
import ReportesView from '../views/reportes/ReportesView.vue';
import authService from '../services/authService';

const routes = [
  {
    path: '/',
    redirect: '/login',
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: DashboardView,
    meta: { requiresAuth: true },
  },
  {
    path: '/solicitudes/nueva',
    name: 'nueva-solicitud',
    component: NuevaSolicitudView,
    meta: { requiresAuth: true },
  },
  {
    path: '/solicitudes/:id',
    name: 'detalle-solicitud',
    component: DetalleSolicitudView,
    meta: { requiresAuth: true },
  },
  {
    path: '/coordinacion',
    name: 'coordinacion',
    component: PanelCoordinacionView,
    meta: { requiresAuth: true },
  },
  {
    path: '/tareas',
    name: 'mis-tareas',
    component: MisTareasView,
    meta: { requiresAuth: true },
  },
  {
    path: '/tareas/:id',
    name: 'detalle-tarea',
    component: DetalleTareaView,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin',
    name: 'admin',
    component: PanelAdminView,
    meta: { requiresAuth: true },
  },
  {
    path: '/reportes',
    name: 'reportes',
    component: ReportesView,
    meta: { requiresAuth: true },
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { requiresGuest: true },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

function rutaSegunRol(rol) {
  if (rol === 'admin') return 'admin';
  if (rol === 'coordinador') return 'coordinacion';
  if (rol === 'tecnico') return 'mis-tareas';
  return 'dashboard';
}

router.beforeEach((to) => {
  const isAuthenticated = authService.isAuthenticated();

  if (to.meta.requiresAuth && !isAuthenticated) {
    return { name: 'login' };
  }

  if (to.meta.requiresGuest && isAuthenticated) {
    const user = authService.getUser();
    return { name: rutaSegunRol(user?.rol) };
  }
});

export default router;