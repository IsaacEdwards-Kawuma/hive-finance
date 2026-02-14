<template>
  <div>
    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
      <h1 class="text-2xl font-bold text-slate-800">{{ t('Vendors') }}</h1>
      <div class="flex gap-2">
        <button type="button" @click="exportCSV" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm">{{ t('Export CSV') }}</button>
        <button @click="openForm()" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm">{{ t('Add vendor') }}</button>
      </div>
    </div>
    <div class="mb-4 flex gap-2">
      <input v-model="search" type="text" :placeholder="t('Search') + ' ' + t('Vendors')" class="rounded-lg border border-slate-300 px-3 py-2 text-sm flex-1 max-w-xs" @keyup.enter="loadVendors" />
      <button type="button" @click="loadVendors" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm">{{ t('Run') }}</button>
    </div>
    <div v-if="loading" class="text-slate-500">{{ t('Loading') }}</div>
    <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium">{{ t('Name') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('Email') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('Phone') }}</th>
            <th class="text-right px-4 py-3 font-medium w-24">{{ t('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="v in vendors" :key="v.id" class="hover:bg-slate-50">
            <td class="px-4 py-3">{{ v.name }}</td>
            <td class="px-4 py-3">{{ v.email || '—' }}</td>
            <td class="px-4 py-3">{{ v.phone || '—' }}</td>
            <td class="px-4 py-3 text-right"><button type="button" @click="openForm(v)" class="text-slate-600 hover:text-slate-800">{{ t('Edit') }}</button></td>
          </tr>
          <tr v-if="!vendors.length">
            <td colspan="4" class="px-4 py-8 text-center text-slate-500">{{ t('No vendors yet. Add one to get started.') }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-10 p-4" @click.self="showForm = false">
      <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6">
        <h2 class="font-semibold text-lg mb-4">{{ editingId ? t('Edit') + ' ' + t('Vendor') : t('New vendor') }}</h2>
        <form @submit.prevent="saveVendor" class="space-y-3">
          <input v-model="form.name" type="text" required :placeholder="t('Name')" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          <input v-model="form.email" type="email" :placeholder="t('Email')" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          <input v-model="form.phone" type="text" :placeholder="t('Phone')" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
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
const vendors = ref([]);
const loading = ref(true);
const showForm = ref(false);
const editingId = ref(null);
const search = ref('');
const form = ref({ name: '', email: '', phone: '' });

async function loadVendors() {
  try {
    const params = { per_page: 100 };
    if ((search.value || '').trim()) params.search = search.value.trim();
    const { data } = await api().get('/vendors', { params });
    vendors.value = Array.isArray(data?.data) ? data.data : [];
  } catch (e) {
    console.error(e);
    vendors.value = [];
  } finally {
    loading.value = false;
  }
}
function openForm(v = null) {
  editingId.value = v ? v.id : null;
  form.value = { name: v?.name ?? '', email: v?.email ?? '', phone: v?.phone ?? '' };
  showForm.value = true;
}
async function saveVendor() {
  try {
    if (editingId.value) {
      await api().put('/vendors/' + editingId.value, form.value);
      toast.show('Vendor updated.', 'success');
    } else {
      await api().post('/vendors', form.value);
      toast.show('Vendor added.', 'success');
    }
    showForm.value = false;
    editingId.value = null;
    form.value = { name: '', email: '', phone: '' };
    loadVendors();
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to save', 'error');
  }
}
function exportCSV() {
  const headers = [t('Name'), t('Email'), t('Phone')];
  const rows = vendors.value.map(v => [v.name, v.email || '', v.phone || ''].map(x => '"' + String(x).replace(/"/g, '""') + '"'));
  const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\r\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'vendors.csv';
  a.click();
  URL.revokeObjectURL(a.href);
}

onMounted(loadVendors);
</script>
