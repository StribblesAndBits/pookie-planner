<template>
  <v-card class="calendar-card">
      <v-card-title class="title-row calendar-title-row">
      <span>Events Calendar</span>
        <v-btn color="primary" size="x-small" density="comfortable" rounded="lg" class="action-btn calendar-nav-btn" @click="openCreateEventDialog()">New Event</v-btn>
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
        :style="{ gridTemplateRows: `repeat(${calendarRowCount}, var(--calendar-row-height))` }"
      >
        <div
          v-for="(cell, idx) in calendarCells"
          :key="idx"
          class="calendar-cell"
          :class="{
            empty: !cell.date,
            today: cell.date === todayDate,
            'other-month': cell.otherMonth
          }"
          @click="handleCellClick(cell)"
        >
          <template v-if="cell.date">
            <div class="date-row">
              <span class="date-label">{{ cell.dayNumber }}</span>
              <div v-if="getJulesMarkers(cell.date).length > 0" class="jules-markers">
                <span
                  v-for="(marker, markerIndex) in getJulesMarkers(cell.date)"
                  :key="`${cell.date}-marker-${markerIndex}`"
                  class="jules-marker"
                  :class="marker.className"
                >
                  {{ marker.label }}
                </span>
              </div>
            </div>

            <div class="calendar-day-content">
              <div
                v-for="entry in getVisibleCalendarEntries(cell.date)"
                :key="entry.key"
                :class="entry.kind === 'event' ? 'event-item' : 'calendar-utility-item'"
                :style="entry.kind === 'event'
                  ? getEventStyle(entry.event)
                  : getUtilityCalendarEntryStyle(entry)"
              >
                <template v-if="entry.kind === 'event'">
                  {{ entry.event.title }}
                </template>
                <template v-else>
                  <div class="calendar-utility-row">
                    <span class="calendar-utility-title">{{ entry.title }}</span>
                    <span class="calendar-utility-note">{{ entry.note }}</span>
                  </div>
                </template>
              </div>

              <div v-if="getHiddenCalendarEntryCount(cell.date) > 0" class="more-events">
                +{{ getHiddenCalendarEntryCount(cell.date) }} more
              </div>
            </div>
          </template>
        </div>
      </div>
      <div class="calendar-legend">
        <span class="legend-item"><span class="legend-dot jules-marker--here">J</span> Jules Here</span>
        <span class="legend-item"><span class="legend-dot jules-marker--arriving">J</span> Jules Arriving</span>
        <span class="legend-item"><span class="legend-dot jules-marker--leaving">J</span> Jules Leaving</span>
        <span class="legend-item"><span class="legend-dot jules-marker--gone">J</span> Jules Gone</span>
      </div>

      <!-- Day View Dialog -->
      <v-dialog v-model="showDayView" max-width="500px">
        <v-card v-if="selectedDayDate" class="day-view-card app-modal-card">
          <v-card-title class="title-row day-title-row">
            <span>{{ formatDisplayDate(selectedDayDate) }}</span>
            <v-btn color="primary" size="x-small" density="comfortable" rounded="lg" class="action-btn day-view-header-btn" @click="openCreateEventDialog(selectedDayDate)">New Event</v-btn>
          </v-card-title>
          <v-card-text class="day-view-content">
            <div class="day-timeline-wrapper">
              <div class="timeline-hour-labels">
                <div v-for="slot in halfHourMarks" :key="`slot-label-${slot}`" class="timeline-hour-label">
                  {{ slot % 2 === 0 ? formatHourLabel(slot / 2) : '' }}
                </div>
              </div>
              <div class="timeline-grid">
                <div
                  v-for="slot in halfHourMarks"
                  :key="`line-${slot}`"
                  class="timeline-hour-line"
                  :class="{ 'half-hour-line': slot % 2 === 1 }"
                  :style="{ top: `${(slot / 48) * 100}%` }"
                />
                <div
                  v-for="entry in dayViewLaneEntries"
                  :key="entry.key"
                  class="timeline-event timeline-event--clickable"
                  :style="[getTimelineEntryStyle(entry), getTimelineColumnStyle(entry)]"
                  @click="openDayViewEntry(entry)"
                >
                  <div class="timeline-event-title">{{ entry.title }}</div>
                  <div v-if="entry.note" class="timeline-event-note">{{ entry.note }}</div>
                </div>
              </div>
            </div>
            <div v-if="dayViewJulesEntries.length > 0" class="day-jules-list">
              <div
                v-for="entry in dayViewJulesEntries"
                :key="entry.key"
                class="timeline-event timeline-event--inline timeline-event--jules"
                :class="`timeline-event--${entry.variant}`"
                :style="getJulesEntryStyle(entry)"
              >
                <div class="timeline-event-title">{{ entry.title }}</div>
                <div class="timeline-event-note">{{ entry.note }}</div>
              </div>
            </div>
            <div v-if="dayViewUtilityEntries.length > 0" class="day-jules-list">
              <div
                v-for="entry in dayViewUtilityEntries"
                :key="entry.key"
                class="timeline-event timeline-event--inline timeline-event--utility"
                :class="`timeline-event--utility-${entry.currency}`"
                :style="getUtilityEntryStyle(entry)"
              >
                <div class="timeline-event-title">{{ entry.title }}</div>
                <div class="timeline-event-note">{{ entry.note }}</div>
              </div>
            </div>
            <div v-else-if="dayViewLaneEntries.length === 0 && dayViewJulesEntries.length === 0" class="no-events">
              No events, Jules days, or bill reminders for this date.
            </div>
          </v-card-text>
          <v-card-actions class="day-view-actions">
            <v-spacer />
            <v-btn color="primary" class="action-btn" size="x-small" density="comfortable" rounded="lg" @click="showDayView = false">Close</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Event Detail Dialog -->
      <v-dialog v-model="showModal" max-width="500px">
        <v-card v-if="selectedEvent" class="event-popover-card app-modal-card">
          <v-card-title>{{ selectedEvent.title }}</v-card-title>
          <v-card-text>
            <div class="event-detail-panel">
              <p><strong>Date:</strong> {{ formatDisplayDate(selectedEvent.occurrence_date || selectedEvent.start) }}</p>
              <p><strong>Time:</strong> {{ formatEventTimeRange(selectedEvent) }}</p>
              <p v-if="selectedEvent.recurrence_type && selectedEvent.recurrence_type !== 'none'">
                <strong>Repeats:</strong> {{ recurrenceSummaryForEvent(selectedEvent) }}
              </p>
              <p v-if="selectedEvent.description"><strong>Description:</strong> {{ selectedEvent.description }}</p>
              <div class="event-color-preview" :style="{ backgroundColor: getColorValue(getEventColor(selectedEvent)) }">
                <span class="event-color-preview-name">{{ eventCreatorLabel }}</span>
              </div>
            </div>
          </v-card-text>
          <v-card-actions>
            <v-btn v-if="canManageSelectedEvent" class="action-btn" size="x-small" density="comfortable" rounded="lg" @click="openEditEventDialog">Edit</v-btn>
            <v-btn v-if="canManageSelectedEvent" class="action-btn" size="x-small" density="comfortable" rounded="lg" @click="deleteSelectedEvent">Delete</v-btn>
            <v-spacer />
            <v-btn color="primary" class="action-btn" size="x-small" density="comfortable" rounded="lg" @click="closeEventPopover">Close</v-btn>
          </v-card-actions>
        </v-card>
       </v-dialog>

      <v-dialog v-model="showEventForm" max-width="500px">
        <v-card class="app-modal-card">
          <v-card-title>{{ isEditingEvent ? 'Edit Event' : 'New Event' }}</v-card-title>
          <v-card-text>
            <v-text-field v-model="eventForm.title" label="Title" density="comfortable" />
            <DatePickerField v-model="eventForm.start" label="Start Date" density="comfortable" class="date-field" />
            <DatePickerField v-model="eventForm.end" label="End Date" density="comfortable" class="date-field" />
            <div class="time-controls-row" :class="{ 'all-day': eventForm.all_day }">
              <label class="all-day-checkbox">
                <input v-model="eventForm.all_day" type="checkbox" class="all-day-checkbox-input" />
                <span class="all-day-checkbox-label">All day</span>
              </label>
              <div class="time-row">
                <v-text-field v-model="eventForm.start_time" label="Start Time" type="time" density="compact" hide-details class="time-field" :disabled="eventForm.all_day" />
                <v-text-field v-model="eventForm.end_time" label="End Time" type="time" density="compact" hide-details class="time-field" :disabled="eventForm.all_day" />
              </div>
            </div>
            <v-select
              v-model="eventForm.recurrence_type"
              label="Repeat"
              density="comfortable"
              :items="recurrenceOptions"
              item-title="title"
              item-value="value"
              :menu-props="{ contentClass: 'event-select-menu' }"
              @update:model-value="handleRecurrenceTypeChange"
            />
            <div v-if="eventForm.recurrence_type === 'custom'" class="custom-recurrence-inline">
              <p class="custom-recurrence-summary">{{ customRecurrenceSummary }}</p>
            </div>
            <v-textarea v-model="eventForm.description" label="Description" rows="3" density="comfortable" />
            <p v-if="formError" class="form-error">{{ formError }}</p>
          </v-card-text>
           <v-card-actions>
             <v-spacer />
             <v-btn color="primary" class="action-btn" size="x-small" density="comfortable" rounded="lg" @click="showEventForm = false">Cancel</v-btn>
             <v-btn color="primary" class="action-btn" size="x-small" density="comfortable" rounded="lg" :loading="savingEvent" @click="saveEvent">Save</v-btn>
           </v-card-actions>
        </v-card>
      </v-dialog>

      <v-dialog v-model="showDeleteScopeDialog" max-width="420px">
        <v-card class="app-modal-card">
           <v-card-title>Delete recurring event?</v-card-title>
           <v-card-text>
             <p class="helper-copy">Do you want to remove only this occurrence or this occurrence and everything after it?</p>
           </v-card-text>
           <v-card-actions>
             <v-spacer />
             <v-btn color="primary" class="action-btn" size="x-small" density="comfortable" rounded="lg" @click="showDeleteScopeDialog = false">Cancel</v-btn>
             <v-btn color="error" class="action-btn" size="x-small" density="comfortable" rounded="lg" @click="confirmDeleteEvent('single')">Just this day</v-btn>
             <v-btn color="error" class="action-btn" size="x-small" density="comfortable" rounded="lg" @click="confirmDeleteEvent('future')">This and following</v-btn>
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
                v-model.number="eventForm.recurrence_interval"
                type="number"
                min="1"
                max="999"
                density="comfortable"
                hide-details
                class="custom-interval-field number-spinner-field"
              />
              <v-select
                v-model="eventForm.recurrence_unit"
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

            <div v-if="eventForm.recurrence_unit === 'week'" class="repeat-on-section">
              <div class="custom-label">Repeat on</div>
              <div class="weekday-picker">
                <button
                  v-for="day in weekdayOptions"
                  :key="day.value"
                  type="button"
                  class="weekday-circle"
                  :class="{ active: eventForm.recurrence_days_of_week.includes(day.value) }"
                  @click="toggleCustomWeekday(day.value)"
                >
                  {{ day.label }}
                </button>
              </div>
            </div>

            <div class="ends-section">
              <div class="custom-label">Ends</div>
              <v-radio-group v-model="eventForm.recurrence_end_type" density="comfortable" hide-details>
                <v-radio label="Never" value="never" />
                <v-radio label="On" value="on" />
                <DatePickerField
                  v-if="eventForm.recurrence_end_type === 'on'"
                  v-model="eventForm.recurrence_end_date"
                  label="End date"
                  density="comfortable"
                  class="custom-end-field"
                />
                <v-radio label="After" value="after" />
                <v-text-field
                  v-if="eventForm.recurrence_end_type === 'after'"
                  v-model.number="eventForm.recurrence_occurrences"
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
            <v-btn color="primary" class="action-btn" size="x-small" density="comfortable" rounded="lg" @click="showCustomRecurrenceDialog = false">Cancel</v-btn>
            <v-btn color="primary" class="action-btn" size="x-small" density="comfortable" rounded="lg" @click="showCustomRecurrenceDialog = false">Done</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
     </v-card-text>
   </v-card>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import {
  VCard, VCardTitle, VCardText, VCardActions, VDialog, VBtn, VSpacer, VTextField, VTextarea,
  VSelect, VRadioGroup, VRadio,
} from 'vuetify/components';
import { useAuth } from '@/composables/useAuth';
import api from '@/services/api';
import DatePickerField from '@/components/DatePickerField.vue';
import { buildJulesMarker, JULES_TYPE_ARRIVING, JULES_TYPE_LEAVING, JULES_TYPE_HERE, JULES_TYPE_GONE } from '@/utils/jules';
import { formatUtilityAmount, normalizeUtilityCurrency } from '@/utils/utility';
import { getOccurrenceStartDate, occursOnDate } from '@/utils/recurrence';

