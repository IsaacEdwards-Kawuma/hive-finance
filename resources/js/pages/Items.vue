<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-slate-800">{{ t('Items') }}</h1>
      <button @click="showForm = true" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm">{{ t('Add item') }}</button>
    </div>
    <div v-if="loading" class="text-slate-500">{{ t('Loading') }}</div>
    <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium">{{ t('Name') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('SKU') }}</th>
            <th class="text-right px-4 py-3 font-medium">{{ t('Sale price') }}</th>
            <th class="text-right px-4 py-3 font-medium">{{ t('Purchase price') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="i in items" :key="i.id" class="hover:bg-slate-50">
            <td class="px-4 py-3">{{ i.name }}</td>
            <td class="px-4 py-3">{{ i.sku || '—' }}</td>
            <td class="px-4 py-3 text-right">{{ formatMoney(i.sale_price) }}</td>
            <td class="px-4 py-3 text-right">{{ formatMoney(i.purchase_price) }}</td>
          </tr>
          <tr v-if="!items.length">
            <td colspan="4" class="px-4 py-8 text-center text-slate-500">{{ t('No items yet. Add one to get started.') }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-10 p-4" @click.self="showForm = false">
      <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 max-h-[90vh] overflow-y-auto">
        <h2 class="font-semibold text-lg mb-4">{{ t('New item') }}</h2>
        <form @submit.prevent="addItem" class="space-y-3">
          <input v-model="form.name" type="text" required :placeholder="t('Name')" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          <input v-model="form.sku" type="text" :placeholder="t('SKU')" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          <textarea v-model="form.description" :placeholder="t('Description')" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Sale price') }}</label>
            <input v-model.number="form.sale_price" type="number" step="0.01" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Purchase price') }}</label>
            <input v-model.number="form.purchase_price" type="number" step="0.01" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div class="flex gap-2 pt-2">
            <button type="button" @click="showForm = false" class="flex-1 py-2 border border-slate-300 rounded-lg">{{ t('Cancel') }}</button>
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
import { useFormats } from '../composables/useFormats';
import { useI18n } from '../i18n';
import { useToast } from '../composables/useToast';

const { formatNumber } = useFormats();
const toast = useToast();
const { t } = useI18n();
const items = ref([]);
const loading = ref(true);
const showForm = ref(false);
const form = ref({ name: '', sku: '', description: '', sale_price: 0, purchase_price: 0 });

function formatMoney(n) {
  return formatNumber(n ?? 0, { currency: true });
}

async function loadItems() {
  try {
    const { data } = await api().get('/items?per_page=100');
    items.value = data.data || data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
}
async function addItem() {
  try {
    await api().post('/items', form.value);
    showForm.value = false;
    form.value = { name: '', sku: '', description: '', sale_price: 0, purchase_price: 0 };
    loadItems();
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to add', 'error');
  }
}

onMounted(loadItems);
</script>
