<template>
  <v-card>
      <v-card-title class="title-row">
      <span>Events Calendar</span>
        <v-btn color="primary" size="x-small" density="comfortable" rounded="lg" class="action-btn" @click="openCreateEventDialog()">New Event</v-btn>
    </v-card-title>
    <v-card-text>
      <div class="calendar-header">
        <v-btn variant="text" size="small" @click="previousMonth">Prev</v-btn>
        <div class="month-title">{{ monthLabel }}</div>
        <v-btn variant="text" size="small" @click="nextMonth">Next</v-btn>
      </div>
      <div class="weekday-row">
        <div v-for="day in weekDays" :key="day" class="weekday-cell">{{ day }}</div>
      </div>

      <div class="calendar-grid">
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
            <div class="date-label">{{ cell.dayNumber }}</div>

            <div
              v-for="(event, eventIndex) in getVisibleEvents(cell.date)"
              :key="eventIndex"
              class="event-item"
              :style="getEventStyle()"
            >
              {{ event.title }}
            </div>

            <div v-if="getHiddenEventCount(cell.date) > 0" class="more-events">
              +{{ getHiddenEventCount(cell.date) }} more events
            </div>
          </template>
        </div>
      </div>

      <!-- Day View Dialog -->
      <v-dialog v-model="showDayView" max-width="500px">
        <v-card v-if="selectedDayDate" class="day-view-card">
          <v-card-title class="title-row day-title-row">
            <span>{{ formatDisplayDate(selectedDayDate) }}</span>
            <v-btn color="primary" size="x-small" density="comfortable" rounded="lg" class="action-btn" @click="openCreateEventDialog(selectedDayDate)">New Event</v-btn>
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
                  v-for="event in dayViewLaneEvents"
                  :key="event.id"
                  class="timeline-event"
                  :style="[getTimelineEventStyle(event), getTimelineColumnStyle(event)]"
                  @click="openEventFromDayView(event)"
                >
                  <div class="timeline-event-title">{{ event.title }}</div>
                </div>
              </div>
            </div>
            <div v-if="dayViewLaneEvents.length === 0" class="no-events">
              No events for this day.
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
        <v-card v-if="selectedEvent" class="event-popover-card">
          <v-card-title>{{ selectedEvent.title }}</v-card-title>
          <v-card-text>
            <p><strong>Date:</strong> {{ formatDisplayDate(selectedEvent.start) }}</p>
            <p><strong>Time:</strong> {{ formatEventTimeRange(selectedEvent) }}</p>
            <p v-if="selectedEvent.description"><strong>Description:</strong> {{ selectedEvent.description }}</p>
            <div class="event-color-preview" :style="{ backgroundColor: getColorValue(getCurrentUserColor()) }" />
          </v-card-text>
          <v-card-actions>
            <v-btn v-if="canManageSelectedEvent" color="primary" variant="text" @click="openEditEventDialog">Edit</v-btn>
            <v-btn v-if="canManageSelectedEvent" color="error" variant="text" @click="deleteSelectedEvent">Delete</v-btn>
            <v-spacer />
            <v-btn color="primary" class="action-btn" size="x-small" density="comfortable" rounded="lg" @click="closeEventPopover">Close</v-btn>
          </v-card-actions>
        </v-card>
       </v-dialog>

      <v-dialog v-model="showEventForm" max-width="500px">
        <v-card>
          <v-card-title>{{ isEditingEvent ? 'Edit Event' : 'New Event' }}</v-card-title>
          <v-card-text>
            <v-text-field v-model="eventForm.title" label="Title" density="comfortable" />
            <v-text-field v-model="eventForm.start" label="Start date" type="date" density="comfortable" />
            <v-text-field v-model="eventForm.end" label="End date" type="date" density="comfortable" />
            <div class="time-row">
              <v-text-field v-model="eventForm.start_time" label="Start time" type="time" density="comfortable" />
              <v-text-field v-model="eventForm.end_time" label="End time" type="time" density="comfortable" />
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
     </v-card-text>
   </v-card>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { VCard, VCardTitle, VCardText, VCardActions, VDialog, VBtn, VSpacer, VTextField, VTextarea } from 'vuetify/components';
import { useAuth } from '@/composables/useAuth';
import api from '@/services/api';

type CalendarEvent = {
  id: number;
  user_id: number;
  title: string;
  start: string;
  end: string;
  start_time: string;
  end_time: string;
  description?: string | null;
};

type EventForm = {
  title: string;
  start: string;
  end: string;
  start_time: string;
  end_time: string;
  description: string;
};

type CalendarCell = {
  date: string | null;
  dayNumber: number | null;
  otherMonth?: boolean;
};

const { user } = useAuth();

