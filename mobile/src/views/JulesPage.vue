<template>
  <ion-page>
    <Navbar />

    <div class="dashboard-content">
      <div class="dashboard-container">
        <div class="calendar-section">
          <JulesCalendarCard />
        </div>
      </div>
    </div>
  </ion-page>
</template>

<script setup lang="ts">
import { IonPage } from '@ionic/vue';
import Navbar from '@/components/Navbar.vue';
import JulesCalendarCard from '@/components/JulesCalendarCard.vue';
</script>

<style scoped>
.dashboard-content {
  min-height: calc(100vh - 65px);
  min-height: calc(100dvh - 65px);
  padding: 10px 5px;
  box-sizing: border-box;
}

.dashboard-container {
  display: flex;
  flex-direction: column;
  justify-content: stretch;
  align-items: stretch;
  gap: 10px;
  min-height: 100%;
}

.calendar-section {
  height: calc(100vh - 85px);
  height: calc(100dvh - 85px);
  display: flex;
  justify-content: center;
  align-items: stretch;
}

:deep(.v-card:not(.utility-form-card)) {
  border-radius: 18px;
  width: 100%;
  max-width: 900px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  background-color: #ffffff !important;
}

:deep(.v-app-bar) {
   position: static;
}
</style>
