<template>
  <div class="min-h-screen flex" :dir="dir">
    <aside class="w-56 bg-slate-800 text-white flex flex-col">
      <div class="p-4 border-b border-slate-700 flex items-center gap-3">
        <img src="/logo.png" alt="HiveStack IntelliCash" class="h-10 w-10 object-contain flex-shrink-0" @error="$event.target.style.display='none'" />
        <h1 class="font-bold text-lg truncate">HiveStack IntelliCash</h1>
      </div>
      <nav class="p-2 flex-1 overflow-y-auto">
        <!-- ─── Employee sidebar ─── -->
        <template v-if="isEmployeeArea && can('employee.dashboard')">
          <p class="px-3 py-1.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('Employee') }}</p>
          <router-link to="/employee" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Employee dashboard') }}</router-link>
          <router-link to="/employee/schedule" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('My schedule') }}</router-link>
          <router-link to="/employee/shifts" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('My shifts') }}</router-link>
          <router-link to="/employee/availability" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('My availability') }}</router-link>
          <router-link to="/employee/announcements" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Announcements') }}</router-link>
          <router-link to="/employee/chat" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Chat') }}</router-link>
          <router-link to="/employee/time-off" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Time off') }}</router-link>
          <p class="px-3 py-1.5 mt-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('Preferences') }}</p>
          <router-link to="/employee/profile" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('My profile') }}</router-link>
        </template>
        <!-- ─── Management / Admin sidebar ─── -->
        <template v-else-if="isAdmin || isCeo">
          <p class="px-3 py-1.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ isAdmin ? t('Admin') : t('Management') }}</p>
          <router-link v-if="can('dashboard.view')" to="/" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700" data-tour="nav-dashboard">{{ t('Management dashboard') }}</router-link>
          <router-link v-if="can('reports.view')" to="/dashboard/financial" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Financial dashboard') }}</router-link>
          <router-link v-if="can('shifts.manage')" to="/dashboard/operational" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Operational dashboard') }}</router-link>
          <router-link v-if="can('secretary.dashboard')" to="/dashboard/secretary" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Secretary dashboard') }}</router-link>
          <p class="px-3 py-1.5 mt-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('Finance & accounts') }}</p>
          <router-link v-if="can('customers.view')" to="/customers" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700" data-tour="nav-customers">{{ t('Customers') }}</router-link>
          <router-link v-if="can('invoices.view')" to="/invoices" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700" data-tour="nav-invoices">{{ t('Invoices') }}</router-link>
          <router-link v-if="can('bills.view')" to="/bills" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700" data-tour="nav-bills">{{ t('Bills') }}</router-link>
          <router-link v-if="can('bills.view')" to="/recurring-bills" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Recurring bills') }}</router-link>
          <router-link v-if="can('invoices.view')" to="/credit-notes" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Credit notes') }}</router-link>
          <router-link v-if="can('invoices.view')" to="/recurring-invoices" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Recurring invoices') }}</router-link>
          <router-link v-if="can('vendors.view')" to="/vendors" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Vendors') }}</router-link>
          <router-link v-if="can('accounts.view')" to="/accounts" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Chart of Accounts') }}</router-link>
          <router-link v-if="can('items.view')" to="/items" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Items') }}</router-link>
          <router-link v-if="can('tax-rates.view')" to="/tax-rates" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Tax rates') }}</router-link>
          <router-link v-if="can('journal-entries.view')" to="/journal-entries" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Journal Entries') }}</router-link>
          <router-link v-if="can('banking.view')" to="/banking" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700" data-tour="nav-banking">{{ t('Banking') }}</router-link>
          <router-link v-if="can('banking.view')" to="/banking/reconcile" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Reconcile') }}</router-link>
          <router-link v-if="can('investments.view')" to="/investments" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700" data-tour="nav-investments">{{ t('Investments') }}</router-link>
          <router-link v-if="can('communications.view')" to="/communications" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Communications') }}</router-link>
          <router-link v-if="can('meetings.view')" to="/meetings" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Meetings') }}</router-link>
          <router-link v-if="can('reports.view')" to="/reports" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700" data-tour="nav-reports">{{ t('Reports') }}</router-link>
          <template v-if="isAdmin">
            <p class="px-3 py-1.5 mt-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('System & settings') }}</p>
            <router-link to="/settings" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700" data-tour="nav-settings">{{ t('Settings') }}</router-link>
            <router-link to="/audit-log" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Audit log') }}</router-link>
            <router-link to="/apps" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Apps') }}</router-link>
          </template>
          <p class="px-3 py-1.5 mt-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('General') }}</p>
          <router-link to="/help" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700" data-tour="nav-help">{{ t('Help') }}</router-link>
          <router-link to="/about" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('About') }}</router-link>
          <router-link v-if="can('employee.dashboard')" to="/employee" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Employee portal') }}</router-link>
        </template>
        <!-- ─── Financial sidebar ─── -->
        <template v-else-if="isFinance">
          <p class="px-3 py-1.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('Financial') }}</p>
          <router-link to="/dashboard/financial" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Financial dashboard') }}</router-link>
          <p class="px-3 py-1.5 mt-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('Sales & receivables') }}</p>
          <router-link v-if="can('customers.view')" to="/customers" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Customers') }}</router-link>
          <router-link v-if="can('invoices.view')" to="/invoices" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Invoices') }}</router-link>
          <router-link v-if="can('invoices.view')" to="/recurring-invoices" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Recurring invoices') }}</router-link>
          <router-link v-if="can('invoices.view')" to="/credit-notes" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Credit notes') }}</router-link>
          <p class="px-3 py-1.5 mt-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('Purchases & payables') }}</p>
          <router-link v-if="can('vendors.view')" to="/vendors" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Vendors') }}</router-link>
          <router-link v-if="can('bills.view')" to="/bills" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Bills') }}</router-link>
          <router-link v-if="can('bills.view')" to="/recurring-bills" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Recurring bills') }}</router-link>
          <p class="px-3 py-1.5 mt-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('Accounts & reports') }}</p>
          <router-link v-if="can('accounts.view')" to="/accounts" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Chart of Accounts') }}</router-link>
          <router-link v-if="can('items.view')" to="/items" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Items') }}</router-link>
          <router-link v-if="can('tax-rates.view')" to="/tax-rates" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Tax rates') }}</router-link>
          <router-link v-if="can('journal-entries.view')" to="/journal-entries" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Journal Entries') }}</router-link>
          <router-link v-if="can('banking.view')" to="/banking" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Banking') }}</router-link>
          <router-link v-if="can('banking.view')" to="/banking/reconcile" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Reconcile') }}</router-link>
          <router-link v-if="can('investments.view')" to="/investments" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Investments') }}</router-link>
          <router-link v-if="can('communications.view')" to="/communications" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Communications') }}</router-link>
          <router-link v-if="can('reports.view')" to="/reports" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Reports') }}</router-link>
          <p class="px-3 py-1.5 mt-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('Settings') }}</p>
          <router-link to="/settings/finance" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Financial settings') }}</router-link>
          <p class="px-3 py-1.5 mt-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('General') }}</p>
          <router-link to="/help" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Help') }}</router-link>
          <router-link to="/about" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('About') }}</router-link>
        </template>
        <!-- ─── Operational sidebar ─── -->
        <template v-else-if="isOperations">
          <p class="px-3 py-1.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('Operations') }}</p>
          <router-link to="/dashboard/operational" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Operational dashboard') }}</router-link>
          <router-link to="/employee" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Employee portal') }}</router-link>
          <router-link to="/employee/schedule" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Schedule') }}</router-link>
          <router-link v-if="can('communications.view')" to="/communications" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Communications') }}</router-link>
          <p class="px-3 py-1.5 mt-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('Settings') }}</p>
          <router-link to="/settings/operations" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Operational settings') }}</router-link>
          <p class="px-3 py-1.5 mt-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('General') }}</p>
          <router-link to="/help" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Help') }}</router-link>
          <router-link to="/about" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('About') }}</router-link>
        </template>
        <!-- ─── Secretary sidebar (Meetings & communications) ─── -->
        <template v-else-if="isSecretary">
          <p class="px-3 py-1.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('Secretary') }}</p>
          <router-link to="/dashboard/secretary" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Secretary dashboard') }}</router-link>
          <p class="px-3 py-1.5 mt-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('Meetings & communications') }}</p>
          <router-link v-if="can('meetings.view')" to="/meetings" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Meetings') }}</router-link>
          <router-link to="/communications" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Communications') }}</router-link>
          <p class="px-3 py-1.5 mt-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('Schedule') }}</p>
          <router-link to="/employee/schedule" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('View schedule') }}</router-link>
          <router-link to="/employee" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Employee portal') }}</router-link>
          <p class="px-3 py-1.5 mt-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('Settings') }}</p>
          <router-link to="/settings/secretary" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Secretary settings') }}</router-link>
          <p class="px-3 py-1.5 mt-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('General') }}</p>
          <router-link to="/help" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Help') }}</router-link>
          <router-link to="/about" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('About') }}</router-link>
        </template>
        <!-- ─── Employee (main app) minimal sidebar ─── -->
        <template v-else-if="isEmployee">
          <p class="px-3 py-1.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('Employee') }}</p>
          <router-link to="/employee" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Employee portal') }}</router-link>
          <p class="px-3 py-1.5 mt-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('General') }}</p>
          <router-link to="/help" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Help') }}</router-link>
          <router-link to="/about" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('About') }}</router-link>
        </template>
        <!-- Fallback -->
        <template v-else>
          <p class="px-3 py-1.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('General') }}</p>
          <router-link to="/help" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('Help') }}</router-link>
          <router-link to="/about" class="block px-3 py-2 rounded hover:bg-slate-700" active-class="bg-slate-700">{{ t('About') }}</router-link>
        </template>
      </nav>
      <div class="p-2 border-t border-slate-700 space-y-1">
        <div class="relative">
          <button type="button" @click="notifOpen = !notifOpen" class="w-full text-left px-3 py-2 rounded hover:bg-slate-700 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
            {{ t('Notifications') }}
            <span v-if="unreadCount > 0" class="ml-1 min-w-[1.25rem] h-5 px-1.5 rounded-full bg-amber-500 text-slate-900 text-xs flex items-center justify-center">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
          </button>
          <div v-if="notifOpen" class="absolute bottom-full left-0 right-0 mb-1 bg-white rounded-lg shadow-xl border border-slate-200 max-h-64 overflow-y-auto z-50 text-slate-800">
            <div class="p-2 border-b border-slate-200 flex justify-between items-center">
              <span class="font-medium text-sm">{{ t('Notifications') }}</span>
              <button type="button" @click="markAllRead" class="text-xs text-slate-600 hover:text-slate-800">{{ t('Mark all as read') }}</button>
            </div>
            <div v-if="notifLoading" class="p-4 text-slate-500 text-sm">{{ t('Loading') }}</div>
            <template v-else>
              <a v-for="n in notifList" :key="n.id" href="#" @click.prevent="markRead(n); notifOpen = false; $router.push(notificationsSeeAllPath)" class="block px-3 py-2 text-sm hover:bg-slate-50 border-b border-slate-100" :class="{ 'bg-slate-50': !n.read_at }">
                <span class="font-medium">{{ n.title }}</span>
                <span class="block text-xs text-slate-500 truncate">{{ n.body || '' }}</span>
              </a>
              <a v-if="!notifList.length" href="#" @click.prevent="notifOpen = false; $router.push(notificationsSeeAllPath)" class="block px-3 py-4 text-sm text-slate-500">{{ t('No communications yet.') }}</a>
              <a href="#" @click.prevent="notifOpen = false; $router.push(notificationsSeeAllPath)" class="block px-3 py-2 text-center text-sm text-slate-600 hover:bg-slate-50">{{ t('See all') }}</a>
            </template>
          </div>
        </div>
        <button type="button" @click="startTour" class="w-full text-left px-3 py-2 rounded hover:bg-slate-700 text-sm flex items-center gap-2">
          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          {{ t('Take a tour') }}
        </button>
        <button @click="logout" class="w-full text-left px-3 py-2 rounded hover:bg-slate-700 text-sm">{{ t('Logout') }}</button>
      </div>
    </aside>
    <main class="flex-1 p-6 overflow-auto">
      <router-view />
    </main>
    <ToastContainer />
  </div>
