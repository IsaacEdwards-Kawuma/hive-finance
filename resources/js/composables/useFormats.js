import { computed } from 'vue';

export function useFormats() {
  const locale = computed(() => localStorage.getItem('locale') || 'en');
  const dateFormat = computed(() => {
    const prefs = localStorage.getItem('preferences');
    if (prefs) try {
      const p = JSON.parse(prefs);
      return p.date_format || 'Y-m-d';
    } catch (_) {}
    return 'Y-m-d';
  });
  const numberFormat = computed(() => {
    const prefs = localStorage.getItem('preferences');
    if (prefs) try {
      const p = JSON.parse(prefs);
      return p.number_format || '1,234.56';
    } catch (_) {}
    return '1,234.56';
  });

  function formatDate(value) {
    if (!value) return '';
    const d = new Date(value);
    if (isNaN(d.getTime())) return value;
    const fmt = dateFormat.value;
    const pad = (n) => String(n).padStart(2, '0');
    const y = d.getFullYear(), m = pad(d.getMonth() + 1), day = pad(d.getDate());
    if (fmt === 'd/m/Y') return `${day}/${m}/${y}`;
    if (fmt === 'm/d/Y') return `${m}/${day}/${y}`;
    if (fmt === 'd.m.Y') return `${day}.${m}.${y}`;
    return `${y}-${m}-${day}`;
  }

  function formatNumber(value, options = {}) {
    const n = Number(value);
    if (Number.isNaN(n)) return '';
    const useCurrency = options.currency === true || typeof options.currency === 'string';
    const style = useCurrency ? 'currency' : 'decimal';
    const opts = {
      style,
      minimumFractionDigits: options.minFraction ?? 2,
      maximumFractionDigits: options.maxFraction ?? 2,
    };
    if (style === 'currency') {
      opts.currency = typeof options.currency === 'string' ? options.currency : 'USD';
    }
    return new Intl.NumberFormat(locale.value.replace('_', '-'), opts).format(n);
  }

  return { locale, formatDate, formatNumber };
}
