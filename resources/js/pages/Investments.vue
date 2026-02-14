<template>
  <div>
    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
      <h1 class="text-2xl font-bold text-slate-800">{{ t('Investments') }}</h1>
      <div class="flex gap-2">
        <button
          type="button"
          @click="exportCSV"
          class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm"
        >
          {{ t('Export CSV') }}
        </button>
        <button
          v-if="can('investments.create')"
          @click="openForm()"
          class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm"
        >
          {{ t('Add investment') }}
        </button>
      </div>
    </div>

    <!-- Summary cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg border border-slate-200 p-4">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">{{ t('Total cost basis') }}</p>
        <p class="text-xl font-semibold text-slate-800 mt-1">{{ formatMoney(summary.total_cost_basis) }}</p>
      </div>
      <div class="bg-white rounded-lg border border-slate-200 p-4">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">{{ t('Total current value') }}</p>
        <p class="text-xl font-semibold text-slate-800 mt-1">{{ formatMoney(summary.total_current_value) }}</p>
      </div>
      <div class="bg-white rounded-lg border border-slate-200 p-4">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">{{ t('Total gain/loss') }}</p>
        <p :class="['text-xl font-semibold mt-1', summary.total_gain_loss >= 0 ? 'text-green-600' : 'text-red-600']">
          {{ formatMoney(summary.total_gain_loss) }}
          <span v-if="summary.total_gain_loss_percent != null" class="text-sm font-normal">
            ({{ summary.total_gain_loss_percent >= 0 ? '+' : '' }}{{ summary.total_gain_loss_percent }}%)
          </span>
        </p>
      </div>
      <div class="bg-white rounded-lg border border-slate-200 p-4">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">{{ t('Status') }}</p>
        <p class="text-xl font-semibold text-slate-800 mt-1">{{ summary.count }} {{ summary.count === 1 ? 'investment' : 'investments' }}</p>
      </div>
    </div>

    <!-- Charts -->
    <div v-if="!loading && (investments.length > 0 || summary.total_current_value > 0)" class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
      <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-800 mb-4">{{ t('Allocation by type') }}</h3>
        <div class="h-72 flex items-center justify-center">
          <Doughnut v-if="allocationByTypeData.labels.length" :data="allocationByTypeData" :options="doughnutOptions" />
          <p v-else class="text-slate-500 text-sm">{{ t('No data to display') }}</p>
        </div>
      </div>
      <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-800 mb-4">{{ t('Top holdings by value') }}</h3>
        <div class="h-72">
          <Bar v-if="topHoldingsData.labels.length" :data="topHoldingsData" :options="barOptions" />
          <p v-else class="text-slate-500 text-sm flex items-center justify-center h-full">{{ t('No data to display') }}</p>
        </div>
      </div>
      <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-800 mb-4">{{ t('Cost vs value') }}</h3>
        <div class="h-72">
          <Bar v-if="costVsValueData.labels.length" :data="costVsValueData" :options="costVsValueBarOptions" />
          <p v-else class="text-slate-500 text-sm flex items-center justify-center h-full">{{ t('No data to display') }}</p>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg border border-slate-200 p-4 mb-6">
      <div class="flex flex-wrap items-end gap-4">
        <div class="min-w-[180px]">
          <label class="block text-xs font-medium text-slate-500 mb-1">{{ t('Type') }}</label>
          <select v-model="filters.type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="all">{{ t('All types') }}</option>
            <option value="stock">{{ t('Stock') }}</option>
            <option value="bond">{{ t('Bond') }}</option>
            <option value="mutual_fund">{{ t('Mutual fund') }}</option>
            <option value="real_estate">{{ t('Real estate') }}</option>
            <option value="crypto">{{ t('Crypto') }}</option>
            <option value="other">{{ t('Other') }}</option>
          </select>
        </div>
        <div class="flex-1 min-w-[200px]">
          <label class="block text-xs font-medium text-slate-500 mb-1">{{ t('Search investments') }}</label>
          <input
            v-model="filters.search"
            type="text"
            :placeholder="t('Name') + ' / ' + t('Reference')"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
          />
        </div>
        <button type="button" @click="loadInvestments" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm">
          {{ t('Run') }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-slate-500">{{ t('Loading') }}</div>
    <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium cursor-pointer hover:bg-slate-100" @click="setSort('name')">
              {{ t('Name') }} <span v-if="sortKey === 'name'" class="text-slate-400">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th class="text-left px-4 py-3 font-medium w-28 cursor-pointer hover:bg-slate-100" @click="setSort('type')">
              {{ t('Type') }} <span v-if="sortKey === 'type'" class="text-slate-400">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th class="text-left px-4 py-3 font-medium w-24">{{ t('Symbol') }}</th>
            <th class="text-right px-4 py-3 font-medium w-32 cursor-pointer hover:bg-slate-100" @click="setSort('cost_basis')">
              {{ t('Cost basis') }} <span v-if="sortKey === 'cost_basis'" class="text-slate-400">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th class="text-right px-4 py-3 font-medium w-32 cursor-pointer hover:bg-slate-100" @click="setSort('current_value')">
              {{ t('Current value') }} <span v-if="sortKey === 'current_value'" class="text-slate-400">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th class="text-right px-4 py-3 font-medium w-28 cursor-pointer hover:bg-slate-100" @click="setSort('gain_loss')">
              {{ t('Gain/Loss') }} <span v-if="sortKey === 'gain_loss'" class="text-slate-400">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th class="text-left px-4 py-3 font-medium w-28 cursor-pointer hover:bg-slate-100" @click="setSort('date_acquired')">
              {{ t('Date acquired') }} <span v-if="sortKey === 'date_acquired'" class="text-slate-400">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th class="text-left px-4 py-3 font-medium w-40">{{ t('Account') }}</th>
            <th class="text-right px-4 py-3 font-medium w-36">{{ t('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="inv in sortedInvestments" :key="inv.id" class="hover:bg-slate-50 cursor-pointer" @click="openDetail(inv)">
            <td class="px-4 py-3 font-medium">{{ inv.name }}</td>
            <td class="px-4 py-3">{{ typeLabel(inv.type) }}</td>
            <td class="px-4 py-3">{{ inv.symbol || '—' }}</td>
            <td class="px-4 py-3 text-right font-mono">{{ formatMoney(inv.cost_basis) }}</td>
            <td class="px-4 py-3 text-right font-mono">{{ formatMoney(inv.current_value) }}</td>
            <td class="px-4 py-3 text-right font-mono" :class="(inv.gain_loss || 0) >= 0 ? 'text-green-600' : 'text-red-600'">
              {{ formatMoney(inv.gain_loss) }}
              <span v-if="inv.gain_loss_percent != null" class="text-xs">({{ inv.gain_loss_percent >= 0 ? '+' : '' }}{{ inv.gain_loss_percent }}%)</span>
            </td>
            <td class="px-4 py-3">{{ inv.date_acquired ? formatDate(inv.date_acquired) : '—' }}</td>
            <td class="px-4 py-3 text-slate-600">{{ inv.account ? inv.account.code + ' ' + inv.account.name : '—' }}</td>
            <td class="px-4 py-3 text-right space-x-2" @click.stop>
              <button type="button" @click="openDetail(inv)" class="text-slate-600 hover:text-slate-800">{{ t('View details') }}</button>
              <button
                v-if="can('investments.edit')"
                type="button"
                @click="openForm(inv)"
                class="text-slate-600 hover:text-slate-800"
              >
                {{ t('Edit') }}
              </button>
              <button
                v-if="can('investments.delete')"
                type="button"
                @click="confirmDelete(inv)"
                class="text-red-600 hover:text-red-800"
              >
                {{ t('Delete') }}
              </button>
            </td>
          </tr>
          <tr v-if="!investments.length">
            <td colspan="9" class="px-4 py-8 text-center text-slate-500">{{ t('No investments yet.') }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Detail drawer -->
    <div v-if="detailInvestment" class="fixed inset-0 z-30 flex justify-end">
      <div class="absolute inset-0 bg-black/50" @click="closeDetail"></div>
      <div class="relative w-full max-w-lg bg-white shadow-xl overflow-y-auto flex flex-col">
        <div class="p-6 border-b border-slate-200 flex justify-between items-start">
          <div>
            <h2 class="text-xl font-semibold text-slate-800">{{ detailInvestment.name }}</h2>
            <p class="text-sm text-slate-500 mt-0.5">{{ typeLabel(detailInvestment.type) }} {{ detailInvestment.symbol ? '· ' + detailInvestment.symbol : '' }}</p>
          </div>
          <button type="button" @click="closeDetail" class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
        </div>
        <div class="p-6 space-y-6 flex-1">
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="text-slate-500">{{ t('Cost basis') }}</span><p class="font-medium">{{ formatMoney(detailInvestment.cost_basis) }}</p></div>
            <div><span class="text-slate-500">{{ t('Current value') }}</span><p class="font-medium">{{ formatMoney(detailInvestment.current_value) }}</p></div>
            <div><span class="text-slate-500">{{ t('Gain/Loss') }}</span>
              <p :class="['font-medium', (detailInvestment.gain_loss || 0) >= 0 ? 'text-green-600' : 'text-red-600']">
                {{ formatMoney(detailInvestment.gain_loss) }}
                <span v-if="detailInvestment.gain_loss_percent != null">({{ detailInvestment.gain_loss_percent >= 0 ? '+' : '' }}{{ detailInvestment.gain_loss_percent }}%)</span>
              </p>
            </div>
            <div><span class="text-slate-500">{{ t('Date acquired') }}</span><p class="font-medium">{{ detailInvestment.date_acquired ? formatDate(detailInvestment.date_acquired) : '—' }}</p></div>
            <div class="col-span-2"><span class="text-slate-500">{{ t('Account') }}</span><p class="font-medium">{{ detailInvestment.account ? detailInvestment.account.code + ' ' + detailInvestment.account.name : '—' }}</p></div>
            <div v-if="detailInvestment.notes" class="col-span-2"><span class="text-slate-500">{{ t('Description') }}</span><p class="mt-1 text-slate-700 whitespace-pre-wrap">{{ detailInvestment.notes }}</p></div>
          </div>
          <div class="pt-4 border-t border-slate-200">
            <div class="flex justify-between items-center mb-3">
              <h3 class="font-medium text-slate-800">{{ t('Activity') }}</h3>
              <button v-if="can('investments.edit')" type="button" @click="editFromDetail" class="text-sm text-slate-600 hover:text-slate-800">{{ t('Edit') }}</button>
            </div>
            <form v-if="can('investments.edit')" @submit.prevent="addTransaction" class="mb-4 p-3 bg-slate-50 rounded-lg space-y-2 text-sm">
              <div class="grid grid-cols-2 gap-2">
                <input v-model="txForm.trans_date" type="date" required class="rounded border border-slate-300 px-2 py-1" />
                <select v-model="txForm.type" required class="rounded border border-slate-300 px-2 py-1">
                  <option value="buy">{{ t('Buy') }}</option>
                  <option value="sell">{{ t('Sell') }}</option>
                  <option value="dividend">{{ t('Dividend') }}</option>
                  <option value="adjustment">{{ t('Adjustment') }}</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div class="grid grid-cols-3 gap-2">
                <input v-model.number="txForm.quantity" type="number" step="any" min="0" placeholder="Qty" class="rounded border border-slate-300 px-2 py-1" />
                <input v-model.number="txForm.price_per_unit" type="number" step="0.01" min="0" :placeholder="t('Price per unit')" class="rounded border border-slate-300 px-2 py-1" />
                <input v-model.number="txForm.amount" type="number" step="0.01" :placeholder="t('Amount')" class="rounded border border-slate-300 px-2 py-1" />
              </div>
              <input v-model="txForm.note" type="text" :placeholder="t('Note')" class="w-full rounded border border-slate-300 px-2 py-1" />
              <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white rounded text-sm" :disabled="txSaving">{{ txSaving ? '…' : t('Add transaction') }}</button>
            </form>
            <div v-if="detailTransactionsLoading" class="text-slate-500 text-sm">{{ t('Loading') }}</div>
            <ul v-else-if="detailTransactions.length" class="space-y-2">
              <li v-for="tx in detailTransactions" :key="tx.id" class="flex justify-between items-center text-sm py-2 border-b border-slate-100">
                <span>{{ formatDate(tx.trans_date) }} · {{ txTypeLabel(tx.type) }}{{ tx.quantity != null ? ' ' + tx.quantity : '' }}{{ tx.amount != null ? ' ' + formatMoney(tx.amount) : '' }}{{ tx.note ? ' · ' + tx.note : '' }}</span>
                <button v-if="can('investments.edit')" type="button" @click="deleteTransaction(tx)" class="text-red-600 hover:text-red-800 text-xs">{{ t('Delete') }}</button>
              </li>
            </ul>
            <p v-else class="text-slate-500 text-sm">{{ t('No activity yet.') }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit modal -->
    <div v-if="formOpen" class="fixed inset-0 bg-black/50 flex items-center justify-center z-20 p-4" @click.self="formOpen = false">
      <div class="bg-white rounded-xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-6">
        <h2 class="font-semibold text-lg mb-4">{{ editingId ? t('Edit investment') : t('New investment') }}</h2>
        <form @submit.prevent="saveInvestment" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Name') }} *</label>
            <input v-model="form.name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Type') }} *</label>
            <select v-model="form.type" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="stock">{{ t('Stock') }}</option>
              <option value="bond">{{ t('Bond') }}</option>
              <option value="mutual_fund">{{ t('Mutual fund') }}</option>
              <option value="real_estate">{{ t('Real estate') }}</option>
              <option value="crypto">{{ t('Crypto') }}</option>
              <option value="other">{{ t('Other') }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Symbol') }}</label>
            <input v-model="form.symbol" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="e.g. AAPL" />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Cost basis') }} *</label>
              <input v-model.number="form.cost_basis" type="number" step="0.01" min="0" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Current value') }} *</label>
              <input v-model.number="form.current_value" type="number" step="0.01" min="0" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Date acquired') }}</label>
              <input v-model="form.date_acquired" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Account') }}</label>
              <select v-model="form.account_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                <option value="">{{ t('Select account') }}</option>
                <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.code }} – {{ a.name }}</option>
              </select>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Description') }}</label>
            <textarea v-model="form.notes" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
          </div>
          <p v-if="formError" class="text-sm text-red-600">{{ formError }}</p>
          <div class="flex gap-2 pt-2">
            <button type="button" @click="formOpen = false" class="px-4 py-2 border border-slate-300 rounded-lg">{{ t('Cancel') }}</button>
            <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg" :disabled="saving">{{ saving ? '…' : t('Save') }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
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
import { useToast } from '../composables/useToast';
import { usePermissions } from '../composables/usePermissions';

ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title);

const { formatNumber, formatDate } = useFormats();
const { t } = useI18n();
const toast = useToast();
const { can } = usePermissions();

const investments = ref([]);
const loading = ref(true);
const formOpen = ref(false);
const editingId = ref(null);
const saving = ref(false);
const formError = ref('');
const summary = reactive({
  total_cost_basis: 0,
  total_current_value: 0,
  total_gain_loss: 0,
  total_gain_loss_percent: null,
  count: 0,
});
const filters = ref({ type: 'all', search: '' });
const sortKey = ref('name');
const sortOrder = ref('asc');
const detailInvestment = ref(null);
const detailTransactions = ref([]);
const detailTransactionsLoading = ref(false);
const txForm = ref({ trans_date: new Date().toISOString().slice(0, 10), type: 'buy', quantity: null, price_per_unit: null, amount: null, note: '' });
const txSaving = ref(false);
const form = ref({
  name: '',
  type: 'stock',
  symbol: '',
  cost_basis: 0,
  current_value: 0,
  currency: 'USD',
  date_acquired: '',
  account_id: '',
  notes: '',
});
const accounts = ref([]);

const sortedInvestments = computed(() => {
  const list = [...investments.value];
  const key = sortKey.value;
  const order = sortOrder.value === 'asc' ? 1 : -1;
  list.sort((a, b) => {
    let va = a[key];
    let vb = b[key];
    if (key === 'name' || key === 'type') {
      va = (va || '').toString().toLowerCase();
      vb = (vb || '').toString().toLowerCase();
      return order * (va < vb ? -1 : va > vb ? 1 : 0);
    }
    if (key === 'date_acquired') {
      va = va ? new Date(va).getTime() : 0;
      vb = vb ? new Date(vb).getTime() : 0;
      return order * (va - vb);
    }
    va = parseFloat(va) || 0;
    vb = parseFloat(vb) || 0;
    return order * (va - vb);
  });
  return list;
});

function setSort(key) {
  if (sortKey.value === key) sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  else { sortKey.value = key; sortOrder.value = 'asc'; }
}

const typeLabels = {
  stock: 'Stock',
  bond: 'Bond',
  mutual_fund: 'Mutual fund',
  real_estate: 'Real estate',
  crypto: 'Crypto',
  other: 'Other',
};
function typeLabel(type) {
  return t(typeLabels[type] || type);
}

const CHART_COLORS = [
  'rgb(59, 130, 246)',   // blue
  'rgb(16, 185, 129)',   // emerald
  'rgb(245, 158, 11)',   // amber
  'rgb(139, 92, 246)',   // violet
  'rgb(236, 72, 153)',   // pink
  'rgb(20, 184, 166)',   // teal
  'rgb(251, 146, 60)',   // orange
  'rgb(99, 102, 241)',   // indigo
];

const allocationByTypeData = computed(() => {
  const byType = {};
  investments.value.forEach(inv => {
    const type = inv.type || 'other';
    byType[type] = (byType[type] || 0) + parseFloat(inv.current_value || 0);
  });
  const labels = Object.keys(byType).map(k => typeLabel(k));
  const values = Object.values(byType);
  const colors = CHART_COLORS.slice(0, labels.length);
  return {
    labels,
    datasets: [{ data: values, backgroundColor: colors, borderColor: 'rgb(255,255,255)', borderWidth: 2 }],
  };
});

const topHoldingsData = computed(() => {
  const sorted = [...investments.value]
    .sort((a, b) => (parseFloat(b.current_value) || 0) - (parseFloat(a.current_value) || 0))
    .slice(0, 8);
  return {
    labels: sorted.map(inv => inv.name || inv.symbol || '—'),
    datasets: [{
      label: t('Current value'),
      data: sorted.map(inv => parseFloat(inv.current_value) || 0),
      backgroundColor: CHART_COLORS[0],
      borderRadius: 6,
    }],
  };
});

const costVsValueData = computed(() => {
  const sorted = [...investments.value]
    .sort((a, b) => (parseFloat(b.current_value) || 0) - (parseFloat(a.current_value) || 0))
    .slice(0, 6);
  return {
    labels: sorted.map(inv => inv.name || inv.symbol || '—'),
    datasets: [
      { label: t('Cost basis'), data: sorted.map(inv => parseFloat(inv.cost_basis) || 0), backgroundColor: 'rgb(148, 163, 184)', borderRadius: 6 },
      { label: t('Current value'), data: sorted.map(inv => parseFloat(inv.current_value) || 0), backgroundColor: CHART_COLORS[0], borderRadius: 6 },
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
          return ` ${ctx.label}: ${formatNumber(ctx.raw, { currency: true, minFraction: 2 })} (${pct}%)`;
        },
      },
    },
  },
};

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  indexAxis: 'y',
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label: (ctx) => ` ${formatNumber(ctx.raw, { currency: true, minFraction: 2 })}`,
      },
    },
  },
  scales: {
    x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.06)' } },
    y: { grid: { display: false } },
  },
};

