<template>
  <div class="space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">{{ t('Financial dashboard') }}</h1>
        <p class="text-slate-500 text-sm mt-0.5">{{ t('Revenue, AR, AP, cash flow, and reports for finance teams') }}</p>
      </div>
      <div class="flex flex-wrap gap-2 items-center">
        <span class="text-sm text-slate-500">{{ t('Date range') }}:</span>
        <input v-model="dateFrom" type="date" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm" />
        <span class="text-slate-400">–</span>
        <input v-model="dateTo" type="date" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm" />
        <button type="button" @click="loadSummary" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-sm">{{ t('Apply') }}</button>
      </div>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="flex flex-col items-center gap-3 text-slate-500">
        <svg class="w-10 h-10 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
        <span>{{ t('Loading dashboard…') }}</span>
      </div>
    </div>

    <template v-else>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm overflow-hidden relative">
          <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500" />
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">{{ t('Revenue (YTD)') }}</p>
              <p class="text-2xl font-bold text-slate-800 mt-1">{{ formatMoney(summary.revenue_ytd) }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm overflow-hidden relative">
          <div class="absolute top-0 left-0 w-1 h-full bg-blue-500" />
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">{{ t('Outstanding AR') }}</p>
              <p class="text-2xl font-bold text-slate-800 mt-1">{{ formatMoney(summary.outstanding_ar) }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            </div>
          </div>
          <router-link to="/reports/ar-aging" class="text-sm text-blue-600 hover:text-blue-700 font-medium mt-3 inline-block">{{ t('View AR aging') }} →</router-link>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm overflow-hidden relative">
          <div class="absolute top-0 left-0 w-1 h-full bg-amber-500" />
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">{{ t('Outstanding AP') }}</p>
              <p class="text-2xl font-bold text-slate-800 mt-1">{{ formatMoney(summary.outstanding_ap) }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
            </div>
          </div>
          <router-link to="/reports/ap-aging" class="text-sm text-amber-600 hover:text-amber-700 font-medium mt-3 inline-block">{{ t('View AP aging') }} →</router-link>
        </div>
        <router-link to="/investments" class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm overflow-hidden relative block">
          <div class="absolute top-0 left-0 w-1 h-full bg-violet-500" />
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">{{ t('Portfolio value') }}</p>
              <p class="text-2xl font-bold text-slate-800 mt-1">{{ formatMoney(investmentSummary.total_current_value) }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-violet-50 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
            </div>
          </div>
          <span class="text-sm text-violet-600 hover:text-violet-700 font-medium mt-3 inline-block">{{ t('View investments') }} →</span>
        </router-link>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
          <h3 class="text-sm font-semibold text-slate-800 mb-4">{{ t('Cash flow overview') }}</h3>
          <div class="h-72 flex items-center justify-center">
            <Doughnut v-if="cashFlowChartData.labels.length" :data="cashFlowChartData" :options="doughnutOptions" />
            <p v-else class="text-slate-500 text-sm">{{ t('No data to display') }}</p>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
          <h3 class="text-sm font-semibold text-slate-800 mb-4">{{ t('Recent invoices & bills') }}</h3>
          <div class="h-72">
            <Bar v-if="recentActivityChartData.labels.length" :data="recentActivityChartData" :options="barOptions" />
            <p v-else class="text-slate-500 text-sm flex items-center justify-center h-full">{{ t('No data to display') }}</p>
          </div>
        </div>
      </div>

      <div class="bg-slate-50 rounded-xl border border-slate-200 p-5">
        <h3 class="text-sm font-medium text-slate-500 uppercase tracking-wide mb-4">{{ t('Reports') }}</h3>
        <div class="flex flex-wrap gap-3">
          <router-link to="/reports/profit-loss" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:border-slate-300 hover:shadow-sm transition-all">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
            {{ t('Profit & Loss') }}
          </router-link>
          <router-link to="/reports/balance-sheet" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:border-slate-300 hover:shadow-sm transition-all">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
            {{ t('Balance Sheet') }}
          </router-link>
          <router-link to="/reports/cash-flow" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:border-slate-300 hover:shadow-sm transition-all">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            {{ t('Cash flow') }}
          </router-link>
          <router-link to="/reports/tax-summary" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:border-slate-300 hover:shadow-sm transition-all">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" /></svg>
            {{ t('Tax summary') }}
          </router-link>
          <router-link to="/reports/ar-aging" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:border-slate-300 hover:shadow-sm transition-all">
            {{ t('AR aging') }}
          </router-link>
          <router-link to="/reports/ap-aging" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:border-slate-300 hover:shadow-sm transition-all">
            {{ t('AP aging') }}
          </router-link>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Bar, Doughnut } from 'vue-chartjs';
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
} from 'chart.js';
import { api } from '../api';
import { useFormats } from '../composables/useFormats';
import { useI18n } from '../i18n';

ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title);

const summary = ref({
  revenue_ytd: 0,
  outstanding_ar: 0,
  outstanding_ap: 0,
  recent_invoices: [],
  recent_bills: [],
});
const investmentSummary = ref({ total_current_value: 0 });
const loading = ref(true);
const dateFrom = ref(new Date().getFullYear() + '-01-01');
const dateTo = ref(new Date().toISOString().slice(0, 10));
const { formatNumber } = useFormats();
const { t } = useI18n();

function formatMoney(n) {
  return formatNumber(n ?? 0, { currency: true });
}

const cashFlowChartData = computed(() => {
  const rev = summary.value.revenue_ytd ?? 0;
  const ar = summary.value.outstanding_ar ?? 0;
  const ap = summary.value.outstanding_ap ?? 0;
  const total = rev + ar + ap;
  if (total === 0) return { labels: [], datasets: [] };
  const labels = [t('Revenue (YTD)'), t('Outstanding AR'), t('Outstanding AP')];
  const values = [rev, ar, ap];
  const colors = ['rgb(16, 185, 129)', 'rgb(59, 130, 246)', 'rgb(245, 158, 11)'];
  return {
    labels,
    datasets: [{ data: values, backgroundColor: colors, borderColor: 'rgb(255,255,255)', borderWidth: 2 }],
  };
});

const recentActivityChartData = computed(() => {
  const invs = summary.value.recent_invoices ?? [];
  const bills = summary.value.recent_bills ?? [];
  const maxLen = Math.max(invs.length, bills.length);
  if (maxLen === 0) return { labels: [], datasets: [] };
  const labels = [];
  const invoiceAmounts = [];
  const billAmounts = [];
  for (let i = 0; i < maxLen; i++) {
    labels.push(
      invs[i] ? (invs[i].invoice_number || `Inv #${i + 1}`) : (bills[i] ? (bills[i].bill_number || `Bill #${i + 1}`) : `#${i + 1}`)
    );
    invoiceAmounts.push(invs[i] ? parseFloat(invs[i].total) || 0 : 0);
    billAmounts.push(bills[i] ? parseFloat(bills[i].total) || 0 : 0);
  }
  return {
    labels,
    datasets: [
      { label: t('Invoices'), data: invoiceAmounts, backgroundColor: 'rgb(16, 185, 129)', borderRadius: 6 },
      { label: t('Bills'), data: billAmounts, backgroundColor: 'rgb(245, 158, 11)', borderRadius: 6 },
    ],
  };
});

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'bottom' },
    tooltip: {
      callbacks: {
        label: (ctx) => {
          const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
          const pct = total ? ((ctx.raw / total) * 100).toFixed(1) : 0;
          return ` ${ctx.label}: ${formatMoney(ctx.raw)} (${pct}%)`;
        },
      },
    },
  },
};

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'top' },
    tooltip: {
      callbacks: {
        label: (ctx) => ` ${ctx.dataset.label}: ${formatMoney(ctx.raw)}`,
      },
    },
  },
  scales: {
    x: { grid: { color: 'rgba(0,0,0,0.06)' } },
    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.06)' } },
  },
};

async function loadSummary() {
  loading.value = true;
  try {
    const params = {};
    if (dateFrom.value) params.from = dateFrom.value;
    if (dateTo.value) params.to = dateTo.value;
    const [summaryRes, invRes] = await Promise.all([
      api().get('/dashboard/summary', { params }),
      api().get('/investments/summary').catch(() => ({ data: {} })),
    ]);
    summary.value = summaryRes.data.data ?? summary.value;
    const invData = invRes.data?.data ?? invRes.data;
    investmentSummary.value = { total_current_value: invData?.total_current_value ?? 0 };
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
}

onMounted(loadSummary);
</script>
