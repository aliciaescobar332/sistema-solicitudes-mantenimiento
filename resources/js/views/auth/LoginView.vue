<template>
  <div class="login-page">
    <div class="login-card">
      <div class="login-header">
        <div class="login-brand">
          <div class="login-logo">
            <!-- Ícono maletín médico -->
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <rect x="3" y="7" width="18" height="13" rx="2" stroke="white" stroke-width="2"/>
              <path d="M12 11v4M10 13h4" stroke="white" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </div>
          <div>
            <h1 class="login-title">Hospital Salud Integral</h1>
            <p class="login-subtitle">EL SALVADOR</p>
          </div>
        </div>

        <h2 class="login-welcome">Bienvenido de nuevo</h2>
        <p class="login-tagline">Sistema de Gestión de Mantenimiento y Servicios</p>
      </div>

      <div class="login-divider"></div>

      <form class="login-form" @submit.prevent="handleSubmit">
        <div class="field-group">
          <label for="email">Usuario o Correo Electrónico</label>
          <div class="input-wrapper">
            <span class="input-icon">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2"/>
                <path d="M4 20c0-3.3 3.6-6 8-6s8 2.7 8 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </span>
            <input
              id="email"
              v-model="email"
              type="email"
              placeholder="ejemplo@hospital.com"
              required
              autocomplete="username"
            />
          </div>
        </div>

        <div class="field-group">
          <div class="field-label-row">
            <label for="password">Contraseña</label>
            <a href="#" class="forgot-link" @click.prevent="handleForgotPassword">
              ¿Olvidó su contraseña?
            </a>
          </div>
          <div class="input-wrapper">
            <span class="input-icon">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="5" y="11" width="14" height="9" rx="2" stroke="currentColor" stroke-width="2"/>
                <path d="M8 11V8a4 4 0 1 1 8 0v3" stroke="currentColor" stroke-width="2"/>
              </svg>
            </span>
            <input
              id="password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="••••••••"
              required
              autocomplete="current-password"
            />
            <button type="button" class="toggle-visibility" @click="showPassword = !showPassword" tabindex="-1">
              <svg v-if="!showPassword" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="2"/>
                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
              </svg>
              <svg v-else viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 3l18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 5.1A10.4 10.4 0 0 1 12 5c6.4 0 10 7 10 7a13.6 13.6 0 0 1-2.6 3.4M6.6 6.6A13.6 13.6 0 0 0 2 12s3.6 7 10 7c1 0 1.9-.1 2.8-.4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </button>
          </div>
        </div>

        <label class="remember-row">
          <input type="checkbox" v-model="rememberMe" />
          <span>Recordar sesión en este equipo</span>
        </label>

        <p v-if="authStore.error" class="form-error">{{ authStore.error }}</p>
        <p v-if="forgotMessage" class="form-success">{{ forgotMessage }}</p>

        <button type="submit" class="submit-btn" :disabled="authStore.loading">
          <span>{{ authStore.loading ? 'Ingresando...' : 'Iniciar Sesión' }}</span>
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
      </form>

      <div class="login-footer">
        <p>© 2024 Hospital Salud Integral. Todos los derechos reservados.</p>
        <p>Uso exclusivo para personal autorizado.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import authService from '../../services/authService';

const email = ref('');
const password = ref('');
const showPassword = ref(false);
const rememberMe = ref(false);
const forgotMessage = ref('');

const router = useRouter();
const authStore = useAuthStore();

const REMEMBER_KEY = 'remembered_email';

onMounted(() => {
  const savedEmail = localStorage.getItem(REMEMBER_KEY);
  if (savedEmail) {
    email.value = savedEmail;
    rememberMe.value = true;
  }
});

