<template>
  <div>
    <router-link to="/reports" class="text-sm text-slate-600 hover:text-slate-800 mb-4 inline-block">← {{ t('Back to Reports') }}</router-link>
    <div class="flex items-center justify-between gap-4 mb-6">
      <h1 class="text-2xl font-bold text-slate-800">{{ t('Cash Flow') }}</h1>
      <button v-if="!loading && data.from" type="button" @click="downloadPdf" class="px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 text-sm">{{ t('Download PDF') }}</button>
    </div>
    <div v-if="loading" class="text-slate-500">{{ t('Loading') }}</div>
    <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden p-4 space-y-4">
      <p class="text-sm text-slate-500">{{ data.from }} {{ t('To') }} {{ data.to }}</p>
      <div>
        <p class="font-medium text-slate-700">{{ t('Operating activities') }}</p>
        <p class="text-slate-600">{{ t('Income') }}: {{ formatMoney(data.operating_activities?.income) }}</p>
        <p class="text-slate-600">{{ t('Expense') }}: {{ formatMoney(data.operating_activities?.expense) }}</p>
        <p class="font-medium">{{ t('Net operating') }}: {{ formatMoney(data.operating_activities?.net) }}</p>
      </div>
      <div>
        <p class="font-medium text-slate-700">{{ t('Bank net change') }}</p>
        <p>{{ formatMoney(data.bank_net_change) }}</p>
      </div>
      <div class="border-t pt-2">
        <p class="font-semibold">{{ t('Net cash change') }}: {{ formatMoney(data.net_cash_change) }}</p>
      </div>
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
const data = ref({ from: '', to: '', operating_activities: {}, bank_net_change: 0, net_cash_change: 0 });
const loading = ref(true);
function formatMoney(n) {
  return formatNumber(n ?? 0, { currency: true });
}
onMounted(async () => {
  try {
    const { data: res } = await api().get('/reports/cash-flow');
    data.value = res.data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
});
function downloadPdf() {
  downloadReportPdf('/reports/cash-flow/pdf', { from: data.value.from, to: data.value.to });
}
</script>
