<template>
  <div>
    <div v-if="stripeMessage" :class="['mb-4 px-4 py-3 rounded-lg text-sm', stripeMessage === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-amber-50 text-amber-800 border border-amber-200']">
      {{ stripeMessage === 'success' ? t('Payment successful') : t('Payment cancelled') }}
      <button type="button" @click="stripeMessage = null; clearStripeQuery()" class="ml-2 underline">{{ t('Close') }}</button>
    </div>
    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
      <h1 class="text-2xl font-bold text-slate-800">{{ t('Invoices') }}</h1>
      <div class="flex gap-2">
        <button type="button" @click="exportCSV" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm">{{ t('Export CSV') }}</button>
        <router-link to="/invoices/create" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm">{{ t('New invoice') }}</router-link>
      </div>
    </div>
    <div v-if="loading" class="text-slate-500">{{ t('Loading') }}</div>
    <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium">{{ t('Number') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('Customer') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('Date') }}</th>
            <th class="text-right px-4 py-3 font-medium">{{ t('Total') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('Status') }}</th>
            <th class="text-right px-4 py-3 font-medium w-40">{{ t('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="inv in invoices" :key="inv.id" class="hover:bg-slate-50">
            <td class="px-4 py-3">{{ inv.invoice_number }}</td>
            <td class="px-4 py-3">{{ inv.customer?.name || '—' }}</td>
            <td class="px-4 py-3">{{ formatDate(inv.invoice_date) }}</td>
            <td class="px-4 py-3 text-right">{{ formatMoney(inv.total) }}</td>
            <td class="px-4 py-3"><span :class="statusClass(inv.status)">{{ inv.status }}</span></td>
            <td class="px-4 py-3 text-right space-x-2">
              <button type="button" @click="viewPdf(inv)" class="text-slate-600 hover:text-slate-800">{{ t('View/Print') }}</button>
              <router-link v-if="inv.status === 'draft'" :to="`/invoices/${inv.id}/edit`" class="text-slate-600 hover:text-slate-800">{{ t('Edit') }}</router-link>
              <button type="button" @click="duplicate(inv)" class="text-slate-600 hover:text-slate-800">{{ t('Duplicate') }}</button>
              <router-link :to="`/credit-notes/create?invoice_id=${inv.id}`" class="text-slate-600 hover:text-slate-800">{{ t('Issue credit') }}</router-link>
              <a v-if="payNowUrl(inv)" :href="payNowUrl(inv)" target="_blank" rel="noopener" class="text-slate-600 hover:text-slate-800">{{ t('Pay now') }}</a>
              <button v-if="canPayWithStripe(inv)" type="button" @click="payWithStripe(inv)" :disabled="stripeLoading === inv.id" class="text-slate-600 hover:text-slate-800">{{ stripeLoading === inv.id ? '…' : t('Pay with Stripe') }}</button>
              <button v-if="inv.status !== 'paid' && inv.status !== 'cancelled' && (inv.balance_due ?? (inv.total - (inv.paid_total ?? 0))) > 0" type="button" @click="openPayment(inv)" class="text-slate-600 hover:text-slate-800">{{ t('Record payment') }}</button>
            </td>
          </tr>
          <tr v-if="!invoices.length">
            <td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ t('No invoices yet') }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <RecordPaymentModal
      :open="paymentModal.open"
      :balance-due="paymentModal.balanceDue"
      :record-url="paymentModal.recordUrl"
      @close="paymentModal.open = false"
      @saved="onPaymentSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { api } from '../api';
import { useFormats } from '../composables/useFormats';
import { useI18n } from '../i18n';
import { useToast } from '../composables/useToast';
const router = useRouter();
const toast = useToast();
const route = useRoute();
import RecordPaymentModal from '../components/RecordPaymentModal.vue';

const { formatNumber, formatDate } = useFormats();
const { t } = useI18n();
const invoices = ref([]);
const loading = ref(true);
const paymentModal = ref({ open: false, balanceDue: 0, recordUrl: '' });
const stripeLoading = ref(null);
const stripeMessage = ref(null);

function clearStripeQuery() {
  if (route.query.stripe) {
    const q = { ...route.query };
    delete q.stripe;
    router.replace({ path: route.path, query: Object.keys(q).length ? q : {} });
  }
}
watch(() => route.query.stripe, (val) => {
  if (val === 'success' || val === 'cancel') stripeMessage.value = val;
}, { immediate: true });

function canPayWithStripe(inv) {
  if (inv.status === 'paid' || inv.status === 'cancelled') return false;
  const balance = inv.balance_due ?? (parseFloat(inv.total) - (parseFloat(inv.paid_total) || 0));
  return balance > 0;
}
async function payWithStripe(inv) {
  stripeLoading.value = inv.id;
  try {
    const { data } = await api().post(`invoices/${inv.id}/payment-link`);
    const url = data?.data?.url || data?.url;
    if (url) window.open(url, '_blank', 'noopener');
    else toast.show(data?.message || 'Could not get payment link', 'error');
  } catch (e) {
    toast.show(e.response?.data?.message || 'Stripe payment link unavailable', 'error');
  } finally {
    stripeLoading.value = null;
  }
}

function openPayment(inv) {
  const balanceDue = inv.balance_due ?? (parseFloat(inv.total) - (parseFloat(inv.paid_total) || 0));
  paymentModal.value = { open: true, balanceDue, recordUrl: `invoices/${inv.id}/payments` };
}
function onPaymentSaved() {
  loadInvoices();
}
async function viewPdf(inv) {
  try {
    const { data } = await api().get(`invoices/${inv.id}/pdf`, { responseType: 'text' });
    const w = window.open('', '_blank');
    if (w) {
      w.document.write(data);
      w.document.close();
    }
  } catch (e) {
    console.error(e);
    toast.show('Could not load invoice view', 'error');
  }
}
const paymentUrlTemplate = ref('');
function payNowUrl(inv) {
  if (!paymentUrlTemplate.value) return null;
  return paymentUrlTemplate.value.replace(/\{id\}/g, inv.id).replace(/\{invoice_id\}/g, inv.id).replace(/\{number\}/g, (inv.invoice_number || '').replace(/#/g, '%23'));
}
async function duplicate(inv) {
  try {
    const { data } = await api().post(`/invoices/${inv.id}/duplicate`);
    const newInv = data.data || data;
    router.push(`/invoices/${newInv.id}/edit`);
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to duplicate', 'error');
  }
}
async function loadInvoices() {
  try {
    const cid = localStorage.getItem('companyId');
    if (cid) {
      const cr = await api().get('/companies/' + cid);
      const c = cr.data.data || cr.data;
      paymentUrlTemplate.value = c.settings?.payment_url_template || '';
    }
    const { data } = await api().get('/invoices?per_page=50');
    invoices.value = data.data || data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
}

function formatMoney(n) {
  return formatNumber(n ?? 0, { currency: true });
}
function statusClass(s) {
  const m = { draft: 'text-slate-500', sent: 'text-blue-600', partial: 'text-amber-600', paid: 'text-green-600', cancelled: 'text-red-600' };
  return m[s] || '';
}
function exportCSV() {
  const headers = [t('Number'), t('Customer'), t('Date'), t('Total'), t('Status')];
  const rows = (invoices.value || []).map(inv => [
    inv.invoice_number || '',
    (inv.customer?.name || '').replace(/"/g, '""'),
    formatDate(inv.invoice_date) || '',
    String(inv.total ?? ''),
    inv.status || ''
  ].map(x => '"' + String(x).replace(/"/g, '""') + '"'));
  const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\r\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'invoices.csv';
  a.click();
  URL.revokeObjectURL(a.href);
}

onMounted(loadInvoices);
</script>
