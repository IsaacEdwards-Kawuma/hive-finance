<template>
  <div>
    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
      <h1 class="text-2xl font-bold text-slate-800">{{ t('Credit notes') }}</h1>
      <div class="flex gap-2">
        <button type="button" @click="exportCSV" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm">{{ t('Export CSV') }}</button>
        <router-link to="/credit-notes/create" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm">{{ t('New credit note') }}</router-link>
      </div>
    </div>
    <div class="flex flex-wrap gap-3 mb-4">
      <input v-model="filters.search" type="text" :placeholder="t('Search') + ' (Number, Customer)'" class="rounded-lg border border-slate-300 px-3 py-2 text-sm w-56" @keyup.enter="loadCreditNotes" />
      <select v-model="filters.status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">{{ t('All statuses') }}</option>
        <option value="draft">Draft</option>
        <option value="applied">Applied</option>
      </select>
      <button type="button" @click="loadCreditNotes" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm">{{ t('Run') }}</button>
    </div>
    <div v-if="loading" class="text-slate-500">{{ t('Loading') }}</div>
    <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium">{{ t('Number') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('Customer') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('Invoice') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('Date') }}</th>
            <th class="text-right px-4 py-3 font-medium">{{ t('Total') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('Status') }}</th>
            <th class="text-right px-4 py-3 font-medium w-32">{{ t('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="cn in creditNotes" :key="cn.id" class="hover:bg-slate-50">
            <td class="px-4 py-3">{{ cn.credit_note_number }}</td>
            <td class="px-4 py-3">{{ cn.customer?.name || '—' }}</td>
            <td class="px-4 py-3">{{ cn.invoice?.invoice_number || '—' }}</td>
            <td class="px-4 py-3">{{ formatDate(cn.credit_note_date) }}</td>
            <td class="px-4 py-3 text-right">{{ formatMoney(cn.total) }}</td>
            <td class="px-4 py-3"><span :class="cn.status === 'applied' ? 'text-green-600' : 'text-amber-600'">{{ cn.status }}</span></td>
            <td class="px-4 py-3 text-right">
              <button v-if="cn.status === 'draft'" type="button" @click="apply(cn)" class="text-slate-600 hover:text-slate-800">{{ t('Apply') }}</button>
            </td>
          </tr>
          <tr v-if="!creditNotes.length">
            <td colspan="7" class="px-4 py-8 text-center text-slate-500">{{ t('No credit notes yet.') }}</td>
          </tr>
        </tbody>
      </table>
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
const toast = useToast();
const { t } = useI18n();
const creditNotes = ref([]);
const loading = ref(true);
const filters = ref({ search: '', status: '' });

function formatMoney(n) {
  return formatNumber(parseFloat(n) ?? 0, { minFraction: 2 });
}

async function loadCreditNotes() {
  loading.value = true;
  try {
    const params = new URLSearchParams({ per_page: '50' });
    if ((filters.value.search || '').trim()) params.set('search', filters.value.search.trim());
    if (filters.value.status) params.set('status', filters.value.status);
    const { data } = await api().get('/credit-notes?' + params.toString());
    creditNotes.value = data.data ?? (Array.isArray(data) ? data : []);
  } catch (e) {
    creditNotes.value = [];
  } finally {
    loading.value = false;
  }
}

function exportCSV() {
  const headers = [t('Number'), t('Customer'), t('Invoice'), t('Date'), t('Total'), t('Status')];
  const rows = (creditNotes.value || []).map((cn) => [
    cn.credit_note_number || '',
    (cn.customer?.name || '').replace(/"/g, '""'),
    (cn.invoice?.invoice_number || '').replace(/"/g, '""'),
    formatDate(cn.credit_note_date) || '',
    String(cn.total ?? ''),
    cn.status || '',
  ].map((x) => '"' + String(x).replace(/"/g, '""') + '"'));
  const csv = [headers.join(','), ...rows.map((r) => r.join(','))].join('\r\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'credit-notes.csv';
  a.click();
  URL.revokeObjectURL(a.href);
}

async function apply(cn) {
  if (!(await toast.showConfirm(t('Apply this credit note? This will update the linked invoice if any.')))) return;
  try {
    await api().post(`/credit-notes/${cn.id}/apply`);
    loadCreditNotes();
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to apply', 'error');
  }
}

onMounted(loadCreditNotes);
</script>
