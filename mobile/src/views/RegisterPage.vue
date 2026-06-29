<template>
  <ion-page>
    <ion-content :fullscreen="true" class="ion-padding">
      <div class="auth-container">
        <div class="auth-header">
          <h1 class="app-title">Pookie Planner</h1>
          <p class="app-subtitle">Create your account</p>
        </div>

        <ion-list lines="none" class="auth-form">
          <ion-item class="input-item">
            <ion-input
              v-model="firstName"
              type="text"
              label="First Name"
              label-placement="floating"
              placeholder="First name"
              :clear-input="true"
            />
          </ion-item>

          <ion-item class="input-item">
            <ion-input
              v-model="lastName"
              type="text"
              label="Last Name"
              label-placement="floating"
              placeholder="Last name"
              :clear-input="true"
            />
          </ion-item>

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

          <ion-item class="input-item">
            <ion-input
              v-model="passwordConfirmation"
              :type="showConfirm ? 'text' : 'password'"
              label="Confirm Password"
              label-placement="floating"
              placeholder="••••••••"
            />
            <ion-button fill="clear" slot="end" @click="showConfirm = !showConfirm">
              <ion-icon :icon="showConfirm ? eyeOff : eye" />
            </ion-button>
          </ion-item>
        </ion-list>

        <ion-button
          expand="block"
          class="auth-btn"
          :disabled="loading"
          @click="handleRegister"
        >
          <ion-spinner v-if="loading" name="crescent" />
          <span v-else>Create Account</span>
        </ion-button>

        <p class="switch-auth">
          Already have an account?
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
  IonPage, IonContent, IonList, IonItem, IonInput,
  IonButton, IonIcon, IonSpinner, toastController
} from '@ionic/vue';
import { eye, eyeOff } from 'ionicons/icons';
import { useAuth } from '@/composables/useAuth';

const router = useRouter();
const { register } = useAuth();

const firstName = ref('');
const lastName = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const showPassword = ref(false);
const showConfirm = ref(false);
const loading = ref(false);

async function handleRegister() {
  if (!firstName.value || !lastName.value || !email.value || !password.value || !passwordConfirmation.value) {
    const toast = await toastController.create({
      message: 'Please fill in all fields.',
      duration: 2000,
      color: 'warning',
      position: 'top',
    });
    await toast.present();
    return;
  }

  if (password.value !== passwordConfirmation.value) {
    const toast = await toastController.create({
      message: 'Passwords do not match.',
      duration: 2000,
      color: 'danger',
      position: 'top',
    });
    await toast.present();
    return;
  }

  loading.value = true;
  try {
    await register({
      first_name: firstName.value,
      last_name: lastName.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    });
    await router.push('/dashboard');
  } catch (err: any) {
    const errors = err?.response?.data?.errors;
    const firstError = errors ? Object.values(errors).flat()[0] as string : 'Registration failed. Please try again.';
    const toast = await toastController.create({
      message: firstError,
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
}

.auth-header {
  text-align: center;
  margin-bottom: 36px;
}

.app-title {
  font-size: 32px;
  font-weight: 700;
  margin: 0 0 6px;
}

.app-subtitle {
  font-size: 16px;
  color: var(--ion-color-medium);
  margin: 0;
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