type CalendarEvent = {
  id: number;
  user_id: number;
  user?: {
    id: number;
    first_name: string;
    color_preference?: string | null;
  } | null;
  title: string;
  start: string;
  end: string;
  start_time: string;
  end_time: string;
  description?: string | null;
  all_day?: boolean;
  recurrence_type?: 'none' | 'daily' | 'weekly' | 'biweekly' | 'annually' | 'custom';
  recurrence_interval?: number | null;
  recurrence_unit?: 'day' | 'week' | 'month' | 'year' | null;
  recurrence_days_of_week?: number[] | null;
  recurrence_end_type?: 'never' | 'on' | 'after' | null;
  recurrence_end_date?: string | null;
  recurrence_occurrences?: number | null;
  excluded_occurrences?: string[] | null;
  occurrence_date?: string;
};

type EventForm = {
  title: string;
  start: string;
  end: string;
  start_time: string;
  end_time: string;
  description: string;
  all_day: boolean;
  recurrence_type: 'none' | 'daily' | 'weekly' | 'biweekly' | 'annually' | 'custom';
  recurrence_interval: number;
  recurrence_unit: 'day' | 'week' | 'month' | 'year';
  recurrence_days_of_week: number[];
  recurrence_end_type: 'never' | 'on' | 'after';
  recurrence_end_date: string;
  recurrence_occurrences: number;
};

type CalendarCell = {
  date: string | null;
  dayNumber: number | null;
  otherMonth?: boolean;
};

type JulesDayRecord = {
  id: number;
  type: 'arriving' | 'leaving' | 'here' | 'gone';
  start: string;
  end: string;
  coming_time?: string | null;
  leaving_time?: string | null;
  recurrence_type?: 'none' | 'daily' | 'weekly' | 'biweekly' | 'annually' | 'custom';
  recurrence_interval?: number | null;
  recurrence_unit?: 'day' | 'week' | 'month' | 'year' | null;
  recurrence_days_of_week?: number[] | null;
  recurrence_end_type?: 'never' | 'on' | 'after' | null;
  recurrence_end_date?: string | null;
  recurrence_occurrences?: number | null;
  excluded_occurrences?: string[] | null;
};

