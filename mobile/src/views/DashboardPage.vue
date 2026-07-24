<template>
  <ion-page>
    <AppNavbar />

    <ion-content :fullscreen="true">
      <div class="dashboard-content">
        <div class="dashboard-container">
          <div class="calendar-section">
            <CalendarCard ref="calendarCardRef" :jules-days="julesDays" :utilities="utilities" />
          </div>

          <v-card class="utilities-summary-card">
            <v-card-title class="utilities-summary-title utilities-summary-title-row">
              <span>Utilities Tracker</span>
              <div class="summary-actions">
                <v-btn color="primary" size="x-small" density="comfortable" rounded="lg" class="action-btn" @click="openAddUtilityDialog">
                  Add Utility
                </v-btn>
                <v-btn color="primary" size="x-small" density="comfortable" rounded="lg" class="action-btn" @click="goToUtilities">
                  See All
                </v-btn>
              </div>
            </v-card-title>
            <v-card-text class="utilities-summary-content">
              <p v-if="loadingUtilities" class="utilities-summary-copy">Loading utilities...</p>
              <p v-else-if="utilitiesError" class="utilities-summary-error">{{ utilitiesError }}</p>
              <template v-else-if="currentMonthUnpaidUtilities.length > 0">
                <p class="utilities-summary-copy">Unpaid utilities this month:</p>
                <ul class="utility-list">
                  <li
                    v-for="utility in currentMonthUnpaidUtilities"
                    :key="utility.id"
                    class="utility-item utility-item--bill"
                    :class="`utility-item--${normalizeUtilityCurrency(utility.utility_currency)}`"
                    :style="getUtilityItemStyle(utility.utility_currency)"
                  >
                    <div class="utility-main">
                      <span class="utility-name">{{ utility.name }}</span>
                      <span class="utility-meta">{{ formatDueDate(utility.due_date) }} • {{ formatUtilityAmount(utility.amount, utility.utility_currency) }}</span>
                    </div>
                    <div class="utility-actions">
                      <span class="utility-tag" :class="utility.tag">{{ utility.tag === 'essential' ? 'Essential' : 'Non-essential' }}</span>
                      <div class="utility-action-row">
                        <v-btn color="primary" size="x-small" density="comfortable" rounded="lg" class="action-btn" @click="openEditUtilityDialog(utility)">
                          Edit
                        </v-btn>
                        <v-btn color="primary" size="x-small" density="comfortable" rounded="lg" class="action-btn" @click="markUtilityAsPaid(utility.id)">
                          Mark Paid
                        </v-btn>
                      </div>
                    </div>
                  </li>
                </ul>
              </template>
              <template v-else>
                <p class="utilities-summary-copy">No unpaid utilities for this month.</p>
              </template>
            </v-card-text>
          </v-card>

          <v-card class="jules-summary-card">
            <v-card-title class="utilities-summary-title jules-summary-title-row">
              <span>Jules Days</span>
              <div class="summary-actions">
                <v-btn color="primary" size="x-small" density="comfortable" rounded="lg" class="action-btn" @click="openAddJulesDayDialog()">
                  Add Jules Day
                </v-btn>
                <v-btn color="primary" size="x-small" density="comfortable" rounded="lg" class="action-btn" @click="goToJules">
                  Jules Page
                </v-btn>
              </div>
            </v-card-title>
            <v-card-text class="utilities-summary-content">
              <p v-if="loadingJulesDays" class="utilities-summary-copy">Loading Jules Days...</p>
              <p v-else-if="julesDaysError" class="utilities-summary-error">{{ julesDaysError }}</p>
              <template v-else-if="currentWeekJulesDays.length > 0">
                <p class="utilities-summary-copy">Jules Days this week:</p>
                <ul class="utility-list">
                  <li v-for="day in currentWeekJulesDays" :key="`${day.id}-${day.occurrence_start}`" class="utility-item">
                    <div class="utility-main">
                      <span class="utility-name">{{ describeJulesDay(day.type, day.coming_time, day.leaving_time) }}</span>
                      <span class="utility-meta">{{ formatJulesOccurrenceDate(day) }}</span>
                    </div>
                  </li>
                </ul>
              </template>
              <template v-else>
                <p class="utilities-summary-copy">No Jules Days for this week.</p>
              </template>
            </v-card-text>
          </v-card>

        </div>
      </div>
    </ion-content>

    <v-dialog v-model="showAddUtilityDialog" max-width="500px">
      <v-card class="utility-form-card app-modal-card">
        <v-card-title>{{ editingUtilityId ? 'Edit Utility' : 'New Utility' }}</v-card-title>
        <v-card-text>
          <v-text-field v-model="utilityForm.name" label="Name" density="comfortable" />
          <v-select
            v-model="utilityForm.tag"
            label="Tag"
            density="comfortable"
            :items="utilityTagOptions"
            item-title="title"
            item-value="value"
            :menu-props="{ contentClass: 'event-select-menu' }"
          />
          <DatePickerField v-model="utilityForm.due_date" label="Due date" density="comfortable" class="date-field" />
          <div class="custom-recurrence-inline">
            <p class="custom-recurrence-summary">Bill unit</p>
            <UtilityCurrencyToggle v-model="utilityForm.utility_currency" />
          </div>
          <CurrencyAmountField v-model="utilityForm.amount" label="Amount" :prefix-variant="utilityPrefixVariant" />
          <label class="all-day-checkbox">
            <input v-model="utilityForm.recurs_monthly" type="checkbox" class="all-day-checkbox-input">
            <span class="all-day-checkbox-label">Recurring monthly</span>
          </label>
          <p v-if="utilityFormError" class="form-error">{{ utilityFormError }}</p>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn color="primary" class="action-btn" size="x-small" density="comfortable" rounded="lg" @click="showAddUtilityDialog = false">Cancel</v-btn>
          <v-btn color="primary" class="action-btn" size="x-small" density="comfortable" rounded="lg" :disabled="savingUtility" @click="saveUtility">
            {{ savingUtility ? 'Saving...' : (editingUtilityId ? 'Update' : 'Save') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <JulesDayDialog
      v-model="showAddJulesDialog"
      :form="julesForm"
      :is-editing="false"
      :saving="savingJulesDay"
      :error="julesFormError"
      @save="saveJulesDay"
    />

  </ion-page>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { onIonViewWillEnter } from '@ionic/vue';
import { IonPage, IonContent } from '@ionic/vue';
import { useRouter } from 'vue-router';
import { VCard, VCardTitle, VCardText, VBtn, VDialog, VTextField, VSelect, VCardActions, VSpacer } from 'vuetify/components';
import AppNavbar from '@/components/AppNavbar.vue';
import CalendarCard from '@/components/CalendarCard.vue';
import DatePickerField from '@/components/DatePickerField.vue';
import JulesDayDialog from '@/components/JulesDayDialog.vue';
import CurrencyAmountField from '@/components/CurrencyAmountField.vue';
import UtilityCurrencyToggle from '@/components/UtilityCurrencyToggle.vue';
import api from '@/services/api';
import {
  describeJulesDay,
} from '@/utils/jules';
import { formatUtilityAmount, normalizeUtilityCurrency } from '@/utils/utility';
import {
  datesInRange,
  formatDateString,
  getOccurrenceStartDate,
  getWeekRange,
  occursOnDate,
} from '@/utils/recurrence';

type UtilityItem = {
  id: number;
  name: string;
  tag: 'essential' | 'non-essential';
  due_date: string;
  amount: string;
  utility_currency?: 'dollars' | 'kisses' | null;
  status: 'unpaid' | 'paid';
  recurs_monthly: boolean;
};

type JulesDayItem = {
  id: number;
  type: 'arriving' | 'leaving' | 'here' | 'gone';
  start: string;
  end: string;
  coming_time?: string | null;
  leaving_time?: string | null;
  description?: string | null;
  recurrence_type?: 'none' | 'daily' | 'weekly' | 'biweekly' | 'annually' | 'custom';
  recurrence_interval?: number | null;
  recurrence_unit?: 'day' | 'week' | 'month' | 'year' | null;
  recurrence_days_of_week?: number[] | null;
  recurrence_end_type?: 'never' | 'on' | 'after' | null;
  recurrence_end_date?: string | null;
  recurrence_occurrences?: number | null;
  excluded_occurrences?: string[] | null;
};

type JulesDayForm = {
  type: 'arriving' | 'leaving' | 'here' | 'gone';
  start: string;
  coming_time: string;
  leaving_time: string;
  description: string;
  recurrence_type: 'none' | 'daily' | 'weekly' | 'biweekly' | 'annually' | 'custom';
  recurrence_interval: number;
  recurrence_unit: 'day' | 'week' | 'month' | 'year';
  recurrence_days_of_week: number[];
  recurrence_end_type: 'never' | 'on' | 'after';
  recurrence_end_date: string;
  recurrence_occurrences: number;
};

const router = useRouter();
const calendarCardRef = ref<InstanceType<typeof CalendarCard> | null>(null);
const utilities = ref<UtilityItem[]>([]);
const loadingUtilities = ref(false);
const utilitiesError = ref('');
const showAddUtilityDialog = ref(false);
const savingUtility = ref(false);
const editingUtilityId = ref<number | null>(null);
const utilityFormError = ref('');
const utilityTagOptions = [
  { title: 'Essential', value: 'essential' },
  { title: 'Non-essential', value: 'non-essential' },
] as const;
const utilityForm = ref({
  name: '',
  tag: 'essential' as 'essential' | 'non-essential',
  due_date: formatDateString(new Date()),
  amount: 0,
  utility_currency: 'dollars' as 'dollars' | 'kisses',
  recurs_monthly: false,
});

const julesDays = ref<JulesDayItem[]>([]);
const loadingJulesDays = ref(false);
const julesDaysError = ref('');
const showAddJulesDialog = ref(false);
const savingJulesDay = ref(false);
const julesFormError = ref('');
const julesForm = ref<JulesDayForm>({
  type: 'here',
  start: formatDateString(new Date()),
  coming_time: '',
  leaving_time: '',
  description: '',
  recurrence_type: 'none',
  recurrence_interval: 1,
  recurrence_unit: 'week',
  recurrence_days_of_week: [new Date().getDay()],
  recurrence_end_type: 'never',
  recurrence_end_date: '',
  recurrence_occurrences: 13,
});



const currentWeekRange = computed(() => getWeekRange(new Date()));
const currentWeekDates = computed(() => datesInRange(currentWeekRange.value.start, currentWeekRange.value.end));
const currentWeekJulesDays = computed(() => {
  return currentWeekDates.value.flatMap((date) => {
    return julesDays.value
      .filter((day) => occursOnDate(day, date))
      .map((day) => ({
        ...day,
        occurrence_start: getOccurrenceStartDate(day, date) || day.start,
        occurrence_date: date,
      }));
  }).sort((a, b) => a.occurrence_date.localeCompare(b.occurrence_date) || a.type.localeCompare(b.type));
});

const currentMonthUnpaidUtilities = computed(() => {
  const now = new Date();
  const year = now.getFullYear();
  const month = now.getMonth();

  return utilities.value
    .filter((utility) => {
      if (utility.status !== 'unpaid') return false;
      const dueDate = new Date(`${utility.due_date}T00:00:00`);
      return dueDate.getFullYear() === year && dueDate.getMonth() === month;
    })
    .sort((a, b) => a.due_date.localeCompare(b.due_date));
});

const utilityPrefixVariant = computed(() => (utilityForm.value.utility_currency === 'kisses' ? 'kiss' : 'dollar'));

function formatDueDate(date: string) {
  return new Date(`${date}T00:00:00`).toLocaleDateString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

function goToUtilities() {
  router.push('/utilities');
}

function goToJules() {
  router.push('/jules');
}

function openAddUtilityDialog() {
  editingUtilityId.value = null;
  utilityFormError.value = '';
  utilityForm.value = {
    name: '',
    tag: 'essential',
    due_date: new Date().toISOString().slice(0, 10),
    amount: 0,
    utility_currency: 'dollars',
    recurs_monthly: false,
  };
  showAddUtilityDialog.value = true;
}

function openAddJulesDayDialog(date?: string) {
  julesFormError.value = '';
  showAddJulesDialog.value = true;
  const initialDate = typeof date === 'string' && date ? date : formatDateString(new Date());
  julesForm.value = {
    type: 'here',
    start: initialDate,
    coming_time: '',
    leaving_time: '',
    description: '',
    recurrence_type: 'none',
    recurrence_interval: 1,
    recurrence_unit: 'week',
    recurrence_days_of_week: [new Date(`${initialDate}T00:00:00`).getDay()],
    recurrence_end_type: 'never',
    recurrence_end_date: '',
    recurrence_occurrences: 13,
  };
}

function openEditUtilityDialog(utility: UtilityItem) {
  editingUtilityId.value = utility.id;
  utilityFormError.value = '';
  utilityForm.value = {
    name: utility.name,
    tag: utility.tag,
    due_date: utility.due_date,
    amount: Number(utility.amount),
    utility_currency: normalizeUtilityCurrency(utility.utility_currency),
    recurs_monthly: utility.recurs_monthly,
  };
  showAddUtilityDialog.value = true;
}

async function loadUtilities() {
  loadingUtilities.value = true;
  utilitiesError.value = '';
  try {
    const { data } = await api.get('/utilities');
    utilities.value = data;
  } catch (error: any) {
    utilitiesError.value = error?.response?.data?.message || 'Unable to load utilities.';
  } finally {
    loadingUtilities.value = false;
  }
}

async function loadJulesDays() {
  loadingJulesDays.value = true;
  julesDaysError.value = '';
  try {
    const { data } = await api.get('/jules-days');
    julesDays.value = data;
  } catch (error: any) {
    julesDaysError.value = friendlyJulesLoadError(error);
  } finally {
    loadingJulesDays.value = false;
  }
}

async function saveUtility() {
  utilityFormError.value = '';
  savingUtility.value = true;
  try {
    const payload = {
      ...utilityForm.value,
      amount: Number(utilityForm.value.amount),
    };
    if (editingUtilityId.value) {
      await api.put(`/utilities/${editingUtilityId.value}`, payload);
    } else {
      await api.post('/utilities', payload);
    }
    editingUtilityId.value = null;
    showAddUtilityDialog.value = false;
    await loadUtilities();
  } catch (error: any) {
    const fieldErrors = error?.response?.data?.errors;
    const firstField = fieldErrors ? Object.keys(fieldErrors)[0] : null;
    utilityFormError.value = firstField ? fieldErrors[firstField]?.[0] : (error?.response?.data?.message || 'Unable to save utility.');
  } finally {
    savingUtility.value = false;
  }
}

async function markUtilityAsPaid(utilityId: number) {
  if (!window.confirm('Are you suuuuuuuure you paid this one?')) return;
  await api.put(`/utilities/${utilityId}`, { status: 'paid' });
  await loadUtilities();
}

function formatJulesOccurrenceDate(day: JulesDayItem & { occurrence_date: string }) {
  return new Date(`${day.occurrence_date}T00:00:00`).toLocaleDateString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

function getUtilityItemStyle(currency?: 'dollars' | 'kisses' | null) {
  const color = normalizeUtilityCurrency(currency) === 'kisses' ? '#ec4899' : '#3b82f6';
  return {
    backgroundColor: '#ffffff',
    border: `1px solid ${color}`,
  };
}

function friendlyJulesLoadError(error: any) {
  const message = String(error?.response?.data?.message || '');
  if (message.includes('SQLSTATE[42S02]') || message.includes('doesn\'t exist')) {
    return 'Jules Days are not set up yet. Run the database migration.';
  }

  return message || 'Unable to load Jules Days.';
}

async function saveJulesDay() {
  julesFormError.value = '';
  savingJulesDay.value = true;
  try {
    const payload = {
      ...julesForm.value,
      coming_time: julesForm.value.coming_time || null,
      leaving_time: julesForm.value.leaving_time || null,
      recurrence_end_date: julesForm.value.recurrence_end_type === 'on' ? julesForm.value.recurrence_end_date : null,
      recurrence_occurrences: julesForm.value.recurrence_end_type === 'after' ? julesForm.value.recurrence_occurrences : null,
      recurrence_days_of_week: julesForm.value.recurrence_unit === 'week' ? julesForm.value.recurrence_days_of_week : [],
    };

    if (payload.recurrence_type !== 'custom') {
      payload.recurrence_end_date = null;
      payload.recurrence_occurrences = null;
    }

    await api.post('/jules-days', payload);
    showAddJulesDialog.value = false;
    await loadJulesDays();
  } catch (error: any) {
    const fieldErrors = error?.response?.data?.errors;
    const firstField = fieldErrors ? Object.keys(fieldErrors)[0] : null;
    julesFormError.value = firstField ? fieldErrors[firstField]?.[0] : (error?.response?.data?.message || 'Unable to save Jules Day.');
  } finally {
    savingJulesDay.value = false;
  }
}

onMounted(async () => {
  await loadUtilities();
  await loadJulesDays();
});

onIonViewWillEnter(async () => {
  await loadUtilities();
  await loadJulesDays();
  calendarCardRef.value?.refresh();
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

.utilities-summary-card {
  max-width: 900px;
  margin: 0 auto;
}

.jules-summary-card {
  max-width: 900px;
  margin: 0 auto;
}

.utilities-summary-title {
  font-size: 16px;
  font-weight: 700;
}

.utilities-summary-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.jules-summary-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.summary-actions {
  display: inline-flex;
  gap: 8px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.utilities-summary-content {
  display: grid;
  gap: 12px;
}

.utilities-summary-copy {
  margin: 0;
  color: #64748b;
  font-size: 14px;
}

.utilities-summary-error {
  margin: 0;
  color: #b91c1c;
  font-size: 14px;
}

.utility-list {
  margin: 0;
  padding: 0;
  list-style: none;
  display: grid;
  gap: 8px;
}

.utility-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  border: 1px solid #dbe4f0;
  border-radius: 10px;
  padding: 8px 10px;
  background: #f8fbff;
}

.utility-item--bill {
  background: #ffffff;
}

.utility-item--dollars {
  border-color: #3b82f6;
}

.utility-item--kisses {
  border-color: #ec4899;
}

.utility-main {
  display: grid;
  gap: 2px;
}

.utility-name {
  font-weight: 700;
  color: #0f172a;
}

.utility-meta {
  color: #64748b;
  font-size: 12px;
}

.utility-tag {
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
}

.utility-tag.essential {
  background: #dbeafe;
  color: #1e3a8a;
}

.utility-tag.non-essential {
  background: #ede9fe;
  color: #5b21b6;
}

.utility-actions {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 8px;
  align-self: flex-end;
}

.utility-action-row {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.utilities-empty-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.utilities-summary-footer {
  display: flex;
  justify-content: flex-start;
}

.jules-summary-card .utility-tag.essential {
  background: #111111;
  color: #ffffff;
}

.utility-form-card {
  border-radius: 20px;
  background-color: var(--app-modal-bg) !important;
}

.custom-recurrence-inline {
  display: grid;
  gap: 8px;
  margin-bottom: 16px;
}

.custom-recurrence-summary {
  margin: 0;
  color: #475569;
  font-size: 14px;
}

.custom-row {
  display: flex;
  gap: 10px;
  align-items: center;
  margin-bottom: 12px;
}

.custom-label {
  font-size: 13px;
  font-weight: 700;
  color: #334155;
}

.custom-interval-field {
  max-width: 96px;
}

.custom-unit-field {
  max-width: 130px;
}

.repeat-on-section {
  margin-bottom: 12px;
}

.weekday-picker {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 8px;
}

.weekday-circle {
  width: 38px;
  height: 38px;
  border-radius: 999px;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  font-weight: 700;
  color: #334155;
}

.weekday-circle.active {
  background: var(--color-primary);
  color: var(--color-text);
  border-color: var(--color-primary);
}

.ends-section {
  display: grid;
  gap: 8px;
}

.custom-end-field {
  max-width: 240px;
}

.all-day-checkbox {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.all-day-checkbox-input {
  width: 18px;
  height: 18px;
  margin: 0;
  appearance: none;
  border: 1.5px solid #ffffff;
  border-radius: 4px;
  background-color: #ffffff;
  box-shadow: 0 0 0 1px #94a3b8;
  position: relative;
  cursor: pointer;
}

.all-day-checkbox-input:checked {
  background-color: var(--color-primary);
  border-color: #ffffff;
}

.all-day-checkbox-input:checked::after {
  content: '';
  position: absolute;
  left: 50%;
  top: 50%;
  width: 6px;
  height: 11px;
  border: solid #ffffff;
  border-width: 0 2px 2px 0;
  transform: translate(-50%, -58%) rotate(45deg);
}

.all-day-checkbox-label {
  font-size: 14px;
  color: #334155;
}

.date-field :deep(.v-field__input) {
  display: flex !important;
  align-items: center !important;
}

.date-field :deep(input[type='date']) {
  line-height: 1.2;
}

:deep(.v-text-field .v-field),
:deep(.v-select .v-field) {
  background-color: #ffffff !important;
}

.form-error {
  margin: 8px 0 0;
  color: #b91c1c;
  font-size: 13px;
}

.action-btn {
  text-transform: none;
  border-radius: 12px !important;
  padding-inline: 10px;
  min-height: 30px;
  background-color: var(--app-button-bg) !important;
  color: var(--app-button-text) !important;
}

.action-btn :deep(.v-btn__content) {
  font-size: 13px;
  font-weight: 700;
}

.btn-plus-icon {
  font-size: 14px;
  margin-left: 4px;
  line-height: 1;
}

@media (max-width: 768px) {
  .dashboard-content {
    padding: 10px 5px;
  }

  .calendar-section {
    height: fit-content;
  }

  .utility-item {
    align-items: flex-start;
  }

  :deep(.v-card:not(.utility-form-card)) {
    width: 100%;
    max-width: 100%;
    border-radius: 12px;
  }
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
