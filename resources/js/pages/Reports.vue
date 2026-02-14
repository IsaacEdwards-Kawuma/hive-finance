<template>
  <div>
    <h1 class="text-2xl font-bold text-slate-800 mb-6">{{ t('Reports') }}</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <router-link to="/reports/profit-loss" class="block p-4 bg-white rounded-lg border border-slate-200 hover:border-slate-300 hover:shadow">
        <h3 class="font-semibold text-slate-800">{{ t('Profit & Loss') }}</h3>
        <p class="text-sm text-slate-500 mt-1">{{ t('Income statement for a period') }}</p>
      </router-link>
      <router-link to="/reports/balance-sheet" class="block p-4 bg-white rounded-lg border border-slate-200 hover:border-slate-300 hover:shadow">
        <h3 class="font-semibold text-slate-800">{{ t('Balance Sheet') }}</h3>
        <p class="text-sm text-slate-500 mt-1">{{ t('Assets, liabilities, equity as of a date') }}</p>
      </router-link>
      <router-link to="/reports/trial-balance" class="block p-4 bg-white rounded-lg border border-slate-200 hover:border-slate-300 hover:shadow">
        <h3 class="font-semibold text-slate-800">{{ t('Trial Balance') }}</h3>
        <p class="text-sm text-slate-500 mt-1">{{ t('All account balances') }}</p>
      </router-link>
      <router-link to="/reports/gl-detail" class="block p-4 bg-white rounded-lg border border-slate-200 hover:border-slate-300 hover:shadow">
        <h3 class="font-semibold text-slate-800">{{ t('GL Detail') }}</h3>
        <p class="text-sm text-slate-500 mt-1">{{ t('Journal lines by account') }}</p>
      </router-link>
      <router-link to="/reports/ar-aging" class="block p-4 bg-white rounded-lg border border-slate-200 hover:border-slate-300 hover:shadow">
        <h3 class="font-semibold text-slate-800">{{ t('AR Aging') }}</h3>
        <p class="text-sm text-slate-500 mt-1">{{ t('Outstanding receivables by age') }}</p>
      </router-link>
      <router-link to="/reports/ap-aging" class="block p-4 bg-white rounded-lg border border-slate-200 hover:border-slate-300 hover:shadow">
        <h3 class="font-semibold text-slate-800">{{ t('AP Aging') }}</h3>
        <p class="text-sm text-slate-500 mt-1">{{ t('Outstanding payables by age') }}</p>
      </router-link>
      <router-link to="/reports/cash-flow" class="block p-4 bg-white rounded-lg border border-slate-200 hover:border-slate-300 hover:shadow">
        <h3 class="font-semibold text-slate-800">{{ t('Cash Flow') }}</h3>
        <p class="text-sm text-slate-500 mt-1">{{ t('Cash movement summary') }}</p>
      </router-link>
      <router-link to="/reports/tax-summary" class="block p-4 bg-white rounded-lg border border-slate-200 hover:border-slate-300 hover:shadow">
        <h3 class="font-semibold text-slate-800">{{ t('Tax Summary') }}</h3>
        <p class="text-sm text-slate-500 mt-1">{{ t('Sales tax collected vs paid') }}</p>
      </router-link>
      <router-link to="/reports/customer-statement" class="block p-4 bg-white rounded-lg border border-slate-200 hover:border-slate-300 hover:shadow">
        <h3 class="font-semibold text-slate-800">{{ t('Customer statement') }}</h3>
        <p class="text-sm text-slate-500 mt-1">{{ t('Invoices and payments by customer') }}</p>
      </router-link>
      <router-link to="/reports/vendor-statement" class="block p-4 bg-white rounded-lg border border-slate-200 hover:border-slate-300 hover:shadow">
        <h3 class="font-semibold text-slate-800">{{ t('Vendor statement') }}</h3>
        <p class="text-sm text-slate-500 mt-1">{{ t('Bills and payments by vendor') }}</p>
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from '../i18n';
const { t } = useI18n();
</script>
