<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-100 p-4">
    <div class="w-full max-w-sm bg-white rounded-xl shadow-lg p-6">
      <h1 class="text-xl font-bold text-center mb-6">Set new password</h1>
      <form @submit.prevent="submit" class="space-y-4">
        <input v-model="token" type="hidden" />
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
          <input v-model="email" type="email" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">New password</label>
          <input v-model="password" type="password" required minlength="8" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Confirm password</label>
          <input v-model="password_confirmation" type="password" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
        <p v-if="success" class="text-sm text-green-600">{{ success }}</p>
        <button type="submit" class="w-full bg-slate-800 text-white py-2 rounded-lg hover:bg-slate-700" :disabled="loading">
          {{ loading ? 'Resetting…' : 'Reset password' }}
        </button>
      </form>
      <p class="mt-4 text-center text-sm text-slate-600">
        <router-link to="/login" class="text-slate-800 font-medium">Back to sign in</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { profileApi } from '@/api';

const router = useRouter();
const route = useRoute();
const token = ref('');
const email = ref('');
const password = ref('');
const password_confirmation = ref('');
const error = ref('');
const success = ref('');
const loading = ref(false);

onMounted(() => {
  token.value = route.query.token || '';
  email.value = route.query.email || '';
});

async function submit() {
  error.value = '';
  success.value = '';
  if (password.value !== password_confirmation.value) {
    error.value = 'Passwords do not match';
    return;
  }
  loading.value = true;
  try {
    await profileApi().post('/reset-password', {
      token: token.value,
      email: email.value,
      password: password.value,
      password_confirmation: password_confirmation.value,
    });
    success.value = 'Password has been reset. Redirecting to sign in…';
    setTimeout(() => router.push('/login'), 2000);
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to reset password';
  } finally {
    loading.value = false;
  }
}
</script>
