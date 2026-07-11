<template>
  <v-card class="calendar-card">
    <v-card-title class="title-row calendar-title-row">
      <span>Jules Tracker</span>
      <v-btn color="primary" size="x-small" density="comfortable" rounded="lg" class="action-btn calendar-nav-btn" @click="openCreateJulesDayDialog()">
        Add Jules Day
      </v-btn>
    </v-card-title>

    <v-card-text class="calendar-body">
      <div class="calendar-header">
        <v-btn size="x-small" density="comfortable" rounded="lg" class="action-btn calendar-nav-btn" @click="previousMonth">Prev</v-btn>
        <div class="month-controls">
          <button type="button" class="month-title month-picker-trigger" @click="openMonthYearDialog">
            <span>{{ monthLabel }}</span>
          </button>
          <v-btn
            size="x-small"
            density="comfortable"
            rounded="lg"
            class="action-btn calendar-nav-btn today-month-btn"
            title="Return to current month"
            aria-label="Return to current month"
            :disabled="isCurrentDisplayMonth"
            @click="goToCurrentMonth"
          >
            ↩
          </v-btn>
        </div>
        <v-btn size="x-small" density="comfortable" rounded="lg" class="action-btn calendar-nav-btn" @click="nextMonth">Next</v-btn>
      </div>

      <v-dialog v-model="showMonthYearDialog" max-width="380px">
        <v-card class="app-modal-card">
          <v-card-title>Select month and year</v-card-title>
          <v-card-text class="month-year-dialog-fields">
            <v-select
              v-model="pickerMonth"
              label="Month"
              density="comfortable"
              :items="monthPickerOptions"
              item-title="title"
              item-value="value"
              :menu-props="{ contentClass: 'event-select-menu' }"
            />
            <v-select
              v-model="pickerYear"
              label="Year"
              density="comfortable"
              :items="yearPickerOptions"
              item-title="title"
              item-value="value"
              :menu-props="{ contentClass: 'event-select-menu' }"
            />
          </v-card-text>
          <v-card-actions>
            <v-spacer />
            <v-btn color="primary" class="action-btn" size="x-small" density="comfortable" rounded="lg" @click="showMonthYearDialog = false">Cancel</v-btn>
            <v-btn color="primary" class="action-btn" size="x-small" density="comfortable" rounded="lg" @click="applyMonthYearSelection">Done</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <div class="weekday-row">
        <div v-for="day in weekDays" :key="day" class="weekday-cell">{{ day }}</div>
      </div>

      <div
        class="calendar-grid"
        :style="{ gridTemplateRows: `repeat(${calendarRowCount}, 74px)` }"
      >
        <div
          v-for="(cell, idx) in calendarCells"
          :key="idx"
          class="calendar-cell"
          :class="{ empty: !cell.date, today: cell.date === todayDate, 'other-month': cell.otherMonth }"
          @click="handleCellClick(cell)"
        >
          <template v-if="cell.date">
            <div class="date-row">
              <span class="date-label">{{ cell.dayNumber }}</span>
              <span v-if="getJulesMarker(cell.date)" class="jules-marker" :class="getJulesMarker(cell.date)?.class">{{ getJulesMarker(cell.date)?.label }}</span>
            </div>
          </template>
        </div>
      </div>

      <v-dialog v-model="showDayModal" max-width="560px">
        <v-card v-if="selectedDayDate" class="day-view-card app-modal-card">
          <v-card-title class="title-row day-title-row">
            <span>{{ formatDisplayDate(selectedDayDate) }}</span>
            <v-btn color="primary" size="x-small" density="comfortable" rounded="lg" class="action-btn day-view-header-btn" @click="openCreateJulesDayDialog(selectedDayDate)">
              Add Jules Day
            </v-btn>
          </v-card-title>
          <v-card-text class="day-view-content">
            <template v-if="selectedDayOccurrences.length > 0">
              <div v-for="day in selectedDayOccurrences" :key="`${day.id}-${day.occurrence_start}`" class="jules-occurrence-item" @click="openEditJulesDayDialog(day)">
                <div class="jules-occurrence-main">
                  <span class="jules-occurrence-title">{{ day.title }}</span>
                  <span class="jules-occurrence-meta">{{ formatOccurrenceRange(day) }}</span>
                </div>
                <div class="jules-occurrence-actions">
                  <span class="jules-occurrence-badge" :class="getJulesMarker(day.title)?.class">{{ getJulesMarker(day.title)?.label }}</span>
                  <v-btn class="action-btn" size="x-small" density="comfortable" rounded="lg" @click.stop="requestDeleteJulesDay(day)">Delete</v-btn>
                </div>
              </div>
            </template>
            <div v-else class="no-events">
              No Jules Days for this date.
            </div>
          </v-card-text>
          <v-card-actions class="day-view-actions">
            <v-spacer />
            <v-btn color="primary" class="action-btn" size="x-small" density="comfortable" rounded="lg" @click="showDayModal = false">Close</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
      <JulesDayDialog
        v-model="showJulesForm"
        :form="julesForm"
        :is-editing="isEditingJulesDay"
        :saving="savingJulesDay"
        :error="formError"
        :show-custom-recurrence-button="false"
        @save="saveJulesDay"
      />

      <v-dialog v-model="showDeleteScopeDialog" max-width="420px">
        <v-card class="app-modal-card">
          <v-card-title>Delete recurring Jules Day?</v-card-title>
          <v-card-text>
            <p class="helper-copy">Do you want to remove only this occurrence or this occurrence and everything after it?</p>
          </v-card-text>
          <v-card-actions>
            <v-spacer />
            <v-btn color="primary" class="action-btn" size="x-small" density="comfortable" rounded="lg" @click="showDeleteScopeDialog = false">Cancel</v-btn>
            <v-btn color="error" class="action-btn" size="x-small" density="comfortable" rounded="lg" @click="confirmDeleteJulesDay('single')">Just this day</v-btn>
            <v-btn color="error" class="action-btn" size="x-small" density="comfortable" rounded="lg" @click="confirmDeleteJulesDay('future')">This and following</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { VCard, VCardTitle, VCardText, VCardActions, VDialog, VBtn, VSpacer, VSelect } from 'vuetify/components';