type UtilityRecord = {
  id: number;
  name: string;
  due_date: string;
  amount: string;
  utility_currency?: 'dollars' | 'kisses' | null;
  status: 'unpaid' | 'paid';
};

type DayViewEventEntry = {
  key: string;
  title: string;
  note: string;
  startMinutes: number;
  endMinutes: number;
  color: string;
  event?: CalendarEvent;
};

type DayViewEventLaneEntry = DayViewEventEntry & {
  lane: number;
  laneCount: number;
};

type DayViewJulesEntry = {
  key: string;
  title: string;
  note: string;
  variant: 'arriving' | 'normal' | 'leaving';
  color: string;
};

type DayViewUtilityEntry = {
  key: string;
  title: string;
  note: string;
  currency: 'dollars' | 'kisses';
};

type CalendarUtilityEntry = {
  id: number;
  name: string;
  amountLabel: string;
  currency: 'dollars' | 'kisses';
};

type CalendarMonthEntry =
  | {
      key: string;
      kind: 'event';
      event: CalendarEvent;
    }
  | {
      key: string;
      kind: 'utility';
      title: string;
      note: string;
      currency: 'dollars' | 'kisses';
    };

const props = withDefaults(defineProps<{
  julesDays?: JulesDayRecord[];
  utilities?: UtilityRecord[];
}>(), {
  julesDays: () => [],
  utilities: () => [],
});

const { user } = useAuth();

function formatDateString(date: Date): string {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function formatDisplayDate(dateStr: string): string {
  if (!dateStr) return '';

  const date = dateStr.includes('T') || dateStr.includes(' ')
    ? new Date(dateStr)
    : new Date(`${dateStr}T00:00:00`);

  if (Number.isNaN(date.getTime())) return dateStr;

  return date.toLocaleDateString('en-US', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
}

function parseDateOnly(dateStr: string): Date {
  const [year, month, day] = dateStr.split('-').map((value) => Number(value));
  return new Date(Date.UTC(year, month - 1, day));
}

function formatDateOnly(date: Date): string {
  const year = date.getUTCFullYear();
  const month = String(date.getUTCMonth() + 1).padStart(2, '0');
  const day = String(date.getUTCDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function addDays(dateStr: string, amount: number): string {
  const date = parseDateOnly(dateStr);
  date.setUTCDate(date.getUTCDate() + amount);
  return formatDateOnly(date);
}

function addMonths(dateStr: string, amount: number): string {
  const date = parseDateOnly(dateStr);
  const day = date.getUTCDate();
  date.setUTCDate(1);
  date.setUTCMonth(date.getUTCMonth() + amount);
  const daysInMonth = new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth() + 1, 0)).getUTCDate();
  date.setUTCDate(Math.min(day, daysInMonth));
  return formatDateOnly(date);
}

function addYears(dateStr: string, amount: number): string {
  const date = parseDateOnly(dateStr);
  date.setUTCFullYear(date.getUTCFullYear() + amount);
  return formatDateOnly(date);
}

function diffDays(startDate: string, endDate: string): number {
  const start = parseDateOnly(startDate).getTime();
  const end = parseDateOnly(endDate).getTime();
  return Math.floor((end - start) / 86400000);
}

function getColorValue(color: string): string {
  // Color is already a hex value or a color name
  if (color.startsWith('#')) {
    return color;
  }
  // Legacy support for old color names
  const colors: Record<string, string> = {
    blue: '#2196f3',
    green: '#4caf50',
    pink: '#e91e63',
    yellow: '#ffc107',
    primary: '#1976d2',
  };
  return colors[color] || '#1976d2';
}

function getEventColor(event: CalendarEvent): string {
  return event.user?.color_preference || user.value?.color_preference || '#D6486B';
}

function getEventStyle(event: CalendarEvent) {
  const colorValue = getEventColor(event);
  const bg = getColorValue(colorValue);
  const textColor = '#ffffff';
  return {
    backgroundColor: bg,
    color: textColor,
    borderLeftColor: bg,
  };
}

function getDefaultEventForm(date?: string): EventForm {
  const initialDate = date || formatDateString(new Date());
  const weekday = parseDateOnly(initialDate).getUTCDay();
  return {
    title: '',
    start: initialDate,
    end: initialDate,
    start_time: '09:00',
    end_time: '10:00',
    description: '',
    all_day: false,
    recurrence_type: 'none',
    recurrence_interval: 1,
    recurrence_unit: 'week',
    recurrence_days_of_week: [weekday],
    recurrence_end_type: 'never',
    recurrence_end_date: '',
    recurrence_occurrences: 13,
  };
}

const showModal = ref(false);
const selectedEvent = ref<CalendarEvent | null>(null);
const showDayView = ref(false);
const selectedDayDate = ref<string | null>(null);
const showEventForm = ref(false);
const showCustomRecurrenceDialog = ref(false);
const showMonthYearDialog = ref(false);
const showDeleteScopeDialog = ref(false);
const isEditingEvent = ref(false);
const savingEvent = ref(false);
const formError = ref('');
const eventForm = ref<EventForm>(getDefaultEventForm());
const displayMonth = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1));
const pickerMonth = ref(displayMonth.value.getMonth());
const pickerYear = ref(displayMonth.value.getFullYear());
const todayDate = formatDateString(new Date());
const deleteScopeTarget = ref<CalendarEvent | null>(null);

const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const events = ref<CalendarEvent[]>([]);
const weekdayOptions = weekDays.map((label, value) => ({ label: label.slice(0, 1), value }));
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
const eventCreatorLabel = computed(() => {
  if (!selectedEvent.value) return 'User';
  if (selectedEvent.value.user?.first_name) return selectedEvent.value.user.first_name;
  return selectedEvent.value.user_id === user.value?.id ? 'You' : 'User';
});
const isCurrentDisplayMonth = computed(() => {
  const now = new Date();
  return displayMonth.value.getFullYear() === now.getFullYear()
    && displayMonth.value.getMonth() === now.getMonth();
});
const canManageSelectedEvent = computed(() => !!selectedEvent.value && selectedEvent.value.user_id === user.value?.id);
const customRecurrenceSummary = computed(() => recurrenceSummaryFromForm(eventForm.value));
const dayViewEvents = computed(() => {
  if (!selectedDayDate.value) return [] as CalendarEvent[];
  return [...getEventsForDate(selectedDayDate.value)].sort((a, b) => {
    const byStart = parseTimeToMinutes(a.start_time) - parseTimeToMinutes(b.start_time);
    if (byStart !== 0) return byStart;
    return parseTimeToMinutes(a.end_time) - parseTimeToMinutes(b.end_time);
  });
});

const halfHourMarks = computed(() => Array.from({ length: 48 }, (_, index) => index));

const dayViewEventEntries = computed<DayViewEventEntry[]>(() => {
  const selectedDate = selectedDayDate.value;
  if (!selectedDate) return [];

  return dayViewEvents.value.map((event) => ({
    key: `event-${event.id}-${event.occurrence_date || selectedDate}`,
    title: event.title,
    note: formatEventTimeRange(event),
    startMinutes: event.all_day ? 0 : parseTimeToMinutes(event.start_time),
    endMinutes: event.all_day ? 1440 : Math.max(parseTimeToMinutes(event.end_time), parseTimeToMinutes(event.start_time) + 15),
    color: getColorValue(getEventColor(event)),
    event,
  })).sort((a, b) => a.startMinutes - b.startMinutes || a.endMinutes - b.endMinutes || a.title.localeCompare(b.title));
});