</template>

<script setup>
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';
import ToastContainer from './components/ToastContainer.vue';
import { usePermissions } from './composables/usePermissions';
import { useAccessLevel } from './composables/useAccessLevel';
import { useI18n } from './i18n';
import { useTour } from './composables/useTour';
import { computed, ref, onMounted, watch } from 'vue';
import { api } from './api';

const route = useRoute();
const router = useRouter();
const { can, load } = usePermissions();
const { accessLevel, homePath, isAdmin, isCeo, isFinance, isOperations, isSecretary, isEmployee, canAccessPath } = useAccessLevel();
const { t } = useI18n();
const { startTour } = useTour();
load();

const isEmployeeArea = computed(() => route.path.startsWith('/employee'));
const notificationsSeeAllPath = computed(() => (isEmployee.value && !isSecretary.value ? '/employee' : '/communications'));

const dir = computed(() => (localStorage.getItem('locale') === 'ar' ? 'rtl' : 'ltr'));
const notifOpen = ref(false);
const unreadCount = ref(0);
const notifList = ref([]);
const notifLoading = ref(false);

async function fetchUnreadCount() {
  try {
    const { data } = await api().get('/notifications/unread-count');
    unreadCount.value = data?.data?.count ?? 0;
  } catch (_) {}
}

async function fetchNotifList() {
  if (!notifOpen.value) return;
  notifLoading.value = true;
  try {
    const { data } = await api().get('/notifications?per_page=10');
    notifList.value = Array.isArray(data?.data) ? data.data : [];
  } catch (_) {
    notifList.value = [];
  } finally {
    notifLoading.value = false;
  }
}