const costVsValueBarOptions = {
  responsive: true,
  maintainAspectRatio: false,
  indexAxis: 'y',
  plugins: {
    legend: { position: 'top' },
    tooltip: {
      callbacks: {
        label: (ctx) => ` ${ctx.dataset.label}: ${formatNumber(ctx.raw, { currency: true, minFraction: 2 })}`,
      },
    },
  },
  scales: {
    x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.06)' } },
    y: { grid: { display: false } },
  },
};

const txTypeLabels = { buy: 'Buy', sell: 'Sell', dividend: 'Dividend', adjustment: 'Adjustment', other: 'Other' };
function txTypeLabel(type) {
  return t(txTypeLabels[type] || type);
}

function formatMoney(n) {
  return formatNumber(parseFloat(n) ?? 0, { minFraction: 2 });
}

function exportCSV() {
  const list = sortedInvestments.value;
  const headers = [t('Name'), t('Type'), t('Symbol'), t('Cost basis'), t('Current value'), t('Gain/Loss'), t('Date acquired')];
  const rows = list.map(inv => [
    inv.name,
    typeLabel(inv.type),
    inv.symbol || '',
    String(inv.cost_basis ?? ''),
    String(inv.current_value ?? ''),
    String(inv.gain_loss ?? ''),
    inv.date_acquired ? formatDate(inv.date_acquired) : '',
  ]);
  const csv = [headers.join(','), ...rows.map(r => r.map(c => '"' + String(c).replace(/"/g, '""') + '"').join(','))].join('\r\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'investments.csv';
  a.click();
  URL.revokeObjectURL(a.href);
}

async function openDetail(inv) {
  detailInvestment.value = inv || null;
  detailTransactions.value = [];
  if (!inv) return;
  detailTransactionsLoading.value = true;
  try {
    const { data } = await api().get(`/investments/${inv.id}/transactions`);
    detailTransactions.value = data.data || data || [];
  } catch (_) {
    detailTransactions.value = [];
  } finally {
    detailTransactionsLoading.value = false;
  }
  txForm.value = { trans_date: new Date().toISOString().slice(0, 10), type: 'buy', quantity: null, price_per_unit: null, amount: null, note: '' };
}

function closeDetail() {
  detailInvestment.value = null;
  detailTransactions.value = [];
}

function editFromDetail() {
  const inv = detailInvestment.value;
  closeDetail();
  if (inv) openForm(inv);
}

async function addTransaction() {
  if (!detailInvestment.value) return;
  txSaving.value = true;
  try {
    await api().post(`/investments/${detailInvestment.value.id}/transactions`, {
      trans_date: txForm.value.trans_date,
      type: txForm.value.type,
      quantity: txForm.value.quantity || null,
      price_per_unit: txForm.value.price_per_unit || null,
      amount: txForm.value.amount || null,
      note: txForm.value.note || null,
    });
    toast.show('Transaction added.', 'success');
    txForm.value = { trans_date: new Date().toISOString().slice(0, 10), type: 'buy', quantity: null, price_per_unit: null, amount: null, note: '' };
    const { data } = await api().get(`/investments/${detailInvestment.value.id}/transactions`);
    detailTransactions.value = data.data || data || [];
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to add transaction', 'error');
  } finally {
    txSaving.value = false;
  }
}

async function deleteTransaction(tx) {
  if (!detailInvestment.value) return;
  try {
    await api().delete(`/investments/${detailInvestment.value.id}/transactions/${tx.id}`);
    detailTransactions.value = detailTransactions.value.filter(t => t.id !== tx.id);
    toast.show('Transaction deleted.', 'success');
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to delete', 'error');
  }
}

async function loadSummary() {
  try {
    const { data } = await api().get('/investments/summary');
    const d = data.data || data;
    Object.assign(summary, {
      total_cost_basis: d.total_cost_basis ?? 0,
      total_current_value: d.total_current_value ?? 0,
      total_gain_loss: d.total_gain_loss ?? 0,
      total_gain_loss_percent: d.total_gain_loss_percent ?? null,
      count: d.count ?? 0,
    });
  } catch (_) {}
}

async function loadInvestments() {
  loading.value = true;
  try {
    const params = { per_page: 100 };
    if (filters.value.type !== 'all') params.type = filters.value.type;
    if ((filters.value.search || '').trim()) params.search = filters.value.search.trim();
    const { data } = await api().get('/investments', { params });
    investments.value = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []);
  } catch (e) {
    console.error(e);
    investments.value = [];
  } finally {
    loading.value = false;
  }
}

async function loadAccounts() {
  try {
    const { data } = await api().get('/accounts?per_page=500');
    accounts.value = data.data || data || [];
  } catch (_) {
    accounts.value = [];
  }
}

function openForm(inv = null) {
  editingId.value = inv ? inv.id : null;
  form.value = {
    name: inv?.name ?? '',
    type: inv?.type ?? 'stock',
    symbol: inv?.symbol ?? '',
    cost_basis: inv ? parseFloat(inv.cost_basis) : 0,
    current_value: inv ? parseFloat(inv.current_value) : 0,
    currency: inv?.currency ?? 'USD',
    date_acquired: inv?.date_acquired ? String(inv.date_acquired).slice(0, 10) : '',
    account_id: inv?.account_id ?? '',
    notes: inv?.notes ?? '',
  };
  formError.value = '';
  formOpen.value = true;
}

async function saveInvestment() {
  formError.value = '';
  saving.value = true;
  try {
    const payload = {
      name: form.value.name,
      type: form.value.type,
      symbol: form.value.symbol || null,
      cost_basis: form.value.cost_basis,
      current_value: form.value.current_value,
      currency: form.value.currency,
      date_acquired: form.value.date_acquired || null,
      account_id: form.value.account_id || null,
      notes: form.value.notes || null,
    };
    if (editingId.value) {
      await api().put('/investments/' + editingId.value, payload);
      toast.show('Investment updated.', 'success');
    } else {
      await api().post('/investments', payload);
      toast.show('Investment added.', 'success');
    }
    formOpen.value = false;
    loadInvestments();
    loadSummary();
  } catch (e) {
    formError.value = e.response?.data?.message || 'Failed to save';
  } finally {
    saving.value = false;
  }
}

async function confirmDelete(inv) {
  const ok = await toast.showConfirm(t('Delete this investment?'));
  if (!ok) return;
  try {
    await api().delete('/investments/' + inv.id);
    toast.show('Investment deleted.', 'success');
    loadInvestments();
    loadSummary();
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to delete', 'error');
  }
}

onMounted(() => {
  loadAccounts();
  loadInvestments();
  loadSummary();
});
</script>