function formatDateString(date: Date): string {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function formatDisplayDate(dateStr: string): string {
  const date = new Date(dateStr + 'T00:00:00');
  return date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
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

function getCurrentUserColor(): string {
  return user.value?.color_preference || '#D6486B';
}

function getEventStyle() {
  const colorValue = getCurrentUserColor();
  const bg = getColorValue(colorValue);
  const textColor = '#ffffff';
  return {
    backgroundColor: bg,
    color: textColor,
    borderLeftColor: bg,
  };
}

function getDayViewEventStyle() {
  const colorValue = getCurrentUserColor();
  const bg = getColorValue(colorValue);
  const textColor = '#ffffff';
  return { backgroundColor: bg, color: textColor, borderLeftColor: bg };
}

function getDefaultEventForm(date?: string): EventForm {
  const initialDate = date || formatDateString(new Date());
  return {
    title: '',
    start: initialDate,
    end: initialDate,
    start_time: '09:00',
    end_time: '10:00',
    description: '',
  };
}

const showModal = ref(false);
const selectedEvent = ref<CalendarEvent | null>(null);
const showDayView = ref(false);
const selectedDayDate = ref<string | null>(null);
const showEventForm = ref(false);
const isEditingEvent = ref(false);
const savingEvent = ref(false);
const formError = ref('');
const eventForm = ref<EventForm>(getDefaultEventForm());
const displayMonth = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1));
const todayDate = formatDateString(new Date());

const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const events = ref<CalendarEvent[]>([]);

const monthLabel = computed(() => displayMonth.value.toLocaleDateString('en-US', { month: 'long', year: 'numeric' }));
const canManageSelectedEvent = computed(() => !!selectedEvent.value && selectedEvent.value.user_id === user.value?.id);
const dayViewEvents = computed(() => {
  if (!selectedDayDate.value) return [] as CalendarEvent[];
  return [...getEventsForDate(selectedDayDate.value)].sort((a, b) => {
    const byStart = parseTimeToMinutes(a.start_time) - parseTimeToMinutes(b.start_time);
    if (byStart !== 0) return byStart;
    return parseTimeToMinutes(a.end_time) - parseTimeToMinutes(b.end_time);
  });
});

type TimelineLaneEvent = CalendarEvent & { lane: number; laneCount: number };

const halfHourMarks = computed(() => Array.from({ length: 48 }, (_, index) => index));

const dayViewLaneEvents = computed<TimelineLaneEvent[]>(() => {
  const eventsForDay = dayViewEvents.value;
  const activeLaneEndTimes: number[] = [];

  return eventsForDay.map((event) => {
    const startMinutes = parseTimeToMinutes(event.start_time);
    const endMinutes = Math.max(parseTimeToMinutes(event.end_time), startMinutes + 15);

    let lane = activeLaneEndTimes.findIndex((laneEnd) => laneEnd <= startMinutes);
    if (lane === -1) {
      lane = activeLaneEndTimes.length;
      activeLaneEndTimes.push(endMinutes);
    } else {
      activeLaneEndTimes[lane] = endMinutes;
    }

    return { ...event, lane, laneCount: 1 };
  });
});

