<template>
  <v-dialog v-model="dialogOpen" max-width="540px">
    <v-card class="jules-form-card app-modal-card">
      <v-card-title>{{ isEditing ? 'Edit Jules Day' : 'New Jules Day' }}</v-card-title>
      <v-card-text class="jules-form-fields">
        <v-select
          v-model="form.type"
          label="Type"
          density="comfortable"
          :items="typeOptions"
          item-title="title"
          item-value="value"
          :menu-props="{ contentClass: 'event-select-menu' }"
        />
        <DatePickerField v-model="form.start" label="Date" density="comfortable" class="date-field" />
        <template v-if="form.type === 'arriving' || form.type === 'leaving'">
          <v-text-field
            v-if="form.type === 'arriving'"
            v-model="form.coming_time"
            label="Arriving time"
            type="time"
            density="comfortable"
          />
          <v-text-field
            v-if="form.type === 'leaving'"
            v-model="form.leaving_time"
            label="Leaving time"
            type="time"
            density="comfortable"
          />
        </template>
        <v-select
          v-model="form.recurrence_type"
          label="Repeat"
          density="comfortable"
          :items="recurrenceOptions"
          item-title="title"
          item-value="value"
          :menu-props="{ contentClass: 'event-select-menu' }"
          @update:model-value="handleRecurrenceTypeChange"
        />
        <div v-if="form.recurrence_type === 'custom'" class="custom-recurrence-inline">
          <p class="custom-recurrence-summary">{{ customRecurrenceSummary }}</p>
          <v-btn
            color="primary"
            size="x-small"
            density="comfortable"
            rounded="lg"
            class="action-btn"
            @click="showCustomRecurrenceDialog = true"
          >
            Edit Custom Recurrence
          </v-btn>
        </div>
        <v-textarea v-model="form.description" label="Notes" rows="3" density="comfortable" />
        <p v-if="error" class="form-error">{{ error }}</p>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn color="primary" class="action-btn jules-modal-action-btn" size="small" density="comfortable" rounded="pill" @click="closeDialog">Cancel</v-btn>
        <v-btn color="primary" class="action-btn jules-modal-action-btn" size="small" density="comfortable" rounded="pill" :loading="saving" @click="emit('save')">
          Save
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <v-dialog v-model="showCustomRecurrenceDialog" max-width="460px">
    <v-card class="custom-recurrence-card app-modal-card">
      <v-card-title>Custom recurrence</v-card-title>
      <v-card-text>
        <div class="custom-row">
          <span class="custom-label">Repeat every</span>
          <v-text-field
            v-model.number="form.recurrence_interval"
            type="number"
            min="1"
            max="999"
            density="comfortable"
            hide-details
            class="custom-interval-field number-spinner-field"
          />
          <v-select
            v-model="form.recurrence_unit"
            density="comfortable"
            hide-details
            :items="customRecurrenceUnits"
            item-title="title"
            item-value="value"
            :menu-props="{ contentClass: 'event-select-menu' }"
            class="custom-unit-field"
            @update:model-value="handleCustomUnitChange"
          />
        </div>

        <div v-if="form.recurrence_unit === 'week'" class="repeat-on-section">
          <div class="custom-label">Repeat on</div>
          <div class="weekday-picker">
            <button
              v-for="day in weekdayOptions"
              :key="day.value"
              type="button"
              class="weekday-circle"
              :class="{ active: form.recurrence_days_of_week.includes(day.value) }"
              @click="toggleCustomWeekday(day.value)"
            >
              {{ day.label }}
            </button>
          </div>
        </div>

        <div class="ends-section">
          <div class="custom-label">Ends</div>
          <v-radio-group v-model="form.recurrence_end_type" density="comfortable" hide-details>
            <v-radio label="Never" value="never" />
            <v-radio label="On" value="on" />
            <DatePickerField
              v-if="form.recurrence_end_type === 'on'"
              v-model="form.recurrence_end_date"
              label="End date"
              density="comfortable"
              class="custom-end-field"
            />
            <v-radio label="After" value="after" />
            <v-text-field
              v-if="form.recurrence_end_type === 'after'"
              v-model.number="form.recurrence_occurrences"
              type="number"
              min="1"
              max="9999"
              density="comfortable"
              class="custom-end-field number-spinner-field"
              suffix="occurrences"
            />
          </v-radio-group>
        </div>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn color="primary" class="action-btn jules-modal-action-btn" size="small" density="comfortable" rounded="pill" @click="showCustomRecurrenceDialog = false">Done</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { computed, ref, toRef, watch } from 'vue';
import { VBtn, VCard, VCardActions, VCardText, VDialog, VRadio, VRadioGroup, VSelect, VSpacer, VTextField, VTextarea } from 'vuetify/components';
import DatePickerField from '@/components/DatePickerField.vue';
import { recurrenceSummary, weekDays } from '@/utils/recurrence';
import {
  JULES_TYPE_ARRIVING,
  JULES_TYPE_LEAVING,
  JULES_TYPE_HERE,
  JULES_TYPE_GONE,
} from '@/utils/jules';

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

const props = withDefaults(defineProps<{
  modelValue: boolean;
  form: JulesDayForm;
  isEditing?: boolean;
  saving?: boolean;
  error?: string;
}>(), {
  isEditing: false,
  saving: false,
  error: '',
});

