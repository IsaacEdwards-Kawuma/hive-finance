<template>
  <div>
    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
      <h1 class="text-2xl font-bold text-slate-800">{{ t('Audit log') }}</h1>
      <button type="button" @click="exportCSV" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm">{{ t('Export CSV') }}</button>
    </div>
    <p class="text-slate-600 text-sm mb-4">{{ t('Audit log description') }}</p>
    <div class="flex flex-wrap gap-3 mb-4">
      <select v-model="filters.action" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">{{ t('All actions') }}</option>
        <option value="created">{{ t('Created') }}</option>
        <option value="updated">{{ t('Updated') }}</option>
        <option value="deleted">{{ t('Deleted') }}</option>
      </select>
      <select v-model="filters.auditable_type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">{{ t('All types') }}</option>
        <option value="App\Models\Invoice">Invoice</option>
        <option value="App\Models\Bill">Bill</option>
        <option value="App\Models\Customer">Customer</option>
        <option value="App\Models\Vendor">Vendor</option>
        <option value="App\Models\Account">Account</option>
        <option value="App\Models\JournalEntry">Journal entry</option>
        <option value="App\Models\CreditNote">Credit note</option>
        <option value="App\Models\Communication">Communication</option>
      </select>
      <input v-model="filters.user_id" type="number" min="1" :placeholder="t('User ID')" class="rounded-lg border border-slate-300 px-3 py-2 text-sm w-24" />
      <button type="button" @click="applyFilters" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm">{{ t('Run') }}</button>
    </div>
    <div v-if="loading" class="text-slate-500">{{ t('Loading') }}</div>
    <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium">{{ t('When') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('User') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('Action') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('Model') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('ID') }}</th>
            <th class="w-16"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <template v-for="log in logs" :key="log.id">
            <tr class="hover:bg-slate-50">
              <td class="px-4 py-3">{{ formatDate(log.created_at) }}</td>
              <td class="px-4 py-3">{{ log.user?.name || log.user?.email || '—' }}</td>
              <td class="px-4 py-3"><span :class="actionClass(log.action)">{{ log.action }}</span></td>
              <td class="px-4 py-3">{{ modelName(log.auditable_type) }}</td>
              <td class="px-4 py-3">{{ log.auditable_id }}</td>
              <td class="px-4 py-3">
                <button v-if="hasDetails(log)" type="button" @click="toggleDetail(log.id)" class="text-slate-500 hover:text-slate-800 text-xs">
                  {{ expandedId === log.id ? t('Hide') : t('Details') }}
                </button>
              </td>
            </tr>
            <tr v-if="expandedId === log.id && hasDetails(log)" class="bg-slate-50">
              <td colspan="6" class="px-4 py-3 text-xs">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div v-if="log.old_values && Object.keys(log.old_values).length"><strong>{{ t('Old values') }}:</strong><pre class="mt-1 p-2 bg-white rounded border overflow-x-auto">{{ JSON.stringify(log.old_values, null, 2) }}</pre></div>
                  <div v-if="log.new_values && Object.keys(log.new_values).length"><strong>{{ t('New values') }}:</strong><pre class="mt-1 p-2 bg-white rounded border overflow-x-auto">{{ JSON.stringify(log.new_values, null, 2) }}</pre></div>
                </div>
              </td>
            </tr>
          </template>
          <tr v-if="!logs.length">
            <td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ t('No audit entries') }}</td>
          </tr>
        </tbody>
      </table>
      <div v-if="hasMore" class="px-4 py-3 border-t border-slate-200 text-center">
        <button type="button" @click="loadMore" :disabled="loadingMore" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm disabled:opacity-50">{{ loadingMore ? t('Loading') + '…' : t('Load more') }}</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { api } from '../api';
import { useI18n } from '../i18n';

const { t } = useI18n();
const logs = ref([]);
const loading = ref(true);
const loadingMore = ref(false);
const currentPage = ref(1);
const hasMore = ref(false);
const expandedId = ref(null);
const filters = ref({ action: '', auditable_type: '', user_id: '' });

function hasDetails(log) {
  const o = log.old_values && Object.keys(log.old_values).length;
  const n = log.new_values && Object.keys(log.new_values).length;
  return o || n;
}
function toggleDetail(id) {
  expandedId.value = expandedId.value === id ? null : id;
}

function formatDate(s) {
  if (!s) return '—';
  try {
    return new Date(s).toLocaleString();
  } catch (_) {
    return s;
  }
}
function actionClass(action) {
  if (action === 'created') return 'text-green-600';
  if (action === 'updated') return 'text-blue-600';
  if (action === 'deleted') return 'text-red-600';
  return '';
}
function modelName(type) {
  if (!type) return '—';
  const parts = type.split('\\');
  return parts[parts.length - 1] || type;
}

async function load(reset = true) {
  if (reset) {
    currentPage.value = 1;
    loading.value = true;
  } else {
    loadingMore.value = true;
  }
  try {
    const params = new URLSearchParams({ per_page: '25', page: currentPage.value });
    if (filters.value.action) params.set('action', filters.value.action);
    if (filters.value.auditable_type) params.set('auditable_type', filters.value.auditable_type);
    if (filters.value.user_id) params.set('user_id', filters.value.user_id);
    const { data } = await api().get('/audit-logs?' + params.toString());
    const list = data.data ?? data ?? [];
    if (reset) logs.value = list;
    else logs.value = [...logs.value, ...list];
    hasMore.value = (data.current_page ?? 1) < (data.last_page ?? 1);
  } catch (_) {
    if (reset) logs.value = [];
  } finally {
    loading.value = false;
    loadingMore.value = false;
  }
}

function applyFilters() {
  load(true);
}

function loadMore() {
  currentPage.value += 1;
  load(false);
}

function exportCSV() {
  const headers = [t('When'), t('User'), t('Action'), t('Model'), t('ID')];
  const rows = logs.value.map((log) => [
    formatDate(log.created_at),
    (log.user?.name || log.user?.email || '').replace(/"/g, '""'),
    log.action || '',
    modelName(log.auditable_type),
    String(log.auditable_id ?? ''),
  ].map((x) => '"' + String(x).replace(/"/g, '""') + '"'));
  const csv = [headers.join(','), ...rows.map((r) => r.join(','))].join('\r\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'audit-log.csv';
  a.click();
  URL.revokeObjectURL(a.href);
}

onMounted(() => load(true));
</script>
