<template>
  <div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">{{ t('Management dashboard') }}</h1>
        <p class="text-slate-500 text-sm mt-0.5">{{ t('Full overview: finances, operations, and key metrics for decision-making') }}</p>
      </div>
      <div class="flex flex-wrap gap-2 items-center">
        <span class="text-sm text-slate-500">{{ t('Date range') }}:</span>
        <input v-model="dateFrom" type="date" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm" />
        <span class="text-slate-400">–</span>
        <input v-model="dateTo" type="date" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm" />
        <button type="button" @click="loadSummary" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-sm">{{ t('Apply') }}</button>
        <router-link to="/invoices/create" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 text-white text-sm font-medium rounded-lg hover:bg-slate-700 shadow-sm">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
          {{ t('New invoice') }}
        </router-link>
        <router-link to="/bills/create" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50 shadow-sm">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
          {{ t('New bill') }}
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
      <!-- KPI cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-shadow overflow-hidden relative">
          <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500" />
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">{{ t('Revenue (YTD)') }}</p>
              <p class="text-2xl font-bold text-slate-800 mt-1">{{ formatMoney(summary.revenue_ytd) }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-shadow overflow-hidden relative">
          <div class="absolute top-0 left-0 w-1 h-full bg-blue-500" />
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">{{ t('Outstanding AR') }}</p>
              <p class="text-2xl font-bold text-slate-800 mt-1">{{ formatMoney(summary.outstanding_ar) }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            </div>
          </div>
          <router-link to="/reports/ar-aging" class="text-sm text-blue-600 hover:text-blue-700 font-medium mt-3 inline-block">{{ t('View AR aging') }} →</router-link>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-shadow overflow-hidden relative">
          <div class="absolute top-0 left-0 w-1 h-full bg-amber-500" />
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">{{ t('Outstanding AP') }}</p>
              <p class="text-2xl font-bold text-slate-800 mt-1">{{ formatMoney(summary.outstanding_ap) }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
            </div>
          </div>
          <router-link to="/reports/ap-aging" class="text-sm text-amber-600 hover:text-amber-700 font-medium mt-3 inline-block">{{ t('View AP aging') }} →</router-link>
        </div>
        <router-link to="/investments" class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-shadow overflow-hidden relative block">
          <div class="absolute top-0 left-0 w-1 h-full bg-violet-500" />
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">{{ t('Portfolio value') }}</p>
              <p class="text-2xl font-bold text-slate-800 mt-1">{{ formatMoney(investmentSummary.total_current_value) }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-violet-50 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
            </div>
          </div>
          <span class="text-sm text-violet-600 hover:text-violet-700 font-medium mt-3 inline-block">{{ t('View investments') }} →</span>
        </router-link>
      </div>

      <!-- Charts -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
          <h3 class="text-sm font-semibold text-slate-800 mb-4">{{ t('Cash flow overview') }}</h3>
          <div class="h-72 flex items-center justify-center">
            <Doughnut v-if="cashFlowChartData.labels.length" :data="cashFlowChartData" :options="doughnutOptions" />
            <p v-else class="text-slate-500 text-sm">{{ t('No data to display') }}</p>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
          <h3 class="text-sm font-semibold text-slate-800 mb-4">{{ t('Recent invoices & bills') }}</h3>
          <div class="h-72">
            <Bar v-if="recentActivityChartData.labels.length" :data="recentActivityChartData" :options="barOptions" />
            <p v-else class="text-slate-500 text-sm flex items-center justify-center h-full">{{ t('No data to display') }}</p>
          </div>
        </div>
      </div>

      <!-- Recent activity -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
            <h2 class="font-semibold text-slate-800">{{ t('Recent Invoices') }}</h2>
            <router-link to="/invoices" class="text-sm text-slate-600 hover:text-slate-800 font-medium">{{ t('View all') }}</router-link>
          </div>
          <ul class="divide-y divide-slate-100">
            <li v-for="inv in summary.recent_invoices" :key="inv.id" class="px-5 py-4 flex items-center justify-between gap-4 hover:bg-slate-50/50 transition-colors">
              <div class="min-w-0 flex-1">
                <p class="font-medium text-slate-800 truncate">{{ inv.invoice_number }}</p>
                <p class="text-sm text-slate-500 truncate">{{ inv.customer?.name || '—' }}</p>
              </div>
              <div class="flex items-center gap-3 flex-shrink-0">
                <span :class="statusPillClass(inv.status)" class="text-xs font-medium px-2 py-0.5 rounded-full">{{ inv.status }}</span>
                <span class="font-semibold text-slate-800 tabular-nums">{{ formatMoney(inv.total) }}</span>
              </div>
            </li>
            <li v-if="!summary.recent_invoices?.length" class="px-5 py-12 text-center text-slate-500">
              <svg class="w-12 h-12 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
              {{ t('No invoices yet') }}
            </li>
          </ul>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
            <h2 class="font-semibold text-slate-800">{{ t('Recent Bills') }}</h2>
            <router-link to="/bills" class="text-sm text-slate-600 hover:text-slate-800 font-medium">{{ t('View all') }}</router-link>
          </div>
          <ul class="divide-y divide-slate-100">
            <li v-for="bill in summary.recent_bills" :key="bill.id" class="px-5 py-4 flex items-center justify-between gap-4 hover:bg-slate-50/50 transition-colors">
              <div class="min-w-0 flex-1">
                <p class="font-medium text-slate-800 truncate">{{ bill.bill_number }}</p>
                <p class="text-sm text-slate-500 truncate">{{ bill.vendor?.name || '—' }}</p>
              </div>
              <div class="flex items-center gap-3 flex-shrink-0">
                <span :class="statusPillClass(bill.status)" class="text-xs font-medium px-2 py-0.5 rounded-full">{{ bill.status }}</span>
                <span class="font-semibold text-slate-800 tabular-nums">{{ formatMoney(bill.total) }}</span>
              </div>
            </li>
            <li v-if="!summary.recent_bills?.length" class="px-5 py-12 text-center text-slate-500">
              <svg class="w-12 h-12 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
              {{ t('No bills yet') }}
            </li>
          </ul>
        </div>
      </div>

      <!-- Operational overview (management) -->
      <div v-if="showOperational" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/50">
          <h2 class="font-semibold text-slate-800">{{ t('Operational overview') }}</h2>
          <p class="text-sm text-slate-500 mt-0.5">{{ t('Shifts and team at a glance') }}</p>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="flex items-center gap-4 p-4 rounded-lg bg-slate-50">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </div>
            <div>
              <p class="text-sm font-medium text-slate-500">{{ t('Shifts this week') }}</p>
              <p class="text-xl font-bold text-slate-800">{{ operational.shifts_this_week }}</p>
            </div>
          </div>
          <div class="flex items-center gap-4 p-4 rounded-lg bg-slate-50">
            <div class="w-12 h-12 rounded-xl bg-teal-100 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
            </div>
            <div>
              <p class="text-sm font-medium text-slate-500">{{ t('Shifts this month') }}</p>
              <p class="text-xl font-bold text-slate-800">{{ operational.shifts_this_month }}</p>
            </div>
          </div>
          <div class="flex items-center gap-4 p-4 rounded-lg bg-slate-50">
            <div class="w-12 h-12 rounded-xl bg-cyan-100 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            </div>
            <div>
              <p class="text-sm font-medium text-slate-500">{{ t('Team size') }}</p>
              <p class="text-xl font-bold text-slate-800">{{ operational.team_members_count }}</p>
            </div>
          </div>
        </div>
        <div v-if="operational.upcoming_shifts?.length" class="px-5 pb-5">
          <h3 class="text-sm font-medium text-slate-600 mb-2">{{ t('Upcoming shifts') }}</h3>
          <ul class="space-y-2">
            <li v-for="s in operational.upcoming_shifts.slice(0, 5)" :key="s.id" class="flex items-center justify-between text-sm py-2 border-b border-slate-100 last:border-0">
              <span class="text-slate-800">{{ s.user?.name || '—' }}</span>
              <span class="text-slate-500">{{ formatShiftDate(s.start_at) }}</span>
            </li>
          </ul>
        </div>
        <div class="px-5 pb-5">
          <router-link v-if="can('shifts.manage')" to="/employee/shifts" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">{{ t('Manage shifts') }} →</router-link>
          <router-link v-else-if="can('employee.dashboard')" to="/employee" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">{{ t('Employee portal') }} →</router-link>
        </div>
      </div>

      <!-- Notifications / Communications card -->
      <div v-if="unreadCount >= 0" class="bg-white rounded-xl border border-slate-200 p-4 flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center">
            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
          </div>
          <div>
            <p class="font-medium text-slate-800">{{ t('Notifications') }}</p>
            <p class="text-sm text-slate-500">{{ unreadCount === 0 ? t('All caught up') : (unreadCount + ' ' + t('unread')) }}</p>
          </div>
        </div>
        <router-link to="/communications" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm">{{ t('See all') }}</router-link>
      </div>

      <!-- Quick links -->
      <div class="bg-slate-50 rounded-xl border border-slate-200 p-5">
        <h3 class="text-sm font-medium text-slate-500 uppercase tracking-wide mb-4">{{ t('Quick links') }}</h3>
        <div class="flex flex-wrap gap-3">
          <router-link to="/reports/profit-loss" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:border-slate-300 hover:shadow-sm transition-all">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
            {{ t('Profit & Loss') }}
          </router-link>
          <router-link to="/reports/balance-sheet" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:border-slate-300 hover:shadow-sm transition-all">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
            {{ t('Balance Sheet') }}
          </router-link>
          <router-link to="/journal-entries" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:border-slate-300 hover:shadow-sm transition-all">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            {{ t('Journal Entries') }}
          </router-link>
          <router-link to="/banking" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:border-slate-300 hover:shadow-sm transition-all">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
            {{ t('Banking') }}
          </router-link>
          <router-link to="/communications" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:border-slate-300 hover:shadow-sm transition-all">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
            {{ t('Communications') }}
          </router-link>
          <router-link to="/credit-notes" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:border-slate-300 hover:shadow-sm transition-all">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            {{ t('Credit notes') }}
          </router-link>
          <router-link to="/investments" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:border-slate-300 hover:shadow-sm transition-all">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
            {{ t('Investments') }}
          </router-link>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Bar, Doughnut } from 'vue-chartjs';
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
} from 'chart.js';
import { api } from '../api';
import { useFormats } from '../composables/useFormats';
import { useI18n } from '../i18n';
import { usePermissions } from '../composables/usePermissions';

ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title);

const { can } = usePermissions();
const summary = ref({
  revenue_ytd: 0,
  outstanding_ar: 0,
  outstanding_ap: 0,
  recent_invoices: [],
  recent_bills: [],
});
const investmentSummary = ref({ total_current_value: 0 });
const operational = ref({
  shifts_this_week: 0,
  shifts_this_month: 0,
  team_members_count: 0,
  upcoming_shifts: [],
});
const showOperational = ref(false);
const loading = ref(true);
const unreadCount = ref(-1);
const dateFrom = ref(new Date().getFullYear() + '-01-01');
const dateTo = ref(new Date().toISOString().slice(0, 10));
const { formatNumber } = useFormats();
const { t } = useI18n();

function formatShiftDate(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  return d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function formatMoney(n) {
  return formatNumber(n ?? 0, { currency: true });
}

function statusPillClass(status) {
  const map = {
    draft: 'bg-slate-100 text-slate-600',
    sent: 'bg-blue-100 text-blue-700',
    partial: 'bg-amber-100 text-amber-700',
    paid: 'bg-emerald-100 text-emerald-700',
    received: 'bg-blue-100 text-blue-700',
    cancelled: 'bg-red-100 text-red-700',
  };
  return map[status] || 'bg-slate-100 text-slate-600';
}

const cashFlowChartData = computed(() => {
  const rev = summary.value.revenue_ytd ?? 0;
  const ar = summary.value.outstanding_ar ?? 0;
  const ap = summary.value.outstanding_ap ?? 0;
  const total = rev + ar + ap;
  if (total === 0) return { labels: [], datasets: [] };
  const labels = [t('Revenue (YTD)'), t('Outstanding AR'), t('Outstanding AP')];
  const values = [rev, ar, ap];
  const colors = ['rgb(16, 185, 129)', 'rgb(59, 130, 246)', 'rgb(245, 158, 11)'];
  return {
    labels,
    datasets: [{ data: values, backgroundColor: colors, borderColor: 'rgb(255,255,255)', borderWidth: 2 }],
  };
});

const recentActivityChartData = computed(() => {
  const invs = summary.value.recent_invoices ?? [];
  const bills = summary.value.recent_bills ?? [];
  const maxLen = Math.max(invs.length, bills.length);
  if (maxLen === 0) return { labels: [], datasets: [] };
  const labels = [];
  const invoiceAmounts = [];
  const billAmounts = [];
  for (let i = 0; i < maxLen; i++) {
    labels.push(
      invs[i] ? (invs[i].invoice_number || `Inv #${i + 1}`) : (bills[i] ? (bills[i].bill_number || `Bill #${i + 1}`) : `#${i + 1}`)
    );
    invoiceAmounts.push(invs[i] ? parseFloat(invs[i].total) || 0 : 0);
    billAmounts.push(bills[i] ? parseFloat(bills[i].total) || 0 : 0);
  }
  return {
    labels,
    datasets: [
      { label: t('Invoices'), data: invoiceAmounts, backgroundColor: 'rgb(16, 185, 129)', borderRadius: 6 },
      { label: t('Bills'), data: billAmounts, backgroundColor: 'rgb(245, 158, 11)', borderRadius: 6 },
    ],
  };
});

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'bottom' },
    tooltip: {
      callbacks: {
        label: (ctx) => {
          const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
          const pct = total ? ((ctx.raw / total) * 100).toFixed(1) : 0;
          return ` ${ctx.label}: ${formatMoney(ctx.raw)} (${pct}%)`;
        },
      },
    },
  },
};

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'top' },
    tooltip: {
      callbacks: {
        label: (ctx) => ` ${ctx.dataset.label}: ${formatMoney(ctx.raw)}`,
      },
    },
  },
  scales: {
    x: { grid: { color: 'rgba(0,0,0,0.06)' } },
    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.06)' } },
  },
};

