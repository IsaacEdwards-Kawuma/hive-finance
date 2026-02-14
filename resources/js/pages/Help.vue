<template>
  <div class="max-w-4xl mx-auto space-y-10">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">{{ t('Help & How to use') }}</h1>
        <p class="text-slate-500 text-sm mt-0.5">{{ t('Learn how to use every feature in HiveStack IntelliCash') }}</p>
      </div>
      <button type="button" @click="startTour" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        {{ t('Take a guided tour') }}
      </button>
    </div>

    <nav class="bg-slate-50 rounded-xl p-4 border border-slate-200">
      <p class="text-sm font-medium text-slate-700 mb-2">{{ t('Jump to section') }}</p>
      <div class="flex flex-wrap gap-2">
        <a v-for="s in sections" :key="s.id" :href="`#${s.id}`" class="text-sm text-slate-600 hover:text-slate-900 hover:underline">{{ t(s.title) }}</a>
      </div>
    </nav>

    <section v-for="s in sections" :key="s.id" :id="s.id" class="scroll-mt-6">
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/50">
          <h2 class="text-lg font-semibold text-slate-800">{{ t(s.title) }}</h2>
          <p v-if="s.subtitle" class="text-sm text-slate-500 mt-0.5">{{ t(s.subtitle) }}</p>
        </div>
        <div class="px-5 py-4 prose prose-slate max-w-none text-sm">
          <div v-html="s.content" class="space-y-3 help-content"></div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { useTour } from '../composables/useTour';
import { useI18n } from '../i18n';

const { startTour } = useTour();
const { t } = useI18n();

