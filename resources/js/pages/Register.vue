<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-100 p-4">
    <div class="w-full max-w-sm bg-white rounded-xl shadow-lg p-6">
      <h1 class="text-xl font-bold text-center mb-6">Create account</h1>
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
          <input v-model="name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
          <input v-model="email" type="email" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
          <input v-model="password" type="password" required minlength="8" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Confirm password</label>
          <input v-model="password_confirmation" type="password" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
        <button type="submit" class="w-full bg-slate-800 text-white py-2 rounded-lg hover:bg-slate-700" :disabled="loading">
          {{ loading ? 'Creating…' : 'Register' }}
        </button>
      </form>
      <p class="mt-4 text-center text-sm text-slate-600">
        Already have an account? <router-link to="/login" class="text-slate-800 font-medium">Sign in</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { profileApi } from '@/api';

const router = useRouter();
const name = ref('');
const email = ref('');
const password = ref('');
const password_confirmation = ref('');
const error = ref('');
const loading = ref(false);

async function submit() {
  if (password.value !== password_confirmation.value) {
    error.value = 'Passwords do not match';
    return;
  }
  error.value = '';
  loading.value = true;
  try {
    const { data } = await profileApi().post('/register', {
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: password_confirmation.value,
    });
    localStorage.setItem('token', data.token);
    if (data.current_company_id) localStorage.setItem('companyId', data.current_company_id);
    if (data.user) localStorage.setItem('user', JSON.stringify({ name: data.user.name, email: data.user.email, locale: data.user.locale }));
    router.push('/setup/company');
  } catch (e) {
    error.value = e.response?.data?.message || (e.response?.data?.errors ? Object.values(e.response.data.errors).flat().join(' ') : 'Registration failed');
  } finally {
    loading.value = false;
  }
}
</script>