const dayViewLaneEntries = computed<DayViewEventLaneEntry[]>(() => {
  const activeLaneEndTimes: number[] = [];

  return dayViewEventEntries.value.map((entry) => {
    const endMinutes = Math.max(entry.endMinutes, entry.startMinutes + 15);
    let lane = activeLaneEndTimes.findIndex((laneEnd) => laneEnd <= entry.startMinutes);
    if (lane === -1) {
      lane = activeLaneEndTimes.length;
      activeLaneEndTimes.push(endMinutes);
    } else {
      activeLaneEndTimes[lane] = endMinutes;
    }

    return { ...entry, lane, laneCount: 1 };
  });
});

const dayViewMaxLaneCount = computed(() => {
  if (!dayViewLaneEntries.value.length) return 1;
  const points: Array<{ minute: number; delta: number }> = [];

  dayViewLaneEntries.value.forEach((entry) => {
    const start = entry.startMinutes;
    const end = Math.max(entry.endMinutes, start + 15);
    points.push({ minute: start, delta: 1 }, { minute: end, delta: -1 });
  });

  points.sort((a, b) => a.minute - b.minute || a.delta - b.delta);

  let active = 0;
  let maxActive = 1;
  points.forEach((point) => {
    active += point.delta;
    maxActive = Math.max(maxActive, active);
  });

  return maxActive;
});

const dayViewJulesEntries = computed<DayViewJulesEntry[]>(() => {
  const selectedDate = selectedDayDate.value;
  if (!selectedDate) return [];

  const variantOrder: Record<DayViewJulesEntry['variant'], number> = {
    arriving: 0,
    normal: 1,
    leaving: 2,
  };

  return props.julesDays
    .filter((item) => occursOnDate(item, selectedDate))
    .flatMap((item) => buildJulesDayEntries(item, selectedDate))
    .sort((a, b) => variantOrder[a.variant] - variantOrder[b.variant] || a.title.localeCompare(b.title));
});

const dayViewUtilityEntries = computed<DayViewUtilityEntry[]>(() => {
  const selectedDate = selectedDayDate.value;
  if (!selectedDate) return [];

  return props.utilities
    .filter((utility) => utility.status === 'unpaid' && utility.due_date === selectedDate)
    .map((utility) => ({
      key: `utility-${utility.id}-${selectedDate}`,
      title: utility.name,
      note: formatUtilityAmount(utility.amount, normalizeUtilityCurrency(utility.utility_currency)),
      currency: normalizeUtilityCurrency(utility.utility_currency),
    }))
    .sort((a, b) => a.title.localeCompare(b.title));
});

const utilityEntriesByDate = computed(() => {
  const entries = new Map<string, CalendarUtilityEntry[]>();

  props.utilities
    .filter((utility) => utility.status === 'unpaid')
    .forEach((utility) => {
      const date = utility.due_date;
      const currency = normalizeUtilityCurrency(utility.utility_currency);
      const list = entries.get(date) || [];
      list.push({
        id: utility.id,
        name: utility.name,
        amountLabel: formatUtilityAmount(utility.amount, currency),
        currency,
      });
      entries.set(date, list);
    });

  entries.forEach((list, date) => {
    list.sort((a, b) => a.name.localeCompare(b.name));
    entries.set(date, list);
  });

  return entries;
});

const calendarRowCount = computed(() => calendarCells.value.length / 7);

