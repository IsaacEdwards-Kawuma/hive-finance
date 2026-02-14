import { ref } from 'vue';

const toasts = ref([]);
const confirmState = ref({ visible: false, message: '', resolve: null });
let toastId = 0;
const DEFAULT_DURATION = 4000;

export function useToast() {
  function show(message, type = 'info', duration = DEFAULT_DURATION) {
    const id = ++toastId;
    toasts.value = [...toasts.value, { id, message, type }];
    if (duration > 0) {
      setTimeout(() => {
        toasts.value = toasts.value.filter((t) => t.id !== id);
      }, duration);
    }
  }

  function remove(id) {
    toasts.value = toasts.value.filter((t) => t.id !== id);
  }

  function showConfirm(message) {
    return new Promise((resolve) => {
      confirmState.value = { visible: true, message, resolve };
    });
  }

  function resolveConfirm(ok) {
    if (confirmState.value.resolve) {
      confirmState.value.resolve(!!ok);
    }
    confirmState.value = { visible: false, message: '', resolve: null };
  }

  return {
    toasts,
    confirmState,
    show,
    remove,
    showConfirm,
    resolveConfirm,
  };
}
