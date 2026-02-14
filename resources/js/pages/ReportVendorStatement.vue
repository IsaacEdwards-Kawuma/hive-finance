<template>
  <div>
    <router-link to="/reports" class="text-sm text-slate-600 hover:text-slate-800 mb-4 inline-block">← Reports</router-link>
    <h1 class="text-2xl font-bold text-slate-800 mb-6">Vendor statement</h1>
    <div class="flex flex-wrap gap-4 items-end mb-6">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Vendor</label>
        <select v-model="vendorId" class="rounded-lg border border-slate-300 px-3 py-2 min-w-[200px]">
          <option value="">Select vendor</option>
          <option v-for="v in vendors" :key="v.id" :value="v.id">{{ v.name }}</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">From</label>
        <input v-model="from" type="date" class="rounded-lg border border-slate-300 px-3 py-2" />
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">To</label>
        <input v-model="to" type="date" class="rounded-lg border border-slate-300 px-3 py-2" />
      </div>
      <button type="button" @click="load" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700">Run</button>
      <button v-if="vendorId" type="button" @click="downloadPdf" class="px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 text-sm">Download PDF</button>
    </div>
    <div v-if="loading" class="text-slate-500">Loading…</div>
    <div v-else-if="lines.length" class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium">Type</th>
            <th class="text-left px-4 py-3 font-medium">Date</th>
            <th class="text-left px-4 py-3 font-medium">Number</th>
            <th class="text-right px-4 py-3 font-medium">Amount</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="(line, i) in lines" :key="i">
            <td class="px-4 py-3">{{ line.type }}</td>
            <td class="px-4 py-3">{{ line.date }}</td>
            <td class="px-4 py-3">{{ line.number }}</td>
            <td class="px-4 py-3 text-right">{{ formatMoney(line.amount) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-else-if="run" class="text-slate-500">No data for this period.</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { api, downloadReportPdf } from '../api';

const vendors = ref([]);
const vendorId = ref('');
const from = ref(new Date(Date.now() - 365 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10));
const to = ref(new Date().toISOString().slice(0, 10));
const lines = ref([]);
const loading = ref(false);
const run = ref(false);

function formatMoney(n) {
  return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(parseFloat(n) ?? 0);
}

async function loadVendors() {
  const { data } = await api().get('/vendors?per_page=500');
  vendors.value = data.data ?? data ?? [];
}

async function load() {
  if (!vendorId.value) return;
  loading.value = true;
  run.value = true;
  try {
    const { data } = await api().get('/reports/vendor-statement', { params: { vendor_id: vendorId.value, from: from.value, to: to.value } });
    const d = data.data ?? data;
    lines.value = d.lines ?? [];
  } catch (_) {
    lines.value = [];
  } finally {
    loading.value = false;
  }
}

function downloadPdf() {
  downloadReportPdf('/reports/vendor-statement/pdf', { vendor_id: vendorId.value, from: from.value, to: to.value });
}
onMounted(loadVendors);
</script>
