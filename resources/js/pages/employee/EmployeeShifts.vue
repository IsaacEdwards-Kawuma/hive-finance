<template>
  <div>
    <h1 class="text-2xl font-bold text-slate-800 mb-2">{{ t('My shifts') }}</h1>
    <p class="text-slate-600 mb-6">{{ t('My shifts description') }}</p>
    <div v-if="loading" class="text-slate-500">{{ t('Loading') }}</div>
    <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium">{{ t('Date') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('Start') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('End') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('Title') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('Notes') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="s in shifts" :key="s.id" class="hover:bg-slate-50">
            <td class="px-4 py-3">{{ formatDate(s.start_at) }}</td>
            <td class="px-4 py-3">{{ formatTime(s.start_at) }}</td>
            <td class="px-4 py-3">{{ formatTime(s.end_at) }}</td>
            <td class="px-4 py-3">{{ s.title || '—' }}</td>
            <td class="px-4 py-3 text-slate-500">{{ s.notes || '—' }}</td>
          </tr>
          <tr v-if="!shifts.length">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ t('No shifts assigned.') }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { api } from '../../api';
import { useI18n } from '../../i18n';

const { t } = useI18n();
const shifts = ref([]);
const loading = ref(true);

function formatDate(d) {
  if (!d) return '';
  return new Date(d).toLocaleDateString();
}

function formatTime(d) {
  if (!d) return '';
  return new Date(d).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

onMounted(async () => {
  try {
    const { data } = await api().get('/employee/shifts?per_page=100');
    shifts.value = Array.isArray(data?.data) ? data.data : (data?.data ? [data.data] : []);
  } catch (_) {
    shifts.value = [];
  } finally {
    loading.value = false;
  }
});
</script>