const sections = [
  {
    id: 'dashboard',
    title: 'Dashboard',
    subtitle: 'Home overview',
    content: `
      <p><strong>What it does:</strong> The dashboard gives you a snapshot of your finances: revenue (YTD), outstanding AR and AP, and portfolio value (total investment value). Charts show cash flow overview (revenue vs AR vs AP) and recent invoices &amp; bills. Recent invoices and bills are listed with quick links to Reports, Investments, and more.</p>
      <p><strong>How to use:</strong> You land here when you open the app. Use the date range to filter. Click "New invoice" or "New bill" in the header, or "View all" on recent lists. The Portfolio value card links to Investments. Use quick links (P&amp;L, Balance Sheet, Journal Entries, Banking, Investments, etc.) to jump to key areas.</p>
    `,
  },
  {
    id: 'customers',
    title: 'Customers',
    subtitle: 'Manage who you sell to',
    content: `
      <p><strong>What it does:</strong> Stores your customers (name, email, address, etc.) so you can attach them to invoices.</p>
      <p><strong>How to use:</strong> Click "Customers" in the sidebar. Add a new customer with "Add customer" (or similar). Fill in name and contact details. Edit or delete from the list. You need at least one customer before creating an invoice.</p>
    `,
  },
  {
    id: 'invoices',
    title: 'Invoices',
    subtitle: 'Send bills to customers',
    content: `
      <p><strong>What it does:</strong> Create and manage sales invoices. Each invoice has a customer, line items (products/services), optional tax, and due date. You can mark as sent, record payments, and duplicate an invoice.</p>
      <p><strong>How to use:</strong> Go to "Invoices" and click to create a new invoice. Select a customer, add line items (description, quantity, price), apply tax if needed, and save. Use "Send" or "Pay now" if configured. From the list you can edit, duplicate, or record a payment. Download PDF from the invoice view if available.</p>
    `,
  },
  {
    id: 'bills',
    title: 'Bills',
    subtitle: 'Track what you owe to vendors',
    content: `
      <p><strong>What it does:</strong> Like invoices but for purchases: bills from vendors. You record bills, due dates, and payments.</p>
      <p><strong>How to use:</strong> Go to "Bills", create a new bill, select a vendor, add line items and amounts. Save and optionally record payments. You can duplicate a bill from the list.</p>
    `,
  },
  {
    id: 'credit-notes',
    title: 'Credit notes',
    subtitle: 'Refunds and adjustments',
    content: `
      <p><strong>What it does:</strong> Credit notes reduce what a customer owes. You can create a standalone credit note or "Issue credit" from an invoice, then apply it to that invoice.</p>
      <p><strong>How to use:</strong> Open "Credit notes". Create a credit note (customer, amount, reason) or open an invoice and use "Issue credit". After creating, use "Apply" to apply the credit to an invoice.</p>
    `,
  },
  {
    id: 'vendors',
    title: 'Vendors',
    subtitle: 'Suppliers you buy from',
    content: `
      <p><strong>What it does:</strong> Stores vendor/supplier details so you can attach them to bills.</p>
      <p><strong>How to use:</strong> Go to "Vendors", add vendors with name and contact info. You need vendors before creating bills.</p>
    `,
  },
  {
    id: 'chart-of-accounts',
    title: 'Chart of Accounts',
    subtitle: 'Your accounting categories',
    content: `
      <p><strong>What it does:</strong> Lists all accounts (e.g. Cash, Sales, Rent expense). Each account has a type (asset, liability, equity, income, expense) and is used in journal entries and reports.</p>
      <p><strong>How to use:</strong> Open "Chart of Accounts". Add accounts with a code and name, and choose the correct type. Edit or deactivate as needed. Do not delete accounts that have transactions; use them in journal entries for double-entry bookkeeping.</p>
    `,
  },
  {
    id: 'items',
    title: 'Items',
    subtitle: 'Products and services',
    content: `
      <p><strong>What it does:</strong> Reusable products or services you add to invoices and bills (description, price, optional tax).</p>
      <p><strong>How to use:</strong> Go to "Items", create items with name and default price. When creating an invoice or bill, you can pick an item to fill the line quickly.</p>
    `,
  },
  {
    id: 'tax-rates',
    title: 'Tax rates',
    subtitle: 'Sales and purchase tax',
    content: `
      <p><strong>What it does:</strong> Defines tax rates (e.g. VAT 20%) that you can apply to invoice or bill lines.</p>
      <p><strong>How to use:</strong> Open "Tax rates", add a rate (name and percentage). Then when adding lines to an invoice or bill, select the tax rate to apply.</p>
    `,
  },
  {
    id: 'journal-entries',
    title: 'Journal entries',
    subtitle: 'Manual double-entry bookkeeping',
    content: `
      <p><strong>What it does:</strong> Record manual journal entries (debits and credits) for adjustments, opening balances, or any transaction not covered by invoices/bills.</p>
      <p><strong>How to use:</strong> Go to "Journal entries", create a new entry with a date and lines. Each line has an account, debit or credit amount; total debits must equal total credits. Save as draft, then "Post" when ready. You can edit draft or posted entries (if your setup allows editing posted).</p>
    `,
  },
  {
    id: 'recurring',
    title: 'Recurring invoices & bills',
    subtitle: 'Automate repeat documents',
    content: `
      <p><strong>What it does:</strong> Schedule invoices or bills to be created automatically (e.g. monthly rent). You set the template, frequency, and next run date.</p>
      <p><strong>How to use:</strong> Open "Recurring invoices" or "Recurring bills" from the sidebar. Create a recurring template linked to a customer or vendor and set the frequency. The system will create draft invoices/bills on schedule; you can edit and send them as usual.</p>
    `,
  },
  {
    id: 'banking',
    title: 'Banking',
    subtitle: 'Bank accounts and transactions',
    content: `
      <p><strong>What it does:</strong> Manage bank (and cash) accounts and their transactions: deposits, withdrawals, and transfers. Reconcile by matching transactions to your bank statement.</p>
      <p><strong>How to use:</strong> Open "Banking" to see accounts and transactions. Add bank accounts (name, GL account, opening balance) and add transactions. Use "Reconcile" to match transactions to your statement: enter statement date and ending balance, then check off transactions that appear on the statement. Mark selected as reconciled when done.</p>
    `,
  },
  {
    id: 'investments',
    title: 'Investments',
    subtitle: 'Track your portfolio',
    content: `
      <p><strong>What it does:</strong> Track investments (stocks, bonds, mutual funds, real estate, crypto, etc.) with cost basis and current value. Summary cards show total cost, total value, and gain/loss. Charts show allocation by type, top holdings by value, and cost vs value comparison.</p>
      <p><strong>How to use:</strong> Open "Investments", add each holding with name, type, cost basis, and current value. Optionally link to an account and add buy/sell/dividend transactions in the detail drawer. Use filters by type or search. Export to CSV if needed. The dashboard shows your total portfolio value with a link here.</p>
    `,
  },
  {
    id: 'communications',
    title: 'Communications',
    subtitle: 'Announcements and messages',
    content: `
      <p><strong>What it does:</strong> Post company-wide announcements or target specific roles. Team members see notifications in the sidebar and can read full messages in Communications.</p>
      <p><strong>How to use:</strong> Open "Communications" to create a new announcement (title, message). Optionally pin it or target certain roles. Notifications appear in the bell icon in the sidebar; "See all" opens Communications. Mark as read from the list or notification dropdown.</p>
    `,
  },
  {
    id: 'reports',
    title: 'Reports',
    subtitle: 'Financial reports and PDF export',
    content: `
      <p><strong>What it does:</strong> Run financial reports: Profit &amp; Loss, Balance Sheet, Trial Balance, GL Detail, AR/AP Aging, Cash Flow, Tax Summary, Customer Statement, Vendor Statement. Each report can be viewed on screen and downloaded as a PDF.</p>
      <p><strong>How to use:</strong> Click "Reports" in the sidebar. Choose a report (e.g. Profit &amp; Loss). Set the date range or "as of" date where applicable. The report loads with your data. Use "Download PDF" to get a professional PDF. For Customer or Vendor statement, select the customer/vendor and date range first.</p>
    `,
  },
  {
    id: 'settings',
    title: 'Settings',
    subtitle: 'Company and app settings',
    content: `
      <p><strong>What it does:</strong> Configure your company (name, email), team members and roles, period closing, webhooks, and other options.</p>
      <p><strong>How to use:</strong> Open "Settings". Use the tabs: update company details, invite or assign roles to members, close periods to lock past dates, add webhooks if you use integrations. Save changes as needed.</p>
    `,
  },
  {
    id: 'audit-log',
    title: 'Audit log',
    subtitle: 'Who did what and when',
    content: `
      <p><strong>What it does:</strong> Shows a log of actions (e.g. who created or updated an invoice) for accountability.</p>
      <p><strong>How to use:</strong> Open "Audit log" from the sidebar. Browse or filter by date/user/action to see history.</p>
    `,
  },
  {
    id: 'apps',
    title: 'Apps',
    subtitle: 'Integrations and extensions',
    content: `
      <p><strong>What it does:</strong> Lists available apps or integrations you can enable (e.g. payment gateways, sync tools).</p>
      <p><strong>How to use:</strong> Open "Apps", see what is available, and enable or configure any app you need.</p>
    `,
  },
];
</script>

<style scoped>
.help-content p { margin: 0.75em 0; }
.help-content p:first-child { margin-top: 0; }
.help-content strong { color: #1e293b; }
</style>
