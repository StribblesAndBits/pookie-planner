<template>
  <v-dialog v-model="dialogOpen" max-width="540px">
    <v-card class="jules-form-card app-modal-card">
      <v-card-title>{{ isEditing ? 'Edit Jules Day' : 'New Jules Day' }}</v-card-title>
      <v-card-text class="jules-form-fields">
        <v-select
          v-model="form.title"
          label="Title"
          density="comfortable"
          :items="titleOptions"
          item-title="title"
          item-value="value"
          :menu-props="{ contentClass: 'event-select-menu' }"
        />
        <DatePickerField v-model="form.start" label="Start date" density="comfortable" class="date-field" />
        <DatePickerField v-model="form.end" label="End date" density="comfortable" class="date-field" />
        <v-text-field
          v-model="form.coming_time"
          :label="comingTimeLabel"
          type="time"
          density="comfortable"
        />
        <v-text-field
          v-model="form.leaving_time"
          :label="leavingTimeLabel"
          type="time"
          density="comfortable"
        />
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
            v-if="showCustomRecurrenceButton"
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
        <v-btn color="primary" class="action-btn jules-modal-action-btn" size="small" density="comfortable" rounded="pill" :loading="saving" @click="handleSaveClick">
          Save
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <v-dialog v-model="showOverwriteConfirmDialog" max-width="520px">
    <v-card class="jules-form-card app-modal-card">
      <v-card-title>Overwrite Jules Days?</v-card-title>
      <v-card-text>
        <div class="overwrite-panel">
          <p class="overwrite-copy">
            Clicking "Yes" will overwrite previously scheduled Jules days. Are you sure you would like to do this?
          </p>
          <div v-if="overwriteConflictDates.length > 0" class="overwrite-dates">
            <p class="overwrite-dates-title">Dates that will be overwritten:</p>
            <ul class="overwrite-date-list">
              <li v-for="date in overwriteConflictDates" :key="date">{{ formatDisplayDate(date) }}</li>
            </ul>
          </div>
        </div>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn color="primary" class="action-btn jules-modal-action-btn" size="small" density="comfortable" rounded="pill" @click="showOverwriteConfirmDialog = false">No</v-btn>
        <v-btn color="error" class="action-btn jules-modal-action-btn" size="small" density="comfortable" rounded="pill" @click="confirmOverwrite">Yes</v-btn>
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
        <v-btn color="primary" class="action-btn jules-modal-action-btn" size="small" density="comfortable" rounded="pill" @click="showCustomRecurrenceDialog = false">Cancel</v-btn>
        <v-btn color="primary" class="action-btn jules-modal-action-btn" size="small" density="comfortable" rounded="pill" @click="showCustomRecurrenceDialog = false">Done</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { computed, ref, toRef, watch } from 'vue';
import { VBtn, VCard, VCardActions, VCardText, VDialog, VRadio, VRadioGroup, VSelect, VSpacer, VTextField, VTextarea } from 'vuetify/components';
import DatePickerField from '@/components/DatePickerField.vue';
import { formatDisplayDate, getOccurrenceDatesUpTo, occursOnDate, recurrenceSummary, weekDays } from '@/utils/recurrence';
import {
  JULES_TITLE_GENERAL,
  JULES_TITLE_NO,
  normalizeJulesTitle,
} from '@/utils/jules';