const calendarCells = computed(() => {
  const year = displayMonth.value.getFullYear();
  const month = displayMonth.value.getMonth();
  const firstDay = new Date(year, month, 1);
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const leadingEmpty = firstDay.getDay();

  const cells: CalendarCell[] = [];
  const prevMonthDays = new Date(year, month, 0).getDate();
  for (let i = 0; i < leadingEmpty; i += 1) {
    const day = prevMonthDays - leadingEmpty + i + 1;
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

function getCalendarEntriesForDate(date: string) {
  const entries: CalendarMonthEntry[] = getEventsForDate(date).map((event) => ({
    key: `event-${event.id}-${date}`,
    kind: 'event',
    event,
  }));

  const utilityEntries = utilityEntriesByDate.value.get(date) || [];
  utilityEntries.forEach((utility) => {
    entries.push({
      key: `utility-${utility.id}-${date}`,
      kind: 'utility',
      title: utility.name,
      note: utility.amountLabel,
      currency: utility.currency,
    });
  });

  return entries.slice(0, 1);
}

function getVisibleCalendarEntries(date: string) {
  return getCalendarEntriesForDate(date);
}

function getHiddenCalendarEntryCount(date: string) {
  const total = getEventsForDate(date).length + (utilityEntriesByDate.value.get(date)?.length || 0);
  return total > 1 ? total - 1 : 0;
}

function getEventsForDate(date: string) {
  return events.value
    .filter((event) => eventOccursOnDate(event, date))
    .map((event) => ({ ...event, occurrence_date: date }))
    .sort((a, b) => parseTimeToMinutes(a.start_time) - parseTimeToMinutes(b.start_time));
}

function getJulesMarkers(date: string) {
  const markers = props.julesDays
    .filter((item) => occursOnDate(item, date))
    .flatMap((item) => {
      const occurrenceStart = getOccurrenceStartDate(item, date) || item.start;
      const duration = Math.max(1, diffDays(item.start, item.end) + 1);
      const occurrenceEnd = addDays(occurrenceStart, duration - 1);

      if (item.type === JULES_TYPE_GONE) {
        if (date === occurrenceStart && item.leaving_time) {
          return [buildJulesMarker(JULES_TYPE_LEAVING, item.leaving_time)];
        }
        if (date === occurrenceEnd && item.coming_time) {
          return [buildJulesMarker(JULES_TYPE_ARRIVING, item.coming_time)];
        }
        return [buildJulesMarker(JULES_TYPE_GONE)];
      }

      const nextMarkers = [];

      if (date === occurrenceStart && item.coming_time) {
        nextMarkers.push(buildJulesMarker(JULES_TYPE_ARRIVING, item.coming_time));
      }

      if (occurrenceEnd !== occurrenceStart && date === occurrenceEnd && item.leaving_time) {
        nextMarkers.push(buildJulesMarker(JULES_TYPE_LEAVING, item.leaving_time));
      }

      return nextMarkers.length > 0 ? nextMarkers : [buildJulesMarker(JULES_TYPE_HERE)];
    });

  return markers.sort((a, b) => a.order - b.order).slice(0, 2);
}

function buildJulesDayEntries(item: JulesDayRecord, date: string): DayViewJulesEntry[] {
  const occurrenceStart = getOccurrenceStartDate(item, date) || item.start;
  const duration = Math.max(1, diffDays(item.start, item.end) + 1);
  const occurrenceEnd = addDays(occurrenceStart, duration - 1);
  const isMultiDay = occurrenceStart !== occurrenceEnd;

  if (isMultiDay && date === occurrenceStart && item.coming_time) {
    return [{
      key: `jules-${item.id}-${date}-arriving`,
      title: 'Jules Arriving',
      note: formatTimeLabel(item.coming_time),
      variant: 'arriving',
      color: '#3b82f6',
    }];
  }

  if (isMultiDay && date === occurrenceEnd && item.leaving_time) {
    return [{
      key: `jules-${item.id}-${date}-leaving`,
      title: 'Jules Leaving',
      note: formatTimeLabel(item.leaving_time),
      variant: 'leaving',
      color: '#f87171',
    }];
  }

  if (item.type === JULES_TYPE_GONE) return [];

  return [{
    key: `jules-${item.id}-${date}-jules`,
    title: 'Jules Day',
    note: 'All day',
    variant: 'normal',
    color: '#16a34a',
  }];
}

function parseTimeToMinutes(time: string) {
  const [hour, minute] = time.split(':').map((value) => Number(value));
  return hour * 60 + minute;
}

function formatTimeLabel(time: string) {
  const [hourRaw, minuteRaw] = time.split(':');
  const hour = Number(hourRaw);
  const minute = Number(minuteRaw);
  const suffix = hour >= 12 ? 'PM' : 'AM';
  const normalizedHour = hour % 12 === 0 ? 12 : hour % 12;
  return `${normalizedHour}:${String(minute).padStart(2, '0')} ${suffix}`;
}

function formatHourLabel(hour: number) {
  const suffix = hour >= 12 ? 'PM' : 'AM';
  const normalizedHour = hour % 12 === 0 ? 12 : hour % 12;
  return `${normalizedHour} ${suffix}`;
}

function formatEventTimeRange(event: CalendarEvent) {
  if (event.all_day) return 'All day';
  return `${formatTimeLabel(event.start_time)} - ${formatTimeLabel(event.end_time)}`;
}

function getTimelineEntryStyle(entry: DayViewEventEntry) {
  const topPct = (entry.startMinutes / 1440) * 100;
  const durationMinutes = Math.max(entry.endMinutes - entry.startMinutes, 15);
  const heightPct = Math.max((durationMinutes / 1440) * 100, 6);

  return {
    backgroundColor: entry.color,
    color: '#ffffff',
    borderLeftColor: entry.color,
    top: `${topPct}%`,
    height: `${heightPct}%`,
  };
}

function getJulesEntryStyle(entry: DayViewJulesEntry) {
  return {
    backgroundColor: entry.color,
    color: '#ffffff',
    borderLeftColor: entry.color === '#16a34a' ? '#15803d' : entry.variant === 'arriving' ? '#2563eb' : '#ef4444',
  };
}

function getUtilityEntryStyle(entry: DayViewUtilityEntry) {
  const color = entry.currency === 'kisses' ? '#ec4899' : '#3b82f6';
  return {
    backgroundColor: '#ffffff',
    color,
    border: `1px solid ${color}`,
  };
}

function getUtilityCalendarEntryStyle(entry: Extract<CalendarMonthEntry, { kind: 'utility' }>) {
  const color = entry.currency === 'kisses' ? '#ec4899' : '#3b82f6';
  return {
    backgroundColor: '#ffffff',
    color,
    border: `1px solid ${color}`,
  };
}

function recurrenceSummaryFromForm(form: EventForm): string {
  if (form.recurrence_type === 'none') return 'Does not repeat';
  if (form.recurrence_type === 'daily') return 'Daily';
  if (form.recurrence_type === 'weekly') return 'Weekly';
  if (form.recurrence_type === 'biweekly') return 'Bi-weekly';
  if (form.recurrence_type === 'annually') return 'Annually';

  const interval = Math.max(1, Number(form.recurrence_interval || 1));
  const unit = form.recurrence_unit;
  const everyLabel = interval === 1 ? unit : `${interval} ${unit}s`;
  const dayLabels = form.recurrence_unit === 'week'
    ? weekDays
      .filter((_, index) => form.recurrence_days_of_week.includes(index))
      .join(', ')
    : '';

  if (form.recurrence_end_type === 'on' && form.recurrence_end_date) {
    return `Every ${everyLabel}${dayLabels ? ` on ${dayLabels}` : ''}, until ${formatDisplayDate(form.recurrence_end_date)}`;
  }

  if (form.recurrence_end_type === 'after') {
    return `Every ${everyLabel}${dayLabels ? ` on ${dayLabels}` : ''}, ${form.recurrence_occurrences} occurrences`;
  }

  return `Every ${everyLabel}${dayLabels ? ` on ${dayLabels}` : ''}`;
}

function recurrenceSummaryForEvent(event: CalendarEvent): string {
  if (!event.recurrence_type || event.recurrence_type === 'none') return 'Does not repeat';
  if (event.recurrence_type === 'daily') return 'Daily';
  if (event.recurrence_type === 'weekly') return 'Weekly';
  if (event.recurrence_type === 'biweekly') return 'Bi-weekly';
  if (event.recurrence_type === 'annually') return 'Annually';

  const interval = Math.max(1, Number(event.recurrence_interval || 1));
  const unit = event.recurrence_unit || 'week';
  const everyLabel = interval === 1 ? unit : `${interval} ${unit}s`;
  const days = (event.recurrence_days_of_week || [])
    .map((day) => weekDays[day])
    .filter(Boolean)
    .join(', ');

  if (event.recurrence_end_type === 'on' && event.recurrence_end_date) {
    return `Every ${everyLabel}${days ? ` on ${days}` : ''}, until ${formatDisplayDate(event.recurrence_end_date)}`;
  }

  if (event.recurrence_end_type === 'after' && event.recurrence_occurrences) {
    return `Every ${everyLabel}${days ? ` on ${days}` : ''}, ${event.recurrence_occurrences} occurrences`;
  }

  return `Every ${everyLabel}${days ? ` on ${days}` : ''}`;
}

function getRecurrenceStartDatesUpTo(event: CalendarEvent, targetDate: string): string[] {
  const type = event.recurrence_type || 'none';
  const starts: string[] = [];
  const firstStart = event.start;
  const excludedStarts = new Set(event.excluded_occurrences || []);
  let generatedCount = 0;

  if (type === 'none') {
    return [firstStart];
  }

  const interval = Math.max(1, Number(event.recurrence_interval || 1));
  const unit = event.recurrence_unit || (
    type === 'daily'
      ? 'day'
      : type === 'annually'
        ? 'year'
        : 'week'
  );
  const daysOfWeek = (event.recurrence_days_of_week || []).length > 0
    ? (event.recurrence_days_of_week || [])
    : [parseDateOnly(firstStart).getUTCDay()];
  const endType = event.recurrence_end_type || 'never';
  const endDate = event.recurrence_end_date || null;
  const maxOccurrences = endType === 'after' ? Number(event.recurrence_occurrences || 0) : null;

  const pushIfValid = (candidate: string): boolean => {
    if (candidate > targetDate) return false;
    if (endType === 'on' && endDate && candidate > endDate) return false;
    if (maxOccurrences !== null && generatedCount >= maxOccurrences) return false;
    generatedCount += 1;
    if (!excludedStarts.has(candidate)) {
      starts.push(candidate);
    }
    return true;
  };

  if (unit === 'week') {
    let cursor = firstStart;
    let safety = 0;
    while (cursor <= targetDate && safety < 10000) {
      const dayDiff = diffDays(firstStart, cursor);
      if (dayDiff >= 0) {
        const weekOffset = Math.floor(dayDiff / 7);
        const weekday = parseDateOnly(cursor).getUTCDay();
        if (weekOffset % interval === 0 && daysOfWeek.includes(weekday)) {
          if (!pushIfValid(cursor)) break;
        }
      }
      if (endType === 'on' && endDate && cursor > endDate) break;
      if (maxOccurrences !== null && starts.length >= maxOccurrences) break;
      cursor = addDays(cursor, 1);
      safety += 1;
    }
    return starts;
  }

  let candidate = firstStart;
  let safety = 0;
  while (candidate <= targetDate && safety < 5000) {
    if (!pushIfValid(candidate)) break;
    if (maxOccurrences !== null && starts.length >= maxOccurrences) break;
    if (unit === 'day') {
      candidate = addDays(candidate, interval);
    } else if (unit === 'month') {
      candidate = addMonths(candidate, interval);
    } else {
      candidate = addYears(candidate, interval);
    }
    safety += 1;
  }

  return starts;
}

function getOccurrenceStartDateForEvent(event: CalendarEvent, targetDate: string): string | null {
  const starts = getRecurrenceStartDatesUpTo(event, targetDate);
  const duration = Math.max(1, diffDays(event.start, event.end) + 1);

  for (let index = starts.length - 1; index >= 0; index -= 1) {
    const occurrenceStart = starts[index];
    const occurrenceEnd = addDays(occurrenceStart, duration - 1);
    if (occurrenceStart <= targetDate && targetDate <= occurrenceEnd) {
      return occurrenceStart;
    }
  }

  return null;
}

function eventOccursOnDate(event: CalendarEvent, date: string): boolean {
  return getOccurrenceStartDateForEvent(event, date) !== null;
}

function getTimelineColumnStyle(entry: DayViewEventLaneEntry) {
  const laneCount = Math.max(dayViewMaxLaneCount.value, 1);
  const width = 100 / laneCount;
  return {
    width: `calc(${width}% - 8px)`,
    left: `calc(${(entry.lane * 100) / laneCount}% + 4px)`,
  };
}

function canManageEvent(event: CalendarEvent) {
  return event.user_id === user.value?.id;
}

function openDayViewEntry(entry: DayViewEventEntry) {
  if (!entry.event) return;
  openEventFromDayView(entry.event);
}

function openEventFromDayView(event: CalendarEvent) {
  selectedEvent.value = event;
  showDayView.value = false;
  showModal.value = true;
}

function closeEventPopover() {
  showModal.value = false;
  if (selectedDayDate.value) {
    showDayView.value = true;
  }
}

function openCreateEventDialog(date?: string) {
  isEditingEvent.value = false;
  formError.value = '';
  eventForm.value = getDefaultEventForm(date);
  showCustomRecurrenceDialog.value = false;
  showEventForm.value = true;
}

function openEditEventDialog() {
  if (!selectedEvent.value || !canManageEvent(selectedEvent.value)) return;
  isEditingEvent.value = true;
  formError.value = '';
  eventForm.value = {
    title: selectedEvent.value.title,
    start: selectedEvent.value.start,
    end: selectedEvent.value.end,
    start_time: (selectedEvent.value.start_time || '').slice(0, 5),
    end_time: (selectedEvent.value.end_time || '').slice(0, 5),
    description: selectedEvent.value.description || '',
    all_day: !!selectedEvent.value.all_day,
    recurrence_type: selectedEvent.value.recurrence_type || 'none',
    recurrence_interval: selectedEvent.value.recurrence_interval || 1,
    recurrence_unit: selectedEvent.value.recurrence_unit || 'week',
    recurrence_days_of_week: selectedEvent.value.recurrence_days_of_week || [parseDateOnly(selectedEvent.value.start).getUTCDay()],
    recurrence_end_type: selectedEvent.value.recurrence_end_type || 'never',
    recurrence_end_date: selectedEvent.value.recurrence_end_date || '',
    recurrence_occurrences: selectedEvent.value.recurrence_occurrences || 13,
  };
  showCustomRecurrenceDialog.value = false;
  showModal.value = false;
  showEventForm.value = true;
}

async function fetchEvents() {
  const { data } = await api.get('/events');
  events.value = data;
}

defineExpose({ refresh: fetchEvents });

async function saveEvent() {
  formError.value = '';
  savingEvent.value = true;
  try {
    const payload = {
      ...eventForm.value,
      start_time: eventForm.value.all_day ? '00:00' : eventForm.value.start_time,
      end_time: eventForm.value.all_day ? '23:59' : eventForm.value.end_time,
      recurrence_end_date: eventForm.value.recurrence_end_type === 'on' ? eventForm.value.recurrence_end_date : null,
      recurrence_occurrences: eventForm.value.recurrence_end_type === 'after' ? eventForm.value.recurrence_occurrences : null,
      recurrence_days_of_week: eventForm.value.recurrence_unit === 'week' ? eventForm.value.recurrence_days_of_week : [],
    };

    if (payload.recurrence_type !== 'custom') {
      payload.recurrence_end_date = null;
      payload.recurrence_occurrences = null;
    }

    if (isEditingEvent.value && selectedEvent.value) {
      await api.put(`/events/${selectedEvent.value.id}`, payload);
    } else {
      await api.post('/events', payload);
    }
    showCustomRecurrenceDialog.value = false;
    showEventForm.value = false;
    await fetchEvents();
  } catch (error: any) {
    const fieldErrors = error?.response?.data?.errors;
    const firstFieldKey = fieldErrors ? Object.keys(fieldErrors)[0] : null;
    const firstFieldMessage = firstFieldKey ? fieldErrors[firstFieldKey]?.[0] : null;
    formError.value = firstFieldMessage || error?.response?.data?.message || 'Unable to save event.';
  } finally {
    savingEvent.value = false;
  }
}

async function deleteSelectedEvent() {
  if (!selectedEvent.value || !canManageEvent(selectedEvent.value)) return;
  if (selectedEvent.value.recurrence_type && selectedEvent.value.recurrence_type !== 'none') {
    deleteScopeTarget.value = selectedEvent.value;
    showDeleteScopeDialog.value = true;
    return;
  }

  if (!window.confirm('Delete this event?')) return;
  await api.delete(`/events/${selectedEvent.value.id}`);
  showModal.value = false;
  selectedEvent.value = null;
  await fetchEvents();
}

async function confirmDeleteEvent(scope: 'single' | 'future' | 'series') {
  if (!deleteScopeTarget.value) return;

  const occurrenceStart = deleteScopeTarget.value.occurrence_date
    ? getOccurrenceStartDateForEvent(deleteScopeTarget.value, deleteScopeTarget.value.occurrence_date) || deleteScopeTarget.value.start
    : deleteScopeTarget.value.start;

  await api.delete(`/events/${deleteScopeTarget.value.id}`, {
    data: scope === 'series'
      ? {}
      : {
          scope,
          occurrence_start: occurrenceStart,
        },
  });
  showDeleteScopeDialog.value = false;
  showModal.value = false;
  selectedEvent.value = null;
  deleteScopeTarget.value = null;
  await fetchEvents();
}

function handleCellClick(cell: CalendarCell) {
  if (!cell.date) return;
  if (cell.otherMonth) {
    const eventDate = new Date(cell.date + 'T00:00:00');
    displayMonth.value = new Date(eventDate.getFullYear(), eventDate.getMonth(), 1);
  } else {
    selectedDayDate.value = cell.date;
    showDayView.value = true;
  }
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

function openMonthYearDialog() {
  pickerMonth.value = displayMonth.value.getMonth();
  pickerYear.value = displayMonth.value.getFullYear();
  showMonthYearDialog.value = true;
}

function applyMonthYearSelection() {
  displayMonth.value = new Date(pickerYear.value, pickerMonth.value, 1);
  showMonthYearDialog.value = false;
}

function handleRecurrenceTypeChange(value: EventForm['recurrence_type']) {
  if (value === 'custom') {
    if (eventForm.value.recurrence_unit === 'week' && eventForm.value.recurrence_days_of_week.length === 0) {
      eventForm.value.recurrence_days_of_week = [parseDateOnly(eventForm.value.start).getUTCDay()];
    }
    showCustomRecurrenceDialog.value = true;
  } else {
    showCustomRecurrenceDialog.value = false;
  }
}

function toggleCustomWeekday(day: number) {
  const selected = eventForm.value.recurrence_days_of_week;
  if (selected.includes(day)) {
    const next = selected.filter((value) => value !== day);
    if (next.length > 0) {
      eventForm.value.recurrence_days_of_week = next;
    }
    return;
  }

  eventForm.value.recurrence_days_of_week = [...selected, day].sort((a, b) => a - b);
}

function handleCustomUnitChange() {
  if (eventForm.value.recurrence_unit !== 'week') {
    eventForm.value.recurrence_days_of_week = [];
    return;
  }

  if (eventForm.value.recurrence_days_of_week.length === 0) {
    eventForm.value.recurrence_days_of_week = [parseDateOnly(eventForm.value.start).getUTCDay()];
  }
}

onMounted(async () => {
  await fetchEvents();
});
</script>

<style scoped>
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
  color: var(--color-text) !important;
}

.calendar-nav-btn :global(.v-btn__content) {
  font-weight: 700;
  color: var(--color-text) !important;
}

.today-month-btn {
  min-width: 30px;
  width: 30px;
  padding-inline: 0;
}

.today-month-btn:disabled {
  opacity: 0.45;
}

.day-title-row {
  font-size: 18px;
}

.month-year-dialog-fields {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

/* Input fields styling */
:deep(.v-text-field .v-field) {
  background-color: #ffffff !important;
}

:deep(.v-textarea .v-field) {
  background-color: #ffffff !important;
}

.calendar-card {
  height: auto;
  display: flex;
  flex-direction: column;
  border-radius: 18px;
}

.calendar-body {
  display: flex;
  flex-direction: column;
  flex: none;
  min-height: 0;
  padding: 8px 10px 10px;
  gap: 2px;
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
  --calendar-row-height: 84px;
}

.calendar-cell {
   min-height: 0;
   border: 1px solid #dbe4f0;
   border-radius: 10px;
   padding: 8px;
   background: #f8fbff;
   overflow: hidden;
   display: flex;
   flex-direction: column;
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

.calendar-cell.other-month .date-label {
  color: #999;
}

.calendar-cell.other-month .event-item {
  opacity: 0.5;
}

.calendar-cell.other-month .calendar-utility-item {
  opacity: 0.5;
}

.helper-copy {
  margin: 0;
  color: #64748b;
}

.date-label {
  font-size: 12px;
  font-weight: 700;
  color: #0f172a;
  line-height: 1;
}

.date-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 6px;
  gap: 4px;
}

.calendar-day-content {
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex: 1 1 auto;
  min-height: 0;
}

.jules-markers {
  display: inline-grid;
  gap: 3px;
  justify-items: end;
}

.jules-marker {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 16px;
  height: 16px;
  padding: 0 4px;
  margin-left: auto;
  border-radius: 999px;
  background: #16a34a;
  color: #ffffff;
  font-weight: 900;
  font-size: 8px;
  line-height: 1;
}

.jules-marker--gone {
  background: #dc2626;
}

.jules-marker--here {
  background: #16a34a;
}

.jules-marker--arriving {
  background: #60a5fa;
}

.jules-marker--leaving {
  background: #f87171;
}

.calendar-legend {
  display: grid;
  grid-template-columns: repeat(2, max-content);
  gap: 8px 12px;
  width: fit-content;
  margin: 8px auto 2px;
  justify-content: center;
  justify-items: start;
  align-items: center;
}

.legend-item {
  display: grid;
  grid-template-columns: 16px auto;
  align-items: center;
  column-gap: 6px;
  font-size: 11px;
  color: #64748b;
  min-width: 0;
  justify-content: start;
  width: max-content;
  text-align: left;
}

.legend-dot {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 16px;
  height: 16px;
  padding: 0 4px;
  border-radius: 999px;
  color: #ffffff;
  font-weight: 900;
  font-size: 8px;
  line-height: 1;
}

.event-item {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  border-left: 3px solid #1976d2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  transition: all 0.2s ease;
  margin-bottom: 0;
  flex: 0 0 auto;
}

.event-item:hover {
  transform: translateX(2px);
  filter: brightness(0.96);
}

.calendar-utility-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 3px 7px;
  margin-bottom: 0;
  border-radius: 5px;
  background: #ffffff;
  border-left: 3px solid #3b82f6;
  box-sizing: border-box;
  overflow: hidden;
  flex: 0 0 auto;
}

.calendar-utility-row {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  min-width: 0;
}

.calendar-utility-item--kisses {
  border-left-color: #ec4899;
}

.calendar-utility-item--dollars {
  border-left-color: #3b82f6;
}

.calendar-utility-title {
  flex: 1 1 auto;
  font-size: 11px;
  font-weight: 700;
  line-height: 1.1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: #3b82f6;
}

.calendar-utility-item--kisses .calendar-utility-title,
.calendar-utility-item--kisses .calendar-utility-note {
  color: #ec4899;
}

.calendar-utility-item--dollars .calendar-utility-title,
.calendar-utility-item--dollars .calendar-utility-note {
  color: #3b82f6;
}

.calendar-utility-note {
  flex: 0 0 auto;
  font-size: 10px;
  font-weight: 600;
  line-height: 1.1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.more-events {
  margin-top: auto;
  padding: 4px 6px 2px;
  font-size: 11px;
  color: #666;
  font-style: italic;
  line-height: 1.1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 0 0 auto;
}

.event-color-preview {
   width: fit-content;
   min-width: 40px;
   height: 40px;
   border-radius: 8px;
   margin-top: 12px;
   display: inline-flex;
   align-items: center;
   justify-content: center;
   padding: 4px 10px;
   box-sizing: border-box;
}

.event-color-preview-name {
  color: #ffffff;
  font-size: 10px;
  line-height: 1;
  font-weight: 700;
  text-align: center;
  white-space: nowrap;
}

.event-detail-panel {
  display: grid;
  gap: 8px;
  background: #ffffff;
  border-radius: 14px;
  padding: 14px;
  box-shadow: inset 0 0 0 1px #dbe4f0;
}

.event-detail-panel p {
  margin: 0;
}

.day-timeline-wrapper {
  display: grid;
  grid-template-columns: 64px 1fr;
  gap: 10px;
  margin-top: 4px;
  flex: 1 1 auto;
  min-height: 0;
  overflow: hidden;
}

.timeline-hour-labels {
  height: 100%;
  position: relative;
}

.timeline-hour-label {
  position: relative;
  height: calc(100% / 48);
  font-size: 11px;
  color: #64748b;
  line-height: 1;
}

.timeline-grid {
  position: relative;
  height: 100%;
  border: 1px solid #dbe4f0;
  border-radius: 8px;
  background: #f8fbff;
  overflow: hidden;
}

.timeline-hour-line {
  position: absolute;
  left: 0;
  right: 0;
  height: 1px;
  background: #e2e8f0;
}

.timeline-hour-line.half-hour-line {
  background: #f1f5f9;
}

.timeline-event {
  position: absolute;
  border-left: 4px solid;
  border-radius: 6px;
  padding: 10px 10px 9px;
  cursor: default;
  overflow: hidden;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  justify-content: center;
  gap: 4px;
}

.timeline-event--clickable {
  cursor: pointer;
}

.timeline-event--jules {
  border-left-style: solid;
}

.timeline-event--utility {
  border-left-style: solid;
}

.timeline-event--utility-kisses {
  background-color: #ffffff;
  color: #ec4899;
  border-color: #ec4899;
}

.timeline-event--utility-dollars {
  background-color: #ffffff;
  color: #3b82f6;
  border-color: #3b82f6;
}

.timeline-event--inline {
  position: relative;
  top: auto !important;
  left: auto !important;
  width: 100% !important;
  height: auto !important;
}

.timeline-event-title {
  font-size: 13px;
  font-weight: 700;
  line-height: 1.1;
  white-space: normal;
  margin: 0;
  color: #ffffff !important;
}

.timeline-event-note {
  font-size: 11px;
  line-height: 1.1;
  font-weight: 600;
  opacity: 0.92;
  color: #ffffff;
}

.timeline-event--utility-kisses .timeline-event-title,
.timeline-event--utility-kisses .timeline-event-note {
  color: #ec4899 !important;
}

.timeline-event--utility-dollars .timeline-event-title,
.timeline-event--utility-dollars .timeline-event-note {
  color: #3b82f6 !important;
}

.day-jules-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 12px;
}

.time-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
  align-items: center;
}

.time-controls-row {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 12px;
  align-items: center;
  min-height: 48px;
  margin: 2px 0 10px;
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

.time-field :deep(.v-field__input) {
  min-height: 36px !important;
  display: flex !important;
  align-items: center !important;
}

.time-field :deep(.v-field--disabled) {
  background-color: #e5e7eb !important;
  opacity: 1 !important;
}

.time-field :deep(.v-field--disabled input) {
  color: #6b7280 !important;
}

.date-field :deep(.v-field__input) {
  display: flex !important;
  align-items: center !important;
}

.date-field :deep(input[type='date']),
.time-field :deep(input[type='time']) {
  line-height: 1.2;
}

.custom-recurrence-inline {
  margin: 0 0 12px;
}

.custom-recurrence-summary {
  font-size: 12px;
  color: #64748b;
  margin: 0;
}

.custom-recurrence-card {
  border-radius: 20px;
  background-color: var(--app-modal-bg) !important;
}

.custom-row {
  display: grid;
  grid-template-columns: auto 96px 132px;
  gap: 10px;
  align-items: center;
  margin-bottom: 14px;
}

.custom-label {
  font-size: 14px;
  font-weight: 500;
  color: #334155;
}

.custom-interval-field :deep(.v-field),
.custom-unit-field :deep(.v-field),
.custom-end-field :deep(.v-field) {
  background-color: #ffffff !important;
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
  width: 30px;
  height: 30px;
  border-radius: 999px;
  border: 1px solid #cbd5e1;
  background: #f1f5f9;
  color: #334155;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

.weekday-circle.active {
  border-color: #93c5fd;
  background: #bfdbfe;
  color: #1e3a8a;
}

.ends-section {
  margin-top: 6px;
}

.custom-end-field {
  margin: 4px 0 0 30px;
  max-width: 210px;
}

:global(.event-select-menu .v-list) {
  background-color: #ffffff !important;
}

:global(.event-select-menu .v-list-item) {
  background-color: #ffffff !important;
}

:global(.event-select-menu .v-list-item:hover) {
  background-color: #f8fafc !important;
}

.no-events {
  color: #888;
  font-style: italic;
  text-align: center;
  padding: 18px 0 10px;
}

.day-view-card {
  display: flex;
  flex-direction: column;
  height: 95vh;
  min-height: 95vh;
  max-height: 95vh;
  overflow: hidden;
}

.day-view-content {
  display: flex;
  flex-direction: column;
  min-height: 0;
  flex: 1 1 auto;
  padding: 10px 10px 0;
  gap: 10px;
  overflow-y: auto;
  overflow-x: hidden;
}

.day-view-actions {
  flex: 0 0 auto;
  padding: 0 10px 10px;
  margin-top: 10px;
}

.event-popover-card {
  min-height: 420px;
}

.form-error {
  color: #b91c1c;
  margin: 4px 0 0;
  font-size: 13px;
}

/* Mobile responsive styles */
@media (max-width: 768px) {
   .calendar-title-row {
     padding-inline: 8px;
   }

   .calendar-body {
     padding: 6px 8px 8px;
   }

   .calendar-header {
     margin-bottom: 8px;
   }

   .month-title {
     font-size: 16px;
   }

   .weekday-row {
     gap: 4px;
     margin-bottom: 6px;
   }

   .weekday-cell {
     font-size: 10px;
   }

   .calendar-grid {
     gap: 4px;
     --calendar-row-height: 88px;
   }

   .calendar-cell {
     min-height: 64px;
     padding: 6px 4px;
     border-radius: 4px;
   }

   .date-label {
     font-size: 10px;
     margin-bottom: 2px;
   }

   .event-item {
     padding: 2px 4px;
     font-size: 10px;
     margin-top: 2px;
     border-left: 2px solid;
   }

   .calendar-utility-item {
     padding: 2px 4px;
   }

   .calendar-utility-row {
     gap: 6px;
   }

   .calendar-utility-title {
     font-size: 10px;
   }

   .calendar-utility-note {
     font-size: 9px;
   }

   .more-events {
     padding: 3px 4px 1px;
     font-size: 9px;
   }

   .day-timeline-wrapper {
     grid-template-columns: 50px 1fr;
     gap: 8px;
     flex: 1 1 auto;
     min-height: 0;
     overflow: hidden;
   }

   .day-view-content {
     padding: 10px 10px 0;
     gap: 10px;
     flex: 1 1 auto;
     overflow-y: auto;
     overflow-x: hidden;
   }

   .day-view-actions {
     padding: 0 10px 10px;
     margin-top: 10px;
     flex: 0 0 auto;
   }

   .timeline-hour-labels,
   .timeline-grid {
     height: 100%;
   }

   .timeline-hour-label {
     height: calc(100% / 48);
     font-size: 10px;
   }

   .timeline-event {
     padding: 7px 7px 6px;
   }

   .timeline-event-title {
     font-size: 11px;
   }

   .day-jules-list {
     gap: 6px;
     margin-top: 10px;
   }

   .timeline-event--inline {
     padding: 8px 10px;
   }

   .event-popover-card {
     min-height: 380px;
   }
}

@media (max-width: 480px) {
   .calendar-title-row {
     padding-inline: 6px;
   }

   .calendar-body {
     padding: 6px 6px 8px;
   }

   .calendar-header {
     margin-bottom: 6px;
   }

   .month-title {
     font-size: 14px;
   }

   .weekday-row {
     gap: 3px;
     margin-bottom: 5px;
   }

   .weekday-cell {
     font-size: 9px;
   }

   .calendar-grid {
     gap: 3px;
     --calendar-row-height: 92px;
   }

   .calendar-cell {
     padding: 4px 3px;
     border-radius: 3px;
   }

   .date-label {
     font-size: 9px;
     margin-bottom: 1px;
   }

   .event-item {
     padding: 1px 3px;
     font-size: 9px;
     margin-top: 2px;
     border-left: 1px solid;
   }

   .calendar-utility-item {
     padding: 1px 3px;
   }

   .calendar-utility-row {
     gap: 4px;
   }

   .calendar-utility-title {
     font-size: 9px;
   }

   .calendar-utility-note {
     font-size: 8px;
   }

   .more-events {
     padding: 2px 3px 1px;
     font-size: 8px;
   }

   .day-timeline-wrapper {
     grid-template-columns: 46px 1fr;
     gap: 6px;
     flex: 1 1 auto;
     min-height: 0;
     overflow: hidden;
   }

   .day-view-content {
     padding: 10px 10px 0;
     gap: 10px;
     flex: 1 1 auto;
     overflow-y: auto;
     overflow-x: hidden;
   }

   .day-view-actions {
     padding: 0 10px 10px;
     margin-top: 10px;
     flex: 0 0 auto;
   }

   .timeline-hour-labels,
   .timeline-grid {
     height: 100%;
   }

   .timeline-hour-label {
     height: calc(100% / 48);
     font-size: 9px;
   }

   .timeline-event {
     padding: 6px 6px;
   }

   .timeline-event-title {
     font-size: 10px;
   }

   .time-row {
     grid-template-columns: 1fr;
     gap: 0;
   }

   .event-popover-card {
     min-height: 340px;
   }
}
</style>