import JulesDayDialog from '@/components/JulesDayDialog.vue';
import api from '@/services/api';
import {
  addDays,
  diffDays,
  formatDateString,
  formatDisplayDate,
  getOccurrenceStartDate,
  occursOnDate,
  weekDays,
} from '@/utils/recurrence';

type JulesDayItem = {
  id: number;
  title: string;
  start: string;
  end: string;
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

type JulesDayOccurrence = JulesDayItem & {
  occurrence_date: string;
  occurrence_start: string;
};

type JulesDayForm = {
  title: string;
  start: string;
  end: string;
  description: string;
  recurrence_type: 'none' | 'daily' | 'weekly' | 'biweekly' | 'annually' | 'custom';
  recurrence_interval: number;
  recurrence_unit: 'day' | 'week' | 'month' | 'year';
  recurrence_days_of_week: number[];
  recurrence_end_type: 'never' | 'on' | 'after';
  recurrence_end_date: string;
  recurrence_occurrences: number;
};

const showMonthYearDialog = ref(false);
const showDayModal = ref(false);
const showJulesForm = ref(false);
const showDeleteScopeDialog = ref(false);
const isEditingJulesDay = ref(false);
const savingJulesDay = ref(false);
const formError = ref('');
const selectedDayDate = ref<string | null>(null);
const selectedOccurrence = ref<JulesDayOccurrence | null>(null);
const julesDays = ref<JulesDayItem[]>([]);
const loadingJulesDays = ref(false);
const julesDaysError = ref('');
const julesForm = ref<JulesDayForm>(getDefaultJulesForm());
const displayMonth = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1));
const pickerMonth = ref(displayMonth.value.getMonth());
const pickerYear = ref(displayMonth.value.getFullYear());
const todayDate = formatDateString(new Date());

const monthPickerOptions = [
  { title: 'January', value: 0 },
  { title: 'February', value: 1 },
  { title: 'March', value: 2 },
  { title: 'April', value: 3 },
  { title: 'May', value: 4 },
  { title: 'June', value: 5 },
  { title: 'July', value: 6 },
  { title: 'August', value: 7 },
  { title: 'September', value: 8 },
  { title: 'October', value: 9 },
  { title: 'November', value: 10 },
  { title: 'December', value: 11 },
] as const;
const yearPickerOptions = computed(() => {
  const currentYear = new Date().getFullYear();
  const startYear = currentYear - 10;
  const endYear = currentYear + 15;
  return Array.from({ length: endYear - startYear + 1 }, (_, index) => {
    const year = startYear + index;
    return { title: String(year), value: year };
  });
});

