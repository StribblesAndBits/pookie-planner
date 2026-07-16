export const JULES_TITLE_COMING = 'Jules Coming Day';
export const JULES_TITLE_LEAVING = 'Jules Leaving Day';
export const JULES_TITLE_NO = 'No Jules Day';
export const JULES_TITLE_GENERAL = 'Jules Day';
export const JULES_TITLE_PICKUP_LEGACY = 'Jules Pickup Day';
export const JULES_TITLE_DROPOFF_LEGACY = 'Jules Dropoff Day';

export type JulesMarker = {
  label: string;
  className: 'jules-marker--coming' | 'jules-marker--leaving' | 'jules-marker--no-jules' | 'jules-marker--jules';
  order: number;
};

export type JulesMarkerKind = 'coming' | 'leaving' | 'no-jules' | 'jules';

function toHourMinute(time?: string | null): { hour: number; minute: number } | null {
  if (!time) return null;
  const match = time.match(/^(\d{2}):(\d{2})$/);
  if (!match) return null;
  const hour = Number(match[1]);
  const minute = Number(match[2]);
  if (Number.isNaN(hour) || Number.isNaN(minute) || hour > 23 || minute > 59) return null;
  return { hour, minute };
}

export function formatLongTime(time?: string | null): string {
  const parsed = toHourMinute(time);
  if (!parsed) return '';
  const suffix = parsed.hour >= 12 ? 'PM' : 'AM';
  const normalizedHour = parsed.hour % 12 === 0 ? 12 : parsed.hour % 12;
  return `${normalizedHour}:${String(parsed.minute).padStart(2, '0')} ${suffix}`;
}

export function normalizeJulesTitle(title: string): string {
  if (title === JULES_TITLE_PICKUP_LEGACY) return JULES_TITLE_GENERAL;
  if (title === JULES_TITLE_DROPOFF_LEGACY) return JULES_TITLE_GENERAL;
  if (title === JULES_TITLE_COMING || title === JULES_TITLE_LEAVING) return JULES_TITLE_GENERAL;
  return title;
}

export function isNoJulesTitle(title: string): boolean {
  return normalizeJulesTitle(title) === JULES_TITLE_NO;
}

export function describeJulesDay(title: string, comingTime?: string | null, leavingTime?: string | null): string {
  const normalized = normalizeJulesTitle(title);
  if (normalized === JULES_TITLE_NO) {
    return 'No Jules Day';
  }
  const coming = formatLongTime(comingTime);
  const leaving = formatLongTime(leavingTime);
  if (coming && leaving) {
    return `Jules Arriving at ${coming} • Leaving at ${leaving}`;
  }
  if (coming) {
    return `Jules Arriving at ${coming}`;
  }
  if (leaving) {
    return `Jules Leaving at ${leaving}`;
  }
  return normalized;
}

export function buildJulesMarker(kind: JulesMarkerKind, _time?: string | null): JulesMarker {
  if (kind === 'no-jules') {
    return { label: 'J', className: 'jules-marker--no-jules', order: 0 };
  }
  if (kind === 'coming') {
    return { label: 'J', className: 'jules-marker--coming', order: 1 };
  }
  if (kind === 'leaving') {
    return { label: 'J', className: 'jules-marker--leaving', order: 2 };
  }
  return { label: 'J', className: 'jules-marker--jules', order: 3 };
}
