<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-100 p-4">
    <div class="w-full max-w-sm bg-white rounded-xl shadow-lg p-6">
      <h1 class="text-xl font-bold text-center mb-6">Reset password</h1>
      <p class="text-sm text-slate-600 mb-4">Enter your email and we’ll send you a link to reset your password.</p>
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
          <input v-model="email" type="email" required class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="you@example.com" />
        </div>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
        <p v-if="success" class="text-sm text-green-600">{{ success }}</p>
        <button type="submit" class="w-full bg-slate-800 text-white py-2 rounded-lg hover:bg-slate-700" :disabled="loading">
          {{ loading ? 'Sending…' : 'Send reset link' }}
        </button>
      </form>
      <p class="mt-4 text-center text-sm text-slate-600">
        <router-link to="/login" class="text-slate-800 font-medium">Back to sign in</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { profileApi } from '@/api';

const email = ref('');
const error = ref('');
const success = ref('');
const loading = ref(false);

async function submit() {
  error.value = '';
  success.value = '';
  loading.value = true;
  try {
    await profileApi().post('/forgot-password', { email: email.value });
    success.value = 'If that email exists, we sent a reset link. Check your inbox.';
  } catch (e) {
    error.value = e.response?.data?.message || 'Something went wrong';
  } finally {
    loading.value = false;
  }
}
</script>