async function loadSummary() {
  loading.value = true;
  showOperational.value = can('dashboard.view') || can('shifts.manage');
  try {
    const params = {};
    if (dateFrom.value) params.from = dateFrom.value;
    if (dateTo.value) params.to = dateTo.value;
    const promises = [
      api().get('/dashboard/summary', { params }),
      api().get('/investments/summary').catch(() => ({ data: {} })),
    ];
    if (showOperational.value) {
      promises.push(api().get('/dashboard/operational').catch(() => ({ data: { data: {} } })));
    }
    const results = await Promise.all(promises);
    summary.value = results[0].data.data ?? summary.value;
    const invData = results[1].data?.data ?? results[1].data;
    investmentSummary.value = {
      total_current_value: invData?.total_current_value ?? 0,
    };
    if (showOperational.value && results[2]?.data?.data) {
      operational.value = {
        shifts_this_week: results[2].data.data.shifts_this_week ?? 0,
        shifts_this_month: results[2].data.data.shifts_this_month ?? 0,
        team_members_count: results[2].data.data.team_members_count ?? 0,
        upcoming_shifts: results[2].data.data.upcoming_shifts ?? [],
      };
    }
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
}

async function fetchUnreadCount() {
  try {
    const { data } = await api().get('/notifications/unread-count');
    unreadCount.value = data?.data?.count ?? 0;
  } catch (_) {
    unreadCount.value = 0;
  }
}

onMounted(async () => {
  await loadSummary();
  fetchUnreadCount();
});
</script>
