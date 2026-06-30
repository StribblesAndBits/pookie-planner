<template>
  <ion-page>
    <Navbar />

    <ion-content :fullscreen="true" class="ion-padding dashboard-content">
      <div class="dashboard-container">
        <CalendarCard />
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { IonContent, IonPage } from '@ionic/vue';
import Navbar from '@/components/Navbar.vue';
import CalendarCard from '@/components/CalendarCard.vue';
</script>

<style scoped>
.dashboard-container {
   display: flex;
   justify-content: center;
   align-items: flex-start;
   min-height: calc(100vh - 200px);
   margin-top: 15px;
   padding: 16px;
}

@media (max-width: 768px) {
   .dashboard-container {
     padding: 0;
   }

   :deep(.v-card) {
     width: 100vw;
     max-width: 100vw;
     border-radius: 8px;
   }

   :deep(.dashboard-content) {
     --padding-start: 0px;
     --padding-end: 0px;
     --padding-top: 0px;
     --padding-bottom: 0px;
   }
}

:deep(.v-card) {
   border-radius: 16px;
   width: 100%;
   max-width: 900px;
   box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

:deep(.v-app-bar) {
   position: static;
}
</style>

