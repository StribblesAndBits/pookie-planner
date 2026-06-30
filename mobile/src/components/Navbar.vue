<template>
  <div class="navbar-container">
    <div class="navbar-content">
      <!-- Logo (left) -->
      <router-link to="/dashboard" class="logo-link">
        <img src="/images/pp-logo.png" alt="Pookie Planner" class="navbar-logo" />
      </router-link>

      <!-- Title (center) -->
      <div class="navbar-title">{{ pageTitle }}</div>

      <!-- User menu (right) -->
      <v-spacer />
      <v-menu v-model="isOpen" :close-on-content-click="true">
        <template v-slot:activator="{ props }">
          <button
            v-bind="props"
            class="initials-button"
          >
            {{ initials }}
          </button>
        </template>
        <v-list>
          <v-list-item @click="goToProfile">
            <v-list-item-title>Profile</v-list-item-title>
          </v-list-item>
          <v-list-item @click="handleLogout">
            <v-list-item-title>Logout</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { VSpacer, VMenu, VList, VListItem, VListItemTitle } from 'vuetify/components';
import { useAuth } from '@/composables/useAuth';

const router = useRouter();
const route = useRoute();
const { logout, user } = useAuth();

const isOpen = ref(false);
const isLoggingOut = ref(false);
const cachedInitials = ref('');

function buildInitials(firstName?: string, lastName?: string) {
   const first = firstName?.[0]?.toUpperCase() || '';
   const last = lastName?.[0]?.toUpperCase() || '';
   return `${first}${last}`.trim();
}

const initials = computed(() => {
   if (isLoggingOut.value) return cachedInitials.value || 'U';
   if (!user.value) return cachedInitials.value || 'U';
   return buildInitials(user.value.first_name, user.value.last_name) || cachedInitials.value || 'U';
});

const pageTitle = computed(() => {
   const routeName = route.name as string | undefined;
   if (routeName === 'Dashboard') return 'Dashboard';
   if (routeName === 'Profile') return 'Profile';
   return 'Dashboard';
});

watch(
   () => user.value,
   (currentUser) => {
      const next = buildInitials(currentUser?.first_name, currentUser?.last_name);
      if (next) cachedInitials.value = next;
   },
   { immediate: true }
);

watch(() => route.fullPath, () => {
   isOpen.value = false;
});

function goToProfile() {
   isOpen.value = false;
   router.push('/profile');
}

async function handleLogout() {
   isLoggingOut.value = true;
   isOpen.value = false;
   await logout();
   await router.replace('/login');
}
</script>

<style scoped>
.navbar-container {
  width: 100%;
  background: white;
  border-bottom: 1px solid #e0e0e0;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 100;
    height: 65px;
}

.navbar-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 65px;
  padding: 0 16px;
  min-height: 56px;
  position: relative;
}

.logo-link {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 60px;
  width: 90px;
  cursor: pointer;
  border-radius: 4px;
  transition: background-color 0.2s;
}

.logo-link:hover {
  background-color: var(--color-background);
}

.navbar-logo {
  height: 60px;
  width: 90px;
  object-fit: contain;
}

.navbar-title {
  font-size: 20px;
  font-weight: 600;
  color: var(--color-text);
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  text-align: center;
  min-width: 100px;
  cursor: none;
}

.initials-button {
  min-width: 44px;
  width: 44px;
  height: 44px;
  font-weight: 700;
  font-size: 16px;
  border-radius: 50%;
  border: none;
  background-color: var(--color-primary);
  color: var(--color-text);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.2s;
  flex-shrink: 0;
}

.initials-button:hover {
  background-color: var(--color-secondary);
}
</style>

