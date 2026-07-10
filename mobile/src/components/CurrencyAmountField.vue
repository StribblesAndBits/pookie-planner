<template>
  <v-text-field
    :model-value="modelValue"
    :label="label"
    :density="density"
    :disabled="disabled"
    :prefix="prefix"
    type="number"
    :step="step"
    :min="min"
    class="currency-amount-field"
    @update:model-value="handleInput"
  >
    <template #append-inner>
      <div class="currency-stepper" aria-hidden="true">
        <button
          type="button"
          class="currency-stepper-button"
          :disabled="disabled"
          @click="increment"
        >
          <svg viewBox="0 0 24 24" class="currency-stepper-icon" aria-hidden="true">
            <path d="M6 14l6-6 6 6" />
          </svg>
        </button>
        <button
          type="button"
          class="currency-stepper-button"
          :disabled="disabled"
          @click="decrement"
        >
          <svg viewBox="0 0 24 24" class="currency-stepper-icon currency-stepper-icon--down" aria-hidden="true">
            <path d="M6 10l6 6 6-6" />
          </svg>
        </button>
      </div>
    </template>
  </v-text-field>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { VTextField } from 'vuetify/components';

const props = withDefaults(defineProps<{
  modelValue: number;
  label: string;
  prefix?: string;
  density?: 'default' | 'comfortable' | 'compact';
  min?: number;
  step?: number;
  disabled?: boolean;
}>(), {
  prefix: '',
  density: 'comfortable',
  min: 0,
  step: 0.01,
  disabled: false,
});

const emit = defineEmits<{
  (event: 'update:modelValue', value: number): void;
}>();

const decimalPlaces = computed(() => {
  const stepText = String(props.step ?? 0.01);
  return stepText.includes('.') ? stepText.split('.')[1].length : 0;
});

function clamp(value: number): number {
  const nextValue = Number.isFinite(value) ? value : 0;
  return Math.max(props.min ?? 0, round(nextValue));
}

function round(value: number): number {
  const factor = 10 ** decimalPlaces.value;
  return Number((Math.round(value * factor) / factor).toFixed(decimalPlaces.value));
}

function handleInput(value: string | number) {
  const parsed = typeof value === 'number' ? value : Number(value);
  if (!Number.isFinite(parsed)) return;
  emit('update:modelValue', clamp(parsed));
}

function increment() {
  emit('update:modelValue', clamp(Number(props.modelValue || 0) + (props.step ?? 0.01)));
}

function decrement() {
  emit('update:modelValue', clamp(Number(props.modelValue || 0) - (props.step ?? 0.01)));
}
</script>

<style scoped>
.currency-amount-field :deep(.v-field__append-inner) {
  align-self: center;
  margin-inline-start: 8px;
  padding-top: 0;
}

.currency-stepper {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.currency-stepper-button {
  width: 28px;
  height: 28px;
  border-radius: 999px;
  border: 1px solid #d8c2c2;
  background: var(--color-primary);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  cursor: pointer;
}

.currency-stepper-icon {
  width: 16px;
  height: 16px;
  stroke: #ffffff;
  stroke-width: 3;
  stroke-linecap: round;
  stroke-linejoin: round;
  fill: none;
  flex: 0 0 auto;
}

.currency-stepper-icon--down {
  transform: translateY(1px);
}

.currency-stepper-button:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}
</style>
