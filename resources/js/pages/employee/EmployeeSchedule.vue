<template>
  <div>
    <h1 class="text-2xl font-bold text-slate-800 mb-2">{{ t('My schedule') }}</h1>
    <p class="text-slate-600 mb-6">{{ t('Calendar view of your shifts.') }}</p>
    <div class="flex flex-wrap items-center gap-4 mb-4">
      <button type="button" @click="prevWeek" class="px-3 py-1.5 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm">← {{ t('Previous') }}</button>
      <span class="font-medium text-slate-800">{{ weekLabel }}</span>
      <button type="button" @click="nextWeek" class="px-3 py-1.5 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm">{{ t('Next') }} →</button>
      <button type="button" @click="goToday" class="px-3 py-1.5 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm">{{ t('Today') }}</button>
    </div>
    <div v-if="loading" class="text-slate-500 py-8">{{ t('Loading') }}</div>
    <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden">
      <div class="grid grid-cols-8 border-b border-slate-200 bg-slate-50 text-sm">
        <div class="p-2 border-r border-slate-200 font-medium text-slate-600">{{ t('Time') }}</div>
        <div v-for="d in weekDays" :key="d.key" class="p-2 border-r border-slate-200 last:border-r-0 text-center" :class="d.isToday ? 'bg-slate-100 font-semibold' : ''">
          <span class="block text-slate-500 text-xs">{{ d.dayName }}</span>
          <span class="block">{{ d.date }}</span>
        </div>
      </div>
      <div class="grid grid-cols-8 divide-x divide-slate-100 min-h-[320px]">
        <div class="bg-slate-50/50 p-2 space-y-1 text-xs text-slate-500 font-medium">
          <div v-for="h in hourSlots" :key="h">{{ h }}</div>
        </div>
        <div v-for="d in weekDays" :key="d.key" class="min-h-[320px] relative p-1">
          <div
            v-for="s in shiftsForDay(d.dateStr)"
            :key="s.id"
            class="absolute rounded-lg bg-slate-800 text-white text-xs p-2 left-1 right-1 overflow-hidden"
            :style="shiftStyle(s, d.dateStr)"
          >
            <span class="font-medium block truncate">{{ s.title || t('Shift') }}</span>
            <span>{{ formatTime(s.start_at) }} – {{ formatTime(s.end_at) }}</span>
          </div>
        </div>
      </div>
    </div>
    <p v-if="!loading && !shifts.length" class="text-slate-500 py-6">{{ t('No shifts in this week.') }}</p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { api } from '../../api';
import { useI18n } from '../../i18n';

const { t } = useI18n();
const shifts = ref([]);
const loading = ref(true);
const weekStart = ref(getWeekStart(new Date()));

function getWeekStart(d) {
  const date = new Date(d);
  const day = date.getDay();
  const diff = date.getDate() - day + (day === 0 ? -6 : 1);
  return new Date(date.setDate(diff));
}

const weekDays = computed(() => {
  const days = [];
  const names = [t('Mon'), t('Tue'), t('Wed'), t('Thu'), t('Fri'), t('Sat'), t('Sun')];
  const today = new Date().toISOString().slice(0, 10);
  for (let i = 0; i < 7; i++) {
    const d = new Date(weekStart.value);
    d.setDate(d.getDate() + i);
    const dateStr = d.toISOString().slice(0, 10);
    days.push({
      key: dateStr,
      dateStr,
      date: d.getDate(),
      dayName: names[i],
      isToday: dateStr === today,
    });
  }
  return days;
});

const weekLabel = computed(() => {
  const start = new Date(weekStart.value);
  const end = new Date(weekStart.value);
  end.setDate(end.getDate() + 6);
  return start.toLocaleDateString() + ' – ' + end.toLocaleDateString();
});

const hourSlots = computed(() => {
  const h = [];
  for (let i = 6; i <= 22; i++) h.push(i + ':00');
  return h;
});

function shiftsForDay(dateStr) {
  return shifts.value.filter((s) => s.start_at && s.start_at.toString().slice(0, 10) === dateStr);
}

function formatTime(d) {
  if (!d) return '';
  return new Date(d).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function shiftStyle(s, dateStr) {
  if (!s.start_at || !s.end_at) return { display: 'none' };
  const start = new Date(s.start_at);
  const end = new Date(s.end_at);
  if (start.toISOString().slice(0, 10) !== dateStr) return { display: 'none' };
  const dayStart = new Date(start);
  dayStart.setHours(6, 0, 0, 0);
  const dayEnd = new Date(dayStart);
  dayEnd.setHours(23, 59, 59, 999);
  const totalMins = (dayEnd - dayStart) / 60000;
  const top = ((start - dayStart) / 60000 / totalMins) * 100;
  const height = ((end - start) / 60000 / totalMins) * 100;
  return { top: top + '%', height: Math.max(height, 8) + '%' };
}

function prevWeek() {
  const d = new Date(weekStart.value);
  d.setDate(d.getDate() - 7);
  weekStart.value = d;
  load();
}
function nextWeek() {
  const d = new Date(weekStart.value);
  d.setDate(d.getDate() + 7);
  weekStart.value = d;
  load();
}
function goToday() {
  weekStart.value = getWeekStart(new Date());
  load();
}

async function load() {
  loading.value = true;
  const start = new Date(weekStart.value);
  start.setHours(0, 0, 0, 0);
  const end = new Date(weekStart.value);
  end.setDate(end.getDate() + 6);
  end.setHours(23, 59, 59, 999);
  const fromStr = start.toISOString().slice(0, 10);
  const toStr = end.toISOString().slice(0, 10);
  try {
    const { data } = await api().get('/employee/shifts', { params: { from: fromStr, to: toStr, per_page: 100 } });
    shifts.value = Array.isArray(data?.data) ? data.data : [];
  } catch (_) {
    shifts.value = [];
  } finally {
    loading.value = false;
  }
}

onMounted(load);
</script>
