import './bootstrap';
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
import Login from './pages/Login.vue';
import Register from './pages/Register.vue';
import ForgotPassword from './pages/ForgotPassword.vue';
import ResetPassword from './pages/ResetPassword.vue';
import CompanySetup from './pages/CompanySetup.vue';
import Dashboard from './pages/Dashboard.vue';
import Layout from './Layout.vue';
import Customers from './pages/Customers.vue';
import Invoices from './pages/Invoices.vue';
import InvoiceForm from './pages/InvoiceForm.vue';
import Bills from './pages/Bills.vue';
import BillForm from './pages/BillForm.vue';
import Accounts from './pages/Accounts.vue';
import JournalEntries from './pages/JournalEntries.vue';
import JournalEntryForm from './pages/JournalEntryForm.vue';
import Banking from './pages/Banking.vue';
import Reports from './pages/Reports.vue';
import ReportProfitLoss from './pages/ReportProfitLoss.vue';
import ReportBalanceSheet from './pages/ReportBalanceSheet.vue';
import ReportTrialBalance from './pages/ReportTrialBalance.vue';
import ReportGlDetail from './pages/ReportGlDetail.vue';
import ReportArAging from './pages/ReportArAging.vue';
import ReportApAging from './pages/ReportApAging.vue';
import ReportCashFlow from './pages/ReportCashFlow.vue';
import ReportTaxSummary from './pages/ReportTaxSummary.vue';
import ReportCustomerStatement from './pages/ReportCustomerStatement.vue';
import ReportVendorStatement from './pages/ReportVendorStatement.vue';
import Settings from './pages/Settings.vue';
import Vendors from './pages/Vendors.vue';
import Items from './pages/Items.vue';
import TaxRates from './pages/TaxRates.vue';
import Apps from './pages/Apps.vue';
import CreditNotes from './pages/CreditNotes.vue';
import CreditNoteForm from './pages/CreditNoteForm.vue';
import AuditLog from './pages/AuditLog.vue';
import Help from './pages/Help.vue';
import About from './pages/About.vue';
import RecurringInvoices from './pages/RecurringInvoices.vue';
import RecurringBills from './pages/RecurringBills.vue';
import BankingReconcile from './pages/BankingReconcile.vue';
import Investments from './pages/Investments.vue';
import Communications from './pages/Communications.vue';
import EmployeeDashboard from './pages/employee/EmployeeDashboard.vue';
import EmployeeAvailability from './pages/employee/EmployeeAvailability.vue';
import EmployeeShifts from './pages/employee/EmployeeShifts.vue';
import EmployeeChat from './pages/employee/EmployeeChat.vue';
import EmployeeAnnouncements from './pages/employee/EmployeeAnnouncements.vue';
import EmployeeSchedule from './pages/employee/EmployeeSchedule.vue';
import EmployeeProfile from './pages/employee/EmployeeProfile.vue';
import EmployeeTimeOff from './pages/employee/EmployeeTimeOff.vue';
import FinancialDashboard from './pages/FinancialDashboard.vue';
import OperationalDashboard from './pages/OperationalDashboard.vue';
import SettingsFinance from './pages/settings/SettingsFinance.vue';
import SettingsOperations from './pages/settings/SettingsOperations.vue';
import SettingsSecretary from './pages/settings/SettingsSecretary.vue';
import SecretaryDashboard from './pages/SecretaryDashboard.vue';
import Meetings from './pages/Meetings.vue';

const routes = [
  { path: '/login', component: Login },
  { path: '/register', component: Register },
  { path: '/forgot-password', component: ForgotPassword },
  { path: '/reset-password', component: ResetPassword },
  { path: '/setup/company', component: CompanySetup, meta: { requiresAuth: true } },
  {
    path: '/',
    component: Layout,
    meta: { requiresAuth: true },
    children: [
      { path: '', component: Dashboard },
      { path: 'dashboard/financial', component: FinancialDashboard },
      { path: 'dashboard/operational', component: OperationalDashboard },
      { path: 'dashboard/secretary', component: SecretaryDashboard },
      { path: 'meetings', component: Meetings },
      { path: 'customers', component: Customers },
      { path: 'invoices', component: Invoices },
      { path: 'invoices/create', component: InvoiceForm },
      { path: 'invoices/:id/edit', component: InvoiceForm },
      { path: 'bills', component: Bills },
      { path: 'bills/create', component: BillForm },
      { path: 'bills/:id/edit', component: BillForm },
      { path: 'vendors', component: Vendors },
      { path: 'items', component: Items },
      { path: 'tax-rates', component: TaxRates },
      { path: 'credit-notes', component: CreditNotes },
      { path: 'credit-notes/create', component: CreditNoteForm },
      { path: 'recurring-invoices', component: RecurringInvoices },
      { path: 'recurring-bills', component: RecurringBills },
      { path: 'banking/reconcile', component: BankingReconcile },
      { path: 'apps', component: Apps },
      { path: 'accounts', component: Accounts },
      { path: 'journal-entries', component: JournalEntries },
      { path: 'journal-entries/create', component: JournalEntryForm },
      { path: 'journal-entries/:id/edit', component: JournalEntryForm },
      { path: 'banking', component: Banking },
      { path: 'investments', component: Investments },
      { path: 'communications', component: Communications },
      { path: 'employee', component: EmployeeDashboard },
      { path: 'employee/availability', component: EmployeeAvailability },
      { path: 'employee/shifts', component: EmployeeShifts },
      { path: 'employee/schedule', component: EmployeeSchedule },
      { path: 'employee/chat', component: EmployeeChat },
      { path: 'employee/announcements', component: EmployeeAnnouncements },
      { path: 'employee/profile', component: EmployeeProfile },
      { path: 'employee/time-off', component: EmployeeTimeOff },
      { path: 'reports', component: Reports },
      { path: 'reports/profit-loss', component: ReportProfitLoss },
      { path: 'reports/balance-sheet', component: ReportBalanceSheet },
      { path: 'reports/trial-balance', component: ReportTrialBalance },
      { path: 'reports/gl-detail', component: ReportGlDetail },
      { path: 'reports/ar-aging', component: ReportArAging },
      { path: 'reports/ap-aging', component: ReportApAging },
      { path: 'reports/cash-flow', component: ReportCashFlow },
      { path: 'reports/tax-summary', component: ReportTaxSummary },
      { path: 'reports/customer-statement', component: ReportCustomerStatement },
      { path: 'reports/vendor-statement', component: ReportVendorStatement },
      { path: 'settings', component: Settings },
      { path: 'settings/finance', component: SettingsFinance },
      { path: 'settings/operations', component: SettingsOperations },
      { path: 'settings/secretary', component: SettingsSecretary },
      { path: 'audit-log', component: AuditLog },
      { path: 'help', component: Help },
      { path: 'about', component: About },
    ],
  },
];

const router = createRouter({ history: createWebHistory(), routes });

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token');
  if (to.meta.requiresAuth && !token) next('/login');
  else next();
});const app = createApp(App);
app.use(router);
app.mount('#app');