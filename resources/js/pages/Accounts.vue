<template>
  <div>
    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
      <h1 class="text-2xl font-bold text-slate-800">{{ t('Chart of Accounts') }}</h1>
      <div class="flex flex-wrap items-center gap-3">
        <button type="button" @click="exportCSV" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm">{{ t('Export CSV') }}</button>
        <button @click="openAddForm()" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm">{{ t('Add account') }}</button>
      </div>
    </div>
    <div class="flex flex-wrap items-center gap-3 mb-4">
      <input v-model="search" type="text" :placeholder="t('Search') + ' (Code, Name)'" class="rounded-lg border border-slate-300 px-3 py-2 text-sm w-56" @keyup.enter="loadAccounts" />
      <select v-model="filterType" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">{{ t('All types') }}</option>
        <option value="asset">{{ t('Asset') }}</option>
        <option value="liability">{{ t('Liability') }}</option>
        <option value="equity">{{ t('Equity') }}</option>
        <option value="income">{{ t('Income') }}</option>
        <option value="expense">{{ t('Expense') }}</option>
      </select>
      <label class="flex items-center gap-2 text-sm">
        <input v-model="viewTree" type="checkbox" class="rounded border-slate-300" />
        {{ t('Tree view') }}
      </label>
      <button type="button" @click="loadAccounts" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm">{{ t('Run') }}</button>
    </div>

    <div v-if="loading" class="text-slate-500">{{ t('Loading') }}</div>
    <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <!-- Flat list -->
      <table v-if="!viewTree" class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium">{{ t('Code') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('Name') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('Type') }}</th>
            <th class="text-left px-4 py-3 font-medium hidden sm:table-cell">{{ t('Description') }}</th>
            <th class="text-right px-4 py-3 font-medium w-44">{{ t('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr
            v-for="a in flatAccounts"
            :key="a.id"
            class="hover:bg-slate-50 cursor-pointer"
            @click="openDetail(a)"
          >
            <td class="px-4 py-3 font-mono">{{ a.code }}</td>
            <td class="px-4 py-3">{{ a.name }}</td>
            <td class="px-4 py-3 capitalize">{{ a.type }}</td>
            <td class="px-4 py-3 text-slate-500 hidden sm:table-cell truncate max-w-xs">{{ a.description || '—' }}</td>
            <td class="px-4 py-3 text-right space-x-2" @click.stop>
              <button type="button" @click="openEditForm(a)" class="text-slate-600 hover:text-slate-800">{{ t('Edit') }}</button>
              <button
                v-if="!a.is_system"
                type="button"
                @click="confirmDelete(a)"
                class="text-red-600 hover:text-red-800"
              >
                {{ t('Delete') }}
              </button>
            </td>
          </tr>
          <tr v-if="!flatAccounts.length">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ t('No accounts. Add one to get started.') }}</td>
          </tr>
        </tbody>
      </table>
      <!-- Tree view -->
      <div v-else class="p-4">
        <template v-if="treeAccounts.length">
          <AccountTreeRow
            v-for="node in treeAccounts"
            :key="node.id"
            :node="node"
            :level="0"
            @edit="openEditForm"
            @delete="confirmDelete"
            @view-detail="openDetail"
          />
        </template>
        <p v-else class="text-slate-500 py-4 text-center">{{ t('No accounts match the filter.') }}</p>
      </div>
    </div>

    <!-- Add/Edit modal -->
    <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-10 p-4" @click.self="showForm = false">
      <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 max-h-[90vh] overflow-y-auto">
        <h2 class="font-semibold text-lg mb-4">{{ editingId ? t('Edit account') : t('New account') }}</h2>
        <form @submit.prevent="saveAccount" class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Code *</label>
            <input v-model="form.code" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="e.g. 1000" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Name *</label>
            <input v-model="form.name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Type *</label>
            <select v-model="form.type" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="asset">Asset</option>
              <option value="liability">Liability</option>
              <option value="equity">Equity</option>
              <option value="income">Income</option>
              <option value="expense">Expense</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Parent account</label>
            <select v-model="form.parent_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="">— None (top level) —</option>
              <option v-for="a in parentAccountOptions" :key="a.id" :value="a.id">{{ a.code }} – {{ a.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
            <textarea v-model="form.description" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
          </div>
          <p v-if="formError" class="text-sm text-red-600">{{ formError }}</p>
          <div class="flex gap-2 pt-2">
            <button type="button" @click="showForm = false" class="flex-1 py-2 border border-slate-300 rounded-lg">Cancel</button>
            <button type="submit" class="flex-1 py-2 bg-slate-800 text-white rounded-lg">Save</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Account detail (balance) modal -->
    <div v-if="detailAccount" class="fixed inset-0 bg-black/50 flex items-center justify-center z-10 p-4" @click.self="detailAccount = null">
      <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
        <h2 class="font-semibold text-lg mb-2">{{ detailAccount.code }} – {{ detailAccount.name }}</h2>
        <p class="text-sm text-slate-500 capitalize mb-4">{{ detailAccount.type }} account</p>
        <div v-if="detailBalance !== null" class="space-y-2 mb-4">
          <div class="flex items-center gap-2">
            <label class="text-sm text-slate-600">As of</label>
            <input v-model="detailAsOf" type="date" class="rounded border border-slate-300 px-2 py-1 text-sm" @change="refreshDetailBalance" />
          </div>
          <p class="text-xl font-semibold" :class="detailBalance.balance >= 0 ? 'text-slate-800' : 'text-red-600'">
            {{ formatMoney(detailBalance.balance) }}
          </p>
          <p class="text-xs text-slate-500">Debit total: {{ formatMoney(detailBalance.total_debit) }} · Credit total: {{ formatMoney(detailBalance.total_credit) }}</p>
        </div>
        <div v-else class="text-slate-500 mb-4">Loading balance…</div>
        <div class="flex gap-2">
          <router-link
            :to="`/reports/gl-detail?account_id=${detailAccount.id}`"
            class="flex-1 py-2 text-center border border-slate-300 rounded-lg hover:bg-slate-50 text-sm"
          >
            View GL detail
          </router-link>
          <button type="button" @click="detailAccount = null; detailBalance = null; detailAsOf = new Date().toISOString().slice(0, 10)" class="px-4 py-2 border border-slate-300 rounded-lg">Close</button>
        </div>
      </div>
    </div>

    <!-- Delete confirmation -->
    <div v-if="deleteTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-10 p-4" @click.self="deleteTarget = null">
      <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6">
        <h2 class="font-semibold text-lg mb-2">Delete account?</h2>
        <p class="text-slate-600 text-sm mb-4">"{{ deleteTarget.code }} – {{ deleteTarget.name }}" will be removed. This cannot be undone.</p>
        <div class="flex gap-2">
          <button type="button" @click="deleteTarget = null" class="flex-1 py-2 border border-slate-300 rounded-lg">Cancel</button>
          <button type="button" @click="doDelete" class="flex-1 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Delete</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { api } from '../api';
import { useI18n } from '../i18n';
import { useToast } from '../composables/useToast';
import AccountTreeRow from '../components/AccountTreeRow.vue';

const { t } = useI18n();
const toast = useToast();
const router = useRouter();
const accounts = ref([]);
const loading = ref(true);
const filterType = ref('');
const viewTree = ref(false);
const search = ref('');
const showForm = ref(false);
const editingId = ref(null);
const form = ref({ code: '', name: '', type: 'expense', description: '', parent_id: '' });
const formError = ref('');
const detailAccount = ref(null);
const detailBalance = ref(null);
const detailAsOf = ref(new Date().toISOString().slice(0, 10));
const deleteTarget = ref(null);

const flatAccounts = computed(() => {
  if (viewTree.value) return [];
  return Array.isArray(accounts.value) ? accounts.value : [];
});

const treeAccounts = computed(() => {
  if (!viewTree.value) return [];
  const list = Array.isArray(accounts.value) ? accounts.value : [];
  return list;
});

const parentAccountOptions = computed(() => {
  const list = Array.isArray(accounts.value) ? accounts.value : [];
  const flat = list.length && list[0]?.children ? flattenTree(list) : list;
  return flat.filter((a) => a.id !== editingId.value);
});

function flattenTree(nodes, out = []) {
  for (const n of nodes) {
    out.push(n);
    if (n.children?.length) flattenTree(n.children, out);
  }
  return out;
}

function formatMoney(n) {
  return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n ?? 0);
}

async function loadAccounts() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (filterType.value) params.set('type', filterType.value);
    if (viewTree.value) params.set('tree', '1');
    if ((search.value || '').trim()) params.set('search', search.value.trim());
    const { data } = await api().get('/accounts' + (params.toString() ? '?' + params.toString() : ''));
    accounts.value = data.data || data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
}

function exportCSV() {
  const list = viewTree.value ? flattenTree(Array.isArray(accounts.value) ? accounts.value : []) : (Array.isArray(accounts.value) ? accounts.value : []);
  const headers = [t('Code'), t('Name'), t('Type'), t('Description')];
  const rows = list.map((a) => [a.code || '', (a.name || '').replace(/"/g, '""'), a.type || '', (a.description || '').replace(/"/g, '""')].map((x) => '"' + String(x).replace(/"/g, '""') + '"'));
  const csv = [headers.join(','), ...rows.map((r) => r.join(','))].join('\r\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'chart-of-accounts.csv';
  a.click();
  URL.revokeObjectURL(a.href);
}

function openAddForm() {
  editingId.value = null;
  form.value = { code: '', name: '', type: 'expense', description: '', parent_id: '' };
  formError.value = '';
  showForm.value = true;
}

function openEditForm(account) {
  editingId.value = account.id;
  form.value = {
    code: account.code,
    name: account.name,
    type: account.type,
    description: account.description || '',
    parent_id: account.parent_id ?? '',
  };
  formError.value = '';
  showForm.value = true;
}

async function saveAccount() {
  formError.value = '';
  const payload = {
    code: form.value.code,
    name: form.value.name,
    type: form.value.type,
    description: form.value.description || null,
    parent_id: form.value.parent_id || null,
  };
  try {
    if (editingId.value) {
      await api().put('/accounts/' + editingId.value, payload);
    } else {
      await api().post('/accounts', payload);
    }
    showForm.value = false;
    loadAccounts();
  } catch (e) {
    formError.value = e.response?.data?.message || (e.response?.data?.errors ? JSON.stringify(e.response.data.errors) : 'Failed to save');
  }
}

function confirmDelete(account) {
  deleteTarget.value = account;
}

async function doDelete() {
  if (!deleteTarget.value) return;
  try {
    await api().delete('/accounts/' + deleteTarget.value.id);
    deleteTarget.value = null;
    loadAccounts();
  } catch (e) {
    toast.show(e.response?.data?.message || 'Cannot delete this account', 'error');
  }
}

async function openDetail(account) {
  detailAccount.value = account;
  detailBalance.value = null;
  detailAsOf.value = new Date().toISOString().slice(0, 10);
  await refreshDetailBalance();
}

async function refreshDetailBalance() {
  if (!detailAccount.value) return;
  try {
    const { data } = await api().get(`/accounts/${detailAccount.value.id}/balance`, { params: { as_of: detailAsOf.value } });
    detailBalance.value = data.data || data;
  } catch (e) {
    detailBalance.value = { balance: 0, total_debit: 0, total_credit: 0, as_of: detailAsOf.value };
  }
}

watch([filterType, viewTree], () => loadAccounts());

onMounted(loadAccounts);
</script>
