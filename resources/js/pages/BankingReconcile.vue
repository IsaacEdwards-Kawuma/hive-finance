<template>
  <div>
    <router-link to="/banking" class="text-sm text-slate-600 hover:text-slate-800 mb-4 inline-block">← {{ t('Back to Banking') }}</router-link>
    <h1 class="text-2xl font-bold text-slate-800 mb-6">{{ t('Reconcile account') }}</h1>

    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6 max-w-2xl">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Bank account') }} *</label>
          <select v-model="accountId" class="w-full rounded-lg border border-slate-300 px-3 py-2" @change="loadAccountAndTransactions">
            <option value="">{{ t('Select account') }}</option>
            <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Statement date') }} *</label>
          <input v-model="statementDate" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2" @change="loadAccountAndTransactions" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Statement ending balance') }} *</label>
          <input v-model.number="statementBalance" type="number" step="0.01" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="0.00" />
        </div>
      </div>
      <div v-if="selectedAccount" class="text-sm text-slate-600 space-y-1">
        <p>{{ t('Opening balance') }}: {{ formatMoney(selectedAccount.opening_balance) }}</p>
        <p>{{ t('Book balance') }} ({{ t('As of') }} {{ statementDate }}): {{ formatMoney(bookBalance) }}</p>
        <p class="font-medium">{{ t('Difference') }} (statement − {{ t('Book balance') }}): <span :class="difference === 0 ? 'text-green-600' : 'text-amber-600'">{{ formatMoney(difference) }}</span></p>
      </div>
    </div>

    <div v-if="accountId && statementDate" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
      <h2 class="px-5 py-3 border-b border-slate-200 font-semibold text-slate-800">{{ t('Reconcile transactions hint') }}</h2>
      <div v-if="txLoading" class="p-8 text-slate-500 text-center">{{ t('Loading') }}</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="text-left px-4 py-3 font-medium w-12"><input type="checkbox" :checked="allChecked" @change="toggleAll" :title="t('Select all')" /></th>
              <th class="text-left px-4 py-3 font-medium">{{ t('Date') }}</th>
              <th class="text-left px-4 py-3 font-medium">{{ t('Description') }}</th>
              <th class="text-right px-4 py-3 font-medium">{{ t('Amount') }}</th>
              <th class="text-center px-4 py-3 font-medium">{{ t('Reconciled') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="tx in transactions" :key="tx.id" :class="clearedIds.has(tx.id) ? 'bg-slate-50' : ''">
              <td class="px-4 py-3">
                <input v-if="!tx.reconciled" type="checkbox" :checked="clearedIds.has(tx.id)" @change="toggleCleared(tx.id)" />
                <span v-else class="text-slate-400">—</span>
              </td>
              <td class="px-4 py-3">{{ tx.date }}</td>
              <td class="px-4 py-3">{{ tx.description || tx.reference || '—' }}</td>
              <td class="px-4 py-3 text-right" :class="tx.amount < 0 ? 'text-red-600' : 'text-green-600'">{{ formatMoney(tx.amount) }}</td>
              <td class="px-4 py-3 text-center">
                <span v-if="tx.reconciled" class="text-green-600">{{ t('Yes') }}</span>
                <span v-else class="text-slate-400">{{ t('No') }}</span>
              </td>
            </tr>
            <tr v-if="!transactions.length && !txLoading">
              <td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ t('No transactions in this period') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="px-5 py-3 border-t border-slate-200 flex justify-end gap-2">
        <button type="button" @click="markReconciled" :disabled="clearedIds.size === 0 || saving" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 disabled:opacity-50 text-sm">
          {{ saving ? t('Saving…') : t('Mark selected as reconciled') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { api } from '../api';
import { useI18n } from '../i18n';

const { t } = useI18n();

const accounts = ref([]);
const accountId = ref('');
const selectedAccount = ref(null);
const statementDate = ref(new Date().toISOString().slice(0, 10));
const statementBalance = ref('');
const transactions = ref([]);
const txLoading = ref(false);
const clearedIds = ref(new Set());
const saving = ref(false);

const bookBalance = computed(() => {
  if (!selectedAccount.value) return 0;
  const opening = parseFloat(selectedAccount.value.opening_balance) || 0;
  const sum = transactions.value.reduce((s, tx) => s + (parseFloat(tx.amount) || 0), 0);
  return opening + sum;
});

const difference = computed(() => {
  const st = parseFloat(statementBalance.value);
  if (Number.isNaN(st)) return 0;
  return st - bookBalance.value;
});

const allChecked = computed(() => {
  const unreconciled = transactions.value.filter((tx) => !tx.reconciled);
  if (!unreconciled.length) return false;
  return unreconciled.every((tx) => clearedIds.value.has(tx.id));
});

function formatMoney(n) {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 }).format(n ?? 0);
}

async function loadAccounts() {
  const { data } = await api().get('/bank-accounts');
  accounts.value = data.data ?? data ?? [];
}

async function loadAccountAndTransactions() {
  if (!accountId.value || !statementDate.value) {
    selectedAccount.value = null;
    transactions.value = [];
    clearedIds.value = new Set();
    return;
  }
  selectedAccount.value = accounts.value.find((a) => a.id == accountId.value) ?? null;
  txLoading.value = true;
  clearedIds.value = new Set();
  try {
    const { data } = await api().get('/bank-transactions', {
      params: { bank_account_id: accountId.value, date_to: statementDate.value, per_page: 500 },
    });
    const list = data.data ?? data ?? [];
    transactions.value = Array.isArray(list) ? list : list.data ?? [];
  } catch (e) {
    console.error(e);
    transactions.value = [];
  } finally {
    txLoading.value = false;
  }
}

function toggleCleared(id) {
  const next = new Set(clearedIds.value);
  if (next.has(id)) next.delete(id);
  else next.add(id);
  clearedIds.value = next;
}

function toggleAll() {
  const unreconciled = transactions.value.filter((tx) => !tx.reconciled);
  if (allChecked.value) {
    unreconciled.forEach((tx) => clearedIds.value.delete(tx.id));
    clearedIds.value = new Set(clearedIds.value);
  } else {
    unreconciled.forEach((tx) => clearedIds.value.add(tx.id));
    clearedIds.value = new Set(clearedIds.value);
  }
}

async function markReconciled() {
  if (clearedIds.value.size === 0) return;
  saving.value = true;
  try {
    for (const id of clearedIds.value) {
      await api().put(`/bank-transactions/${id}`, { reconciled: true });
    }
    clearedIds.value = new Set();
    await loadAccountAndTransactions();
  } catch (e) {
    console.error(e);
  } finally {
    saving.value = false;
  }
}

watch([accountId, statementDate], () => loadAccountAndTransactions());

loadAccounts();
</script>
