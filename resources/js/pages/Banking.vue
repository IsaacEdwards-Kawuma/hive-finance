<template>
  <div>
    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
      <h1 class="text-2xl font-bold text-slate-800">{{ t('Banking') }}</h1>
      <div class="flex gap-2">
        <button type="button" @click="exportCSV" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm">{{ t('Export CSV') }}</button>
        <button @click="showAccountForm = true" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm">{{ t('Add account') }}</button>
        <button @click="showTransactionForm = true" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm">{{ t('Add transaction') }}</button>
      </div>
    </div>
    <div class="bg-white rounded-lg border border-slate-200 p-4 mb-6 flex flex-wrap items-end gap-4">
      <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">{{ t('Date from') }}</label>
        <input v-model="filters.dateFrom" type="date" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">{{ t('Date to') }}</label>
        <input v-model="filters.dateTo" type="date" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">{{ t('Account') }}</label>
        <select v-model="filters.accountId" class="rounded-lg border border-slate-300 px-3 py-2 text-sm min-w-[180px]">
          <option value="">{{ t('All types') }}</option>
          <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option>
        </select>
      </div>
      <button type="button" @click="loadTransactions" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm">{{ t('Run') }}</button>
    </div>
    <div v-if="loading" class="text-slate-500">{{ t('Loading') }}</div>
    <div v-else class="space-y-4">
      <div v-for="acc in accounts" :key="acc.id" class="bg-white rounded-lg border border-slate-200 p-4">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="font-semibold">{{ acc.name }}</h3>
            <p class="text-sm text-slate-500">{{ acc.bank_name || t('Cash account') }} · {{ acc.currency }}</p>
          </div>
          <div class="text-right">
            <p class="text-sm text-slate-500">{{ t('Opening balance') }}</p>
            <p class="font-medium">{{ formatMoney(acc.opening_balance) }}</p>
          </div>
        </div>
      </div>
      <p v-if="!accounts.length" class="text-slate-500 py-8 text-center">{{ t('No bank accounts yet.') }}</p>
    </div>
    <div class="mt-8">
      <h2 class="text-lg font-semibold text-slate-800 mb-3">{{ t('Recent transactions') }}</h2>
      <div v-if="txLoading" class="text-slate-500">{{ t('Loading') }}</div>
      <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="text-left px-4 py-3 font-medium">{{ t('Date') }}</th>
              <th class="text-left px-4 py-3 font-medium">{{ t('Account') }}</th>
              <th class="text-left px-4 py-3 font-medium">{{ t('Description') }}</th>
              <th class="text-right px-4 py-3 font-medium">{{ t('Amount') }}</th>
              <th class="text-center px-4 py-3 font-medium w-28">{{ t('Reconciled') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="tx in transactions" :key="tx.id" :class="tx.reconciled ? 'bg-slate-50' : ''">
              <td class="px-4 py-3">{{ formatDate(tx.date) }}</td>
              <td class="px-4 py-3">{{ tx.bank_account?.name || '—' }}</td>
              <td class="px-4 py-3">{{ tx.description || tx.reference || '—' }}</td>
              <td class="px-4 py-3 text-right" :class="tx.amount < 0 ? 'text-red-600' : 'text-green-600'">{{ formatMoney(tx.amount) }}</td>
              <td class="px-4 py-3 text-center">
                <button type="button" @click="toggleReconciled(tx)" class="text-sm" :class="tx.reconciled ? 'text-green-600' : 'text-slate-400 hover:text-slate-600'">{{ tx.reconciled ? t('Yes') : t('Mark') }}</button>
              </td>
            </tr>
            <tr v-if="!transactions.length">
              <td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ t('No transactions yet.') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div v-if="showAccountForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-10 p-4" @click.self="showAccountForm = false">
      <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
        <h2 class="font-semibold text-lg mb-4">{{ t('New bank account') }}</h2>
        <form @submit.prevent="addAccount" class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Name') }} *</label>
            <input v-model="accountForm.name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">GL Account *</label>
            <select v-model="accountForm.account_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="">{{ t('Select account') }}</option>
              <option v-for="a in glAccounts" :key="a.id" :value="a.id">{{ a.code }} – {{ a.name }}</option>
            </select>
          </div>
          <input v-model="accountForm.number" type="text" :placeholder="t('Account number')" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          <input v-model="accountForm.bank_name" type="text" :placeholder="t('Bank name')" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Opening balance') }}</label>
            <input v-model.number="accountForm.opening_balance" type="number" step="0.01" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div class="flex gap-2 pt-2">
            <button type="button" @click="showAccountForm = false" class="flex-1 py-2 border border-slate-300 rounded-lg">{{ t('Cancel') }}</button>
            <button type="submit" class="flex-1 py-2 bg-slate-800 text-white rounded-lg">{{ t('Save') }}</button>
          </div>
        </form>
      </div>
    </div>
    <div v-if="showTransactionForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-10 p-4" @click.self="showTransactionForm = false">
      <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
        <h2 class="font-semibold text-lg mb-4">{{ t('New transaction') }}</h2>
        <form @submit.prevent="addTransaction" class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Bank account') }} *</label>
            <select v-model="txForm.bank_account_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="">{{ t('Select account') }}</option>
              <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Type') }} *</label>
            <select v-model="txForm.type" class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="deposit">{{ t('Deposit') }}</option>
              <option value="withdrawal">{{ t('Withdrawal') }}</option>
              <option value="transfer">{{ t('Transfer') }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Amount') }} *</label>
            <input v-model.number="txForm.amount" type="number" step="0.01" min="0" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Date') }} *</label>
            <input v-model="txForm.date" type="date" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <input v-model="txForm.description" type="text" :placeholder="t('Description')" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          <input v-model="txForm.reference" type="text" :placeholder="t('Reference')" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          <div class="flex gap-2 pt-2">
            <button type="button" @click="showTransactionForm = false" class="flex-1 py-2 border border-slate-300 rounded-lg">{{ t('Cancel') }}</button>
            <button type="submit" class="flex-1 py-2 bg-slate-800 text-white rounded-lg">{{ t('Save') }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { api } from '../api';
import { useFormats } from '../composables/useFormats';
import { useI18n } from '../i18n';
import { useToast } from '../composables/useToast';

const { formatNumber, formatDate } = useFormats();
const { t } = useI18n();
const toast = useToast();
const accounts = ref([]);
const transactions = ref([]);
const loading = ref(true);
const txLoading = ref(true);
const showAccountForm = ref(false);
const showTransactionForm = ref(false);
const glAccounts = ref([]);
const accountForm = ref({
  name: '',
  account_id: '',
  number: '',
  bank_name: '',
  opening_balance: 0,
});
const txForm = ref({
  bank_account_id: '',
  type: 'deposit',
  amount: 0,
  date: new Date().toISOString().slice(0, 10),
  description: '',
  reference: '',
});
const filters = ref({ dateFrom: '', dateTo: '', accountId: '' });

function formatMoney(n) {
  return formatNumber(n ?? 0, { currency: true });
}

async function loadAccounts() {
  try {
    const { data } = await api().get('/bank-accounts');
    accounts.value = data.data || data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
}
async function loadTransactions() {
  txLoading.value = true;
  try {
    const params = { per_page: 100 };
    if (filters.value.dateFrom) params.date_from = filters.value.dateFrom;
    if (filters.value.dateTo) params.date_to = filters.value.dateTo;
    if (filters.value.accountId) params.bank_account_id = filters.value.accountId;
    const { data } = await api().get('/bank-transactions', { params });
    transactions.value = Array.isArray(data?.data) ? data.data : (data?.data ? [data.data] : []);
  } catch (e) {
    console.error(e);
    transactions.value = [];
  } finally {
    txLoading.value = false;
  }
}
function exportCSV() {
  const headers = [t('Date'), t('Account'), t('Description'), t('Amount'), t('Reconciled')];
  const rows = transactions.value.map(tx => [
    formatDate(tx.date),
    tx.bank_account?.name || '',
    (tx.description || tx.reference || '').replace(/"/g, '""'),
    tx.amount,
    tx.reconciled ? t('Yes') : '',
  ]);
  const csv = [headers.join(','), ...rows.map(r => r.map(c => '"' + String(c) + '"').join(','))].join('\r\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'bank-transactions.csv';
  a.click();
  URL.revokeObjectURL(a.href);
}
async function loadGlAccounts() {
  const { data } = await api().get('/accounts?per_page=500');
  glAccounts.value = data.data || data;
}

async function addAccount() {
  try {
    await api().post('/bank-accounts', accountForm.value);
    showAccountForm.value = false;
    accountForm.value = { name: '', account_id: '', number: '', bank_name: '', opening_balance: 0 };
    loadAccounts();
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to add', 'error');
  }
}
async function addTransaction() {
  try {
    await api().post('/bank-transactions', txForm.value);
    showTransactionForm.value = false;
    txForm.value = { bank_account_id: '', type: 'deposit', amount: 0, date: new Date().toISOString().slice(0, 10), description: '', reference: '' };
    loadTransactions();
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to add', 'error');
  }
}
async function toggleReconciled(tx) {
  try {
    await api().put(`/bank-transactions/${tx.id}`, { reconciled: !tx.reconciled });
    tx.reconciled = !tx.reconciled;
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to update', 'error');
  }
}

onMounted(async () => {
  await loadAccounts();
  loadTransactions();
  loadGlAccounts();
});
</script>
