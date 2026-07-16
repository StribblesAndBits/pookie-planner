export const UTILITY_CURRENCIES = ['dollars', 'kisses'] as const;

export type UtilityCurrency = (typeof UTILITY_CURRENCIES)[number];

export function normalizeUtilityCurrency(value?: string | null): UtilityCurrency {
  return value === 'kisses' ? 'kisses' : 'dollars';
}

export function utilityCurrencyIcon(value?: string | null): string {
  return normalizeUtilityCurrency(value) === 'kisses' ? '💋' : '$';
}

export function utilityCurrencyLabel(value?: string | null): string {
  return normalizeUtilityCurrency(value) === 'kisses' ? 'Kisses' : 'Dollars';
}

export function formatUtilityAmount(amount: string | number, currency?: string | null): string {
  const symbol = utilityCurrencyIcon(currency);
  const formattedAmount = new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number(amount));

  return `${symbol}${formattedAmount}`;
}
