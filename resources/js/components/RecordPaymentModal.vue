<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="close">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">Record payment</h2>
        <p class="text-sm text-slate-600 mb-4">Balance due: {{ formatMoney(balanceDue) }}</p>
        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Amount *</label>
            <input v-model.number="form.amount" type="number" step="0.01" min="0.01" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Date *</label>
            <input v-model="form.paid_at" type="date" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Bank account</label>
            <select v-model="form.bank_account_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="">— None —</option>
              <option v-for="b in bankAccounts" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Payment method</label>
            <input v-model="form.payment_method" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="e.g. Bank transfer" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Reference</label>
            <input v-model="form.reference" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="close" class="px-4 py-2 border border-slate-300 rounded-lg">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700" :disabled="saving">{{ saving ? 'Saving…' : 'Record' }}</button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { api } from '../api';

const props = defineProps({
  open: Boolean,
  balanceDue: { type: Number, default: 0 },
  recordUrl: { type: String, required: true },
});
const emit = defineEmits(['close', 'saved']);

const form = ref({
  amount: 0,
  paid_at: new Date().toISOString().slice(0, 10),
  bank_account_id: '',
  payment_method: '',
  reference: '',
});
const bankAccounts = ref([]);
const saving = ref(false);
const error = ref('');

function formatMoney(n) {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(n ?? 0);
}

watch(() => [props.open, props.balanceDue], () => {
  if (props.open) {
    form.value.amount = Math.max(0, props.balanceDue);
    form.value.paid_at = new Date().toISOString().slice(0, 10);
    form.value.bank_account_id = '';
    form.value.payment_method = '';
    form.value.reference = '';
    error.value = '';
  }
}, { immediate: true });

async function loadBankAccounts() {
  try {
    const { data } = await api().get('/bank-accounts?per_page=100');
    bankAccounts.value = data.data ?? data;
  } catch (e) {
    bankAccounts.value = [];
  }
}

function close() {
  emit('close');
}
async function submit() {
  error.value = '';
  saving.value = true;
  try {
    await api().post(props.recordUrl, {
      amount: form.value.amount,
      paid_at: form.value.paid_at,
      bank_account_id: form.value.bank_account_id || null,
      payment_method: form.value.payment_method || null,
      reference: form.value.reference || null,
    });
    emit('saved');
    close();
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to record payment';
  } finally {
    saving.value = false;
  }
}

onMounted(loadBankAccounts);
</script>
