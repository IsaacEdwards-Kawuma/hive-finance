<template>
  <Teleport to="body">
    <div class="fixed bottom-4 right-4 z-[9999] flex flex-col gap-2 max-w-sm w-full pointer-events-none">
      <div
        v-for="t in toasts"
        :key="t.id"
        :class="[
          'pointer-events-auto px-4 py-3 rounded-lg shadow-lg text-sm border',
          t.type === 'error' && 'bg-red-50 text-red-800 border-red-200',
          t.type === 'success' && 'bg-emerald-50 text-emerald-800 border-emerald-200',
          (t.type === 'info' || !t.type) && 'bg-slate-800 text-white border-slate-700',
        ]"
      >
        <div class="flex items-start justify-between gap-2">
          <span>{{ t.message }}</span>
          <button type="button" @click="remove(t.id)" class="shrink-0 opacity-70 hover:opacity-100" aria-label="Close">
            ×
          </button>
        </div>
      </div>
    </div>
    <div
      v-if="confirmState.visible"
      class="fixed inset-0 z-[10000] flex items-center justify-center p-4 bg-black/50"
      @click.self="resolveConfirm(false)"
    >
      <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6">
        <p class="text-slate-700 mb-4">{{ confirmState.message }}</p>
        <div class="flex gap-2 justify-end">
          <button type="button" @click="resolveConfirm(false)" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50">
            {{ t('Cancel') }}
          </button>
          <button type="button" @click="resolveConfirm(true)" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700">
            {{ t('OK') }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { useToast } from '../composables/useToast';
import { useI18n } from '../i18n';

const { toasts, confirmState, remove, resolveConfirm } = useToast();
const { t } = useI18n();
</script>
