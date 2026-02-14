<template>
  <div class="space-y-8">
    <div>
      <h1 class="text-2xl font-bold text-slate-800">{{ t('Financial settings') }}</h1>
      <p class="text-slate-500 text-sm mt-0.5">{{ t('Preferences and company context for the financial workspace') }}</p>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-12 text-slate-500">
      {{ t('Loading…') }}
    </div>

    <div v-else class="max-w-2xl space-y-6">
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/50">
          <h2 class="font-semibold text-slate-800">{{ t('Company context') }}</h2>
          <p class="text-sm text-slate-500 mt-0.5">{{ t('Read-only view of your current company for reports and invoicing') }}</p>
        </div>
        <div class="p-5 space-y-4">
          <template v-if="company">
            <div>
              <label class="block text-sm font-medium text-slate-500">{{ t('Company name') }}</label>
              <p class="mt-0.5 font-medium text-slate-800">{{ company.name || '—' }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-500">{{ t('Default currency') }}</label>
              <p class="mt-0.5 font-medium text-slate-800">{{ company.default_currency || '—' }}</p>
            </div>
            <div v-if="company.fiscal_year_start">
              <label class="block text-sm font-medium text-slate-500">{{ t('Fiscal year start') }}</label>
              <p class="mt-0.5 font-medium text-slate-800">{{ company.fiscal_year_start }}</p>
            </div>
          </template>
          <p v-else class="text-slate-500 text-sm">{{ t('No company selected or available.') }}</p>
        </div>
      </div>

      <div class="bg-slate-50 rounded-xl border border-slate-200 p-5">
        <h3 class="text-sm font-medium text-slate-700 mb-2">{{ t('Quick links') }}</h3>
        <div class="flex flex-wrap gap-2">
          <router-link to="/dashboard/financial" class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:border-slate-300">{{ t('Financial dashboard') }}</router-link>
          <router-link to="/reports" class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:border-slate-300">{{ t('Reports') }}</router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { api } from '../../api';
import { useI18n } from '../../i18n';

const { t } = useI18n();
const loading = ref(true);
const company = ref(null);

onMounted(async () => {
  try {
    const { data } = await api().get('/companies');
    const list = data?.data ?? data ?? [];
    if (list.length) {
      const id = list[0].id;
      const res = await api().get('/companies/' + id).catch(() => ({}));
      company.value = res.data?.data ?? res.data ?? list[0];
    }
  } catch (_) {
    company.value = null;
  } finally {
    loading.value = false;
  }
});
</script>
