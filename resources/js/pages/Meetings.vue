<template>
  <div>
    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">{{ t('Meetings') }}</h1>
        <p class="text-slate-500 text-sm mt-0.5">{{ t('Schedule and manage meetings. Secretary is responsible for communications and meetings.') }}</p>
      </div>
      <button
        v-if="can('meetings.create')"
        @click="openForm()"
        class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm inline-flex items-center gap-2"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
        {{ t('New meeting') }}
      </button>
    </div>

    <div class="flex flex-wrap gap-3 mb-6">
      <select v-model="filterStatus" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">{{ t('All meetings') }}</option>
        <option value="upcoming">{{ t('Upcoming') }}</option>
        <option value="scheduled">{{ t('Scheduled') }}</option>
        <option value="completed">{{ t('Completed') }}</option>
        <option value="cancelled">{{ t('Cancelled') }}</option>
      </select>
      <button type="button" @click="load" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm">{{ t('Apply') }}</button>
    </div>

    <div v-if="loading" class="text-slate-500 py-8">{{ t('Loading') }}…</div>
    <div v-else class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('Title') }}</th>
              <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('Date & time') }}</th>
              <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('Duration') }}</th>
              <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('Location') }}</th>
              <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('Status') }}</th>
              <th v-if="can('meetings.edit') || can('meetings.delete')" class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('Actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="m in meetings" :key="m.id" class="hover:bg-slate-50/50">
              <td class="px-5 py-4">
                <p class="font-medium text-slate-800">{{ m.title }}</p>
                <p v-if="m.description" class="text-sm text-slate-500 truncate max-w-xs">{{ m.description }}</p>
              </td>
              <td class="px-5 py-4 text-sm text-slate-600">{{ formatDateTime(m.meeting_at) }}</td>
              <td class="px-5 py-4 text-sm text-slate-600">{{ m.duration_minutes }} {{ t('min') }}</td>
              <td class="px-5 py-4 text-sm text-slate-600">{{ m.location || '—' }}</td>
              <td class="px-5 py-4">
                <span :class="statusClass(m.status)" class="text-xs font-medium px-2 py-1 rounded-full">{{ m.status }}</span>
              </td>
              <td v-if="can('meetings.edit') || can('meetings.delete')" class="px-5 py-4 flex gap-2">
                <button v-if="can('meetings.edit')" type="button" @click="openForm(m)" class="text-sm text-slate-600 hover:text-slate-800">{{ t('Edit') }}</button>
                <button v-if="can('meetings.delete')" type="button" @click="confirmDelete(m)" class="text-sm text-red-600 hover:text-red-800">{{ t('Delete') }}</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-if="!meetings.length" class="px-5 py-12 text-center text-slate-500">{{ t('No meetings yet.') }}</p>
      <div v-if="pagination.last_page > 1" class="px-5 py-3 border-t border-slate-100 flex justify-between items-center">
        <span class="text-sm text-slate-500">{{ t('Page') }} {{ pagination.current_page }} {{ t('of') }} {{ pagination.last_page }}</span>
        <div class="flex gap-2">
          <button type="button" :disabled="pagination.current_page <= 1" @click="goPage(pagination.current_page - 1)" class="px-3 py-1 border border-slate-300 rounded text-sm disabled:opacity-50">{{ t('Previous') }}</button>
          <button type="button" :disabled="pagination.current_page >= pagination.last_page" @click="goPage(pagination.current_page + 1)" class="px-3 py-1 border border-slate-300 rounded text-sm disabled:opacity-50">{{ t('Next') }}</button>
        </div>
      </div>
    </div>

    <!-- New/Edit meeting modal -->
    <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-20 p-4" @click.self="showForm = false">
      <div class="bg-white rounded-xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-6">
        <h2 class="font-semibold text-lg mb-4">{{ editingId ? t('Edit meeting') : t('New meeting') }}</h2>
        <form @submit.prevent="save" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Title') }} *</label>
            <input v-model="form.title" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Description') }}</label>
            <textarea v-model="form.description" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Location') }}</label>
            <input v-model="form.location" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2" :placeholder="t('Room or address')" />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Date & time') }} *</label>
              <input v-model="form.meeting_at" type="datetime-local" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Duration (minutes)') }}</label>
              <input v-model.number="form.duration_minutes" type="number" min="5" max="480" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Status') }}</label>
            <select v-model="form.status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="scheduled">{{ t('Scheduled') }}</option>
              <option value="completed">{{ t('Completed') }}</option>
              <option value="cancelled">{{ t('Cancelled') }}</option>
            </select>
          </div>
          <p v-if="formError" class="text-sm text-red-600">{{ formError }}</p>
          <div class="flex gap-2 pt-2">
            <button type="button" @click="showForm = false" class="px-4 py-2 border border-slate-300 rounded-lg">{{ t('Cancel') }}</button>
            <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg" :disabled="saving">{{ saving ? '…' : t('Save') }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { api } from '../api';
import { useI18n } from '../i18n';
import { useToast } from '../composables/useToast';
import { usePermissions } from '../composables/usePermissions';

const { t } = useI18n();
const toast = useToast();
const { can } = usePermissions();

const meetings = ref([]);
const loading = ref(true);
const filterStatus = ref('upcoming');
const pagination = ref({ current_page: 1, last_page: 1 });
const showForm = ref(false);
const editingId = ref(null);
const form = ref({
  title: '',
  description: '',
  location: '',
  meeting_at: '',
  duration_minutes: 60,
  status: 'scheduled',
});
const formError = ref('');
const saving = ref(false);

function formatDateTime(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  return d.toLocaleString(undefined, { dateStyle: 'short', timeStyle: 'short' });
}

function statusClass(status) {
  const map = { scheduled: 'bg-blue-100 text-blue-700', completed: 'bg-emerald-100 text-emerald-700', cancelled: 'bg-slate-100 text-slate-600' };
  return map[status] || 'bg-slate-100 text-slate-600';
}

async function load() {
  loading.value = true;
  try {
    const params = { page: pagination.value.current_page, per_page: 15 };
    if (filterStatus.value === 'upcoming') {
      params.upcoming = 1;
    } else if (filterStatus.value) {
      params.status = filterStatus.value;
    }
    const { data } = await api().get('/meetings', { params });
    meetings.value = data.data ?? [];
    pagination.value = {
      current_page: data.current_page ?? 1,
      last_page: data.last_page ?? 1,
    };
  } catch (e) {
    console.error(e);
    meetings.value = [];
  } finally {
    loading.value = false;
  }
}

function openForm(meeting = null) {
  editingId.value = meeting?.id ?? null;
  form.value = {
    title: meeting?.title ?? '',
    description: meeting?.description ?? '',
    location: meeting?.location ?? '',
    meeting_at: meeting?.meeting_at ? new Date(meeting.meeting_at).toISOString().slice(0, 16) : '',
    duration_minutes: meeting?.duration_minutes ?? 60,
    status: meeting?.status ?? 'scheduled',
  };
  formError.value = '';
  showForm.value = true;
}

async function save() {
  formError.value = '';
  saving.value = true;
  try {
    const payload = {
      title: form.value.title,
      description: form.value.description || null,
      location: form.value.location || null,
      meeting_at: form.value.meeting_at,
      duration_minutes: form.value.duration_minutes || 60,
      status: form.value.status,
    };
    if (editingId.value) {
      await api().put('/meetings/' + editingId.value, payload);
      toast.success(t('Meeting updated.'));
    } else {
      await api().post('/meetings', payload);
      toast.success(t('Meeting created.'));
    }
    showForm.value = false;
    load();
  } catch (e) {
    formError.value = e.response?.data?.message || t('Something went wrong.');
  } finally {
    saving.value = false;
  }
}

function confirmDelete(meeting) {
  if (!confirm(t('Delete this meeting?'))) return;
  api()
    .delete('/meetings/' + meeting.id)
    .then(() => {
      toast.success(t('Meeting deleted.'));
      load();
    })
    .catch((e) => toast.error(e.response?.data?.message || t('Could not delete.')));
}

function goPage(page) {
  pagination.value.current_page = page;
  load();
}

watch(filterStatus, () => {
  pagination.value.current_page = 1;
  load();
});

onMounted(load);
</script>
