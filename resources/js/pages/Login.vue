<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-100 p-4">
    <div class="w-full max-w-sm bg-white rounded-xl shadow-lg p-6">
      <div class="flex flex-col items-center mb-6">
        <img src="/logo.png" alt="HiveStack IntelliCash" class="h-16 w-auto object-contain" @error="$event.target.style.display='none'" />
        <h1 class="text-xl font-bold text-center mt-2">HiveStack IntelliCash</h1>
      </div>
      <p v-if="message" :class="messageSuccess ? 'text-sm text-green-600 mb-4' : 'text-sm text-amber-600 mb-4'">{{ message }}</p>
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
          <input v-model="email" type="email" required class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="you@example.com" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
          <input v-model="password" type="password" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
        <button type="submit" class="w-full bg-slate-800 text-white py-2 rounded-lg hover:bg-slate-700" :disabled="loading">
          {{ loading ? 'Signing in…' : 'Sign in' }}
        </button>
      </form>
      <p class="mt-4 text-center text-sm text-slate-600">
        <router-link to="/forgot-password" class="text-slate-600 hover:text-slate-800">Forgot password?</router-link>
      </p>
      <p class="mt-2 text-center text-sm text-slate-600">
        Don't have an account? <router-link to="/register" class="text-slate-800 font-medium">Register</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const route = useRoute();
const email = ref('');
const password = ref('');
const error = ref('');
const message = ref('');
const messageSuccess = ref(false);
const loading = ref(false);

onMounted(() => {
  if (route.query.verified === '1') {
    message.value = 'Your email has been verified. You can sign in.';
    messageSuccess.value = true;
    router.replace({ path: '/login', query: {} });
  } else if (route.query.error === 'verification_expired') {
    message.value = 'Verification link expired. Request a new one from Settings.';
    messageSuccess.value = false;
    router.replace({ path: '/login', query: {} });
  } else if (route.query.error === 'verification_invalid') {
    message.value = 'Invalid verification link.';
    messageSuccess.value = false;
    router.replace({ path: '/login', query: {} });
  }
});

async function submit() {
  error.value = '';
  loading.value = true;
  try {
    const { data } = await axios.post('/api/login', { email: email.value, password: password.value });
    localStorage.setItem('token', data.token);
    if (data.current_company_id) localStorage.setItem('companyId', data.current_company_id);
    if (data.user) localStorage.setItem('user', JSON.stringify({ name: data.user.name, email: data.user.email, locale: data.user.locale }));
    router.push('/');
  } catch (e) {
    error.value = e.response?.data?.message || 'Invalid credentials';
  } finally {
    loading.value = false;
  }
}
</script>
