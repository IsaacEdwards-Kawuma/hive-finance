<template>
  <div>
    <h1 class="text-2xl font-bold text-slate-800 mb-2">{{ t('Time off') }}</h1>
    <p class="text-slate-600 mb-6">{{ t('Request time off or view your time off balance.') }}</p>
    <div class="bg-white rounded-xl border border-slate-200 p-6 max-w-lg">
      <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center shrink-0 text-2xl">📅</div>
        <div>
          <h2 class="font-semibold text-slate-800">{{ t('Request time off') }}</h2>
          <p class="text-sm text-slate-600 mt-1">{{ t('To request vacation, sick leave, or other time off, please contact your manager or use the Chat to send a message. Your company may have a separate leave policy.') }}</p>
          <router-link to="/employee/chat" class="inline-block mt-4 px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm">{{ t('Open chat') }}</router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from '../../i18n';

const { t } = useI18n();
</script>
