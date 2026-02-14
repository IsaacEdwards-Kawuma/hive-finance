<template>
  <div>
    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
      <h1 class="text-2xl font-bold text-slate-800">{{ t('Tax rates') }}</h1>
      <div class="flex gap-2">
        <button type="button" @click="exportCSV" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm">{{ t('Export CSV') }}</button>
        <button @click="showForm = true" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm">{{ t('Add tax rate') }}</button>
      </div>
    </div>
    <div v-if="loading" class="text-slate-500">{{ t('Loading') }}</div>
    <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium">{{ t('Name') }}</th>
            <th class="text-right px-4 py-3 font-medium">{{ t('Rate') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('Type') }}</th>
            <th class="text-right px-4 py-3 font-medium w-28">{{ t('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="r in taxRates" :key="r.id" class="hover:bg-slate-50">
            <td class="px-4 py-3">{{ r.name }}</td>
            <td class="px-4 py-3 text-right">{{ r.rate }}%</td>
            <td class="px-4 py-3 capitalize">{{ r.type || 'normal' }}</td>
            <td class="px-4 py-3 text-right space-x-2">
              <button type="button" @click="openEdit(r)" class="text-slate-600 hover:text-slate-800">{{ t('Edit') }}</button>
              <button type="button" @click="confirmDelete(r)" class="text-red-600 hover:text-red-800">{{ t('Delete') }}</button>
            </td>
          </tr>
          <tr v-if="!taxRates.length">
            <td colspan="4" class="px-4 py-8 text-center text-slate-500">{{ t('No tax rates yet. Add one to get started.') }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-10 p-4" @click.self="showForm = false">
      <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6">
        <h2 class="font-semibold text-lg mb-4">{{ editingId ? t('Edit') + ' ' + t('Tax rates') : t('New tax rate') }}</h2>
        <form @submit.prevent="saveTaxRate" class="space-y-3">
          <input v-model="form.name" type="text" required :placeholder="t('Name')" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Rate') }} (%)</label>
            <input v-model.number="form.rate" type="number" step="0.01" min="0" max="100" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <select v-model="form.type" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="normal">Normal</option>
            <option value="inclusive">Inclusive</option>
            <option value="compound">Compound</option>
          </select>
          <div class="flex gap-2 pt-2">
            <button type="button" @click="showForm = false; editingId = null" class="flex-1 py-2 border border-slate-300 rounded-lg">{{ t('Cancel') }}</button>
            <button type="submit" class="flex-1 py-2 bg-slate-800 text-white rounded-lg">{{ t('Save') }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { api } from '../api';
import { useI18n } from '../i18n';
import { useToast } from '../composables/useToast';

const { t } = useI18n();
const toast = useToast();
const taxRates = ref([]);
const loading = ref(true);
const showForm = ref(false);
const editingId = ref(null);
const form = ref({ name: '', rate: 0, type: 'normal' });

async function loadTaxRates() {
  try {
    const { data } = await api().get('/tax-rates');
    taxRates.value = data.data || data || [];
  } catch (e) {
    console.error(e);
    taxRates.value = [];
  } finally {
    loading.value = false;
  }
}
function openEdit(r) {
  editingId.value = r.id;
  form.value = { name: r.name, rate: parseFloat(r.rate) || 0, type: r.type || 'normal' };
  showForm.value = true;
}
async function saveTaxRate() {
  try {
    if (editingId.value) {
      await api().put('/tax-rates/' + editingId.value, form.value);
      toast.show('Tax rate updated.', 'success');
    } else {
      await api().post('/tax-rates', form.value);
      toast.show('Tax rate added.', 'success');
    }
    showForm.value = false;
    editingId.value = null;
    form.value = { name: '', rate: 0, type: 'normal' };
    loadTaxRates();
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to save', 'error');
  }
}
async function confirmDelete(r) {
  const ok = await toast.showConfirm('Delete this tax rate?');
  if (!ok) return;
  try {
    await api().delete('/tax-rates/' + r.id);
    toast.show('Tax rate deleted.', 'success');
    loadTaxRates();
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to delete', 'error');
  }
}
function exportCSV() {
  const headers = [t('Name'), t('Rate'), t('Type')];
  const rows = taxRates.value.map(r => [r.name, r.rate + '%', r.type || 'normal']);
  const csv = [headers.join(','), ...rows.map(r => r.map(c => '"' + String(c).replace(/"/g, '""') + '"').join(','))].join('\r\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'tax-rates.csv';
  a.click();
  URL.revokeObjectURL(a.href);
}

onMounted(loadTaxRates);
</script>
