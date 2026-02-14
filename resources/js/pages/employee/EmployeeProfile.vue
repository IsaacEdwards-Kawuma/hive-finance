<template>
  <div>
    <h1 class="text-2xl font-bold text-slate-800 mb-2">{{ t('My profile') }}</h1>
    <p class="text-slate-600 mb-6">{{ t('View your account details. To change name or password, use Settings in the main app.') }}</p>
    <div v-if="loading" class="text-slate-500">{{ t('Loading') }}</div>
    <div v-else class="bg-white rounded-xl border border-slate-200 p-6 max-w-lg">
      <div class="space-y-4">
        <div>
          <label class="block text-xs font-medium text-slate-500 uppercase tracking-wide">{{ t('Name') }}</label>
          <p class="mt-1 font-medium text-slate-800">{{ profile.name || '—' }}</p>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-500 uppercase tracking-wide">{{ t('Email') }}</label>
          <p class="mt-1 font-medium text-slate-800">{{ profile.email || '—' }}</p>
        </div>
      </div>
      <p class="mt-6 text-sm text-slate-500">
        {{ t('To update your name, email, or password, go to') }}
        <router-link to="/settings" class="text-slate-700 font-medium hover:underline">{{ t('Settings') }}</router-link>
        {{ t('in the main app (use the main menu to switch back).') }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { profileApi } from '../../api';
import { useI18n } from '../../i18n';

const { t } = useI18n();
const profile = ref({});
const loading = ref(true);

onMounted(async () => {
  try {
    const { data } = await profileApi().get('/user');
    profile.value = data.data || data || {};
  } catch (_) {
    profile.value = {};
  } finally {
    loading.value = false;
  }
});
</script>
