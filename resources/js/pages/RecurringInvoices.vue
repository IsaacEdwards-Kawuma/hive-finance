<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-slate-800">Recurring Invoices</h1>
      <button type="button" @click="openCreate" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm">Add recurring invoice</button>
    </div>
    <div v-if="loading" class="text-slate-500">Loading…</div>
    <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium">Customer</th>
            <th class="text-left px-4 py-3 font-medium">Frequency</th>
            <th class="text-left px-4 py-3 font-medium">Next run</th>
            <th class="text-left px-4 py-3 font-medium">End date</th>
            <th class="text-center px-4 py-3 font-medium">Enabled</th>
            <th class="text-right px-4 py-3 font-medium w-32">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="r in list" :key="r.id">
            <td class="px-4 py-3">{{ r.customer?.name || '—' }}</td>
            <td class="px-4 py-3 capitalize">{{ r.frequency }}</td>
            <td class="px-4 py-3">{{ formatDate(r.next_run_date) }}</td>
            <td class="px-4 py-3">{{ r.end_date ? formatDate(r.end_date) : '—' }}</td>
            <td class="px-4 py-3 text-center">
              <span :class="r.enabled ? 'text-green-600' : 'text-slate-400'">{{ r.enabled ? 'Yes' : 'No' }}</span>
            </td>
            <td class="px-4 py-3 text-right">
              <button type="button" @click="openEdit(r)" class="text-slate-600 hover:text-slate-800 mr-2">Edit</button>
              <button type="button" @click="confirmDelete(r)" class="text-red-600 hover:text-red-800">Delete</button>
            </td>
          </tr>
          <tr v-if="!list.length">
            <td colspan="6" class="px-4 py-8 text-center text-slate-500">No recurring invoices yet.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create/Edit modal -->
    <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-10 p-4 overflow-y-auto" @click.self="showForm = false">
      <div class="bg-white rounded-xl shadow-xl max-w-lg w-full my-8 p-6">
        <h2 class="font-semibold text-lg mb-4">{{ editingId ? 'Edit recurring invoice' : 'New recurring invoice' }}</h2>
        <form @submit.prevent="save" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Customer *</label>
            <select v-model="form.customer_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2" :disabled="!!editingId">
              <option value="">Select customer</option>
              <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Frequency *</label>
            <select v-model="form.frequency" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="daily">Daily</option>
              <option value="weekly">Weekly</option>
              <option value="monthly">Monthly</option>
              <option value="yearly">Yearly</option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Start date *</label>
              <input v-model="form.start_date" type="date" required class="w-full rounded-lg border border-slate-300 px-3 py-2" :disabled="!!editingId" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">End date</label>
              <input v-model="form.end_date" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
            </div>
          </div>
          <div v-if="editingId" class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Next run date</label>
              <input v-model="form.next_run_date" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
            </div>
          </div>
          <div class="flex items-center gap-2">
            <input v-model="form.enabled" type="checkbox" id="recur-enabled" class="rounded border-slate-300" />
            <label for="recur-enabled" class="text-sm text-slate-700">Enabled</label>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Line items (template)</label>
            <div class="space-y-2 border border-slate-200 rounded-lg p-3 bg-slate-50">
              <div v-for="(item, idx) in form.templateItems" :key="idx" class="flex gap-2 items-start">
                <input v-model="item.description" placeholder="Description" class="flex-1 rounded border border-slate-300 px-2 py-1 text-sm" />
                <input v-model.number="item.quantity" type="number" min="0.01" step="0.01" placeholder="Qty" class="w-16 rounded border border-slate-300 px-2 py-1 text-sm" />
                <input v-model.number="item.price" type="number" min="0" step="0.01" placeholder="Price" class="w-20 rounded border border-slate-300 px-2 py-1 text-sm" />
                <button type="button" @click="form.templateItems.splice(idx, 1)" class="text-red-600 hover:text-red-800 text-sm">×</button>
              </div>
              <button type="button" @click="form.templateItems.push({ description: '', quantity: 1, price: 0, tax: 0, discount: 0 })" class="text-sm text-slate-600 hover:text-slate-800">+ Add line</button>
            </div>
          </div>
          <div class="flex gap-2 pt-2">
            <button type="button" @click="showForm = false" class="flex-1 py-2 border border-slate-300 rounded-lg">Cancel</button>
            <button type="submit" class="flex-1 py-2 bg-slate-800 text-white rounded-lg">Save</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete confirm -->
    <div v-if="deleting" class="fixed inset-0 bg-black/50 flex items-center justify-center z-10 p-4" @click.self="deleting = null">
      <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6">
        <p class="text-slate-700 mb-4">Delete this recurring invoice?</p>
        <div class="flex gap-2">
          <button type="button" @click="deleting = null" class="flex-1 py-2 border border-slate-300 rounded-lg">Cancel</button>
          <button type="button" @click="doDelete" class="flex-1 py-2 bg-red-600 text-white rounded-lg">Delete</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { api } from '../api';