async function handleSubmit() {
  try {
    await authStore.login({ email: email.value, password: password.value });

    if (rememberMe.value) {
      localStorage.setItem(REMEMBER_KEY, email.value);
    } else {
      localStorage.removeItem(REMEMBER_KEY);
    }

    // Redirigir según el rol del usuario
    const rol = authStore.user?.rol;
if (rol === 'admin') {
  router.push('/admin');
} else if (rol === 'coordinador') {
  router.push('/coordinacion');
} else if (rol === 'tecnico') {
  router.push('/tareas');
} else {
  router.push('/dashboard');
}
  } catch (e) {
    // El error ya queda reflejado en authStore.error
  }
}
async function handleForgotPassword() {
  forgotMessage.value = '';
  authStore.error = '';

  if (!email.value) {
    authStore.error = 'Ingresa tu correo electrónico para recuperar tu contraseña.';
    return;
  }

  try {
    await authService.api.post('/password/email', { email: email.value });
    forgotMessage.value = 'Te enviamos un correo con instrucciones para restablecer tu contraseña.';
  } catch (e) {
    authStore.error = 'No pudimos procesar la solicitud. Verifica el correo e intenta de nuevo.';
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #eef1f6;
  font-family: system-ui, -apple-system, sans-serif;
  padding: 2rem 1rem;
}

.login-card {
  width: 100%;
  max-width: 420px;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
  overflow: hidden;
}

.login-header {
  padding: 2rem 2rem 0;
}

.login-brand {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  margin-bottom: 1.5rem;
}

.login-logo {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  background: #2563eb;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.login-logo svg { width: 28px; height: 28px; }

.login-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
  line-height: 1.2;
}
.login-subtitle {
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  color: #2563eb;
  margin: 0.1rem 0 0;
}

.login-welcome {
  font-size: 1.6rem;
  font-weight: 800;
  color: #1e293b;
  margin: 0 0 0.4rem;
  text-align: center;
}
.login-tagline {
  text-align: center;
  color: #64748b;
  font-size: 0.95rem;
  margin: 0 0 1.5rem;
}

.login-divider {
  height: 1px;
  background: #e5e9f0;
  margin: 0 2rem;
}

.login-form {
  padding: 1.75rem 2rem 0.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.field-group label {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
  color: #334155;
  margin-bottom: 0.5rem;
}

.field-label-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.field-label-row label { margin-bottom: 0; }

.forgot-link {
  font-size: 0.8rem;
  font-weight: 600;
  color: #2563eb;
  text-decoration: none;
}
.forgot-link:hover { text-decoration: underline; }

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 0.85rem;
  display: flex;
  color: #94a3b8;
}
.input-icon svg { width: 18px; height: 18px; }

.input-wrapper input {
  width: 100%;
  padding: 0.75rem 0.9rem 0.75rem 2.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.95rem;
  background: #f8fafc;
  box-sizing: border-box;
}
.input-wrapper input:focus {
  outline: none;
  border-color: #2563eb;
  background: #fff;
}

.toggle-visibility {
  position: absolute;
  right: 0.75rem;
  background: none;
  border: none;
  cursor: pointer;
  color: #94a3b8;
  display: flex;
  padding: 0;
}
.toggle-visibility svg { width: 19px; height: 19px; }

.remember-row {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-size: 0.9rem;
  color: #334155;
  cursor: pointer;
}
.remember-row input { width: 16px; height: 16px; cursor: pointer; }

.form-error {
  background: #fef2f2;
  color: #dc2626;
  font-size: 0.85rem;
  padding: 0.6rem 0.8rem;
  border-radius: 8px;
  margin: 0;
}
.form-success {
  background: #f0fdf4;
  color: #16a34a;
  font-size: 0.85rem;
  padding: 0.6rem 0.8rem;
  border-radius: 8px;
  margin: 0;
}

.submit-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.85rem;
  border: none;
  border-radius: 10px;
  background: #2563eb;
  color: #fff;
  font-weight: 700;
  font-size: 1rem;
  cursor: pointer;
  margin-top: 0.25rem;
}
.submit-btn:hover { background: #1d4ed8; }
.submit-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.submit-btn svg { width: 18px; height: 18px; }

.login-footer {
  text-align: center;
  padding: 1.5rem 2rem;
  margin-top: 1rem;
  background: #f8fafc;
  border-top: 1px solid #e5e9f0;
}
.login-footer p {
  font-size: 0.75rem;
  color: #94a3b8;
  margin: 0.15rem 0;
}
</style>