export type RecurrenceType = 'none' | 'daily' | 'weekly' | 'biweekly' | 'annually' | 'custom';
export type RecurrenceUnit = 'day' | 'week' | 'month' | 'year';
export type RecurrenceEndType = 'never' | 'on' | 'after';

export type RecurringRecord = {
  start: string;
  end: string;
  recurrence_type?: RecurrenceType;
  recurrence_interval?: number | null;
  recurrence_unit?: RecurrenceUnit | null;
  recurrence_days_of_week?: number[] | null;
  recurrence_end_type?: RecurrenceEndType | null;
  recurrence_end_date?: string | null;
  recurrence_occurrences?: number | null;
  excluded_occurrences?: string[] | null;
};

export const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as const;

export function formatDateString(date: Date): string {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

export function parseDateOnly(dateStr: string): Date {
  const [year, month, day] = dateStr.split('-').map((value) => Number(value));
  return new Date(Date.UTC(year, month - 1, day));
}

export function formatDateOnly(date: Date): string {
  const year = date.getUTCFullYear();
  const month = String(date.getUTCMonth() + 1).padStart(2, '0');
  const day = String(date.getUTCDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

export function formatDisplayDate(dateStr: string): string {
  if (!dateStr) return '';

  const date = dateStr.includes('T') || dateStr.includes(' ')
    ? new Date(dateStr)
    : new Date(`${dateStr}T00:00:00`);

  if (Number.isNaN(date.getTime())) return dateStr;

  return date.toLocaleDateString('en-US', {
    weekday: 'short',
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

export function addDays(dateStr: string, amount: number): string {
  const date = parseDateOnly(dateStr);
  date.setUTCDate(date.getUTCDate() + amount);
  return formatDateOnly(date);
}

export function addMonths(dateStr: string, amount: number): string {
  const date = parseDateOnly(dateStr);
  const day = date.getUTCDate();
  date.setUTCDate(1);
  date.setUTCMonth(date.getUTCMonth() + amount);
  const daysInMonth = new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth() + 1, 0)).getUTCDate();
  date.setUTCDate(Math.min(day, daysInMonth));
  return formatDateOnly(date);
}

export function addYears(dateStr: string, amount: number): string {
  const date = parseDateOnly(dateStr);
  date.setUTCFullYear(date.getUTCFullYear() + amount);
  return formatDateOnly(date);
}

export function diffDays(startDate: string, endDate: string): number {
  const start = parseDateOnly(startDate).getTime();
  const end = parseDateOnly(endDate).getTime();
  return Math.floor((end - start) / 86400000);
}

export function getWeekRange(date: Date): { start: string; end: string } {
  const start = new Date(date);
  start.setHours(0, 0, 0, 0);
  start.setDate(start.getDate() - start.getDay());
  const end = new Date(start);
  end.setDate(end.getDate() + 6);
  return {
    start: formatDateString(start),
    end: formatDateString(end),
  };
}

export function getRecurrenceStartDatesUpTo(record: RecurringRecord, targetDate: string): string[] {
  const type = record.recurrence_type || 'none';
  const starts: string[] = [];
  const firstStart = record.start;
  const excludedStarts = new Set(record.excluded_occurrences || []);
  let generatedCount = 0;

  if (type === 'none') {
    if (!excludedStarts.has(firstStart) && firstStart <= targetDate) {
      starts.push(firstStart);
    }
    return starts;
  }

  const interval = Math.max(1, Number(record.recurrence_interval || 1));
  const unit = record.recurrence_unit || (
    type === 'daily' ? 'day' : type === 'annually' ? 'year' : 'week'
  );
  const daysOfWeek = (record.recurrence_days_of_week || []).length > 0
    ? (record.recurrence_days_of_week || [])
    : [parseDateOnly(firstStart).getUTCDay()];
  const endType = record.recurrence_end_type || 'never';
  const endDate = record.recurrence_end_date || null;
  const maxOccurrences = endType === 'after' ? Number(record.recurrence_occurrences || 0) : null;

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

export function getOccurrenceStartDate(record: RecurringRecord, targetDate: string): string | null {
  const starts = getRecurrenceStartDatesUpTo(record, targetDate);
  const duration = Math.max(1, diffDays(record.start, record.end) + 1);

  for (let index = starts.length - 1; index >= 0; index -= 1) {
    const startDate = starts[index];
    const endDate = addDays(startDate, duration - 1);
    if (startDate <= targetDate && targetDate <= endDate) {
      return startDate;
    }
  }

  return null;
}

export function getOccurrenceDatesUpTo(record: RecurringRecord, targetDate: string): string[] {
  const starts = getRecurrenceStartDatesUpTo(record, targetDate);
  const duration = Math.max(1, diffDays(record.start, record.end) + 1);
  const dates: string[] = [];

  starts.forEach((startDate) => {
    const endDate = addDays(startDate, duration - 1);
    const cappedEnd = endDate > targetDate ? targetDate : endDate;
    let cursor = startDate;
    while (cursor <= cappedEnd) {
      dates.push(cursor);
      cursor = addDays(cursor, 1);
    }
  });

  return dates;
}

export function occursOnDate(record: RecurringRecord, date: string): boolean {
  return getOccurrenceStartDate(record, date) !== null;
}

export function recurrenceSummary(record: RecurringRecord): string {
  if (!record.recurrence_type || record.recurrence_type === 'none') return 'Does not repeat';
  if (record.recurrence_type === 'daily') return 'Daily';
  if (record.recurrence_type === 'weekly') return 'Weekly';
  if (record.recurrence_type === 'biweekly') return 'Bi-weekly';
  if (record.recurrence_type === 'annually') return 'Annually';

  const interval = Math.max(1, Number(record.recurrence_interval || 1));
  const unit = record.recurrence_unit || 'week';
  const everyLabel = interval === 1 ? unit : `${interval} ${unit}s`;
  const days = (record.recurrence_days_of_week || [])
    .map((day) => weekDays[day])
    .filter(Boolean)
    .join(', ');

  if (record.recurrence_end_type === 'on' && record.recurrence_end_date) {
    return `Every ${everyLabel}${days ? ` on ${days}` : ''}, until ${formatDisplayDate(record.recurrence_end_date)}`;
  }

  if (record.recurrence_end_type === 'after' && record.recurrence_occurrences) {
    return `Every ${everyLabel}${days ? ` on ${days}` : ''}, ${record.recurrence_occurrences} occurrences`;
  }

  return `Every ${everyLabel}${days ? ` on ${days}` : ''}`;
}

export function datesInRange(startDate: string, endDate: string): string[] {
  const dates: string[] = [];
  let cursor = startDate;
  while (cursor <= endDate) {
    dates.push(cursor);
    cursor = addDays(cursor, 1);
  }
  return dates;
}
