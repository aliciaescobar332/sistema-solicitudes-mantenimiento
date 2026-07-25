/**
 * stores/auth.js
 * Store de Pinia que envuelve authService para exponer estado reactivo
 * de autenticación al resto de la app (componentes, guards de router, etc).
 */

import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import authService from '../services/authService';

export const useAuthStore = defineStore('auth', () => {
  const user = ref(authService.getUser());
  const loading = ref(false);
  const error = ref(null);

  const isAuthenticated = computed(() => !!user.value && authService.isAuthenticated());

  async function login(credentials) {
    loading.value = true;
    error.value = null;
    try {
      user.value = await authService.login(credentials);
      return user.value;
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Error al iniciar sesión';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function logout() {
    loading.value = true;
    try {
      await authService.logout();
    } finally {
      user.value = null;
      loading.value = false;
    }
  }

  async function fetchUser() {
    try {
      user.value = await authService.fetchCurrentUser();
    } catch (err) {
      user.value = null;
    }
    return user.value;
  }

  window.addEventListener('auth:logout', () => {
    user.value = null;
  });

  return {
    user,
    loading,
    error,
    isAuthenticated,
    login,
    logout,
    fetchUser,
  };
});