<template>
  <div>
    <router-link to="/reports" class="text-sm text-slate-600 hover:text-slate-800 mb-4 inline-block">← {{ t('Back to Reports') }}</router-link>
    <div class="flex items-center justify-between gap-4 mb-6">
      <h1 class="text-2xl font-bold text-slate-800">{{ t('AR Aging') }}</h1>
      <button v-if="!loading && data.as_of" type="button" @click="downloadPdf" class="px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 text-sm">{{ t('Download PDF') }}</button>
    </div>
    <div v-if="loading" class="text-slate-500">{{ t('Loading') }}</div>
    <div v-else class="bg-white rounded-lg border border-slate-200 p-4">
      <p class="text-sm text-slate-500 mb-4">{{ t('As of') }} {{ data.as_of }}</p>
      <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
        <div v-for="(val, key) in data.buckets" :key="key" class="p-2 rounded bg-slate-50"><span class="text-slate-500 block">{{ key }}</span><span class="font-medium">{{ formatMoney(val) }}</span></div>
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
const data = ref({ as_of: '', buckets: {} });
const loading = ref(true);
function formatMoney(n) {
  return formatNumber(n ?? 0, { currency: true });
}
onMounted(async () => {
  try {
    const { data: res } = await api().get('/reports/ar-aging');
    data.value = res.data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
});
function downloadPdf() {
  downloadReportPdf('/reports/ar-aging/pdf', { as_of: data.value.as_of });
}
</script>