const list = ref([]);
const loading = ref(true);
const customers = ref([]);
const showForm = ref(false);
const editingId = ref(null);
const deleting = ref(null);

const form = ref({
  customer_id: '',
  frequency: 'monthly',
  start_date: new Date().toISOString().slice(0, 10),
  end_date: '',
  next_run_date: '',
  enabled: true,
  templateItems: [{ description: 'Recurring charge', quantity: 1, price: 0, tax: 0, discount: 0 }],
});

function formatDate(d) {
  if (!d) return '—';
  return typeof d === 'string' ? d.slice(0, 10) : (d.toISOString?.()?.slice(0, 10) ?? '—');
}

async function load() {
  loading.value = true;
  try {
    const { data } = await api().get('/recurring-invoices?per_page=100');
    list.value = data.data ?? data;
    if (Array.isArray(list.value)) return;
    list.value = list.value.data ?? list.value ?? [];
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
}

async function loadCustomers() {
  const { data } = await api().get('/customers?per_page=500');
  customers.value = data.data ?? data ?? [];
}

function openCreate() {
  editingId.value = null;
  form.value = {
    customer_id: '',
    frequency: 'monthly',
    start_date: new Date().toISOString().slice(0, 10),
    end_date: '',
    next_run_date: '',
    enabled: true,
    templateItems: [{ description: 'Recurring charge', quantity: 1, price: 0, tax: 0, discount: 0 }],
  };
  showForm.value = true;
}

function openEdit(r) {
  editingId.value = r.id;
  const items = (r.template?.items ?? []).length ? r.template.items : [{ description: 'Recurring charge', quantity: 1, price: 0, tax: 0, discount: 0 }];
  form.value = {
    customer_id: r.customer_id,
    frequency: r.frequency,
    start_date: r.start_date?.slice?.(0, 10) ?? '',
    end_date: (r.end_date?.slice?.(0, 10)) ?? '',
    next_run_date: r.next_run_date?.slice?.(0, 10) ?? '',
    enabled: r.enabled !== false,
    templateItems: items.map((i) => ({ ...i, quantity: i.quantity ?? 1, price: i.price ?? 0, tax: i.tax ?? 0, discount: i.discount ?? 0 })),
  };
  showForm.value = true;
}

async function save() {
  const payload = {
    customer_id: form.value.customer_id,
    frequency: form.value.frequency,
    start_date: form.value.start_date,
    end_date: form.value.end_date || null,
    enabled: form.value.enabled,
    template: { items: form.value.templateItems },
  };
  if (editingId.value) {
    payload.next_run_date = form.value.next_run_date || form.value.start_date;
    await api().put(`/recurring-invoices/${editingId.value}`, payload);
  } else {
    await api().post('/recurring-invoices', payload);
  }
  showForm.value = false;
  load();
}

function confirmDelete(r) {
  deleting.value = r;
}

async function doDelete() {
  if (!deleting.value) return;
  await api().delete(`/recurring-invoices/${deleting.value.id}`);
  deleting.value = null;
  load();
}

onMounted(() => {
  loadCustomers();
  load();
});
</script>
