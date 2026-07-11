<template>
  <ion-page>
    <Navbar />

    <div class="dashboard-content">
      <div class="dashboard-container">
        <div class="calendar-section">
          <JulesCalendarCard ref="julesCalendarCardRef" />
        </div>
      </div>
    </div>
  </ion-page>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { IonPage, onIonViewWillEnter } from '@ionic/vue';
import Navbar from '@/components/Navbar.vue';
import JulesCalendarCard from '@/components/JulesCalendarCard.vue';

const julesCalendarCardRef = ref<InstanceType<typeof JulesCalendarCard> | null>(null);

onIonViewWillEnter(() => {
  julesCalendarCardRef.value?.refresh();
});
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
  height: fit-content;
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: flex-start;
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
