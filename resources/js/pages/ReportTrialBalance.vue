<template>
  <div>
    <router-link to="/reports" class="text-sm text-slate-600 hover:text-slate-800 mb-4 inline-block">← {{ t('Back to Reports') }}</router-link>
    <div class="flex items-center justify-between gap-4 mb-6">
      <h1 class="text-2xl font-bold text-slate-800">{{ t('Trial Balance') }}</h1>
      <button v-if="!loading && data.as_of" type="button" @click="downloadPdf" class="px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 text-sm">{{ t('Download PDF') }}</button>
    </div>
    <div v-if="loading" class="text-slate-500">{{ t('Loading') }}</div>
    <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50"><tr><th class="text-left px-4 py-2">{{ t('Code') }}</th><th class="text-left px-4 py-2">{{ t('Name') }}</th><th class="text-right px-4 py-2">{{ t('Debit') }}</th><th class="text-right px-4 py-2">{{ t('Credit') }}</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="r in (data.rows || [])" :key="r.account_id"><td class="px-4 py-2 font-mono">{{ r.code }}</td><td class="px-4 py-2">{{ r.name }}</td><td class="px-4 py-2 text-right">{{ formatMoney(r.debit) }}</td><td class="px-4 py-2 text-right">{{ formatMoney(r.credit) }}</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { api, downloadReportPdf } from '../api';
import { useFormats } from '../composables/useFormats';
import { useI18n } from '../i18n';

const { formatNumber } = useFormats();
const { t } = useI18n();
const data = ref({ as_of: '', rows: [] });
const loading = ref(true);
function formatMoney(n) {
  return formatNumber(n ?? 0, { currency: true, minFraction: 2 });
}
onMounted(async () => {
  try {
    const { data: res } = await api().get('/reports/trial-balance');
    data.value = res.data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
});
function downloadPdf() {
  downloadReportPdf('/reports/trial-balance/pdf', { as_of: data.value.as_of });
}
</script>
