<template>
  <div>
    <h1 class="text-2xl font-bold text-slate-800 mb-2">{{ t('Apps') }} &amp; {{ t('Modules') }}</h1>
    <p class="text-slate-600 mb-4">{{ t('Apps description') }}</p>

    <div class="mb-6 p-4 bg-slate-50 rounded-lg border border-slate-200">
      <h2 class="font-semibold text-slate-800 mb-3">{{ t('Quick links') }}</h2>
      <div class="flex flex-wrap gap-2">
        <router-link to="/" class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm hover:bg-slate-100">{{ t('Dashboard') }}</router-link>
        <router-link to="/communications" class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm hover:bg-slate-100">{{ t('Communications') }}</router-link>
        <router-link to="/reports" class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm hover:bg-slate-100">{{ t('Reports') }}</router-link>
        <router-link to="/banking" class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm hover:bg-slate-100">{{ t('Banking') }}</router-link>
        <router-link to="/invoices" class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm hover:bg-slate-100">{{ t('Invoices') }}</router-link>
        <router-link to="/bills" class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm hover:bg-slate-100">{{ t('Bills') }}</router-link>
        <router-link to="/accounts" class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm hover:bg-slate-100">{{ t('Chart of Accounts') }}</router-link>
        <router-link to="/settings" class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm hover:bg-slate-100">{{ t('Settings') }}</router-link>
      </div>
    </div>

    <div class="flex flex-wrap items-center gap-3 mb-4">
      <input
        v-model="search"
        type="text"
        :placeholder="t('Search') + ' ' + t('Modules')"
        class="rounded-lg border border-slate-300 px-3 py-2 text-sm w-64"
      />
      <button type="button" @click="exportCSV" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm">{{ t('Export CSV') }}</button>
    </div>

    <div v-if="loading" class="text-slate-500">{{ t('Loading') }}</div>
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="m in filteredModules"
        :key="m.alias"
        class="bg-white rounded-lg border border-slate-200 p-4 flex gap-4"
      >
        <div class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center text-2xl shrink-0">
          {{ moduleIcon(m.icon) }}
        </div>
        <div class="min-w-0 flex-1">
          <h3 class="font-semibold text-slate-800">{{ m.name }}</h3>
          <p class="text-sm text-slate-500 mt-0.5">{{ m.description }}</p>
          <p class="text-xs text-slate-400 mt-2">v{{ m.version }} · {{ m.active ? t('Active') : t('Inactive') }}</p>
        </div>
        <div class="shrink-0">
          <button
            type="button"
            @click="toggleModule(m)"
            :disabled="toggling === m.alias"
            class="px-3 py-1.5 text-sm rounded-lg border border-slate-300 hover:bg-slate-50 disabled:opacity-50"
          >
            {{ toggling === m.alias ? '…' : (m.active ? t('Disable') : t('Enable')) }}
          </button>
        </div>
      </div>
      <div v-if="!filteredModules.length" class="col-span-full text-center py-8 text-slate-500">
        {{ search ? t('No modules match the filter.') : t('No modules in modules/') }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { api } from '../api';
import { useI18n } from '../i18n';

const { t } = useI18n();
const modules = ref([]);
const loading = ref(true);
const toggling = ref(null);
const search = ref('');

const filteredModules = computed(() => {
  const list = modules.value || [];
  const q = (search.value || '').trim().toLowerCase();
  if (!q) return list;
  return list.filter((m) => (m.name || '').toLowerCase().includes(q) || (m.description || '').toLowerCase().includes(q) || (m.alias || '').toLowerCase().includes(q));
});

function exportCSV() {
  const list = filteredModules.value;
  const headers = [t('Name'), t('Description'), 'Version', t('Active')];
  const rows = list.map((m) => [m.name || '', (m.description || '').replace(/"/g, '""'), m.version || '', m.active ? t('Yes') : t('No')].map((x) => '"' + String(x).replace(/"/g, '""') + '"'));
  const csv = [headers.join(','), ...rows.map((r) => r.join(','))].join('\r\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'modules.csv';
  a.click();
  URL.revokeObjectURL(a.href);
}

async function loadModules() {
  loading.value = true;
  try {
    const { data } = await api().get('/modules');
    modules.value = data.data || data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
}

async function toggleModule(m) {
  toggling.value = m.alias;
  try {
    await api().patch(`/modules/${encodeURIComponent(m.alias)}`, { active: !m.active });
    m.active = !m.active;
  } catch (e) {
    console.error(e);
  } finally {
    toggling.value = null;
  }
}

const iconMap = {
  'credit-card': '💳',
  'document-text': '📄',
  'document-arrow-down': '📥',
  'bell': '🔔',
  'arrow-path': '🔄',
  'clipboard-document-list': '📋',
  'bolt': '⚡',
  'banknotes': '🏦',
  'building-office-2': '🏢',
  'puzzle': '🧩',
};
function moduleIcon(icon) {
  return iconMap[icon] || '🧩';
}

onMounted(loadModules);
</script>
