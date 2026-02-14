<template>
  <div>
    <router-link to="/credit-notes" class="text-sm text-slate-600 hover:text-slate-800 mb-4 inline-block">← Back to Credit notes</router-link>
    <h1 class="text-2xl font-bold text-slate-800 mb-6">New credit note</h1>

    <form @submit.prevent="submit" class="space-y-6 max-w-4xl">
      <div class="bg-white rounded-lg border border-slate-200 p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Customer *</label>
            <select v-model="form.customer_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="">Select customer</option>
              <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Link to invoice (optional)</label>
            <select v-model="form.invoice_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="">— None —</option>
              <option v-for="inv in invoices" :key="inv.id" :value="inv.id">{{ inv.invoice_number }} – {{ inv.customer?.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Credit note date *</label>
            <input v-model="form.credit_note_date" type="date" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Currency</label>
            <select v-model="form.currency" class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="USD">USD</option>
              <option value="EUR">EUR</option>
              <option value="GBP">GBP</option>
            </select>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
          <textarea v-model="form.notes" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
        </div>
      </div>

      <div class="bg-white rounded-lg border border-slate-200 p-6">
        <div class="flex justify-between items-center mb-4">
          <h2 class="font-semibold text-slate-800">Line items</h2>
          <button type="button" @click="addLine" class="text-sm px-3 py-1 bg-slate-100 hover:bg-slate-200 rounded">+ Add line</button>
        </div>
        <table class="w-full text-sm">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="text-left px-2 py-2">Description</th>
              <th class="text-right px-2 py-2 w-24">Qty</th>
              <th class="text-right px-2 py-2 w-28">Price</th>
              <th class="text-right px-2 py-2 w-24">Tax</th>
              <th class="text-right px-2 py-2 w-24">Discount</th>
              <th class="w-10"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(line, i) in form.items" :key="i" class="border-b border-slate-100">
              <td class="px-2 py-2"><input v-model="line.description" type="text" required class="w-full rounded border border-slate-300 px-2 py-1" /></td>
              <td class="px-2 py-2"><input v-model.number="line.quantity" type="number" step="0.01" min="0.01" class="w-full rounded border border-slate-300 px-2 py-1 text-right" /></td>
              <td class="px-2 py-2"><input v-model.number="line.price" type="number" step="0.01" min="0" class="w-full rounded border border-slate-300 px-2 py-1 text-right" /></td>
              <td class="px-2 py-2"><input v-model.number="line.tax" type="number" step="0.01" min="0" class="w-full rounded border border-slate-300 px-2 py-1 text-right" /></td>
              <td class="px-2 py-2"><input v-model.number="line.discount" type="number" step="0.01" min="0" class="w-full rounded border border-slate-300 px-2 py-1 text-right" /></td>
              <td class="px-2 py-2"><button v-if="form.items.length > 1" type="button" @click="form.items.splice(i, 1)" class="text-red-600 text-xs">Remove</button></td>
            </tr>
          </tbody>
        </table>
      </div>

      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
      <div class="flex gap-3">
        <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700" :disabled="saving">Create credit note</button>
        <router-link to="/credit-notes" class="px-4 py-2 border border-slate-300 rounded-lg">Cancel</router-link>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { api } from '../api';

const route = useRoute();
const router = useRouter();
const customers = ref([]);
const invoices = ref([]);
const form = ref({
  customer_id: '',
  invoice_id: '',
  credit_note_date: new Date().toISOString().slice(0, 10),
  currency: 'USD',
  notes: '',
  items: [{ description: '', quantity: 1, price: 0, tax: 0, discount: 0 }],
});
const saving = ref(false);
const error = ref('');

function addLine() {
  form.value.items.push({ description: '', quantity: 1, price: 0, tax: 0, discount: 0 });
}

async function loadCustomers() {
  const { data } = await api().get('/customers?per_page=200');
  customers.value = data.data ?? data ?? [];
}
async function loadInvoices() {
  const { data } = await api().get('/invoices?per_page=200');
  invoices.value = data.data ?? data ?? [];
}

async function loadInvoiceForCredit(invoiceId) {
  try {
    const { data } = await api().get(`/invoices/${invoiceId}`);
    const inv = data.data ?? data;
    form.value.customer_id = inv.customer_id;
    form.value.currency = inv.currency ?? 'USD';
    if (inv.items && inv.items.length) {
      form.value.items = inv.items.map((i) => ({
        description: i.description ?? '',
        quantity: i.quantity ?? 1,
        price: i.price ?? 0,
        tax: i.tax ?? 0,
        discount: i.discount ?? 0,
      }));
    }
  } catch (_) {}
}

onMounted(async () => {
  await loadCustomers();
  await loadInvoices();
  const invoiceId = route.query.invoice_id;
  if (invoiceId) {
    form.value.invoice_id = invoiceId;
    await loadInvoiceForCredit(invoiceId);
  }
});

watch(() => form.value.invoice_id, (id) => {
  if (id) loadInvoiceForCredit(id);
});

async function submit() {
  error.value = '';
  saving.value = true;
  try {
    const payload = {
      customer_id: form.value.customer_id,
      credit_note_date: form.value.credit_note_date,
      currency: form.value.currency,
      notes: form.value.notes || null,
      invoice_id: form.value.invoice_id || null,
      items: form.value.items.map((l) => ({
        description: l.description,
        quantity: parseFloat(l.quantity) || 0,
        price: parseFloat(l.price) || 0,
        tax: parseFloat(l.tax) || 0,
        discount: parseFloat(l.discount) || 0,
      })),
    };
    await api().post('/credit-notes', payload);
    router.push('/credit-notes');
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to create';
  } finally {
    saving.value = false;
  }
}
</script>