const emit = defineEmits<{
  (event: 'update:modelValue', value: boolean): void;
  (event: 'save'): void;
}>();

const dialogOpen = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit('update:modelValue', value),
});

const form = toRef(props, 'form');
const showCustomRecurrenceDialog = ref(false);

const typeOptions = [
  { title: 'Jules Here', value: JULES_TYPE_HERE },
  { title: 'Jules Arriving', value: JULES_TYPE_ARRIVING },
  { title: 'Jules Leaving', value: JULES_TYPE_LEAVING },
  { title: 'Jules Gone', value: JULES_TYPE_GONE },
];

const recurrenceOptions = [
  { title: 'Does not repeat', value: 'none' },
  { title: 'Daily', value: 'daily' },
  { title: 'Weekly', value: 'weekly' },
  { title: 'Bi-weekly', value: 'biweekly' },
  { title: 'Annually', value: 'annually' },
  { title: 'Custom...', value: 'custom' },
] as const;

const customRecurrenceUnits = [
  { title: 'day', value: 'day' },
  { title: 'week', value: 'week' },
  { title: 'month', value: 'month' },
  { title: 'year', value: 'year' },
] as const;

const weekdayOptions = weekDays.map((label, value) => ({ label: label.slice(0, 1), value }));
const customRecurrenceSummary = computed(() => recurrenceSummary({ ...form.value, end: form.value.start }));

watch(() => form.value.type, (type) => {
  if (type === JULES_TYPE_HERE || type === JULES_TYPE_GONE) {
    form.value.coming_time = '';
    form.value.leaving_time = '';
  } else if (type === JULES_TYPE_ARRIVING) {
    form.value.leaving_time = '';
  } else if (type === JULES_TYPE_LEAVING) {
    form.value.coming_time = '';
  }
});

watch(() => props.modelValue, (open) => {
  if (!open) showCustomRecurrenceDialog.value = false;
});

function closeDialog() {
  showCustomRecurrenceDialog.value = false;
  emit('update:modelValue', false);
}

function handleRecurrenceTypeChange(value: JulesDayForm['recurrence_type']) {
  if (value === 'custom') {
    if (form.value.recurrence_unit === 'week' && form.value.recurrence_days_of_week.length === 0) {
      form.value.recurrence_days_of_week = [new Date(`${form.value.start}T00:00:00`).getDay()];
    }
    showCustomRecurrenceDialog.value = true;
    return;
  }
  showCustomRecurrenceDialog.value = false;
}

function toggleCustomWeekday(day: number) {
  const selected = form.value.recurrence_days_of_week;
  if (selected.includes(day)) {
    const next = selected.filter((v) => v !== day);
    if (next.length > 0) form.value.recurrence_days_of_week = next;
    return;
  }
  form.value.recurrence_days_of_week = [...selected, day].sort((a, b) => a - b);
}

function handleCustomUnitChange() {
  if (form.value.recurrence_unit !== 'week') {
    form.value.recurrence_days_of_week = [];
    return;
  }
  if (form.value.recurrence_days_of_week.length === 0) {
    form.value.recurrence_days_of_week = [new Date(`${form.value.start}T00:00:00`).getDay()];
  }
}
</script>

<style scoped>
.jules-form-card {
  border-radius: 20px;
  background-color: var(--app-modal-bg) !important;
}

.custom-recurrence-card {
  border-radius: 20px;
  background-color: var(--app-modal-bg) !important;
}

.jules-modal-action-btn {
  min-height: 36px;
  min-width: 96px;
  padding-inline: 18px;
  border-radius: 999px !important;
}

.jules-form-card :deep(.v-field),
.jules-form-card :deep(.v-textarea .v-field),
.jules-form-card :deep(.v-input__control),
.custom-recurrence-card :deep(.v-field),
.custom-recurrence-card :deep(.v-textarea .v-field),
.custom-recurrence-card :deep(.v-input__control) {
  background-color: #ffffff !important;
}

.jules-form-card :deep(.v-field__overlay),
.custom-recurrence-card :deep(.v-field__overlay) {
  background-color: #ffffff !important;
  opacity: 1 !important;
}

.jules-form-fields {
  display: grid;
  gap: 12px;
}

.custom-recurrence-inline {
  display: grid;
  gap: 6px;
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
  flex-wrap: wrap;
}

.custom-label {
  font-size: 14px;
  font-weight: 600;
  color: #334155;
}

.custom-interval-field {
  max-width: 80px;
}

.custom-unit-field {
  max-width: 130px;
}

.repeat-on-section {
  display: grid;
  gap: 8px;
  margin-top: 10px;
}

.weekday-picker {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.weekday-circle {
  width: 32px;
  height: 32px;
  border-radius: 999px;
  border: 1px solid #dbe4f0;
  background: #ffffff;
  color: #334155;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  cursor: pointer;
}

.weekday-circle.active {
  background: #dbeafe;
  border-color: #93c5fd;
  color: #1e3a8a;
}

.ends-section {
  display: grid;
  gap: 8px;
  margin-top: 10px;
}

.custom-end-field {
  max-width: 240px;
}

.form-error {
  margin: 0;
  color: #b91c1c;
  font-size: 14px;
}
</style>
