<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-100 p-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-6">
      <h1 class="text-xl font-bold text-center mb-2">Set up your company</h1>
      <p class="text-sm text-slate-500 text-center mb-6">Name your company and choose a currency. You can change this later.</p>
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Company name</label>
          <input v-model="name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="My Business" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Default currency</label>
          <select v-model="currency" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="USD">USD</option>
            <option value="EUR">EUR</option>
            <option value="GBP">GBP</option>
            <option value="NGN">NGN</option>
          </select>
        </div>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
        <button type="submit" class="w-full bg-slate-800 text-white py-2 rounded-lg hover:bg-slate-700" :disabled="loading">
          {{ loading ? 'Saving…' : 'Continue to dashboard' }}
        </button>
        <p class="text-center mt-3">
          <router-link to="/" class="text-sm text-slate-500 hover:text-slate-700">Skip for now</router-link>
        </p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { api } from '../api';

const router = useRouter();
const name = ref('');
const currency = ref('USD');
const error = ref('');
const loading = ref(false);
const companyId = ref(null);

onMounted(async () => {
  try {
    const { data } = await api().get('/companies');
    const companies = data.data || data;
    if (companies.length) {
      const first = companies[0];
      companyId.value = first.id;
      name.value = first.name || '';
      currency.value = first.default_currency || 'USD';
    }
  } catch (e) {
    error.value = 'Could not load company';
  }
});

async function submit() {
  if (!companyId.value) {
    error.value = 'No company found';
    return;
  }
  error.value = '';
  loading.value = true;
  try {
    await api().put(`/companies/${companyId.value}`, { name: name.value, default_currency: currency.value });
    router.push('/');
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to save';
  } finally {
    loading.value = false;
  }
}
</script>
