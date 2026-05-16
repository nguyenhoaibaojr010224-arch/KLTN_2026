/**
 * Format currency with smart abbreviation for large numbers.
 * >= 1 tỷ (1,000,000,000) → "1.7 tỷ đ"
 * < 1 tỷ → full format "1.234.567 ₫"
 */

const fullFormatter = new Intl.NumberFormat('vi-VN', {
  style: 'currency',
  currency: 'VND',
  maximumFractionDigits: 0,
});

export function formatCurrency(value) {
  const n = Number(value || 0);
  if (Math.abs(n) >= 1e9) {
    const ty = n / 1e9;
    // Show 1 decimal for < 10 tỷ, 0 decimal for >= 10 tỷ
    const formatted = Math.abs(ty) >= 10 ? Math.round(ty) : ty.toFixed(1);
    return `${formatted} tỷ đ`;
  }
  return fullFormatter.format(n);
}

/**
 * Short format for tight spaces (KPI cards, chart labels).
 * >= 1 tỷ → "1.7 tỷ"
 * >= 1 triệu → "45.3 tr"
 * >= 1 nghìn → "120k"
 */
export function formatCurrencyShort(value) {
  const n = Number(value || 0);
  if (Math.abs(n) >= 1e9) return (n / 1e9).toFixed(1) + ' tỷ';
  if (Math.abs(n) >= 1e6) return (n / 1e6).toFixed(1) + ' tr';
  if (Math.abs(n) >= 1e3) return Math.round(n / 1e3) + 'k';
  return n.toLocaleString('vi-VN');
}
