<template>
  <div>
    <h1 class="text-2xl font-bold text-slate-800 mb-2">{{ t('My availability') }}</h1>
    <p class="text-slate-600 mb-6">{{ t('My availability description') }}</p>
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6 max-w-md">
      <h2 class="font-semibold text-slate-800 mb-3">{{ t('Add availability') }}</h2>
      <form @submit.prevent="addSlot" class="space-y-3">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Day') }}</label>
          <select v-model="form.day_of_week" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
            <option v-for="(label, i) in dayLabels" :key="i" :value="i">{{ label }}</option>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Start time') }}</label>
            <input v-model="form.start_time" type="time" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('End time') }}</label>
            <input v-model="form.end_time" type="time" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Notes') }}</label>
          <input v-model="form.notes" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm">{{ t('Add') }}</button>
      </form>
    </div>
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <h2 class="px-4 py-3 border-b border-slate-200 font-semibold text-slate-800">{{ t('Your availability') }}</h2>
      <div v-if="loading" class="p-4 text-slate-500">{{ t('Loading') }}</div>
      <table v-else class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium">{{ t('Day') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('Start time') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('End time') }}</th>
            <th class="text-left px-4 py-3 font-medium">{{ t('Notes') }}</th>
            <th class="w-20"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="a in availabilities" :key="a.id">
            <td class="px-4 py-3">{{ dayLabels[a.day_of_week] }}</td>
            <td class="px-4 py-3">{{ a.start_time }}</td>
            <td class="px-4 py-3">{{ a.end_time }}</td>
            <td class="px-4 py-3 text-slate-500">{{ a.notes || '—' }}</td>
            <td class="px-4 py-3"><button type="button" @click="remove(a)" class="text-red-600 hover:text-red-800 text-sm">{{ t('Delete') }}</button></td>
          </tr>
          <tr v-if="!availabilities.length">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ t('No availability set. Add slots above.') }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { api } from '../../api';
import { useI18n } from '../../i18n';
import { useToast } from '../../composables/useToast';

const { t } = useI18n();
const toast = useToast();
const availabilities = ref([]);
const loading = ref(true);
const form = ref({ day_of_week: 1, start_time: '09:00', end_time: '17:00', notes: '' });
const dayLabels = [t('Sunday'), t('Monday'), t('Tuesday'), t('Wednesday'), t('Thursday'), t('Friday'), t('Saturday')];

async function load() {
  try {
    const { data } = await api().get('/employee/availabilities');
    availabilities.value = data.data || [];
  } catch (_) {
    availabilities.value = [];
  } finally {
    loading.value = false;
  }
}

async function addSlot() {
  try {
    await api().post('/employee/availabilities', form.value);
    toast.show(t('Availability added.'), 'success');
    form.value = { day_of_week: 1, start_time: '09:00', end_time: '17:00', notes: '' };
    load();
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to add', 'error');
  }
}

async function remove(a) {
  if (!(await toast.showConfirm(t('Remove this slot?')))) return;
  try {
    await api().delete('/employee/availabilities/' + a.id);
    load();
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to remove', 'error');
  }
}

onMounted(load);
</script>
