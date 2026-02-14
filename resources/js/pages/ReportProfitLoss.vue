<template>
  <div>
    <router-link to="/reports" class="text-sm text-slate-600 hover:text-slate-800 mb-4 inline-block">← {{ t('Back to Reports') }}</router-link>
    <div class="flex items-center justify-between gap-4 mb-6">
      <h1 class="text-2xl font-bold text-slate-800">{{ t('Profit & Loss') }}</h1>
      <button v-if="!loading && data.from" type="button" @click="downloadPdf" class="px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 text-sm">{{ t('Download PDF') }}</button>
    </div>
    <div v-if="loading" class="text-slate-500">{{ t('Loading') }}</div>
    <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <p class="px-4 py-2 text-sm text-slate-500">{{ data.from }} to {{ data.to }}</p>
      <table class="w-full text-sm">
        <tr><td class="px-4 py-2 font-medium">{{ t('Total Income') }}</td><td class="px-4 py-2 text-right">{{ formatMoney(data.total_income) }}</td></tr>
        <tr><td class="px-4 py-2 font-medium">{{ t('Total Expense') }}</td><td class="px-4 py-2 text-right">{{ formatMoney(data.total_expense) }}</td></tr>
        <tr class="border-t border-slate-200"><td class="px-4 py-2 font-semibold">{{ t('Net Income') }}</td><td class="px-4 py-2 text-right font-semibold">{{ formatMoney(data.net_income) }}</td></tr>
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
const data = ref({ from: '', to: '', total_income: 0, total_expense: 0, net_income: 0 });
const loading = ref(true);
function formatMoney(n) {
  return formatNumber(n ?? 0, { currency: true });
}
onMounted(async () => {
  try {
    const { data: res } = await api().get('/reports/profit-loss');
    data.value = res.data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
});
function downloadPdf() {
  downloadReportPdf('/reports/profit-loss/pdf', { from: data.value.from, to: data.value.to });
}
</script>
