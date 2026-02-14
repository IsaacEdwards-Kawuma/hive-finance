<template>
  <div>
    <h1 class="text-2xl font-bold text-slate-800 mb-2">{{ t('Announcements') }}</h1>
    <p class="text-slate-600 mb-6">{{ t('Company announcements and updates for you.') }}</p>
    <div v-if="loading" class="text-slate-500">{{ t('Loading') }}</div>
    <div v-else class="space-y-4">
      <div
        v-for="a in announcements"
        :key="a.id"
        class="bg-white rounded-xl border border-slate-200 overflow-hidden"
        :class="{ 'border-amber-200 bg-amber-50/30': a.pinned }"
      >
        <div class="px-5 py-4 border-b border-slate-100 flex items-start justify-between gap-3">
          <div class="min-w-0 flex-1">
            <h2 class="font-semibold text-slate-800 flex items-center gap-2">
              <span v-if="a.pinned" class="text-amber-600" title="Pinned">📌</span>
              {{ a.title }}
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
              {{ a.user?.name || '—' }} · {{ formatDate(a.created_at) }}
            </p>
          </div>
        </div>
        <div class="px-5 py-4 prose prose-slate prose-sm max-w-none text-slate-700 whitespace-pre-wrap">{{ a.body }}</div>
      </div>
      <p v-if="!loading && !announcements.length" class="text-slate-500 text-center py-8">{{ t('No announcements yet.') }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { api } from '../../api';
import { useI18n } from '../../i18n';
import { useFormats } from '../../composables/useFormats';

const { t } = useI18n();
const { formatDate } = useFormats();
const announcements = ref([]);
const loading = ref(true);

onMounted(async () => {
  try {
    const { data } = await api().get('/employee/announcements');
    announcements.value = data.data || [];
  } catch (_) {
    announcements.value = [];
  } finally {
    loading.value = false;
  }
});
</script>
