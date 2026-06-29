<template>
  <ion-header :translucent="true">
    <ion-toolbar>
      <ion-title>Dashboard</ion-title>
      <ion-buttons slot="end">
        <ion-button fill="solid" shape="round" @click="openMenu" class="initials-button">
          {{ initials }}
        </ion-button>
      </ion-buttons>
    </ion-toolbar>
  </ion-header>

  <ion-popover :is-open="isOpen" @didDismiss="isOpen = false" :event="triggerEvent">
    <ion-content class="ion-padding">
      <ion-button expand="block" fill="clear" @click="handleLogout">
        Logout
      </ion-button>
    </ion-content>
  </ion-popover>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { IonHeader, IonToolbar, IonTitle, IonButtons, IonButton, IonPopover, IonContent } from '@ionic/vue';
import { useAuth } from '@/composables/useAuth';

const router = useRouter();
const { logout, user } = useAuth();

const isOpen = ref(false);
const triggerEvent = ref<Event | null>(null);

const initials = computed(() => {
  if (!user.value) return '?';
  const first = user.value.first_name?.[0]?.toUpperCase() || '';
  const last = user.value.last_name?.[0]?.toUpperCase() || '';
  return `${first}${last}`;
});

function openMenu(event: Event) {
  triggerEvent.value = event;
  isOpen.value = true;
}

async function handleLogout() {
  await logout();
  await router.push('/login');
}
</script>

<style scoped>
.initials-button {
  min-width: 40px;
  height: 40px;
  font-weight: 700;
  font-size: 16px;
}
</style>