async function markRead(n) {
  try {
    await api().put('/notifications/' + n.id + '/read');
    n.read_at = new Date().toISOString();
    fetchUnreadCount();
  } catch (_) {}
}

async function markAllRead() {
  try {
    await api().post('/notifications/mark-all-read');
    notifList.value = notifList.value.map(n => ({ ...n, read_at: n.read_at || new Date().toISOString() }));
    unreadCount.value = 0;
  } catch (_) {}
}

function ensureAllowedRoute() {
  if (!accessLevel.value) return;
  const path = route.path === '' ? '/' : route.path.replace(/\/$/, '') || '/';
  if (path === '/' && homePath.value !== '/') {
    router.replace(homePath.value);
    return;
  }
  if (!canAccessPath(path)) router.replace(homePath.value);
}

onMounted(async () => {
  await load();
  ensureAllowedRoute();
  fetchUnreadCount();
});
watch(notifOpen, (open) => { if (open) fetchNotifList(); });
watch([() => route.path, accessLevel], () => { ensureAllowedRoute(); });

function logout() {
  const token = localStorage.getItem('token');
  if (token) {
    axios.post('/api/logout', {}, { headers: { Authorization: `Bearer ${token}` } }).catch(() => {});
  }
  localStorage.removeItem('token');
  window.location.href = '/login';
}
</script>

<style scoped>
.router-link-active {
  background-color: rgb(51 65 85);
}
</style>
