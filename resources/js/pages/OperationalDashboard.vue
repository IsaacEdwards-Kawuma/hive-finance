<template>
  <div class="space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">{{ t('Operational dashboard') }}</h1>
        <p class="text-slate-500 text-sm mt-0.5">{{ t('Shifts, team, and scheduling overview for operations') }}</p>
      </div>
      <div class="flex flex-wrap gap-2 items-center">
        <router-link v-if="can('shifts.manage')" to="/employee/shifts" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 text-white text-sm font-medium rounded-lg hover:bg-slate-700 shadow-sm">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
          {{ t('Manage shifts') }}
        </router-link>
        <router-link to="/employee" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50">
          {{ t('Employee portal') }} →
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
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm overflow-hidden relative">
          <div class="absolute top-0 left-0 w-1 h-full bg-indigo-500" />
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">{{ t('Shifts this week') }}</p>
              <p class="text-2xl font-bold text-slate-800 mt-1">{{ operational.shifts_this_week }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm overflow-hidden relative">
          <div class="absolute top-0 left-0 w-1 h-full bg-teal-500" />
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">{{ t('Shifts this month') }}</p>
              <p class="text-2xl font-bold text-slate-800 mt-1">{{ operational.shifts_this_month }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-teal-50 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm overflow-hidden relative">
          <div class="absolute top-0 left-0 w-1 h-full bg-cyan-500" />
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">{{ t('Team size') }}</p>
              <p class="text-2xl font-bold text-slate-800 mt-1">{{ operational.team_members_count }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-cyan-50 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
          <h2 class="font-semibold text-slate-800">{{ t('Upcoming shifts') }}</h2>
          <router-link to="/employee/shifts" class="text-sm text-slate-600 hover:text-slate-800 font-medium">{{ t('Manage shifts') }}</router-link>
        </div>
        <ul class="divide-y divide-slate-100">
          <li v-for="s in operational.upcoming_shifts" :key="s.id" class="px-5 py-4 flex items-center justify-between gap-4 hover:bg-slate-50/50 transition-colors">
            <div class="min-w-0 flex-1">
              <p class="font-medium text-slate-800">{{ s.user?.name || '—' }}</p>
              <p class="text-sm text-slate-500">{{ s.title || t('Shift') }}</p>
            </div>
            <div class="flex items-center gap-3 flex-shrink-0 text-sm text-slate-600">
              {{ formatShiftDate(s.start_at) }} – {{ formatShiftTime(s.end_at) }}
            </div>
          </li>
          <li v-if="!operational.upcoming_shifts?.length" class="px-5 py-12 text-center text-slate-500">
            <svg class="w-12 h-12 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            {{ t('No upcoming shifts') }}
          </li>
        </ul>
      </div>

      <div class="bg-slate-50 rounded-xl border border-slate-200 p-5">
        <h3 class="text-sm font-medium text-slate-500 uppercase tracking-wide mb-4">{{ t('Quick links') }}</h3>
        <div class="flex flex-wrap gap-3">
          <router-link to="/employee/shifts" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:border-slate-300 hover:shadow-sm transition-all">
            {{ t('Manage shifts') }}
          </router-link>
          <router-link to="/employee" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:border-slate-300 hover:shadow-sm transition-all">
            {{ t('Employee portal') }}
          </router-link>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { api } from '../api';
import { useI18n } from '../i18n';
import { usePermissions } from '../composables/usePermissions';

const { can } = usePermissions();
const { t } = useI18n();
const loading = ref(true);
const operational = ref({
  shifts_this_week: 0,
  shifts_this_month: 0,
  team_members_count: 0,
  upcoming_shifts: [],
});

function formatShiftDate(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  return d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
}

function formatShiftTime(iso) {
  if (!iso) return '';
  const d = new Date(iso);
  return d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
}

async function loadOperational() {
  loading.value = true;
  try {
    const { data } = await api().get('/dashboard/operational');
    const d = data?.data ?? {};
    operational.value = {
      shifts_this_week: d.shifts_this_week ?? 0,
      shifts_this_month: d.shifts_this_month ?? 0,
      team_members_count: d.team_members_count ?? 0,
      upcoming_shifts: d.upcoming_shifts ?? [],
    };
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
}

onMounted(loadOperational);
</script>
