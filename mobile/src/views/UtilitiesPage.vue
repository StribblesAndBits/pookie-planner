<template>
  <ion-page>
    <AppNavbar />

    <div class="utilities-content">
      <div class="utilities-container">
        <v-card class="utilities-card">
          <v-card-title class="utilities-title-row">
            <span>Utility Tracker</span>
            <v-btn color="primary" size="x-small" density="comfortable" rounded="lg" class="action-btn" @click="openAddUtilityDialog">
              Add Bill
            </v-btn>
          </v-card-title>
          <v-card-text class="utilities-card-body">
            <div class="tag-row">
              <span class="tag-pill essential">Essential</span>
              <span class="tag-pill non-essential">Non-essential</span>
            </div>
            <p v-if="loadingUtilities" class="helper-copy">Loading utilities...</p>
            <p v-else-if="utilitiesError" class="form-error">{{ utilitiesError }}</p>
            <ul v-else-if="utilities.length > 0" class="utility-list">
              <li v-for="utility in utilities" :key="utility.id" class="utility-item" @click="openUtilityDialog(utility)">
                <div class="utility-main">
                  <span class="utility-name">{{ utility.name }}</span>
                  <span class="utility-meta">{{ formatDueDate(utility.due_date) }} • {{ formatUtilityAmount(utility.amount, utility.utility_currency) }}</span>
                </div>
                <div class="utility-actions">
                  <span class="status-chip" :class="utility.status">{{ utility.status === 'paid' ? 'Paid' : 'Unpaid' }}</span>
                  <v-btn
                    v-if="utility.status === 'unpaid'"
                    color="primary"
                    size="x-small"
                    density="comfortable"
                    rounded="lg"
                    class="action-btn"
                    @click.stop="markPaid(utility.id)"
                  >
                    Mark Paid
                  </v-btn>
                </div>
              </li>
            </ul>
            <div v-else class="empty-state">
              <p class="empty-title">No bills yet</p>
              <p class="empty-copy">Add your first bill to start tracking due dates, reminders, and payments.</p>
            </div>
          </v-card-text>
        </v-card>
      </div>
    </div>

    <v-dialog v-model="showAddUtilityDialog" max-width="500px">
      <v-card class="utility-form-card app-modal-card">
        <v-card-title>{{ utilityDialogTitle }}</v-card-title>
        <v-card-text>
          <v-text-field v-model="utilityForm.name" label="Name" density="comfortable" :disabled="isUtilityModalReadOnly" />
          <v-select
            v-model="utilityForm.tag"
            label="Tag"
            density="comfortable"
            :items="utilityTagOptions"
            item-title="title"
            item-value="value"
            :menu-props="{ contentClass: 'event-select-menu' }"
            :disabled="isUtilityModalReadOnly"
          />
          <DatePickerField v-model="utilityForm.due_date" label="Due date" density="comfortable" class="date-field" :disabled="isUtilityModalReadOnly" />
          <div class="custom-recurrence-inline">
            <p class="custom-recurrence-summary">Bill unit</p>
            <UtilityCurrencyToggle
              v-model="utilityForm.utility_currency"
              :disabled="isUtilityModalReadOnly"
            />
          </div>
          <CurrencyAmountField v-model="utilityForm.amount" label="Amount" :prefix-variant="utilityPrefixVariant" :disabled="isUtilityModalReadOnly" />
          <label class="all-day-checkbox" :class="{ 'is-disabled': isUtilityModalReadOnly }">
            <input v-model="utilityForm.recurs_monthly" type="checkbox" class="all-day-checkbox-input" :disabled="isUtilityModalReadOnly">
            <span class="all-day-checkbox-label">Recurring monthly</span>
          </label>
          <p v-if="utilityFormError" class="form-error">{{ utilityFormError }}</p>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn color="primary" class="action-btn" size="x-small" density="comfortable" rounded="lg" @click="showAddUtilityDialog = false">{{ isUtilityModalReadOnly ? 'Close' : 'Cancel' }}</v-btn>
          <v-btn v-if="!isUtilityModalReadOnly" color="primary" class="action-btn" size="x-small" density="comfortable" rounded="lg" :disabled="savingUtility" @click="saveUtility">
            {{ savingUtility ? 'Saving...' : (editingUtilityId ? 'Update' : 'Save') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </ion-page>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { IonPage, onIonViewWillEnter } from '@ionic/vue';
import { VCard, VCardTitle, VCardText, VBtn, VDialog, VTextField, VSelect, VCardActions, VSpacer } from 'vuetify/components';
import AppNavbar from '@/components/AppNavbar.vue';
import DatePickerField from '@/components/DatePickerField.vue';
import CurrencyAmountField from '@/components/CurrencyAmountField.vue';
import UtilityCurrencyToggle from '@/components/UtilityCurrencyToggle.vue';
import api from '@/services/api';
import { formatUtilityAmount, normalizeUtilityCurrency } from '@/utils/utility';

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

const utilities = ref<UtilityItem[]>([]);
const loadingUtilities = ref(false);
const utilitiesError = ref('');
const showAddUtilityDialog = ref(false);
const savingUtility = ref(false);
const editingUtilityId = ref<number | null>(null);
const isUtilityModalReadOnly = ref(false);
const utilityFormError = ref('');
const utilityTagOptions = [
  { title: 'Essential', value: 'essential' },
  { title: 'Non-essential', value: 'non-essential' },
] as const;
const utilityForm = ref({
  name: '',
  tag: 'essential' as 'essential' | 'non-essential',
  due_date: new Date().toISOString().slice(0, 10),
  amount: 0,
  utility_currency: 'dollars' as 'dollars' | 'kisses',
  recurs_monthly: false,
});
const utilityPrefixVariant = computed(() => (utilityForm.value.utility_currency === 'kisses' ? 'kiss' : 'dollar'));

const utilityDialogTitle = computed(() => {
  if (isUtilityModalReadOnly.value) return 'Utility Details';
  if (editingUtilityId.value) return 'Edit Utility';
  return 'New Utility';
});

function formatDueDate(date: string) {
  return new Date(`${date}T00:00:00`).toLocaleDateString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
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

function openAddUtilityDialog() {
  editingUtilityId.value = null;
  isUtilityModalReadOnly.value = false;
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

function openUtilityDialog(utility: UtilityItem) {
  editingUtilityId.value = utility.id;
  isUtilityModalReadOnly.value = utility.status === 'paid';
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
    isUtilityModalReadOnly.value = false;
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

async function markPaid(utilityId: number) {
  if (!window.confirm('Are you suuuuuuuure you paid this one?')) return;
  await api.put(`/utilities/${utilityId}`, { status: 'paid' });
  await loadUtilities();
}

onMounted(async () => {
  await loadUtilities();
});

onIonViewWillEnter(async () => {
  await loadUtilities();
});
</script>

<style scoped>
.utilities-content {
  min-height: calc(100vh - 65px);
  min-height: calc(100dvh - 65px);
  padding: 10px 5px;
  box-sizing: border-box;
}

.utilities-container {
  display: flex;
  justify-content: center;
  align-items: flex-start;
}

.utilities-card {
  width: 100%;
  max-width: 900px;
  border-radius: 18px;
  background-color: #ffffff !important;
}

.utilities-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.utilities-card-body {
  display: grid;
  gap: 14px;
}

.helper-copy {
  margin: 0;
  color: #64748b;
}

.tag-row {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.tag-pill {
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
}

.tag-pill.essential {
  background: #dbeafe;
  color: #1e3a8a;
}

.tag-pill.non-essential {
  background: #ede9fe;
  color: #5b21b6;
}

.empty-state {
  border: 1px dashed #cbd5e1;
  border-radius: 10px;
  padding: 16px;
  background: #f8fbff;
}

.empty-title {
  margin: 0 0 4px;
  font-weight: 700;
  color: #0f172a;
}

.empty-copy {
  margin: 0;
  color: #64748b;
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

.utility-actions {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.status-chip {
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
}

.status-chip.unpaid {
  background: #fef3c7;
  color: #92400e;
}

.status-chip.paid {
  background: #dcfce7;
  color: #166534;
}

.utility-form-card {
  border-radius: 20px;
  background-color: var(--app-modal-bg) !important;
}

.custom-recurrence-inline {
  display: grid;
  gap: 8px;
  margin-bottom: 12px;
}

.custom-recurrence-summary {
  margin: 0;
  color: #475569;
  font-size: 14px;
}

.all-day-checkbox {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.all-day-checkbox.is-disabled {
  cursor: not-allowed;
  opacity: 0.65;
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

.all-day-checkbox-input:disabled {
  cursor: not-allowed;
  background-color: #e5e7eb;
  box-shadow: 0 0 0 1px #cbd5e1;
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

@media (max-width: 768px) {
  .utilities-content {
    padding: 10px 5px;
  }

  .utility-item {
    align-items: flex-start;
  }
}
</style>
