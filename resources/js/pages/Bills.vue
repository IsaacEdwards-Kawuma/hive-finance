<template>
  <div>
    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
      <h1 class="text-2xl font-bold text-slate-800">{{ t('Bills') }}</h1>
      <div class="flex gap-2">
        <button type="button" @click="exportCSV" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm">{{ t('Export CSV') }}</button>
        <router-link to="/bills/create" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm">{{ t('New bill') }}</router-link>
      </div>
    </div>
    <div v-if="loading" class="text-slate-500">{{ t('Loading') }}</div>
    <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium">{{ t('Number') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('Vendor') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('Date') }}</th>
            <th class="text-right px-4 py-3 font-medium">{{ t('Total') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('Status') }}</th>
            <th class="text-right px-4 py-3 font-medium w-40">{{ t('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="b in bills" :key="b.id" class="hover:bg-slate-50">
            <td class="px-4 py-3">{{ b.bill_number }}</td>
            <td class="px-4 py-3">{{ b.vendor?.name || '—' }}</td>
            <td class="px-4 py-3">{{ formatDate(b.bill_date) }}</td>
            <td class="px-4 py-3 text-right">{{ formatMoney(b.total) }}</td>
            <td class="px-4 py-3"><span :class="statusClass(b.status)">{{ b.status }}</span></td>
            <td class="px-4 py-3 text-right space-x-2">
              <router-link v-if="b.status === 'draft'" :to="`/bills/${b.id}/edit`" class="text-slate-600 hover:text-slate-800">{{ t('Edit') }}</router-link>
              <button type="button" @click="duplicate(b)" class="text-slate-600 hover:text-slate-800">{{ t('Duplicate') }}</button>
              <button v-if="b.status !== 'paid' && b.status !== 'cancelled' && (b.balance_due ?? (b.total - (b.paid_total ?? 0))) > 0" type="button" @click="openPayment(b)" class="text-slate-600 hover:text-slate-800">{{ t('Record payment') }}</button>
            </td>
          </tr>
          <tr v-if="!bills.length">
            <td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ t('No bills yet') }}</td>
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
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { api } from '../api';
import { useFormats } from '../composables/useFormats';
import { useI18n } from '../i18n';
import { useToast } from '../composables/useToast';
const router = useRouter();
const toast = useToast();
import RecordPaymentModal from '../components/RecordPaymentModal.vue';

const { formatNumber, formatDate } = useFormats();
const { t } = useI18n();
const bills = ref([]);
const loading = ref(true);
const paymentModal = ref({ open: false, balanceDue: 0, recordUrl: '' });

function openPayment(bill) {
  const balanceDue = bill.balance_due ?? (parseFloat(bill.total) - (parseFloat(bill.paid_total) || 0));
  paymentModal.value = { open: true, balanceDue, recordUrl: `bills/${bill.id}/payments` };
}
function onPaymentSaved() {
  loadBills();
}
async function duplicate(bill) {
  try {
    const { data } = await api().post(`/bills/${bill.id}/duplicate`);
    const newBill = data.data || data;
    router.push(`/bills/${newBill.id}/edit`);
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to duplicate', 'error');
  }
}
async function loadBills() {
  try {
    const { data } = await api().get('/bills?per_page=50');
    bills.value = data.data || data;
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
  const m = { draft: 'text-slate-500', received: 'text-blue-600', partial: 'text-amber-600', paid: 'text-green-600', cancelled: 'text-red-600' };
  return m[s] || '';
}
function exportCSV() {
  const headers = [t('Number'), t('Vendor'), t('Date'), t('Total'), t('Status')];
  const rows = (bills.value || []).map(b => [
    b.bill_number || '',
    (b.vendor?.name || '').replace(/"/g, '""'),
    formatDate(b.bill_date) || '',
    String(b.total ?? ''),
    b.status || ''
  ].map(x => '"' + String(x).replace(/"/g, '""') + '"'));
  const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\r\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'bills.csv';
  a.click();
  URL.revokeObjectURL(a.href);
}

onMounted(loadBills);
</script>