const dayViewMaxLaneCount = computed(() => {
  if (!dayViewLaneEvents.value.length) return 1;
  const points: Array<{ minute: number; delta: number }> = [];

  dayViewLaneEvents.value.forEach((event) => {
    const start = parseTimeToMinutes(event.start_time);
    const end = Math.max(parseTimeToMinutes(event.end_time), start + 15);
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

  const trailingEmpty = 35 - cells.length;
  for (let day = 1; day <= trailingEmpty; day += 1) {
    const date = new Date(year, month + 1, day);
    cells.push({ date: formatDateString(date), dayNumber: day, otherMonth: true });
  }

  return cells;
});

function getEventsForDate(date: string) {
  return events.value.filter((event) => event.start === date);
}

function getVisibleEvents(date: string) {
  return getEventsForDate(date).slice(0, 2);
}

function getHiddenEventCount(date: string) {
  const total = getEventsForDate(date).length;
  return total > 3 ? total - 2 : 0;
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
  return `${formatTimeLabel(event.start_time)} - ${formatTimeLabel(event.end_time)}`;
}

function getTimelineEventStyle(event: CalendarEvent) {
  const startMinutes = parseTimeToMinutes(event.start_time);
  const endMinutes = Math.max(parseTimeToMinutes(event.end_time), startMinutes + 15);
  const durationMinutes = endMinutes - startMinutes;
  const topPct = (startMinutes / 1440) * 100;
  const heightPct = Math.max((durationMinutes / 1440) * 100, 4);
  const baseStyle = getDayViewEventStyle() as Record<string, string>;

  return {
    ...baseStyle,
    top: `${topPct}%`,
    height: `${heightPct}%`,
  };
}

function getTimelineColumnStyle(event: TimelineLaneEvent) {
  const laneCount = Math.max(dayViewMaxLaneCount.value, 1);
  const width = 100 / laneCount;
  return {
    width: `calc(${width}% - 8px)`,
    left: `calc(${(event.lane * 100) / laneCount}% + 4px)`,
  };
}

function canManageEvent(event: CalendarEvent) {
  return event.user_id === user.value?.id;
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
    start_time: selectedEvent.value.start_time,
    end_time: selectedEvent.value.end_time,
    description: selectedEvent.value.description || '',
  };
  showModal.value = false;
  showEventForm.value = true;
}

async function fetchEvents() {
  const { data } = await api.get('/events');
  events.value = data;
}

async function saveEvent() {
  formError.value = '';
  savingEvent.value = true;
  try {
    if (isEditingEvent.value && selectedEvent.value) {
      await api.put(`/events/${selectedEvent.value.id}`, eventForm.value);
    } else {
      await api.post('/events', eventForm.value);
    }
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
  if (!window.confirm('Delete this event?')) return;
  await api.delete(`/events/${selectedEvent.value.id}`);
  showModal.value = false;
  selectedEvent.value = null;
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

onMounted(async () => {
  await fetchEvents();
});
</script>

<style scoped>
.calendar-header {
   display: flex;
   align-items: center;
   justify-content: space-between;
   margin-bottom: 12px;
}

.month-title {
  font-weight: 700;
}

.title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.action-btn {
  text-transform: none;
  border-radius: 12px !important;
  padding-inline: 10px;
  min-height: 30px;
  background-color: var(--color-primary) !important;
  color: var(--color-text) !important;
}

.action-btn :global(.v-btn__content) {
  font-size: 13px;
  letter-spacing: 0;
}

.action-btn :global(.v-btn__overlay),
.action-btn :global(.v-btn__underlay) {
  border-radius: 12px;
}

.day-title-row {
  font-size: 18px;
}

/* Input fields styling */
:deep(.v-text-field .v-field) {
  background-color: #ffffff !important;
}

:deep(.v-textarea .v-field) {
  background-color: #ffffff !important;
}

.weekday-row {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 6px;
  margin-bottom: 6px;
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
  gap: 6px;
}

.calendar-cell {
   min-height: 150px;
   border: 1px solid #dbe4f0;
   border-radius: 8px;
   padding: 6px;
   background: #f8fbff;
}

.calendar-cell.empty {
  background: #eef3fa;
}

.calendar-cell.today {
  border-color: #93c5fd;
  box-shadow: inset 0 0 0 1px #bfdbfe;
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

.date-label {
  font-size: 12px;
  font-weight: 700;
  margin-bottom: 6px;
  color: #0f172a;
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
  margin-bottom: 4px;
}

.event-item:hover {
  transform: translateX(2px);
  filter: brightness(0.96);
}

.more-events {
  padding: 2px 8px;
  font-size: 11px;
  color: #666;
  font-style: italic;
}

.event-color-preview {
   width: 40px;
   height: 40px;
   border-radius: 8px;
   margin-top: 12px;
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
  cursor: pointer;
  overflow: hidden;
  box-sizing: border-box;
  display: flex;
  align-items: center;
}

.timeline-event-title {
  font-size: 13px;
  font-weight: 700;
  line-height: 1.1;
  white-space: normal;
  margin: 0;
  color: #ffffff !important;
}

.time-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
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
  overflow: hidden;
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
   .calendar-header {
     margin-bottom: 8px;
   }

   .month-title {
     font-size: 16px;
   }

   .weekday-row {
     gap: 2px;
     margin-bottom: 4px;
   }

   .weekday-cell {
     font-size: 10px;
   }

   .calendar-grid {
     gap: 2px;
     grid-auto-rows: 80px;
   }

   .calendar-cell {
     min-height: unset;
     height: 80px;
     overflow: hidden;
     padding: 3px;
     border-radius: 4px;
   }

   .date-label {
     font-size: 10px;
     margin-bottom: 2px;
   }

   .event-item {
     padding: 2px 4px;
     font-size: 10px;
     margin-bottom: 2px;
     border-left: 2px solid;
   }

   .more-events {
     padding: 1px 4px;
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
     overflow: hidden;
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

   .event-popover-card {
     min-height: 380px;
   }
}

@media (max-width: 480px) {
   .calendar-header {
     margin-bottom: 6px;
   }

   .month-title {
     font-size: 14px;
   }

   .weekday-row {
     gap: 1px;
     margin-bottom: 2px;
   }

   .weekday-cell {
     font-size: 9px;
   }

   .calendar-grid {
     gap: 1px;
     grid-auto-rows: 60px;
   }

   .calendar-cell {
     min-height: unset;
     height: 60px;
     overflow: hidden;
     padding: 2px;
     border-radius: 3px;
   }

   .date-label {
     font-size: 9px;
     margin-bottom: 1px;
   }

   .event-item {
     padding: 1px 3px;
     font-size: 9px;
     margin-bottom: 1px;
     border-left: 1px solid;
   }

   .more-events {
     padding: 1px 3px;
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
     overflow: hidden;
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






