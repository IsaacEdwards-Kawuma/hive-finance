<template>
  <div>
    <router-link to="/journal-entries" class="text-sm text-slate-600 hover:text-slate-800 mb-4 inline-block">← Back to Journal Entries</router-link>
    <h1 class="text-2xl font-bold text-slate-800 mb-6">{{ entryId ? 'Edit Journal Entry' : 'New Journal Entry' }}</h1>

    <form @submit.prevent="submit" class="space-y-6 max-w-4xl">
      <div class="bg-white rounded-lg border border-slate-200 p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Date *</label>
            <input v-model="form.date" type="date" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
            <input v-model="form.description" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Reference</label>
            <input v-model="form.reference" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg border border-slate-200 p-6">
        <div class="flex justify-between items-center mb-4">
          <h2 class="font-semibold text-slate-800">Lines (debits must equal credits)</h2>
          <button type="button" @click="addLine" class="text-sm px-3 py-1 bg-slate-100 hover:bg-slate-200 rounded">+ Add line</button>
        </div>
        <table class="w-full text-sm">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="text-left px-2 py-2">Account</th>
              <th class="text-right px-2 py-2 w-32">Debit</th>
              <th class="text-right px-2 py-2 w-32">Credit</th>
              <th class="text-left px-2 py-2">Memo</th>
              <th class="w-10"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(line, i) in form.lines" :key="i" class="border-b border-slate-100">
              <td class="px-2 py-2">
                <select v-model="line.account_id" required class="w-full rounded border border-slate-300 px-2 py-1">
                  <option value="">Select account</option>
                  <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.code }} – {{ a.name }}</option>
                </select>
              </td>
              <td class="px-2 py-2"><input v-model.number="line.debit" type="number" step="0.01" min="0" class="w-full rounded border border-slate-300 px-2 py-1 text-right" placeholder="0" /></td>
              <td class="px-2 py-2"><input v-model.number="line.credit" type="number" step="0.01" min="0" class="w-full rounded border border-slate-300 px-2 py-1 text-right" placeholder="0" /></td>
              <td class="px-2 py-2"><input v-model="line.memo" type="text" class="w-full rounded border border-slate-300 px-2 py-1" /></td>
              <td class="px-2 py-2"><button v-if="form.lines.length > 2" type="button" @click="form.lines.splice(i, 1)" class="text-red-600 text-xs">Remove</button></td>
            </tr>
          </tbody>
        </table>
        <div class="mt-4 flex justify-end gap-4">
          <p class="text-slate-600">Total Debit: {{ formatMoney(totalDebit) }}</p>
          <p class="text-slate-600">Total Credit: {{ formatMoney(totalCredit) }}</p>
          <p :class="balanced ? 'text-green-600' : 'text-red-600'">{{ balanced ? 'Balanced' : 'Out of balance' }}</p>
        </div>
      </div>

      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
      <div class="flex gap-3">
        <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700" :disabled="saving || !balanced">
          {{ saving ? 'Saving…' : (entryId ? 'Update entry' : 'Create entry') }}
        </button>
        <router-link to="/journal-entries" class="px-4 py-2 border border-slate-300 rounded-lg">Cancel</router-link>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { api } from '../api';

const router = useRouter();
const route = useRoute();
const entryId = ref(route.params.id || null);
const accounts = ref([]);
const form = ref({
  date: new Date().toISOString().slice(0, 10),
  description: '',
  reference: '',
  lines: [
    { account_id: '', debit: 0, credit: 0, memo: '' },
    { account_id: '', debit: 0, credit: 0, memo: '' },
  ],
});
const saving = ref(false);
const error = ref('');

const totalDebit = computed(() => form.value.lines.reduce((s, l) => s + (parseFloat(l.debit) || 0), 0));
const totalCredit = computed(() => form.value.lines.reduce((s, l) => s + (parseFloat(l.credit) || 0), 0));
const balanced = computed(() => form.value.lines.length >= 2 && Math.abs(totalDebit.value - totalCredit.value) < 0.01);

function addLine() {
  form.value.lines.push({ account_id: '', debit: 0, credit: 0, memo: '' });
}
function formatMoney(n) {
  return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(n ?? 0);
}

async function loadAccounts() {
  const { data } = await api().get('/accounts?per_page=500');
  accounts.value = data.data || data;
}

async function loadEntry(id) {
  try {
    const { data } = await api().get('/journal-entries/' + id);
    const e = data.data || data;
    if (e.status === 'posted') {
      error.value = 'Cannot edit posted entry';
      return;
    }
    form.value = {
      date: e.date?.slice(0, 10) || new Date().toISOString().slice(0, 10),
      description: e.description || '',
      reference: e.reference || '',
      lines: (e.lines || []).length >= 2
        ? e.lines.map((l) => ({
            account_id: l.account_id,
            debit: parseFloat(l.debit) || 0,
            credit: parseFloat(l.credit) || 0,
            memo: l.memo || '',
          }))
        : [
            { account_id: '', debit: 0, credit: 0, memo: '' },
            { account_id: '', debit: 0, credit: 0, memo: '' },
          ],
    };
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to load';
  }
}

async function submit() {
  error.value = '';
  if (!balanced.value) return;
  saving.value = true;
  try {
    const payload = {
      date: form.value.date,
      description: form.value.description || null,
      reference: form.value.reference || null,
      lines: form.value.lines.map((l) => ({
        account_id: l.account_id,
        debit: parseFloat(l.debit) || 0,
        credit: parseFloat(l.credit) || 0,
        memo: l.memo || null,
      })),
    };
    if (entryId.value) {
      await api().put('/journal-entries/' + entryId.value, payload);
    } else {
      await api().post('/journal-entries', payload);
    }
    router.push('/journal-entries');
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to save';
  } finally {
    saving.value = false;
  }
}

watch(() => route.params.id, (id) => {
  entryId.value = id || null;
  if (id) loadEntry(id);
}, { immediate: true });

onMounted(() => {
  loadAccounts();
  if (entryId.value) loadEntry(entryId.value);
});
</script>
