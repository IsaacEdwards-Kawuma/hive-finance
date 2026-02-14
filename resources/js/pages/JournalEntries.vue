<template>
  <div>
    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
      <h1 class="text-2xl font-bold text-slate-800">{{ t('Journal Entries') }}</h1>
      <div class="flex gap-2">
        <button type="button" @click="exportCSV" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm">{{ t('Export CSV') }}</button>
        <router-link to="/journal-entries/create" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm">{{ t('New entry') }}</router-link>
      </div>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 p-4 mb-6">
      <div class="flex flex-wrap items-end gap-4">
        <div>
          <label class="block text-xs font-medium text-slate-500 mb-1">{{ t('Date from') }}</label>
          <input v-model="filters.dateFrom" type="date" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-500 mb-1">{{ t('Date to') }}</label>
          <input v-model="filters.dateTo" type="date" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-500 mb-1">{{ t('Status') }}</label>
          <select v-model="filters.status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="all">{{ t('All statuses') }}</option>
            <option value="draft">{{ t('Draft') }}</option>
            <option value="posted">{{ t('Posted') }}</option>
          </select>
        </div>
        <div class="min-w-[200px] flex-1">
          <label class="block text-xs font-medium text-slate-500 mb-1">{{ t('Search entries') }}</label>
          <input v-model="filters.search" type="text" :placeholder="t('Description') + ' / ' + t('Reference')" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        </div>
        <button type="button" @click="loadEntries" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm">
          {{ t('Run') }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-slate-500">{{ t('Loading') }}</div>
    <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium w-16">#</th>
            <th class="text-left px-4 py-3 font-medium w-28">{{ t('Date') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('Description') }}</th>
            <th class="text-left px-4 py-3 font-medium w-32">{{ t('Reference') }}</th>
            <th class="text-left px-4 py-3 font-medium w-24">{{ t('Status') }}</th>
            <th class="text-right px-4 py-3 font-medium w-28">{{ t('Total') }}</th>
            <th class="text-right px-4 py-3 font-medium w-44">{{ t('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <template v-for="e in entries" :key="e.id">
            <tr class="hover:bg-slate-50">
              <td class="px-4 py-3">{{ e.number || e.id }}</td>
              <td class="px-4 py-3">{{ formatDate(e.date) }}</td>
              <td class="px-4 py-3">{{ e.description || '—' }}</td>
              <td class="px-4 py-3 text-slate-600">{{ e.reference || '—' }}</td>
              <td class="px-4 py-3">
                <span :class="e.status === 'posted' ? 'text-green-600' : 'text-slate-500'">{{ e.status === 'posted' ? t('Posted') : t('Draft') }}</span>
              </td>
              <td class="px-4 py-3 text-right font-mono">{{ entryTotal(e) }}</td>
              <td class="px-4 py-3 text-right space-x-2">
                <button type="button" @click="toggleLines(e.id)" class="text-slate-600 hover:text-slate-800">
                  {{ expandedId === e.id ? t('Hide lines') : t('View lines') }}
                </button>
                <router-link v-if="e.status === 'draft'" :to="`/journal-entries/${e.id}/edit`" class="text-slate-600 hover:text-slate-800">{{ t('Edit') }}</router-link>
                <button v-if="e.status === 'draft'" type="button" @click="postEntry(e)" class="text-slate-600 hover:text-slate-800">{{ t('Post') }}</button>
                <button v-if="e.status === 'draft'" type="button" @click="deleteEntry(e)" class="text-red-600 hover:text-red-800">{{ t('Delete') }}</button>
              </td>
            </tr>
            <tr v-if="expandedId === e.id" class="bg-slate-50">
              <td colspan="7" class="px-4 py-3">
                <div class="overflow-x-auto">
                  <table class="w-full text-sm border border-slate-200 rounded-lg bg-white">
                    <thead class="bg-slate-100">
                      <tr>
                        <th class="text-left px-3 py-2 font-medium">{{ t('Account') }}</th>
                        <th class="text-right px-3 py-2 font-medium w-28">{{ t('Debit') }}</th>
                        <th class="text-right px-3 py-2 font-medium w-28">{{ t('Credit') }}</th>
                        <th class="text-left px-3 py-2 font-medium">{{ t('Description') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="line in (e.lines || [])" :key="line.id" class="border-t border-slate-100">
                        <td class="px-3 py-2">{{ line.account ? (line.account.code + ' – ' + line.account.name) : '—' }}</td>
                        <td class="px-3 py-2 text-right font-mono">{{ formatMoney(line.debit) }}</td>
                        <td class="px-3 py-2 text-right font-mono">{{ formatMoney(line.credit) }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ line.memo || '—' }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </td>
            </tr>
          </template>
          <tr v-if="!entries.length">
            <td colspan="7" class="px-4 py-8 text-center text-slate-500">
              {{ hasFilters ? t('No journal entries match the filters.') : t('No journal entries yet.') }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { api } from '../api';
import { useFormats } from '../composables/useFormats';
import { useI18n } from '../i18n';
import { useToast } from '../composables/useToast';

const { formatDate, formatNumber } = useFormats();
const toast = useToast();
const { t } = useI18n();
const entries = ref([]);
const loading = ref(true);
const expandedId = ref(null);
const filters = ref({
  dateFrom: '',
  dateTo: '',
  status: 'all',
  search: '',
});

const hasFilters = computed(() => filters.value.dateFrom || filters.value.dateTo || filters.value.status !== 'all' || (filters.value.search || '').trim());

function formatMoney(n) {
  return formatNumber(parseFloat(n) ?? 0, { minFraction: 2 });
}
function entryTotal(entry) {
  const lines = entry.lines || [];
  const debit = lines.reduce((s, l) => s + (parseFloat(l.debit) || 0), 0);
  return formatMoney(debit);
}
function toggleLines(id) {
  expandedId.value = expandedId.value === id ? null : id;
}

async function loadEntries() {
  loading.value = true;
  try {
    const params = { per_page: 50 };
    if (filters.value.dateFrom) params.date_from = filters.value.dateFrom;
    if (filters.value.dateTo) params.date_to = filters.value.dateTo;
    if (filters.value.status !== 'all') params.status = filters.value.status;
    if ((filters.value.search || '').trim()) params.search = filters.value.search.trim();
    const { data } = await api().get('/journal-entries', { params });
    entries.value = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []);
  } catch (e) {
    console.error(e);
    entries.value = [];
  } finally {
    loading.value = false;
  }
}

async function postEntry(entry) {
  try {
    await api().post(`/journal-entries/${entry.id}/post`);
    loadEntries();
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to post', 'error');
  }
}

async function deleteEntry(entry) {
  const ok = await toast.showConfirm(t('Delete this entry?'));
  if (!ok) return;
  try {
    await api().delete(`/journal-entries/${entry.id}`);
    if (expandedId.value === entry.id) expandedId.value = null;
    loadEntries();
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to delete', 'error');
  }
}

function exportCSV() {
  const headers = ['#', t('Date'), t('Description'), t('Reference'), t('Status'), t('Total')];
  const rows = (entries.value || []).map((e) => [
    e.number || e.id,
    formatDate(e.date) || '',
    (e.description || '').replace(/"/g, '""'),
    (e.reference || '').replace(/"/g, '""'),
    e.status || '',
    entryTotal(e),
  ].map((x) => '"' + String(x).replace(/"/g, '""') + '"'));
  const csv = [headers.join(','), ...rows.map((r) => r.join(','))].join('\r\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'journal-entries.csv';
  a.click();
  URL.revokeObjectURL(a.href);
}

onMounted(loadEntries);
</script>
