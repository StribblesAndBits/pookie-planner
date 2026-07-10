<template>
  <v-text-field
    :model-value="displayValue"
    :label="label"
    :density="density"
    :disabled="disabled"
    readonly
    class="date-picker-field"
    @click="openPicker"
  />

  <v-dialog v-model="showPicker" max-width="380px">
    <v-card class="date-picker-card app-modal-card">
      <v-card-title>{{ label || 'Select date' }}</v-card-title>
      <v-card-text class="date-picker-body">
        <v-date-picker
          v-model="draftDate"
          :min="min"
          :max="max"
          :show-current="getTodayDateString()"
          show-adjacent-months
          weeks-in-month="dynamic"
          mode-icon="mdi-chevron-down"
          hide-header
          @update:model-value="handleDateSelection"
        />
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn color="primary" class="action-btn" size="x-small" density="comfortable" rounded="lg" @click="showPicker = false">Cancel</v-btn>
        <v-btn color="primary" class="action-btn" size="x-small" density="comfortable" rounded="lg" @click="applyDate">Done</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { VTextField, VDialog, VCard, VCardTitle, VCardText, VCardActions, VBtn, VSpacer, VDatePicker } from 'vuetify/components';
import { formatDisplayDate } from '@/utils/recurrence';

const props = withDefaults(defineProps<{
  modelValue: string;
  label: string;
  density?: 'default' | 'comfortable' | 'compact';
  min?: string;
  max?: string;
  disabled?: boolean;
}>(), {
  density: 'comfortable',
  min: undefined,
  max: undefined,
  disabled: false,
});

const emit = defineEmits<{
  (event: 'update:modelValue', value: string): void;
}>();

const showPicker = ref(false);
const draftDate = ref<string | Date>('');
const displayValue = computed(() => formatDisplayDate(props.modelValue));

function getTodayDateString(): string {
  const today = new Date();
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, '0');
  const day = String(today.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function normalizeDateValue(value: string | Date | null | undefined): string {
  if (!value) return '';
  if (typeof value === 'string') return value.slice(0, 10);
  return value.toISOString().slice(0, 10);
}

function openPicker() {
  if (props.disabled) return;
  draftDate.value = props.modelValue || getTodayDateString();
  showPicker.value = true;
}

function handleDateSelection(value: string | Date | null | undefined) {
  draftDate.value = value || '';
  emit('update:modelValue', normalizeDateValue(value));
  showPicker.value = false;
}

function applyDate() {
  emit('update:modelValue', normalizeDateValue(draftDate.value));
  showPicker.value = false;
}
</script>

<style scoped>
.date-picker-field :deep(.v-field) {
  background-color: #ffffff !important;
}

.date-picker-card {
  border-radius: 20px;
}

.date-picker-body {
  display: flex;
  justify-content: center;
  padding: 4px 0 12px;
}

/* Give the picker a bit more fixed room so the years/months scrollbar never
   forces the calendar body (and the controls above it) to change width. */
.date-picker-card :deep(.v-date-picker) {
  width: 344px;
  max-width: 100%;
  margin-inline: auto;
}

.date-picker-card :deep(.v-date-picker-controls__month-btn),
.date-picker-card :deep(.v-date-picker-controls__year-btn),
.date-picker-card :deep(.v-date-picker-controls__only-month-btn),
.date-picker-card :deep(.v-date-picker-controls__only-year-btn),
.date-picker-card :deep(.v-date-picker-controls__mode-btn) {
  background-color: #ffffff !important;
  box-shadow: none !important;
  color: #334155 !important;
  white-space: nowrap;
}

.date-picker-card :deep(.v-date-picker-controls__month-btn .v-btn__content),
.date-picker-card :deep(.v-date-picker-controls__year-btn .v-btn__content),
.date-picker-card :deep(.v-date-picker-controls__only-month-btn .v-btn__content),
.date-picker-card :deep(.v-date-picker-controls__only-year-btn .v-btn__content),
.date-picker-card :deep(.v-date-picker-controls__mode-btn .v-btn__content) {
  line-height: 1;
}

.date-picker-card :deep(.v-date-picker-controls) {
  justify-content: space-between;
}

.date-picker-card :deep(.v-date-picker-controls__month),
.date-picker-card :deep(.v-date-picker-controls__year) {
  align-items: center;
  flex-shrink: 0;
}

.date-picker-card :deep(.v-date-picker-controls__month-btn .v-icon),
.date-picker-card :deep(.v-date-picker-controls__year-btn .v-icon),
.date-picker-card :deep(.v-date-picker-controls__only-month-btn .v-icon),
.date-picker-card :deep(.v-date-picker-controls__only-year-btn .v-icon),
.date-picker-card :deep(.v-date-picker-controls__mode-btn .v-icon) {
  color: #334155 !important;
  opacity: 1 !important;
  display: inline-flex;
}

.date-picker-card :deep(.v-date-picker-controls__month-btn .v-btn__append),
.date-picker-card :deep(.v-date-picker-controls__year-btn .v-btn__append),
.date-picker-card :deep(.v-date-picker-controls__only-month-btn .v-btn__append),
.date-picker-card :deep(.v-date-picker-controls__only-year-btn .v-btn__append),
.date-picker-card :deep(.v-date-picker-controls__mode-btn .v-btn__append) {
  display: inline-flex !important;
  align-items: center;
  margin-inline-start: 2px;
  opacity: 1 !important;
}

.date-picker-card :deep(.v-date-picker-controls__month-btn .v-btn__append > .v-icon),
.date-picker-card :deep(.v-date-picker-controls__year-btn .v-btn__append > .v-icon),
.date-picker-card :deep(.v-date-picker-controls__only-month-btn .v-btn__append > .v-icon),
.date-picker-card :deep(.v-date-picker-controls__only-year-btn .v-btn__append > .v-icon),
.date-picker-card :deep(.v-date-picker-controls__mode-btn .v-btn__append > .v-icon) {
  color: #334155 !important;
  opacity: 1 !important;
  font-size: 18px;
  width: 18px;
  height: 18px;
  line-height: 1;
  transform: none !important;
}

.date-picker-card :deep(.v-date-picker-controls .v-btn) {
  color: #334155 !important;
}

.date-picker-card :deep(.v-date-picker-controls .v-btn .v-icon) {
  color: #334155 !important;
  opacity: 1 !important;
}

.date-picker-card :deep(.v-date-picker-years),
.date-picker-card :deep(.v-date-picker-months) {
  /* Reserve gutter space on both sides so the years/months grid stays
     centered the same way whether or not the list actually scrolls,
     preventing the switch to month/year mode from resizing the picker. */
  overflow-y: auto;
  scrollbar-gutter: stable both-edges;
}

.date-picker-card :deep(.v-date-picker-month__day .v-btn) {
  background-color: transparent !important;
  box-shadow: none !important;
  color: #334155 !important;
}

.date-picker-card :deep(.v-date-picker-month__day .v-btn[aria-current='date']) {
  background-color: #ffffff !important;
  border: none !important;
  border-radius: 999px !important;
  box-shadow: none !important;
  color: #000000 !important;
  font-weight: 800 !important;
}

.date-picker-card :deep(.v-date-picker-month__day--selected:not(.v-date-picker-month__day--range-middle) .v-btn) {
  background-color: var(--color-primary) !important;
  color: var(--color-text) !important;
}

.date-picker-card :deep(.v-card-actions) {
  margin-top: 8px;
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
</style>