type JulesDayForm = {
  title: string;
  start: string;
  end: string;
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
  showCustomRecurrenceButton?: boolean;
  existingJulesDays?: Array<{
    id: number;
    title: string;
    start: string;
    end: string;
    recurrence_type?: 'none' | 'daily' | 'weekly' | 'biweekly' | 'annually' | 'custom';
    recurrence_interval?: number | null;
    recurrence_unit?: 'day' | 'week' | 'month' | 'year' | null;
    recurrence_days_of_week?: number[] | null;
    recurrence_end_type?: 'never' | 'on' | 'after' | null;
    recurrence_end_date?: string | null;
    recurrence_occurrences?: number | null;
    excluded_occurrences?: string[] | null;
  }>;
  currentJulesDayId?: number | null;
}>(), {
  isEditing: false,
  saving: false,
  error: '',
  showCustomRecurrenceButton: true,
  existingJulesDays: () => [],
  currentJulesDayId: null,
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
const showOverwriteConfirmDialog = ref(false);

const titleOptions = [
  { title: 'Jules Day', value: JULES_TITLE_GENERAL },
  { title: 'No Jules Day', value: JULES_TITLE_NO },
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
const customRecurrenceSummary = computed(() => recurrenceSummary(form.value));
const isNoJulesTitleSelected = computed(() => normalizeJulesTitle(form.value.title) === JULES_TITLE_NO);
const comingTimeLabel = computed(() => (isNoJulesTitleSelected.value ? 'Leaving time (start day)' : 'Arriving time (start day)'));
const leavingTimeLabel = computed(() => (isNoJulesTitleSelected.value ? 'Arriving time (end day)' : 'Leaving time (end day)'));
const overwriteConflictDates = computed(() => {
  const targetDate = props.existingJulesDays.reduce((latest, day) => (day.end > latest ? day.end : latest), form.value.end);
  const candidateDates = getOccurrenceDatesUpTo(form.value, targetDate);

  return Array.from(new Set(candidateDates.filter((date) => props.existingJulesDays.some((day) => {
    if (day.id === props.currentJulesDayId) return false;
    return occursOnDate(day, date);
  })))).sort();
});
const shouldConfirmOverwrite = computed(() => overwriteConflictDates.value.length > 0);

watch(() => props.modelValue, (open) => {
  if (!open) {
    showCustomRecurrenceDialog.value = false;
  }
});

watch(() => form.value.title, (nextTitle, previousTitle) => {
  const normalizedTitle = normalizeJulesTitle(nextTitle);
  if (normalizedTitle !== nextTitle) {
    form.value.title = normalizedTitle;
    return;
  }

  const wasNoJulesTitle = normalizeJulesTitle(previousTitle ?? '') === JULES_TITLE_NO;
  const isNoJulesTitle = normalizedTitle === JULES_TITLE_NO;
  if (wasNoJulesTitle !== isNoJulesTitle) {
    [form.value.coming_time, form.value.leaving_time] = [form.value.leaving_time, form.value.coming_time];
  }
});

function closeDialog() {
  showCustomRecurrenceDialog.value = false;
  showOverwriteConfirmDialog.value = false;
  emit('update:modelValue', false);
}

function handleSaveClick() {
  if (shouldConfirmOverwrite.value) {
    showOverwriteConfirmDialog.value = true;
    return;
  }

  emit('save');
}

function confirmOverwrite() {
  showOverwriteConfirmDialog.value = false;
  emit('save');
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
    const next = selected.filter((value) => value !== day);
    if (next.length > 0) {
      form.value.recurrence_days_of_week = next;
    }
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
  margin-bottom: 12px;
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

.form-error {
  margin: 0;
  color: #b91c1c;
  font-size: 14px;
}

.overwrite-copy {
  margin: 0;
  color: #475569;
  font-size: 14px;
  line-height: 1.5;
}

.overwrite-panel {
  display: grid;
  gap: 12px;
  background: #ffffff;
  border-radius: 14px;
  padding: 14px;
  box-shadow: inset 0 0 0 1px #dbe4f0;
  margin-bottom: 14px;
}

.overwrite-dates {
  display: grid;
  gap: 8px;
}

.overwrite-dates-title {
  margin: 0;
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
}

.overwrite-date-list {
  margin: 0;
  padding-left: 18px;
  display: grid;
  gap: 4px;
  color: #334155;
  font-size: 14px;
}

.custom-recurrence-card {
  border-radius: 20px;
  background-color: var(--app-modal-bg) !important;
}
</style>
