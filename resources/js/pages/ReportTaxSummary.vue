<template>
  <div>
    <router-link to="/reports" class="text-sm text-slate-600 hover:text-slate-800 mb-4 inline-block">← {{ t('Back to Reports') }}</router-link>
    <div class="flex items-center justify-between gap-4 mb-6">
      <h1 class="text-2xl font-bold text-slate-800">{{ t('Tax Summary') }}</h1>
      <button v-if="!loading && data.from" type="button" @click="downloadPdf" class="px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 text-sm">{{ t('Download PDF') }}</button>
    </div>
    <div v-if="loading" class="text-slate-500">{{ t('Loading') }}</div>
    <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden p-4 space-y-4">
      <p class="text-sm text-slate-500">{{ data.from }} {{ t('To') }} {{ data.to }}</p>
      <div>
        <p class="font-medium">{{ t('Sales tax collected') }}</p>
        <p class="text-lg">{{ formatMoney(data.sales_tax_collected) }}</p>
      </div>
      <div>
        <p class="font-medium">{{ t('Purchase tax paid') }}</p>
        <p class="text-lg">{{ formatMoney(data.purchase_tax_paid) }}</p>
      </div>
      <div class="border-t pt-2">
        <p class="font-medium">{{ t('Tax rates') }}</p>
        <ul class="list-disc list-inside text-sm text-slate-600">
          <li v-for="r in (data.tax_rates || [])" :key="r.id">{{ r.name }}: {{ r.rate }}%</li>
        </ul>
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
const data = ref({ from: '', to: '', sales_tax_collected: 0, purchase_tax_paid: 0, tax_rates: [] });
const loading = ref(true);
function formatMoney(n) {
  return formatNumber(n ?? 0, { currency: true });
}
onMounted(async () => {
  try {
    const { data: res } = await api().get('/reports/tax-summary');
    data.value = res.data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
});
function downloadPdf() {
  downloadReportPdf('/reports/tax-summary/pdf', { from: data.value.from, to: data.value.to });
}
</script>
