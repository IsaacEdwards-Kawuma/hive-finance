import { ref, onMounted } from 'vue';
import { api } from '../api';

const permissions = ref([]);
const loaded = ref(false);

export function usePermissions() {
  async function load() {
    if (loaded.value) return;
    try {
      const { data } = await api().get('/permissions');
      permissions.value = data.data || data || [];
      loaded.value = true;
    } catch (e) {
      permissions.value = [];
      loaded.value = true;
    }
  }

  function can(permission) {
    if (!loaded.value || !permissions.value.length) return true;
    return permissions.value.includes(permission);
  }

  function reset() {
    loaded.value = false;
    permissions.value = [];
  }

  onMounted(load);

  return { permissions, loaded, load, can, reset };
}
