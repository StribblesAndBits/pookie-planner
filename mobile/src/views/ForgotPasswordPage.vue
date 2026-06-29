<template>
  <ion-page>
    <ion-content :fullscreen="true" class="ion-padding">
      <div class="auth-container">
        <div class="auth-header">
          <h1 class="app-title">Forgot Password?</h1>
          <p class="app-subtitle">No worries! Enter your email and we'll send you a reset link.</p>
        </div>

        <div v-if="!submitted">
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
          </ion-list>

          <ion-button
            expand="block"
            class="auth-btn"
            :disabled="loading"
            @click="handleSubmit"
          >
            <ion-spinner v-if="loading" name="crescent" />
            <span v-else>Send Reset Link</span>
          </ion-button>
        </div>

        <div v-else class="success-state">
          <ion-icon :icon="mailOutline" class="success-icon" />
          <h2>Check your email</h2>
          <p>We sent a password reset link to <strong>{{ email }}</strong></p>
          <ion-button expand="block" fill="outline" class="auth-btn" @click="backToLogin">
            Back to Login
          </ion-button>
        </div>

        <p class="switch-auth">
          Remember your password?
          <router-link to="/login">Log in</router-link>
        </p>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import {
  IonPage, IonContent,
  IonList, IonItem, IonInput, IonButton, IonIcon, IonSpinner, toastController
} from '@ionic/vue';
import { mailOutline } from 'ionicons/icons';
import { useAuth } from '@/composables/useAuth';

const router = useRouter();
const { forgotPassword } = useAuth();

const email = ref('');
const loading = ref(false);
const submitted = ref(false);

async function handleSubmit() {
  if (!email.value) {
    const toast = await toastController.create({
      message: 'Please enter your email address.',
      duration: 2000,
      color: 'warning',
      position: 'top',
    });
    await toast.present();
    return;
  }

  loading.value = true;
  try {
    await forgotPassword(email.value);
    submitted.value = true;
  } catch (err: any) {
    const message = err?.response?.data?.message ?? 'Something went wrong. Please try again.';
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

function backToLogin() {
  router.push('/login');
}
</script>

<style scoped>
.auth-container {
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-height: 100%;
  padding: 24px 8px;
}

.auth-header {
  text-align: center;
  margin-bottom: 36px;
}

.app-title {
  font-size: 28px;
  font-weight: 700;
  margin: 0 0 10px;
}

.app-subtitle {
  font-size: 15px;
  color: var(--ion-color-medium);
  margin: 0;
  line-height: 1.5;
}

.auth-form {
  background: transparent;
  margin-bottom: 24px;
}

.input-item {
  --background: var(--ion-color-light);
  --border-radius: 12px;
  --padding-start: 16px;
  margin-bottom: 12px;
  border-radius: 12px;
}

.auth-btn {
  --border-radius: 12px;
  height: 52px;
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 24px;
}

.success-state {
  text-align: center;
  padding: 16px 0 24px;
}

.success-icon {
  font-size: 64px;
  color: var(--ion-color-primary);
  margin-bottom: 16px;
}

.success-state h2 {
  font-size: 22px;
  font-weight: 700;
  margin: 0 0 10px;
}

.success-state p {
  font-size: 15px;
  color: var(--ion-color-medium);
  margin: 0 0 24px;
  line-height: 1.5;
}

.switch-auth {
  text-align: center;
  font-size: 14px;
  color: var(--ion-color-medium);
}

.switch-auth a {
  color: var(--ion-color-primary);
  text-decoration: none;
  font-weight: 500;
}
</style>

