<template>
  <ion-page>
    <ion-content :fullscreen="true" class="ion-padding">
      <div class="auth-container">
        <div class="auth-header">
          <router-link to="/login" class="logo-link">
            <img src="/images/pp-logo-white.png" alt="Pookie Planner" class="app-logo" />
          </router-link>
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

        <div class="color-selection-wrapper">
          <label class="text-subtitle-2">Color Preference</label>
          <div class="color-options mt-2 mb-4">
            <button
              v-for="color in colors"
              :key="color"
              type="button"
              :class="['color-btn', { active: colorPreference === color, taken: isColorTaken(color) }]"
              :style="{ backgroundColor: color }"
              :disabled="isColorTaken(color)"
              @click="colorPreference = color"
              :title="isColorTaken(color) ? `${color} is taken` : color"
            >
              <span v-if="isColorTaken(color)" class="taken-symbol">🚫</span>
            </button>
          </div>
        </div>

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
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import {
  IonPage, IonContent, IonList, IonItem, IonInput,
  IonButton, IonIcon, IonSpinner, toastController
} from '@ionic/vue';
import { eye, eyeOff } from 'ionicons/icons';
import { useAuth } from '@/composables/useAuth';
import api from '@/services/api';

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
const colors = ['#D6486B', '#6B3F38', '#5C6E4A', '#D9A441'] as const;
const colorPreference = ref('#D6486B');
const availableColors = ref<string[]>([...colors]);

const activeColors = computed(() => new Set(availableColors.value));

function isColorTaken(color: string) {
  return !activeColors.value.has(color);
}

onMounted(async () => {
  try {
    const { data } = await api.get('/color-preferences');
    availableColors.value = Array.isArray(data?.available_colors) ? data.available_colors : [...colors];
    if (!availableColors.value.includes(colorPreference.value)) {
      colorPreference.value = availableColors.value[0] ?? colors[0];
    }
  } catch {
    availableColors.value = [...colors];
  }
});

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

  if (isColorTaken(colorPreference.value)) {
    const toast = await toastController.create({
      message: 'That color is already taken. Please choose another.',
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
      color_preference: colorPreference.value,
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
  margin-bottom: 24px;
}

.input-item {
  --background: var(--color-surface-light);
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
  background-color: var(--color-primary) !important;
  color: var(--color-text) !important;
}

.color-selection-wrapper {
  background: white;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 24px;
}

.color-selection-wrapper .text-subtitle-2 {
  display: block;
  color: var(--color-text);
  font-weight: 500;
  margin-bottom: 8px;
}

.color-options {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.color-btn {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  border: 3px solid transparent;
  position: relative;
  cursor: pointer;
  transition: all 0.2s ease;
  overflow: hidden;
}

.color-btn.active {
  border-color: var(--color-text);
  box-shadow: 0 0 0 2px white, 0 0 0 4px var(--color-text);
  transform: scale(1.1);
}

.color-btn:hover {
  transform: scale(1.05);
}

.color-btn:disabled {
  cursor: not-allowed;
  opacity: 0.65;
}

.taken-symbol {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  font-size: 42px;
  line-height: 1;
  pointer-events: none;
}

.text-subtitle-2 {
  font-size: 14px;
  font-weight: 500;
  color: var(--color-text);
}

.mt-2 {
  margin-top: 8px;
}

.mb-4 {
  margin-bottom: 16px;
}

.switch-auth {
  text-align: center;
  font-size: 14px;
  color: var(--color-text);
}

.switch-auth a {
  color: var(--color-primary);
  text-decoration: none;
  font-weight: 500;
}

.switch-auth a:hover {
  color: var(--color-text);
}
</style>

