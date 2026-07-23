export const JULES_TYPE_ARRIVING = 'arriving';
export const JULES_TYPE_LEAVING = 'leaving';
export const JULES_TYPE_HERE = 'here';
export const JULES_TYPE_GONE = 'gone';

export type JulesMarker = {
  label: string;
  className: 'jules-marker--arriving' | 'jules-marker--leaving' | 'jules-marker--here' | 'jules-marker--gone';
  order: number;
};

export type JulesMarkerKind = 'arriving' | 'leaving' | 'here' | 'gone';

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

export function describeJulesDay(type: string, comingTime?: string | null, leavingTime?: string | null): string {
  if (type === JULES_TYPE_GONE) {
    return 'No Jules';
  }
  if (type === JULES_TYPE_ARRIVING) {
    const time = formatLongTime(comingTime);
    return time ? `Jules Arriving at ${time}` : 'Jules Arriving';
  }
  if (type === JULES_TYPE_LEAVING) {
    const time = formatLongTime(leavingTime);
    return time ? `Jules Leaving at ${time}` : 'Jules Leaving';
  }
  const coming = formatLongTime(comingTime);
  const leaving = formatLongTime(leavingTime);
  if (coming && leaving) {
    return `Jules Here (${coming} - ${leaving})`;
  }
  if (coming) {
    return `Jules Here (from ${coming})`;
  }
  if (leaving) {
    return `Jules Here (until ${leaving})`;
  }
  return 'Jules Here';
}

export function buildJulesMarker(type: string, _time?: string | null): JulesMarker {
  if (type === JULES_TYPE_GONE) {
    return { label: 'J', className: 'jules-marker--gone', order: 0 };
  }
  if (type === JULES_TYPE_ARRIVING) {
    return { label: 'J', className: 'jules-marker--arriving', order: 1 };
  }
  if (type === JULES_TYPE_LEAVING) {
    return { label: 'J', className: 'jules-marker--leaving', order: 2 };
  }
  return { label: 'J', className: 'jules-marker--here', order: 3 };
}