const monthLabel = computed(() => displayMonth.value.toLocaleDateString('en-US', { month: 'long', year: 'numeric' }));
const isCurrentDisplayMonth = computed(() => {
  const now = new Date();
  return displayMonth.value.getFullYear() === now.getFullYear()
    && displayMonth.value.getMonth() === now.getMonth();
});

const calendarRowCount = computed(() => calendarCells.value.length / 7);

const calendarCells = computed(() => {
  const year = displayMonth.value.getFullYear();
  const month = displayMonth.value.getMonth();
  const firstDay = new Date(year, month, 1);
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const leadingEmpty = firstDay.getDay();

  const cells: Array<{ date: string | null; dayNumber: number | null; otherMonth?: boolean }> = [];
  const prevMonthDays = new Date(year, month, 0).getDate();
  for (let index = 0; index < leadingEmpty; index += 1) {
    const day = prevMonthDays - leadingEmpty + index + 1;
    const date = new Date(year, month - 1, day);
    cells.push({ date: formatDateString(date), dayNumber: day, otherMonth: true });
  }

  for (let day = 1; day <= daysInMonth; day += 1) {
    const date = new Date(year, month, day);
    cells.push({ date: formatDateString(date), dayNumber: day });
  }

  const totalCells = cells.length > 35 ? 42 : 35;
  const trailingEmpty = totalCells - cells.length;
  for (let day = 1; day <= trailingEmpty; day += 1) {
    const date = new Date(year, month + 1, day);
    cells.push({ date: formatDateString(date), dayNumber: day, otherMonth: true });
  }

  return cells;
});

const selectedDayOccurrences = computed<JulesDayOccurrence[]>(() => {
  if (!selectedDayDate.value) return [];
  return julesDays.value
    .filter((day) => occursOnDate(day, selectedDayDate.value!))
    .map((day) => ({
      ...day,
      occurrence_date: selectedDayDate.value!,
      occurrence_start: getOccurrenceStartDate(day, selectedDayDate.value!) || day.start,
    }))
    .sort((a, b) => a.occurrence_start.localeCompare(b.occurrence_start) || a.title.localeCompare(b.title));
});

