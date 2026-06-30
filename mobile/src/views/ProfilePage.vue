<template>
  <ion-page>
    <Navbar />

    <ion-content :fullscreen="true" class="ion-padding">
      <ion-header collapse="condense">
        <ion-toolbar>
          <ion-title size="large">Profile</ion-title>
        </ion-toolbar>
      </ion-header>

      <div v-if="loading" class="ion-text-center" style="padding: 40px 0">
        <ion-spinner />
      </div>

      <div v-else>
        <v-card class="mb-4">
          <v-card-title>Personal Information</v-card-title>
          <v-card-text>
            <v-form @submit.prevent="updateProfile">
              <v-text-field
                v-model="form.first_name"
                label="First Name"
                variant="outlined"
                class="mb-4"
              />

              <v-text-field
                v-model="form.last_name"
                label="Last Name"
                variant="outlined"
                class="mb-4"
              />

              <v-text-field
                v-model="form.email"
                label="Email"
                type="email"
                variant="outlined"
                class="mb-4"
              />

              <v-divider class="my-4" />

              <v-text-field
                v-model="form.password"
                label="New Password (leave blank to keep current)"
                type="password"
                variant="outlined"
                class="mb-4"
              />

              <v-text-field
                v-model="form.password_confirmation"
                label="Confirm Password"
                type="password"
                variant="outlined"
                class="mb-4"
              />

              <v-divider class="my-4" />

              <label class="text-subtitle-2">Color Preference</label>
              <div class="color-options mt-2 mb-4">
                <button
                  v-for="color in colors"
                  :key="color"
                  type="button"
                  :class="['color-btn', { active: form.color_preference === color, taken: isColorTaken(color) }]"
                  :style="{ backgroundColor: color }"
                  :disabled="isColorTaken(color) && form.color_preference !== color"
                  @click="form.color_preference = color"
                  :title="isColorTaken(color) && form.color_preference !== color ? `${color} is taken` : color"
                >
                  <v-icon v-if="isColorTaken(color) && form.color_preference !== color" class="taken-icon">mdi-prohibition</v-icon>
                </button>
              </div>

              <v-btn
                type="submit"
                :disabled="saving"
                color="primary"
                class="w-100"
              >
                {{ saving ? 'Saving...' : 'Save Changes' }}
              </v-btn>
            </v-form>
          </v-card-text>
        </v-card>

        <div v-if="message" :class="['alert', messageType]">
          {{ message }}
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import { IonPage, IonContent, IonHeader, IonToolbar, IonTitle, IonSpinner, toastController } from '@ionic/vue';
import { VCard, VCardTitle, VCardText, VTextField, VDivider, VBtn, VIcon } from 'vuetify/components';
import Navbar from '@/components/Navbar.vue';
import { useAuth } from '@/composables/useAuth';
import api from '@/services/api';

const { user } = useAuth();
const loading = ref(false);
const saving = ref(false);
const message = ref('');
const messageType = ref('');
const colors = ['#D6486B', '#6B3F38', '#5C6E4A', '#D9A441'] as const;
const availableColors = ref<string[]>([...colors]);

const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  password_confirmation: '',
  color_preference: '',
});

const activeColors = computed(() => new Set(availableColors.value));

function isColorTaken(color: string) {
  return !activeColors.value.has(color) && form.value.color_preference !== color;
}

function applyProfileResponse(data: any) {
  const profileUser = data?.user ?? data;
  user.value = profileUser;
  availableColors.value = Array.isArray(data?.available_colors) ? data.available_colors : [...colors];

  form.value = {
    first_name: profileUser.first_name || '',
    last_name: profileUser.last_name || '',
    email: profileUser.email || '',
    password: '',
    password_confirmation: '',
    color_preference: profileUser.color_preference || availableColors.value[0] || '',
  };
}

onMounted(async () => {
  loading.value = true;

  try {
    const { data } = await api.get('/profile');
    applyProfileResponse(data);
  } catch {
    if (user.value) {
      availableColors.value = [...colors];
      form.value = {
        first_name: user.value.first_name || '',
        last_name: user.value.last_name || '',
        email: user.value.email || '',
        password: '',
        password_confirmation: '',
        color_preference: user.value.color_preference || '',
      };
    }
  } finally {
    loading.value = false;
  }
});

async function updateProfile() {
  saving.value = true;
  message.value = '';

  try {
    const payload: any = {
      first_name: form.value.first_name,
      last_name: form.value.last_name,
      email: form.value.email,
      color_preference: form.value.color_preference,
    };

    if (form.value.password) {
      payload.password = form.value.password;
      payload.password_confirmation = form.value.password_confirmation;
    }

    const { data } = await api.put('/profile', payload);
    applyProfileResponse(data);
    form.value.password = '';
    form.value.password_confirmation = '';

    message.value = 'Profile updated successfully!';
    messageType.value = 'success';

    setTimeout(() => {
      message.value = '';
    }, 3000);
  } catch (err: any) {
    const errorMsg = err?.response?.data?.message || 'Failed to update profile';
    message.value = errorMsg;
    messageType.value = 'error';

    const toast = await toastController.create({
      message: errorMsg,
      duration: 2000,
      color: 'danger',
      position: 'top',
    });
    await toast.present();
  } finally {
    saving.value = false;
  }
}
</script>

<style scoped>
.color-options {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.color-btn {
  width: 40px;
  height: 40px;
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

.color-btn:disabled {
  cursor: not-allowed;
  opacity: 0.5;
  transform: none;
}

.taken-icon {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 40px;
  color: #d32f2f;
  pointer-events: none;
}

.alert {
  padding: 12px;
  border-radius: 8px;
  margin-top: 16px;
  font-weight: 500;
}

.alert.success {
  background-color: #c8e6c9;
  color: #2e7d32;
  border: 1px solid #2e7d32;
}

.alert.error {
  background-color: #ffcdd2;
  color: #c62828;
  border: 1px solid #c62828;
}

.mb-4 {
  margin-bottom: 16px;
}

.mt-2 {
  margin-top: 8px;
}

.my-4 {
  margin-top: 16px;
  margin-bottom: 16px;
}

.w-100 {
  width: 100%;
}

:deep(.v-card) {
  background-color: #ffffff !important;
}

:deep(.v-text-field .v-field) {
  background-color: #ffffff !important;
}
</style>



