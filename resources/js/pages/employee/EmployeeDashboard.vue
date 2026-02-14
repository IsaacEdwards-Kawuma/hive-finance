<template>
  <div>
    <h1 class="text-2xl font-bold text-slate-800 mb-2">{{ t('Employee dashboard') }}</h1>
    <p class="text-slate-600 mb-6">{{ t('Employee dashboard description') }}</p>
    <div v-if="loading" class="text-slate-500">{{ t('Loading') }}</div>
    <div v-else class="space-y-6">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4">
          <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">{{ t('Hours this week') }}</p>
          <p class="text-2xl font-bold text-slate-800 mt-1">{{ summary.hours_this_week ?? 0 }} {{ t('hours') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
          <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">{{ t('Hours this month') }}</p>
          <p class="text-2xl font-bold text-slate-800 mt-1">{{ summary.hours_this_month ?? 0 }} {{ t('hours') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
          <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">{{ t('Unread messages') }}</p>
          <p class="text-2xl font-bold text-slate-800 mt-1">{{ summary.unread_messages_count ?? 0 }}</p>
          <router-link to="/employee/chat" class="text-sm text-slate-600 hover:text-slate-800 mt-1 inline-block">{{ t('Open chat') }}</router-link>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
          <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">{{ t('Upcoming shifts') }}</p>
          <p class="text-2xl font-bold text-slate-800 mt-1">{{ summary.next_shifts?.length ?? 0 }}</p>
          <router-link to="/employee/schedule" class="text-sm text-slate-600 hover:text-slate-800 mt-1 inline-block">{{ t('View schedule') }}</router-link>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
          <h2 class="font-semibold text-slate-800 mb-3">{{ t('My shifts') }}</h2>
          <p v-if="!summary.next_shifts?.length" class="text-slate-500 text-sm">{{ t('No upcoming shifts') }}</p>
          <ul v-else class="space-y-2">
            <li v-for="s in summary.next_shifts" :key="s.id" class="text-sm flex justify-between">
              <span>{{ formatShift(s) }}</span>
              <span class="text-slate-500">{{ formatDate(s.start_at) }}</span>
            </li>
          </ul>
          <router-link to="/employee/shifts" class="inline-block mt-3 text-sm text-slate-600 hover:text-slate-800">{{ t('View all') }}</router-link>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-6">
          <h2 class="font-semibold text-slate-800 mb-3">{{ t('Announcements') }}</h2>
          <p class="text-slate-600 text-sm mb-3">{{ t('Latest company announcements and updates.') }}</p>
          <router-link to="/employee/announcements" class="inline-block px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm">{{ t('View announcements') }}</router-link>
        </div>
      </div>
      <div class="flex flex-wrap gap-3">
        <router-link to="/employee/schedule" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm">{{ t('My schedule') }}</router-link>
        <router-link to="/employee/availability" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm">{{ t('My availability') }}</router-link>
        <router-link to="/employee/shifts" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm">{{ t('My shifts') }}</router-link>
        <router-link to="/employee/announcements" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm">{{ t('Announcements') }}</router-link>
        <router-link to="/employee/chat" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm">{{ t('Chat') }}</router-link>
        <router-link to="/employee/time-off" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm">{{ t('Time off') }}</router-link>
        <router-link to="/employee/profile" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm">{{ t('My profile') }}</router-link>
      </div>
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
const summary = ref({ next_shifts: [], unread_messages_count: 0 });
const loading = ref(true);

function formatShift(s) {
  const start = s.start_at ? new Date(s.start_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';
  const end = s.end_at ? new Date(s.end_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';
  return (s.title || t('Shift')) + ' ' + (start && end ? start + ' – ' + end : '');
}

onMounted(async () => {
  try {
    const { data } = await api().get('/employee/dashboard');
    summary.value = data.data || {};
  } catch (_) {}
  finally {
    loading.value = false;
  }
});
</script>