function getDefaultJulesForm(date?: string): JulesDayForm {
  const initialDate = date || formatDateString(new Date());
  return {
    title: 'Jules Day',
    start: initialDate,
    end: initialDate,
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

function formatOccurrenceRange(day: JulesDayOccurrence): string {
  const duration = Math.max(1, diffDays(day.start, day.end) + 1);
  const start = day.occurrence_start;
  const end = addDays(start, duration - 1);
  return start === end ? formatDisplayDate(start) : `${formatDisplayDate(start)} - ${formatDisplayDate(end)}`;
}

function friendlyJulesLoadError(error: any) {
  const message = String(error?.response?.data?.message || '');
  if (message.includes('SQLSTATE[42S02]') || message.includes('doesn\'t exist')) {
    return 'Jules Days are not set up yet. Run the database migration.';
  }

  return message || 'Unable to load Jules Days.';
}

function getJulesMarker(titleOrDate: string) {
  if (titleOrDate === 'No Jules Day') {
    return { label: 'J', class: 'jules-marker--no-jules' };
  }
  if (titleOrDate === 'Jules Day') {
    return { label: 'J', class: 'jules-marker--jules' };
  }

  const day = julesDays.value.find((item) => occursOnDate(item, titleOrDate));
  if (!day) {
    return null;
  }

  return day.title === 'No Jules Day'
    ? { label: 'J', class: 'jules-marker--no-jules' }
    : { label: 'J', class: 'jules-marker--jules' };
}

function openMonthYearDialog() {
  pickerMonth.value = displayMonth.value.getMonth();
  pickerYear.value = displayMonth.value.getFullYear();
  showMonthYearDialog.value = true;
}

function applyMonthYearSelection() {
  displayMonth.value = new Date(pickerYear.value, pickerMonth.value, 1);
  showMonthYearDialog.value = false;
}

function previousMonth() {
  displayMonth.value = new Date(displayMonth.value.getFullYear(), displayMonth.value.getMonth() - 1, 1);
}

function nextMonth() {
  displayMonth.value = new Date(displayMonth.value.getFullYear(), displayMonth.value.getMonth() + 1, 1);
}

function goToCurrentMonth() {
  const now = new Date();
  displayMonth.value = new Date(now.getFullYear(), now.getMonth(), 1);
}

function handleCellClick(cell: { date: string | null; otherMonth?: boolean }) {
  if (!cell.date) return;
  if (cell.otherMonth) {
    const eventDate = new Date(`${cell.date}T00:00:00`);
    displayMonth.value = new Date(eventDate.getFullYear(), eventDate.getMonth(), 1);
    return;
  }

  selectedDayDate.value = cell.date;
  showDayModal.value = true;
}

function openCreateJulesDayDialog(date?: string) {
  isEditingJulesDay.value = false;
  selectedOccurrence.value = null;
  formError.value = '';
  julesForm.value = getDefaultJulesForm(date || selectedDayDate.value || undefined);
  showJulesForm.value = true;
}

function openEditJulesDayDialog(day: JulesDayOccurrence) {
  isEditingJulesDay.value = true;
  selectedOccurrence.value = day;
  formError.value = '';
  julesForm.value = {
    title: day.title,
    start: day.start,
    end: day.end,
    description: day.description || '',
    recurrence_type: day.recurrence_type || 'none',
    recurrence_interval: day.recurrence_interval || 1,
    recurrence_unit: day.recurrence_unit || 'week',
    recurrence_days_of_week: day.recurrence_days_of_week || [new Date(`${day.start}T00:00:00`).getDay()],
    recurrence_end_type: day.recurrence_end_type || 'never',
    recurrence_end_date: day.recurrence_end_date || '',
    recurrence_occurrences: day.recurrence_occurrences || 13,
  };
  showJulesForm.value = true;
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

async function saveJulesDay() {
  formError.value = '';
  savingJulesDay.value = true;
  try {
    const payload = {
      ...julesForm.value,
      recurrence_end_date: julesForm.value.recurrence_end_type === 'on' ? julesForm.value.recurrence_end_date : null,
      recurrence_occurrences: julesForm.value.recurrence_end_type === 'after' ? julesForm.value.recurrence_occurrences : null,
      recurrence_days_of_week: julesForm.value.recurrence_unit === 'week' ? julesForm.value.recurrence_days_of_week : [],
    };

    if (payload.recurrence_type !== 'custom') {
      payload.recurrence_end_date = null;
      payload.recurrence_occurrences = null;
    }

    if (isEditingJulesDay.value && selectedOccurrence.value) {
      await api.put(`/jules-days/${selectedOccurrence.value.id}`, payload);
    } else {
      await api.post('/jules-days', payload);
    }
    showJulesForm.value = false;
    await loadJulesDays();
  } catch (error: any) {
    const fieldErrors = error?.response?.data?.errors;
    const firstField = fieldErrors ? Object.keys(fieldErrors)[0] : null;
    formError.value = firstField ? fieldErrors[firstField]?.[0] : (error?.response?.data?.message || 'Unable to save Jules Day.');
  } finally {
    savingJulesDay.value = false;
  }
}

function requestDeleteJulesDay(day: JulesDayOccurrence) {
  selectedOccurrence.value = day;
  if (day.recurrence_type && day.recurrence_type !== 'none') {
    showDeleteScopeDialog.value = true;
    return;
  }

  showDeleteScopeDialog.value = false;
  void confirmDeleteJulesDay('series');
}

async function confirmDeleteJulesDay(scope: 'single' | 'future' | 'series') {
  if (!selectedOccurrence.value) return;

  if (scope === 'series') {
    if (!window.confirm('Delete this Jules Day?')) return;
  }

  try {
    await api.delete(`/jules-days/${selectedOccurrence.value.id}`, {
      data: scope === 'series'
        ? {}
        : {
            scope,
            occurrence_start: selectedOccurrence.value.occurrence_start,
          },
    });
    showDeleteScopeDialog.value = false;
    showDayModal.value = false;
    selectedOccurrence.value = null;
    await loadJulesDays();
  } catch (error: any) {
    formError.value = error?.response?.data?.message || 'Unable to delete Jules Day.';
  }
}

onMounted(async () => {
  await loadJulesDays();
});

defineExpose({ refresh: loadJulesDays });
</script>

<style scoped>
.calendar-card {
  height: auto;
  display: flex;
  flex-direction: column;
  border-radius: 18px;
}

.calendar-header {
   display: flex;
   align-items: center;
   justify-content: space-between;
   margin-bottom: 10px;
}

.month-title {
  font-weight: 700;
}

.month-picker-trigger {
  display: inline-flex;
  align-items: center;
  border: 0;
  padding: 0;
  background: transparent;
  color: inherit;
  font: inherit;
  cursor: pointer;
}

.month-picker-trigger:focus-visible {
  outline: 2px solid #93c5fd;
  outline-offset: 2px;
  border-radius: 6px;
}

.month-controls {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.calendar-title-row {
  padding: 10px 10px 8px;
}

.action-btn {
  text-transform: none;
  border-radius: 12px !important;
  padding-inline: 10px;
  min-height: 30px;
  background-color: var(--app-button-bg) !important;
  color: var(--app-button-text) !important;
}

.action-btn :global(.v-btn__content) {
  font-size: 13px;
  letter-spacing: 0;
}

.action-btn :global(.v-btn__overlay),
.action-btn :global(.v-btn__underlay) {
  border-radius: 12px;
}

.calendar-nav-btn {
  min-width: 48px;
}

.today-month-btn {
  min-width: 32px;
}

.calendar-body {
  display: flex;
  flex-direction: column;
  flex: none;
  min-height: 0;
  padding: 8px 10px 10px;
  gap: 2px;
}

.month-year-dialog-fields {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.weekday-row {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 8px;
  margin-bottom: 8px;
}

.weekday-cell {
  text-align: center;
  font-size: 12px;
  font-weight: 600;
  color: #6b7280;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 8px;
  flex: none;
  min-height: 0;
}

.calendar-cell {
  min-height: 0;
  border: 1px solid #dbe4f0;
  border-radius: 10px;
  padding: 8px;
  background: #f8fbff;
  overflow: hidden;
  cursor: pointer;
}

.calendar-grid .calendar-cell:nth-last-child(7) {
  border-bottom-left-radius: 18px;
}

.calendar-grid .calendar-cell:last-child {
  border-bottom-right-radius: 18px;
}

.calendar-cell.empty {
  background: #eef3fa;
}

.calendar-cell.today {
  border-color: #60a5fa;
  box-shadow: inset 0 0 0 2px #93c5fd;
  outline: 2px solid rgba(147, 197, 253, 0.9);
  outline-offset: -2px;
}

.calendar-cell.other-month {
  background: #f5f5f5;
  opacity: 0.6;
}

.date-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}

.date-label {
  font-size: 12px;
  font-weight: 700;
  margin-bottom: 6px;
  color: #0f172a;
  line-height: 1;
}

.jules-marker {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 16px;
  height: 16px;
  margin-left: auto;
  border-radius: 999px;
  background: #16a34a;
  color: #ffffff;
  font-weight: 900;
  font-size: 10px;
  line-height: 1;
}

.jules-marker--no-jules {
  background: #dc2626;
}

.day-title-row {
  padding: 10px 10px 8px;
}

.day-view-content {
  display: grid;
  gap: 10px;
}

.jules-occurrence-item {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: center;
  border: 1px solid #dbe4f0;
  border-radius: 12px;
  padding: 10px 12px;
  background: #f8fbff;
}

.jules-occurrence-main {
  display: grid;
  gap: 2px;
}

.jules-occurrence-title {
  font-weight: 700;
  color: #0f172a;
}

.jules-occurrence-meta {
  color: #64748b;
  font-size: 12px;
}

.jules-occurrence-actions {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.jules-occurrence-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 999px;
  background: #16a34a;
  color: #ffffff;
  font-weight: 900;
  font-size: 12px;
}

.jules-occurrence-badge.jules-marker--no-jules {
  background: #dc2626;
}

.day-view-actions {
  padding-top: 0;
}

.helper-copy {
  margin: 0;
  color: #64748b;
}

.no-events {
  border: 1px dashed #cbd5e1;
  border-radius: 10px;
  padding: 16px;
  background: #f8fbff;
  color: #64748b;
}

.custom-recurrence-card {
  border-radius: 20px;
  background-color: var(--app-modal-bg) !important;
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

.form-error {
  margin: 8px 0 0;
  color: #b91c1c;
  font-size: 13px;
}

@media (max-width: 768px) {
  .calendar-grid {
    gap: 4px;
    grid-template-rows: repeat(6, 78px);
  }

  .calendar-cell {
    min-height: 64px;
    padding: 8px;
  }
}
</style>
