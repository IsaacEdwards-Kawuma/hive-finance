<template>
  <div class="space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">{{ t('Secretary dashboard') }}</h1>
        <p class="text-slate-500 text-sm mt-0.5">{{ t('Communications, schedule overview, and announcements at a glance') }}</p>
      </div>
      <div class="flex flex-wrap gap-2 items-center">
        <router-link v-if="can('communications.create')" to="/communications" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 text-white text-sm font-medium rounded-lg hover:bg-slate-700 shadow-sm">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
          {{ t('Communications') }}
        </router-link>
        <router-link to="/employee/schedule" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50">
          {{ t('View schedule') }} →
        </router-link>
      </div>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="flex flex-col items-center gap-3 text-slate-500">
        <svg class="w-10 h-10 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
        <span>{{ t('Loading dashboard…') }}</span>
      </div>
    </div>

    <template v-else>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm overflow-hidden relative">
          <div class="absolute top-0 left-0 w-1 h-full bg-amber-500" />
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">{{ t('Unread notifications') }}</p>
              <p class="text-2xl font-bold text-slate-800 mt-1">{{ unreadCount }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
            </div>
          </div>
          <router-link to="/communications" class="text-sm text-amber-600 hover:text-amber-700 font-medium mt-3 inline-block">{{ t('View all') }} →</router-link>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm overflow-hidden relative">
          <div class="absolute top-0 left-0 w-1 h-full bg-rose-500" />
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">{{ t('Upcoming shifts (today)') }}</p>
              <p class="text-2xl font-bold text-slate-800 mt-1">{{ upcomingToday }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-rose-50 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </div>
          </div>
          <router-link to="/employee/schedule" class="text-sm text-rose-600 hover:text-rose-700 font-medium mt-3 inline-block">{{ t('View schedule') }} →</router-link>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm overflow-hidden relative">
          <div class="absolute top-0 left-0 w-1 h-full bg-sky-500" />
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">{{ t('Shifts this week') }}</p>
              <p class="text-2xl font-bold text-slate-800 mt-1">{{ operational.shifts_this_week }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-sky-50 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm overflow-hidden relative">
          <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500" />
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">{{ t('Team size') }}</p>
              <p class="text-2xl font-bold text-slate-800 mt-1">{{ operational.team_members_count }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            </div>
          </div>
        </div>
        <router-link v-if="can('meetings.view')" to="/meetings" class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm overflow-hidden relative block">
          <div class="absolute top-0 left-0 w-1 h-full bg-violet-500" />
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">{{ t('Upcoming meetings') }}</p>
              <p class="text-2xl font-bold text-slate-800 mt-1">{{ upcomingMeetingsCount }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-violet-50 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </div>
          </div>
          <span class="text-sm text-violet-600 hover:text-violet-700 font-medium mt-3 inline-block">{{ t('Manage meetings') }} →</span>
        </router-link>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
            <h2 class="font-semibold text-slate-800">{{ t('Upcoming shifts') }}</h2>
            <router-link to="/employee/schedule" class="text-sm text-slate-600 hover:text-slate-800 font-medium">{{ t('View schedule') }}</router-link>
          </div>
          <ul class="divide-y divide-slate-100 max-h-72 overflow-y-auto">
            <li v-for="s in operational.upcoming_shifts.slice(0, 5)" :key="s.id" class="px-5 py-3 flex items-center justify-between gap-4 text-sm">
              <span class="font-medium text-slate-800">{{ s.user?.name || '—' }}</span>
              <span class="text-slate-500">{{ formatShiftDate(s.start_at) }}</span>
            </li>
            <li v-if="!operational.upcoming_shifts?.length" class="px-5 py-8 text-center text-slate-500 text-sm">{{ t('No upcoming shifts') }}</li>
          </ul>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
            <h2 class="font-semibold text-slate-800">{{ t('Quick actions') }}</h2>
          </div>
          <div class="p-5 flex flex-wrap gap-3">
            <router-link v-if="can('meetings.view')" to="/meetings" class="inline-flex items-center gap-2 px-4 py-3 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg text-sm text-slate-700 font-medium transition-colors">
              <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
              {{ t('Meetings') }}
            </router-link>
            <router-link to="/communications" class="inline-flex items-center gap-2 px-4 py-3 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg text-sm text-slate-700 font-medium transition-colors">
              <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
              {{ t('Communications') }}
            </router-link>
            <router-link to="/employee/schedule" class="inline-flex items-center gap-2 px-4 py-3 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg text-sm text-slate-700 font-medium transition-colors">
              <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
              {{ t('View schedule') }}
            </router-link>
            <router-link to="/employee" class="inline-flex items-center gap-2 px-4 py-3 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg text-sm text-slate-700 font-medium transition-colors">
              {{ t('Employee portal') }}
            </router-link>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { api } from '../api';
import { useI18n } from '../i18n';
import { usePermissions } from '../composables/usePermissions';

const { can } = usePermissions();
const { t } = useI18n();
const loading = ref(true);
const unreadCount = ref(0);
const operational = ref({
  shifts_this_week: 0,
  team_members_count: 0,
  upcoming_shifts: [],
});
const upcomingMeetingsCount = ref(0);

const upcomingToday = computed(() => {
  const list = operational.value.upcoming_shifts || [];
  const today = new Date().toDateString();
  return list.filter(s => s.start_at && new Date(s.start_at).toDateString() === today).length;
});

function formatShiftDate(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  return d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

async function load() {
  loading.value = true;
  try {
    const promises = [
      api().get('/notifications/unread-count').catch(() => ({ data: {} })),
      api().get('/dashboard/operational').catch(() => ({ data: {} })),
    ];
    if (can('meetings.view')) {
      promises.push(api().get('/meetings', { params: { upcoming: 1, per_page: 1 } }).catch(() => ({ data: {} })));
    }
    const results = await Promise.all(promises);
    unreadCount.value = results[0].data?.data?.count ?? results[0].data?.count ?? 0;
    const d = results[1].data?.data ?? {};
    operational.value = {
      shifts_this_week: d.shifts_this_week ?? 0,
      team_members_count: d.team_members_count ?? 0,
      upcoming_shifts: d.upcoming_shifts ?? [],
    };
    if (can('meetings.view') && results[2]?.data?.total != null) {
      upcomingMeetingsCount.value = results[2].data.total;
    } else if (can('meetings.view') && Array.isArray(results[2]?.data?.data)) {
      upcomingMeetingsCount.value = results[2].data.data.length;
    }
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
}

onMounted(load);
</script>
