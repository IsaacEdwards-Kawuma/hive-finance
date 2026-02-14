<template>
  <div>
    <router-link to="/reports" class="text-sm text-slate-600 hover:text-slate-800 mb-4 inline-block">← {{ t('Back to Reports') }}</router-link>
    <div class="flex items-center justify-between gap-4 mb-6">
      <h1 class="text-2xl font-bold text-slate-800">{{ t('GL Detail') }}</h1>
      <button v-if="accountId" type="button" @click="downloadPdf" class="px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 text-sm">{{ t('Download PDF') }}</button>
    </div>
    <div v-if="!accountId" class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-amber-800">
      {{ t('Select an account from the') }} <router-link to="/accounts" class="underline">{{ t('Chart of Accounts') }}</router-link> {{ t('and click "View GL detail" to see journal lines.') }}
    </div>
    <div v-else>
      <div class="flex flex-wrap gap-4 mb-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Account') }}</label>
          <select v-model="accountId" class="rounded-lg border border-slate-300 px-3 py-2 min-w-[200px]">
            <option value="">{{ t('Select account') }}</option>
            <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.code }} – {{ a.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('From') }}</label>
          <input v-model="dateFrom" type="date" class="rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('To') }}</label>
          <input v-model="dateTo" type="date" class="rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <button @click="loadLines" class="self-end px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700">{{ t('Apply') }}</button>
      </div>
      <div v-if="loading" class="text-slate-500">{{ t('Loading') }}</div>
      <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="text-left px-4 py-3 font-medium">{{ t('Date') }}</th>
              <th class="text-left px-4 py-3 font-medium">{{ t('Number') }}</th>
              <th class="text-left px-4 py-3 font-medium">{{ t('Description') }}</th>
              <th class="text-right px-4 py-3 font-medium">{{ t('Debit') }}</th>
              <th class="text-right px-4 py-3 font-medium">{{ t('Credit') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="line in lines" :key="line.id">
              <td class="px-4 py-3">{{ line.journal_entry?.date }}</td>
              <td class="px-4 py-3">{{ line.journal_entry?.number }}</td>
              <td class="px-4 py-3">{{ line.memo || line.journal_entry?.description || '—' }}</td>
              <td class="px-4 py-3 text-right">{{ formatMoney(line.debit) }}</td>
              <td class="px-4 py-3 text-right">{{ formatMoney(line.credit) }}</td>
            </tr>
            <tr v-if="!lines.length">
              <td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ t('No journal lines for this account in the selected period.') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { api, downloadReportPdf } from '../api';
import { useFormats } from '../composables/useFormats';
import { useI18n } from '../i18n';

const route = useRoute();
const { formatNumber } = useFormats();
const { t } = useI18n();
const accountId = ref(route.query.account_id || '');
const accounts = ref([]);
const lines = ref([]);
const loading = ref(false);
const dateFrom = ref(new Date().getFullYear() + '-01-01');
const dateTo = ref(new Date().toISOString().slice(0, 10));

function formatMoney(n) {
  return formatNumber(n ?? 0, { minFraction: 2 });
}

async function loadAccounts() {
  const { data } = await api().get('/accounts');
  accounts.value = data.data || data;
  if (!accountId.value && accounts.value.length) accountId.value = accounts.value[0].id;
}

async function loadLines() {
  if (!accountId.value) return;
  loading.value = true;
  try {
    const params = new URLSearchParams({ account_id: accountId.value });
    if (dateFrom.value) params.set('from', dateFrom.value);
    if (dateTo.value) params.set('to', dateTo.value);
    const { data } = await api().get('/reports/gl-detail?' + params.toString());
    lines.value = data.data || data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
}

watch(accountId, () => loadLines());

function downloadPdf() {
  const params = { account_id: accountId.value };
  if (dateFrom.value) params.from = dateFrom.value;
  if (dateTo.value) params.to = dateTo.value;
  downloadReportPdf('/reports/gl-detail/pdf', params);
}

onMounted(async () => {
  await loadAccounts();
  if (accountId.value) loadLines();
});
</script>
