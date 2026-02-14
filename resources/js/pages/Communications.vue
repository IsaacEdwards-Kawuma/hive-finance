<template>
  <div>
    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
      <h1 class="text-2xl font-bold text-slate-800">{{ t('Communications') }}</h1>
      <div class="flex gap-2">
        <button type="button" @click="exportCSV" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm">{{ t('Export CSV') }}</button>
        <button
          v-if="can('communications.create')"
          @click="openForm()"
          class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm"
        >
          {{ t('New announcement') }}
        </button>
      </div>
    </div>
    <p class="text-slate-600 mb-4">{{ t('Communications description') }}</p>

    <div class="flex flex-wrap gap-3 mb-6">
      <input
        v-model="filters.search"
        type="text"
        :placeholder="t('Search') + ' ' + t('Title') + ' / ' + t('Message')"
        class="rounded-lg border border-slate-300 px-3 py-2 text-sm w-64 max-w-full"
        @keyup.enter="applyFilters"
      />
      <select v-model="filters.sort" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="newest">{{ t('Newest first') }}</option>
        <option value="oldest">{{ t('Oldest first') }}</option>
      </select>
      <select v-model="filters.pinned" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">{{ t('All announcements') }}</option>
        <option value="1">{{ t('Pinned only') }}</option>
      </select>
      <button type="button" @click="applyFilters" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm">{{ t('Run') }}</button>
    </div>

    <div v-if="loading" class="text-slate-500">{{ t('Loading') }}</div>
    <div v-else class="space-y-4">
      <article
        v-for="c in communications"
        :key="c.id"
        class="bg-white rounded-lg border border-slate-200 p-5"
        :class="{ 'border-l-4 border-l-slate-800': c.pinned }"
      >
        <div class="flex justify-between items-start gap-4">
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 flex-wrap">
              <h2 class="font-semibold text-slate-800">{{ c.title }}</h2>
              <span v-if="c.pinned" class="text-xs bg-slate-200 text-slate-700 px-2 py-0.5 rounded">{{ t('Pinned') }}</span>
            </div>
            <p class="text-sm text-slate-500 mt-0.5">
              {{ c.user?.name }} · {{ formatDate(c.created_at) }}
              <span v-if="audienceLabel(c)" class="text-slate-400"> · {{ audienceLabel(c) }}</span>
            </p>
            <p class="text-slate-700 mt-2 whitespace-pre-wrap">{{ c.body }}</p>
          </div>
          <div v-if="can('communications.edit') || can('communications.delete')" class="flex gap-2 shrink-0">
            <button v-if="can('communications.edit')" type="button" @click="openEdit(c)" class="text-sm text-slate-600 hover:text-slate-800">{{ t('Edit') }}</button>
            <button v-if="can('communications.delete')" type="button" @click="confirmDelete(c)" class="text-sm text-red-600 hover:text-red-800">{{ t('Delete') }}</button>
          </div>
        </div>
      </article>
      <p v-if="!communications.length" class="text-slate-500 py-8 text-center">{{ t('No communications yet.') }}</p>
      <div v-if="hasMore && communications.length" class="pt-4 flex justify-center">
        <button type="button" @click="loadMore" :disabled="loadingMore" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm disabled:opacity-50">
          {{ loadingMore ? t('Loading') + '…' : t('Load more') }}
        </button>
      </div>
    </div>

    <!-- New/Edit form modal -->
    <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-20 p-4" @click.self="showForm = false">
      <div class="bg-white rounded-xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-6">
        <h2 class="font-semibold text-lg mb-4">{{ editingId ? t('Edit announcement') : t('New announcement') }}</h2>
        <form @submit.prevent="save" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Title') }} *</label>
            <input v-model="form.title" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Message') }} *</label>
            <textarea v-model="form.body" rows="4" required class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('Target audience') }}</label>
            <select v-model="form.target_role_ids" multiple class="w-full rounded-lg border border-slate-300 px-3 py-2 min-h-[80px]">
              <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.display_name || r.name }}</option>
            </select>
            <p class="text-xs text-slate-500 mt-1">{{ t('Target audience hint') }}</p>
          </div>
          <div class="flex items-center gap-2">
            <input v-model="form.pinned" type="checkbox" id="comm-pinned" class="rounded border-slate-300" />
            <label for="comm-pinned" class="text-sm text-slate-700">{{ t('Pinned') }}</label>
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
import { ref, onMounted } from 'vue';
import { api } from '../api';
import { useFormats } from '../composables/useFormats';
import { useI18n } from '../i18n';
import { useToast } from '../composables/useToast';
import { usePermissions } from '../composables/usePermissions';

