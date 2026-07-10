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

  <v-dialog v-model="showPicker" max-width="360px">
    <v-card class="date-picker-card app-modal-card">
      <v-card-title>{{ label || 'Select date' }}</v-card-title>
      <v-card-text class="date-picker-body">
        <v-date-picker v-model="draftDate" :min="min" :max="max" hide-header />
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
  return new Date().toISOString().slice(0, 10);
}

function normalizeDateValue(value: string | Date | null | undefined): string {
  if (!value) return '';
  if (typeof value === 'string') return value.slice(0, 10);
  return value.toISOString().slice(0, 10);
}

function formatDisplayDate(value: string): string {
  if (!value) return '';
  return new Date(`${value}T00:00:00`).toLocaleDateString('en-US', {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  });
}

function openPicker() {
  if (props.disabled) return;
  draftDate.value = props.modelValue || getTodayDateString();
  showPicker.value = true;
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
  padding-top: 4px;
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
