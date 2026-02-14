<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Portal – HiveStack IntelliCash</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen p-4">
    <div id="portal-app" class="max-w-2xl mx-auto">
        <!-- Login -->
        <div v-if="!token" class="bg-white rounded-xl shadow-lg p-8">
            <h1 class="text-2xl font-bold text-slate-800 mb-2">Client Portal</h1>
            <p class="text-slate-600 mb-6">Sign in to view your invoices.</p>
            <form @submit.prevent="login" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Company</label>
                    <select v-model="loginForm.company_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">Select company</option>
                        <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input v-model="loginForm.email" type="email" required class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="you@example.com" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <input v-model="loginForm.password" type="password" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
                </div>
                <p v-if="loginError" class="text-sm text-red-600">{{ loginError }}</p>
                <button type="submit" class="w-full py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700" :disabled="loggingIn">{{ loggingIn ? 'Signing in…' : 'Sign in' }}</button>
            </form>
            <p class="text-sm text-slate-500 mt-4">Don't have a password? Ask your account manager to set one for your customer account.</p>
        </div>

        <!-- Invoices -->
        <div v-else>
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-slate-800">My Invoices</h1>
                <button @click="logout" class="text-sm text-slate-600 hover:text-slate-800">Sign out</button>
            </div>
            <div v-if="loading" class="text-slate-500">Loading…</div>
            <div v-else class="bg-white rounded-xl shadow overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-4 py-3 font-medium">Number</th>
                            <th class="text-left px-4 py-3 font-medium">Date</th>
                            <th class="text-right px-4 py-3 font-medium">Total</th>
                            <th class="text-left px-4 py-3 font-medium">Status</th>
                            <th class="text-right px-4 py-3 font-medium w-24"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="inv in invoices" :key="inv.id">
                            <td class="px-4 py-3">{{ inv.invoice_number }}</td>
                            <td class="px-4 py-3">{{ inv.invoice_date }}</td>
                            <td class="px-4 py-3 text-right">{{ formatMoney(inv.total) }}</td>
                            <td class="px-4 py-3">{{ inv.status }}</td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <button type="button" @click="openPdf(inv.id)" class="text-slate-600 hover:text-slate-800">View PDF</button>
                                <a v-if="inv.status !== 'paid'" href="/portal/pay" target="_blank" class="text-slate-600 hover:text-slate-800">Pay now</a>
                            </td>
                        </tr>
                        <tr v-if="!invoices.length">
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No invoices yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    <script>
        const { createApp, ref, onMounted, computed } = Vue;
        createApp({
            setup() {
                const token = ref(localStorage.getItem('portalToken'));
                const companies = ref([]);
                const loginForm = ref({ company_id: '', email: '', password: '' });
                const loggingIn = ref(false);
                const loginError = ref('');
                const invoices = ref([]);
                const loading = ref(false);

                function formatMoney(n) {
                    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(n ?? 0);
                }
                async function openPdf(id) {
                    try {
                        const r = await fetch('/api/portal/invoices/' + id + '/pdf', {
                            headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'text/html' },
                        });
                        const html = await r.text();
                        const w = window.open('', '_blank');
                        if (w) { w.document.write(html); w.document.close(); }
                    } catch (e) {
                        alert('Could not load PDF');
                    }
                }
                async function loadCompanies() {
                    const r = await fetch('/api/portal/companies');
                    const d = await r.json();
                    companies.value = d.data || [];
                }
                async function login() {
                    loginError.value = '';
                    loggingIn.value = true;
                    try {
                        const r = await fetch('/api/portal/login', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify(loginForm.value),
                        });
                        const d = await r.json();
                        if (!r.ok) throw new Error(d.message || 'Login failed');
                        token.value = d.token;
                        localStorage.setItem('portalToken', d.token);
                        loadInvoices();
                    } catch (e) {
                        loginError.value = e.message || 'Invalid credentials';
                    } finally {
                        loggingIn.value = false;
                    }
                }
                function logout() {
                    token.value = null;
                    localStorage.removeItem('portalToken');
                    fetch('/api/portal/logout', {
                        method: 'POST',
                        headers: { 'Authorization': 'Bearer ' + localStorage.getItem('portalToken'), 'Accept': 'application/json' },
                    }).catch(() => {});
                }
                async function loadInvoices() {
                    if (!token.value) return;
                    loading.value = true;
                    try {
                        const r = await fetch('/api/portal/invoices', {
                            headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' },
                        });
                        const d = await r.json();
                        invoices.value = d.data || d;
                    } catch (e) {
                        invoices.value = [];
                    } finally {
                        loading.value = false;
                    }
                }
                onMounted(() => {
                    loadCompanies();
                    if (token.value) loadInvoices();
                });
                return { token, companies, loginForm, loggingIn, loginError, invoices, loading, login, logout, loadInvoices, formatMoney, openPdf };
            },
        }).mount('#portal-app');
    </script>
</body>
</html>