const { formatDate } = useFormats();
const { t } = useI18n();
const toast = useToast();
const { can } = usePermissions();

const communications = ref([]);
const roles = ref([]);
const loading = ref(true);
const loadingMore = ref(false);
const showForm = ref(false);
const editingId = ref(null);
const saving = ref(false);
const formError = ref('');
const currentPage = ref(1);
const hasMore = ref(false);
const filters = ref({
  search: '',
  sort: 'newest',
  pinned: '',
});
const form = ref({
  title: '',
  body: '',
  target_role_ids: [],
  pinned: false,
});

function audienceLabel(c) {
  const ids = c.target_role_ids;
  if (!ids || !ids.length) return t('All company');
  const names = ids.map((id) => roles.value.find((r) => r.id === id)?.display_name || roles.value.find((r) => r.id === id)?.name).filter(Boolean);
  return names.length ? names.join(', ') : t('All company');
}

async function loadCommunications(reset = true) {
  if (reset) {
    currentPage.value = 1;
    loading.value = true;
  } else {
    loadingMore.value = true;
  }
  try {
    const params = { per_page: 20, page: currentPage.value };
    if ((filters.value.search || '').trim()) params.search = filters.value.search.trim();
    if (filters.value.sort === 'oldest') params.sort = 'oldest';
    if (filters.value.pinned === '1') params.pinned = 1;
    const { data } = await api().get('/communications', { params });
    const list = Array.isArray(data?.data) ? data.data : [];
    if (reset) communications.value = list;
    else communications.value = [...communications.value, ...list];
    const lastPage = data.last_page ?? 1;
    hasMore.value = currentPage.value < lastPage;
  } catch (e) {
    if (reset) communications.value = [];
  } finally {
    loading.value = false;
    loadingMore.value = false;
  }
}

function applyFilters() {
  loadCommunications(true);
}

function loadMore() {
  currentPage.value += 1;
  loadCommunications(false);
}

function openForm() {
  editingId.value = null;
  form.value = { title: '', body: '', target_role_ids: [], pinned: false };
  formError.value = '';
  showForm.value = true;
}

function exportCSV() {
  const headers = [t('Title'), t('Message'), t('Author'), t('Date'), t('Pinned'), t('Target audience')];
  const rows = communications.value.map((c) => [
    (c.title || '').replace(/"/g, '""'),
    (c.body || '').replace(/"/g, '""').replace(/\r?\n/g, ' '),
    (c.user?.name || '').replace(/"/g, '""'),
    formatDate(c.created_at) || '',
    c.pinned ? t('Yes') : t('No'),
    audienceLabel(c).replace(/"/g, '""'),
  ].map((x) => '"' + String(x) + '"'));
  const csv = [headers.join(','), ...rows.map((r) => r.join(','))].join('\r\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'communications.csv';
  a.click();
  URL.revokeObjectURL(a.href);
}

async function loadRoles() {
  try {
    const { data } = await api().get('/roles');
    roles.value = data.data || data || [];
  } catch (_) {
    roles.value = [];
  }
}

function openEdit(c) {
  editingId.value = c.id;
  form.value = {
    title: c.title,
    body: c.body,
    target_role_ids: c.target_role_ids || [],
    pinned: !!c.pinned,
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
      body: form.value.body,
      pinned: form.value.pinned,
    };
    if (form.value.target_role_ids && form.value.target_role_ids.length) {
      payload.target_role_ids = form.value.target_role_ids.filter(Boolean);
    }
    if (editingId.value) {
      await api().put('/communications/' + editingId.value, payload);
      toast.show(t('Communication updated.'), 'success');
    } else {
      await api().post('/communications', payload);
      toast.show(t('Announcement posted.'), 'success');
    }
    showForm.value = false;
    editingId.value = null;
    form.value = { title: '', body: '', target_role_ids: [], pinned: false };
    loadCommunications(true);
  } catch (e) {
    formError.value = e.response?.data?.message || 'Failed to save';
  } finally {
    saving.value = false;
  }
}

async function confirmDelete(c) {
  const ok = await toast.showConfirm(t('Delete this communication?'));
  if (!ok) return;
  try {
    await api().delete('/communications/' + c.id);
    toast.show(t('Communication deleted.'), 'success');
    loadCommunications(true);
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to delete', 'error');
  }
}

onMounted(() => {
  loadRoles();
  loadCommunications(true);
});
</script>
