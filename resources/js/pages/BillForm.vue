<template>
  <div>
    <router-link to="/bills" class="text-sm text-slate-600 hover:text-slate-800 mb-4 inline-block">← Back to Bills</router-link>
    <h1 class="text-2xl font-bold text-slate-800 mb-6">{{ isEdit ? 'Edit Bill' : 'New Bill' }}</h1>

    <form @submit.prevent="submit" class="space-y-6 max-w-4xl">
      <div class="bg-white rounded-lg border border-slate-200 p-6 space-y-4">
        <h2 class="font-semibold text-slate-800">Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Vendor *</label>
            <select v-model="form.vendor_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="">Select vendor</option>
              <option v-for="v in vendors" :key="v.id" :value="v.id">{{ v.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Currency</label>
            <select v-model="form.currency" class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="USD">USD</option>
              <option value="EUR">EUR</option>
              <option value="GBP">GBP</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Bill date *</label>
            <input v-model="form.bill_date" type="date" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Due date *</label>
            <input v-model="form.due_date" type="date" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
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
              <th class="text-left px-2 py-2 w-8">#</th>
              <th class="text-left px-2 py-2">Description</th>
              <th class="text-right px-2 py-2 w-24">Qty</th>
              <th class="text-right px-2 py-2 w-28">Price</th>
              <th class="text-right px-2 py-2 w-24">Tax</th>
              <th class="text-right px-2 py-2 w-24">Discount</th>
              <th class="text-right px-2 py-2 w-28">Total</th>
              <th class="w-10"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(line, i) in form.items" :key="i" class="border-b border-slate-100">
              <td class="px-2 py-2">{{ i + 1 }}</td>
              <td class="px-2 py-2"><input v-model="line.description" type="text" required class="w-full rounded border border-slate-300 px-2 py-1" placeholder="Description" /></td>
              <td class="px-2 py-2"><input v-model.number="line.quantity" type="number" step="0.01" min="0.01" class="w-full rounded border border-slate-300 px-2 py-1 text-right" /></td>
              <td class="px-2 py-2"><input v-model.number="line.price" type="number" step="0.01" min="0" class="w-full rounded border border-slate-300 px-2 py-1 text-right" /></td>
              <td class="px-2 py-2"><input v-model.number="line.tax" type="number" step="0.01" min="0" class="w-full rounded border border-slate-300 px-2 py-1 text-right" /></td>
              <td class="px-2 py-2"><input v-model.number="line.discount" type="number" step="0.01" min="0" class="w-full rounded border border-slate-300 px-2 py-1 text-right" /></td>
              <td class="px-2 py-2 text-right font-medium">{{ lineTotal(line) }}</td>
              <td class="px-2 py-2"><button v-if="form.items.length > 1" type="button" @click="form.items.splice(i, 1)" class="text-red-600 text-xs">Remove</button></td>
            </tr>
          </tbody>
        </table>
        <div class="mt-4 flex justify-end">
          <div class="text-right space-y-1">
            <p class="text-slate-600">Subtotal: {{ formatMoney(computedSubtotal) }}</p>
            <p class="text-slate-600">Tax: {{ formatMoney(computedTax) }}</p>
            <p class="text-slate-600">Discount: {{ formatMoney(computedDiscount) }}</p>
            <p class="font-semibold text-lg">Total: {{ formatMoney(computedTotal) }}</p>
          </div>
        </div>
      </div>

      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
      <div class="flex gap-3">
        <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700" :disabled="saving">
          {{ saving ? 'Saving…' : (isEdit ? 'Update bill' : 'Create bill') }}
        </button>
        <router-link v-if="isEdit" to="/bills" class="px-4 py-2 border border-slate-300 rounded-lg">Cancel</router-link>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { api } from '../api';

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => !!route.params.id);
const vendors = ref([]);
const form = ref({
  vendor_id: '',
  currency: 'USD',
  bill_date: new Date().toISOString().slice(0, 10),
  due_date: new Date().toISOString().slice(0, 10),
  notes: '',
  items: [{ description: '', quantity: 1, price: 0, tax: 0, discount: 0 }],
});
const saving = ref(false);
const error = ref('');

function addLine() {
  form.value.items.push({ description: '', quantity: 1, price: 0, tax: 0, discount: 0 });
}
function lineTotal(line) {
  const t = (line.quantity || 0) * (line.price || 0) + (line.tax || 0) - (line.discount || 0);
  return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(t);
}
const computedSubtotal = computed(() => form.value.items.reduce((s, l) => s + (l.quantity || 0) * (l.price || 0), 0));
const computedTax = computed(() => form.value.items.reduce((s, l) => s + (l.tax || 0), 0));
const computedDiscount = computed(() => form.value.items.reduce((s, l) => s + (l.discount || 0), 0));
const computedTotal = computed(() => computedSubtotal.value + computedTax.value - computedDiscount.value);
function formatMoney(n) {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: form.value.currency }).format(n ?? 0);
}

async function loadVendors() {
  const { data } = await api().get('/vendors?per_page=500');
  vendors.value = data.data || data;
}
async function loadBill() {
  const { data } = await api().get(`/bills/${route.params.id}`);
  const bill = data.data || data;
  form.value = {
    vendor_id: bill.vendor_id,
    currency: bill.currency || 'USD',
    bill_date: bill.bill_date?.slice(0, 10) || form.value.bill_date,
    due_date: bill.due_date?.slice(0, 10) || form.value.due_date,
    notes: bill.notes || '',
    items: (bill.items || []).length ? bill.items.map(i => ({ description: i.description, quantity: parseFloat(i.quantity), price: parseFloat(i.price), tax: parseFloat(i.tax) || 0, discount: parseFloat(i.discount) || 0 })) : [{ description: '', quantity: 1, price: 0, tax: 0, discount: 0 }],
  };
}

async function submit() {
  error.value = '';
  saving.value = true;
  try {
    const payload = {
      vendor_id: form.value.vendor_id,
      bill_date: form.value.bill_date,
      due_date: form.value.due_date,
      currency: form.value.currency,
      notes: form.value.notes || null,
      items: form.value.items.map(l => ({ description: l.description, quantity: l.quantity, price: l.price, tax: l.tax || 0, discount: l.discount || 0 })),
    };
    if (isEdit.value) {
      await api().put(`/bills/${route.params.id}`, payload);
    } else {
      const { data } = await api().post('/bills', payload);
      const bill = data.data || data;
      router.push(`/bills/${bill.id}/edit`);
      return;
    }
    router.push('/bills');
  } catch (e) {
    error.value = e.response?.data?.message || (e.response?.data?.errors ? JSON.stringify(e.response.data.errors) : 'Failed to save');
  } finally {
    saving.value = false;
  }
}

onMounted(async () => {
  await loadVendors();
  if (isEdit.value) await loadBill();
});
</script>
