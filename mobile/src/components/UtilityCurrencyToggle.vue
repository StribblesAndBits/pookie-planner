<template>
  <div class="currency-toggle" role="group" aria-label="Utility currency">
    <button
      type="button"
      class="currency-toggle-button"
      :class="{ active: modelValue === 'dollars' }"
      :disabled="disabled"
      @click="emit('update:modelValue', 'dollars')"
    >
      <v-icon icon="mdi-currency-usd" class="currency-toggle-icon" />
      <span>Dollars</span>
    </button>
    <button
      type="button"
      class="currency-toggle-button"
      :class="{ active: modelValue === 'kisses' }"
      :disabled="disabled"
      @click="emit('update:modelValue', 'kisses')"
    >
      <v-icon icon="mdi-emoticon-kiss" class="currency-toggle-icon" />
      <span>Kisses</span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { VIcon } from 'vuetify/components';
import type { UtilityCurrency } from '@/utils/utility';

defineProps<{
  modelValue: UtilityCurrency;
  disabled?: boolean;
}>();

const emit = defineEmits<{
  (event: 'update:modelValue', value: UtilityCurrency): void;
}>();
</script>

<style scoped>
.currency-toggle {
  display: inline-flex;
  gap: 8px;
  flex-wrap: wrap;
}

.currency-toggle-button {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  border-radius: 999px;
  border: 1px solid #dbe4f0;
  background: #ffffff;
  color: #334155;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.15s ease;
}

.currency-toggle-button.active {
  background: #dbeafe;
  border-color: #93c5fd;
  color: #1e3a8a;
}

.currency-toggle-button:disabled {
  cursor: not-allowed;
  opacity: 0.55;
}

.currency-toggle-icon {
  font-size: 18px;
  line-height: 1;
}
</style>
