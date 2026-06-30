<template>
  <ion-page>
    <ion-content :fullscreen="true" class="ion-padding">
      <div class="auth-container">
        <div class="auth-header">
          <router-link to="/login" class="logo-link">
            <img src="/images/pp-logo-white.png" alt="Pookie Planner" class="app-logo" />
          </router-link>
          <p class="app-subtitle">Welcome back</p>
        </div>

        <ion-list lines="none" class="auth-form">
          <ion-item class="input-item">
            <ion-input
              v-model="email"
              type="email"
              label="Email"
              label-placement="floating"
              placeholder="you@example.com"
              :clear-input="true"
            />
          </ion-item>

          <ion-item class="input-item">
            <ion-input
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              label="Password"
              label-placement="floating"
              placeholder="••••••••"
            />
            <ion-button fill="clear" slot="end" @click="showPassword = !showPassword">
              <ion-icon :icon="showPassword ? eyeOff : eye" />
            </ion-button>
          </ion-item>
        </ion-list>

        <div class="forgot-link">
          <router-link to="/forgot-password">Forgot password?</router-link>
        </div>

        <ion-button
          expand="block"
          class="auth-btn"
          :disabled="loading"
          @click="handleLogin"
        >
          <ion-spinner v-if="loading" name="crescent" />
          <span v-else>Log In</span>
        </ion-button>

        <p class="switch-auth">
          Don't have an account?
          <router-link to="/register">Sign up</router-link>
        </p>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import {
  IonPage, IonContent, IonList, IonItem, IonInput,
  IonButton, IonIcon, IonSpinner, toastController
} from '@ionic/vue';
import { eye, eyeOff } from 'ionicons/icons';
import { useAuth } from '@/composables/useAuth';

const router = useRouter();
const { login } = useAuth();

const email = ref('');
const password = ref('');
const showPassword = ref(false);
const loading = ref(false);

async function handleLogin() {
  if (!email.value || !password.value) {
    const toast = await toastController.create({
      message: 'Please fill in all fields.',
      duration: 2000,
      color: 'warning',
      position: 'top',
    });
    await toast.present();
    return;
  }

  loading.value = true;
  try {
    await login({ email: email.value, password: password.value });
    await router.push('/dashboard');
  } catch (err: any) {
    const message = err?.response?.data?.message ?? 'Invalid email or password.';
    const toast = await toastController.create({
      message,
      duration: 2000,
      color: 'danger',
      position: 'top',
    });
    await toast.present();
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.auth-container {
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-height: 100%;
  padding: 24px 8px;
  background-color: var(--color-background);
}

.auth-header {
  text-align: center;
  margin-bottom: 36px;
}

.logo-link {
  display: inline-block;
  cursor: pointer;
  transition: transform 0.2s ease;
  text-decoration: none;
}

.logo-link:hover {
  transform: scale(1.05);
}

.app-logo {
  height: 100px;
  width: 180px;
  object-fit: contain;
  margin: 0 auto 12px;
}

.app-subtitle {
  font-size: 16px;
  color: var(--color-text);
  margin: 0;
}

.auth-form {
  background: transparent;
  margin-bottom: 8px;
}

.input-item {
  --background: var(--color-surface-light);
  --border-radius: 12px;
  --padding-start: 16px;
  margin-bottom: 12px;
  border-radius: 12px;
}

.forgot-link {
  text-align: right;
  margin-bottom: 24px;
  font-size: 14px;
}

.forgot-link a,
.switch-auth a {
  color: var(--color-primary);
  text-decoration: none;
  font-weight: 500;
}

.forgot-link a:hover,
.switch-auth a:hover {
  color: var(--color-text);
}

.auth-btn {
  --border-radius: 12px;
  height: 52px;
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 24px;
  background-color: var(--color-primary) !important;
  color: var(--color-text) !important;
}

.switch-auth {
  text-align: center;
  font-size: 14px;
  color: var(--color-text);
}
</style>

