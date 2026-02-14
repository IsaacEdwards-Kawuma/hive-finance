<template>
  <div>
    <h1 class="text-2xl font-bold text-slate-800 mb-2">{{ t('Chat') }}</h1>
    <p class="text-slate-600 mb-6">{{ t('Chat with manager or team description') }}</p>
    <div class="flex gap-4 flex-col lg:flex-row">
      <div class="lg:w-64 shrink-0 space-y-2">
        <button
          type="button"
          :class="['w-full text-left px-4 py-3 rounded-lg border text-sm', activeChat === 'manager' ? 'bg-slate-800 text-white border-slate-800' : 'bg-white border-slate-200 hover:bg-slate-50']"
          @click="activeChat = 'manager'"
        >
          {{ t('Chat with manager') }}
        </button>
        <button
          type="button"
          :class="['w-full text-left px-4 py-3 rounded-lg border text-sm', activeChat === 'team' ? 'bg-slate-800 text-white border-slate-800' : 'bg-white border-slate-200 hover:bg-slate-50']"
          @click="activeChat = 'team'"
        >
          {{ t('Team chat') }}
        </button>
      </div>
      <div class="flex-1 bg-white rounded-xl border border-slate-200 flex flex-col min-h-[400px]">
        <div class="p-4 border-b border-slate-200 font-medium text-slate-800">
          {{ activeChat === 'manager' ? t('Chat with manager') : t('Team chat') }}
        </div>
        <div ref="messagesEl" class="flex-1 overflow-y-auto p-4 space-y-3">
          <div v-if="messagesLoading" class="text-slate-500 text-sm">{{ t('Loading') }}</div>
          <div v-else-if="!filteredMessages.length" class="text-slate-500 text-sm">{{ t('No messages yet. Send one below.') }}</div>
          <div v-for="m in filteredMessages" :key="m.id" :class="['flex', m.from_user_id === myId ? 'justify-end' : 'justify-start']">
            <div :class="['max-w-[80%] rounded-lg px-3 py-2 text-sm', m.from_user_id === myId ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-800']">
              <span class="font-medium text-xs block mb-0.5">{{ m.from_user?.name }}</span>
              <p class="whitespace-pre-wrap">{{ m.body }}</p>
              <span class="text-xs opacity-75">{{ formatTime(m.created_at) }}</span>
            </div>
          </div>
        </div>
        <form @submit.prevent="send" class="p-4 border-t border-slate-200 flex gap-2">
          <textarea v-model="newBody" rows="2" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm" :placeholder="t('Message')" required></textarea>
          <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 shrink-0">{{ t('Send') }}</button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { api } from '../../api';
import { useI18n } from '../../i18n';
import { useToast } from '../../composables/useToast';

const { t } = useI18n();
const toast = useToast();
const activeChat = ref('manager');
const messages = ref([]);
const messagesLoading = ref(true);
const newBody = ref('');
const messagesEl = ref(null);
const managerId = ref(null);
const myId = ref(null);

const filteredMessages = computed(() => {
  const list = messages.value || [];
  if (activeChat.value === 'manager') {
    return list.filter((m) => m.to_user_id === managerId.value || m.from_user_id === managerId.value);
  }
  return list.filter((m) => m.to_user_id == null);
});

function formatTime(d) {
  if (!d) return '';
  return new Date(d).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

async function loadMessages() {
  messagesLoading.value = true;
  try {
    const [msgRes, managerRes, meRes] = await Promise.all([
      api().get('/employee/messages?limit=200'),
      api().get('/employee/manager'),
      api().get('/user'),
    ]);
    messages.value = msgRes.data?.data || [];
    managerId.value = managerRes.data?.data?.id ?? null;
    myId.value = meRes.data?.id ?? null;
  } catch (_) {
    messages.value = [];
  } finally {
    messagesLoading.value = false;
  }
}

async function send() {
  const body = (newBody.value || '').trim();
  if (!body) return;
  try {
    const payload = { body };
    if (activeChat.value === 'manager' && managerId.value) payload.to_user_id = managerId.value;
    else if (activeChat.value === 'team') payload.to_user_id = null;
    await api().post('/employee/messages', payload);
    newBody.value = '';
    loadMessages();
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to send', 'error');
  }
}

onMounted(loadMessages);
watch(activeChat, () => { nextTick(() => messagesEl.value?.scrollTo(0, messagesEl.value.scrollHeight)); });
</script>
