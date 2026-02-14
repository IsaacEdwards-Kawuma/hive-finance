<template>
  <div class="space-y-8">
    <div>
      <h1 class="text-2xl font-bold text-slate-800">{{ t('Secretary settings') }}</h1>
      <p class="text-slate-500 text-sm mt-0.5">{{ t('Preferences and shortcuts for the secretary workspace') }}</p>
    </div>

    <div class="max-w-2xl space-y-6">
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/50">
          <h2 class="font-semibold text-slate-800">{{ t('Workspace') }}</h2>
          <p class="text-sm text-slate-500 mt-0.5">{{ t('Post announcements, view schedule, and manage communications from the Secretary dashboard') }}</p>
        </div>
        <div class="p-5 space-y-4">
          <p class="text-slate-600 text-sm">{{ t('You are responsible for meetings and communications. Use Meetings to schedule and manage meetings; use Communications for announcements and messages. View the schedule in Employee portal for shifts.') }}</p>
        </div>
      </div>

      <div class="bg-slate-50 rounded-xl border border-slate-200 p-5">
        <h3 class="text-sm font-medium text-slate-700 mb-2">{{ t('Quick links') }}</h3>
        <div class="flex flex-wrap gap-2">
          <router-link to="/dashboard/secretary" class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:border-slate-300">{{ t('Secretary dashboard') }}</router-link>
          <router-link to="/meetings" class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:border-slate-300">{{ t('Meetings') }}</router-link>
          <router-link to="/communications" class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:border-slate-300">{{ t('Communications') }}</router-link>
          <router-link to="/employee/schedule" class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:border-slate-300">{{ t('View schedule') }}</router-link>
          <router-link to="/employee" class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:border-slate-300">{{ t('Employee portal') }}</router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from '../../i18n';

const { t } = useI18n();
</script>
