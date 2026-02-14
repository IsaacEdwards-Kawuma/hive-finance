<template>
  <div>
    <h1 class="text-2xl font-bold text-slate-800 mb-6">Settings</h1>

    <div class="flex border-b border-slate-200 mb-6">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        @click="activeTab = tab.id"
        :class="[
          'px-4 py-2 text-sm font-medium border-b-2 -mb-px',
          activeTab === tab.id
            ? 'border-slate-800 text-slate-800'
            : 'border-transparent text-slate-500 hover:text-slate-700'
        ]"
      >
        {{ t(tab.label) }}
      </button>
    </div>

    <!-- Company -->
    <div v-show="activeTab === 'company'" class="max-w-2xl">
      <div v-if="companyLoading" class="text-slate-500">Loading…</div>

      <!-- No company: create first company -->
      <div v-else-if="!companies.length" class="space-y-4">
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800">
          You don’t have a company yet. Create one to start managing invoices, bills, and reports.
        </div>
        <form @submit.prevent="createFirstCompany" class="space-y-4 bg-white rounded-lg border border-slate-200 p-6">
          <h2 class="font-semibold text-slate-800 mb-4">Create your company</h2>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Company name</label>
            <input v-model="newCompany.name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="My Business" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Default currency</label>
            <select v-model="newCompany.default_currency" class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="USD">USD</option>
              <option value="EUR">EUR</option>
              <option value="GBP">GBP</option>
              <option value="NGN">NGN</option>
            </select>
          </div>
          <p v-if="newCompanyError" class="text-sm text-red-600">{{ newCompanyError }}</p>
          <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700" :disabled="newCompanySaving">
            {{ newCompanySaving ? 'Creating…' : 'Create company' }}
          </button>
        </form>
      </div>

      <form v-else @submit.prevent="saveCompany" class="space-y-4 bg-white rounded-lg border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-800 mb-4">Company details</h2>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Company name</label>
          <input v-model="company.name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
          <input v-model="company.email" type="email" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Address</label>
          <textarea v-model="company.address" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Default currency</label>
            <select v-model="company.default_currency" class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="USD">USD</option>
              <option value="EUR">EUR</option>
              <option value="GBP">GBP</option>
              <option value="NGN">NGN</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Fiscal year start (MM-DD)</label>
            <input v-model="company.fiscal_year_start" type="text" placeholder="01-01" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Tax ID / VAT number</label>
          <input v-model="company.tax_id" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Payment URL template (Pay now link)</label>
          <input v-model="company.settings.payment_url_template" type="url" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="https://pay.example.com?invoice_id={id}" />
          <p class="text-xs text-slate-500 mt-0.5">Use {id} or {invoice_id} for invoice ID, {number} for invoice number.</p>
        </div>
        <div class="border-t border-slate-200 pt-4 mt-4">
          <h3 class="font-medium text-slate-800 mb-2">Payment reminders</h3>
          <div class="flex items-center gap-2 mb-2">
            <input v-model="company.settings.payment_reminders_enabled" type="checkbox" id="reminders-enabled" class="rounded border-slate-300" />
            <label for="reminders-enabled" class="text-sm text-slate-700">Send overdue invoice reminder emails to customers</label>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Days after due date to send reminder</label>
            <input v-model="company.settings.payment_reminders_days_after" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="0, 7, 30" />
            <p class="text-xs text-slate-500 mt-0.5">Comma-separated: 0 = on due date, 7 = 7 days after, etc. Leave empty to use 0 only.</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Days before due date to send reminder</label>
            <input v-model="company.settings.payment_reminders_days_before" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="1, 3, 7" />
            <p class="text-xs text-slate-500 mt-0.5">Comma-separated: e.g. 1, 3, 7 = send reminder 1, 3, and 7 days before due. Leave empty to disable.</p>
          </div>
        </div>
        <p v-if="companyError" class="text-sm text-red-600">{{ companyError }}</p>
        <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700" :disabled="companySaving">
          {{ companySaving ? 'Saving…' : 'Save company' }}
        </button>
      </form>
    </div>

    <!-- Profile -->
    <div v-show="activeTab === 'profile'" class="max-w-2xl space-y-6">
      <div v-if="profileLoading" class="text-slate-500">Loading…</div>
      <template v-else>
        <div v-if="!profile.email_verified_at" class="bg-amber-50 border border-amber-200 rounded-lg p-4 flex items-center justify-between">
          <span class="text-sm text-amber-800">Your email is not verified. Check your inbox or resend the link.</span>
          <button type="button" @click="resendVerification" :disabled="verificationSending" class="px-3 py-1 text-sm bg-amber-200 hover:bg-amber-300 rounded">
            {{ verificationSending ? 'Sending…' : 'Resend' }}
          </button>
        </div>
        <form @submit.prevent="saveProfile" class="space-y-4 bg-white rounded-lg border border-slate-200 p-6">
          <h2 class="font-semibold text-slate-800 mb-4">Profile</h2>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
            <input v-model="profile.name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input v-model="profile.email" type="email" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Language</label>
            <select v-model="profile.locale" class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="en">English</option>
              <option value="fr">Français</option>
              <option value="es">Español</option>
              <option value="de">Deutsch</option>
              <option value="ar">العربية</option>
            </select>
          </div>
          <p v-if="profileError" class="text-sm text-red-600">{{ profileError }}</p>
          <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700" :disabled="profileSaving">
            {{ profileSaving ? 'Saving…' : 'Save profile' }}
          </button>
        </form>
        <!-- Two-factor authentication -->
        <div class="bg-white rounded-lg border border-slate-200 p-6">
          <h2 class="font-semibold text-slate-800 mb-4">Two-factor authentication (2FA)</h2>
          <div v-if="twoFaEnabling" class="space-y-4">
            <p class="text-sm text-slate-600">Scan this QR code with your authenticator app (e.g. Google Authenticator), then enter the 6-digit code below.</p>
            <img :src="'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(twoFaQrUrl)" alt="QR code" class="w-48 h-48 border border-slate-200 rounded" />
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Verification code</label>
              <input v-model="twoFaConfirmCode" type="text" maxlength="6" placeholder="000000" class="w-32 rounded-lg border border-slate-300 px-3 py-2" />
              <button type="button" @click="confirm2Fa" class="ml-2 px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700" :disabled="twoFaConfirming">Confirm</button>
            </div>
            <p v-if="twoFaError" class="text-sm text-red-600">{{ twoFaError }}</p>
          </div>
          <div v-else-if="twoFaEnabled">
            <p class="text-sm text-slate-600 mb-4">2FA is enabled. To disable, enter your password.</p>
            <div class="flex gap-2 items-center">
              <input v-model="twoFaDisablePassword" type="password" placeholder="Your password" class="w-48 rounded-lg border border-slate-300 px-3 py-2" />
              <button type="button" @click="disable2Fa" class="px-4 py-2 border border-red-300 text-red-700 rounded-lg hover:bg-red-50" :disabled="twoFaDisabling">Disable 2FA</button>
            </div>
            <p v-if="twoFaError" class="text-sm text-red-600 mt-2">{{ twoFaError }}</p>
          </div>
          <div v-else>
            <p class="text-sm text-slate-600 mb-4">Add an extra layer of security by enabling 2FA.</p>
            <button type="button" @click="startEnable2Fa" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700" :disabled="twoFaLoading">
              {{ twoFaLoading ? 'Loading…' : 'Enable 2FA' }}
            </button>
            <p v-if="twoFaError" class="text-sm text-red-600 mt-2">{{ twoFaError }}</p>
          </div>
        </div>
      </template>
    </div>

    <!-- Password -->
    <div v-show="activeTab === 'password'" class="max-w-2xl">
      <form @submit.prevent="savePassword" class="space-y-4 bg-white rounded-lg border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-800 mb-4">Change password</h2>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Current password</label>
          <input v-model="passwordForm.current_password" type="password" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">New password</label>
          <input v-model="passwordForm.password" type="password" required minlength="8" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Confirm new password</label>
          <input v-model="passwordForm.password_confirmation" type="password" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <p v-if="passwordError" class="text-sm text-red-600">{{ passwordError }}</p>
        <p v-if="passwordSuccess" class="text-sm text-green-600">Password updated.</p>
        <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700" :disabled="passwordSaving">
          {{ passwordSaving ? 'Updating…' : 'Update password' }}
        </button>
      </form>
    </div>

    <!-- Preferences (company-level) -->
    <div v-show="activeTab === 'preferences'" class="max-w-2xl">
      <div v-if="companyLoading" class="text-slate-500">Loading…</div>
      <form v-else @submit.prevent="savePreferences" class="space-y-4 bg-white rounded-lg border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-800 mb-4">Display preferences</h2>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Date format</label>
          <select v-model="preferences.date_format" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="Y-m-d">YYYY-MM-DD (2025-02-13)</option>
            <option value="d/m/Y">DD/MM/YYYY (13/02/2025)</option>
            <option value="m/d/Y">MM/DD/YYYY (02/13/2025)</option>
            <option value="d.m.Y">DD.MM.YYYY (13.02.2025)</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Number format</label>
          <select v-model="preferences.number_format" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="1,234.56">1,234.56 (comma thousands, dot decimal)</option>
            <option value="1.234,56">1.234,56 (dot thousands, comma decimal)</option>
            <option value="1 234.56">1 234.56 (space thousands, dot decimal)</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">First day of week</label>
          <select v-model="preferences.first_day_of_week" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="0">Sunday</option>
            <option value="1">Monday</option>
            <option value="6">Saturday</option>
          </select>
        </div>
        <p v-if="preferencesError" class="text-sm text-red-600">{{ preferencesError }}</p>
        <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700" :disabled="preferencesSaving">
          {{ preferencesSaving ? 'Saving…' : 'Save preferences' }}
        </button>
      </form>
    </div>

    <!-- Team (company members & role assignment) -->
    <div v-show="activeTab === 'team'" class="max-w-4xl">
      <div v-if="!can('settings.roles')" class="text-slate-500">You don't have permission to manage team roles.</div>
      <template v-else>
        <h2 class="font-semibold text-slate-800 mb-4">Team members</h2>
        <p class="text-sm text-slate-500 mb-4">Users in the current company. Assign a role to each member.</p>
        <div v-if="membersLoading" class="text-slate-500">Loading…</div>
        <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
              <tr>
                <th class="text-left px-4 py-3 font-medium">Name</th>
                <th class="text-left px-4 py-3 font-medium">Email</th>
                <th class="text-left px-4 py-3 font-medium w-64">Role</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="m in members" :key="m.user_id">
                <td class="px-4 py-3">{{ m.name }}</td>
                <td class="px-4 py-3">{{ m.email }}</td>
                <td class="px-4 py-3">
                  <select
                    :value="m.role_id || ''"
                    @change="setMemberRole(m.user_id, $event.target.value)"
                    class="w-full rounded border border-slate-300 px-2 py-1"
                  >
                    <option value="">— No role —</option>
                    <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.display_name || r.name }}</option>
                  </select>
                </td>
              </tr>
              <tr v-if="!members.length">
                <td colspan="3" class="px-4 py-8 text-center text-slate-500">No members. Switch company or add users to the company.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>

    <!-- Company switch & Add company (multi-company) -->
    <div v-show="activeTab === 'company' && companies.length > 0" class="max-w-2xl mt-8 space-y-6">
      <div class="bg-white rounded-lg border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-800 mb-4">Current company</h2>
        <div v-if="companies.length > 1" class="space-y-2">
          <select
            :value="currentCompanyId"
            @change="switchCompany($event.target.value)"
            class="w-full rounded-lg border border-slate-300 px-3 py-2"
          >
            <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }} ({{ c.default_currency }})</option>
          </select>
          <p class="text-sm text-slate-500">Switch which company you're working in. Settings above apply to the selected company.</p>
        </div>
        <div v-if="companies.length > 0 && roles.length >= 0" class="mt-4">
          <label class="block text-sm font-medium text-slate-700 mb-1">My role in this company</label>
          <select
            :value="myRoleId"
            @change="setMyRole($event.target.value)"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 max-w-xs"
          >
            <option value="">— No role (full access) —</option>
            <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.display_name || r.name }}</option>
          </select>
          <p class="text-sm text-slate-500 mt-1">Your permissions are based on this role. Create roles under the Roles tab.</p>
        </div>
        <p v-else-if="companies.length === 1" class="text-sm text-slate-500">You have one company.</p>
      </div>

      <div class="bg-white rounded-lg border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-800 mb-4">Add another company</h2>
        <p class="text-sm text-slate-500 mb-4">Create a new company to manage a separate business or entity.</p>
        <form @submit.prevent="addCompany" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Company name</label>
            <input v-model="addCompanyForm.name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="New company name" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Default currency</label>
            <select v-model="addCompanyForm.default_currency" class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="USD">USD</option>
              <option value="EUR">EUR</option>
              <option value="GBP">GBP</option>
              <option value="NGN">NGN</option>
            </select>
          </div>
          <p v-if="addCompanyError" class="text-sm text-red-600">{{ addCompanyError }}</p>
          <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700" :disabled="addCompanySaving">
            {{ addCompanySaving ? 'Adding…' : 'Add company' }}
          </button>
        </form>
      </div>
    </div>

    <!-- Roles & Permissions -->
    <div v-show="activeTab === 'roles'" class="max-w-4xl">
      <div v-if="!can('settings.roles')" class="text-slate-500">You don't have permission to manage roles.</div>
      <template v-else>
        <div class="flex justify-between items-center mb-4">
          <h2 class="font-semibold text-slate-800">Roles & permissions</h2>
          <button @click="openRoleForm()" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm">Add role</button>
        </div>
        <div v-if="rolesLoading" class="text-slate-500">Loading…</div>
        <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden mb-6">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
              <tr>
                <th class="text-left px-4 py-3 font-medium">Name</th>
                <th class="text-left px-4 py-3 font-medium">Display name</th>
                <th class="text-right px-4 py-3 font-medium w-24">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="r in roles" :key="r.id">
                <td class="px-4 py-3">{{ r.name }}</td>
                <td class="px-4 py-3">{{ r.display_name || '—' }}</td>
                <td class="px-4 py-3 text-right">
                  <button type="button" @click="openRoleForm(r)" class="text-slate-600 hover:text-slate-800 mr-2">Edit</button>
                  <button type="button" @click="deleteRole(r)" class="text-red-600 hover:text-red-800">Delete</button>
                </td>
              </tr>
              <tr v-if="!roles.length">
                <td colspan="3" class="px-4 py-8 text-center text-slate-500">No roles. Add one to assign permissions to users.</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="text-sm text-slate-500 mb-4">Assign a role to users in the Team tab.</p>
      </template>
    </div>

    <!-- Period closing -->
    <div v-show="activeTab === 'period-closing'" class="max-w-2xl">
      <div v-if="!can('settings.view')" class="text-slate-500">No permission.</div>
      <template v-else>
        <h2 class="font-semibold text-slate-800 mb-4">Close period</h2>
        <p class="text-sm text-slate-500 mb-4">Closing a period locks it from further edits. Enter the period start and end dates.</p>
        <form @submit.prevent="closePeriod" class="flex gap-4 items-end mb-6">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Period start</label>
            <input v-model="periodForm.period_start" type="date" required class="rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Period end</label>
            <input v-model="periodForm.period_end" type="date" required class="rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700" :disabled="periodClosing">Close period</button>
        </form>
        <p v-if="periodError" class="text-sm text-red-600 mb-4">{{ periodError }}</p>
        <h3 class="font-medium text-slate-800 mb-2">Closed periods</h3>
        <div v-if="closedPeriodsLoading" class="text-slate-500 py-4">Loading…</div>
        <p v-else-if="closedPeriodsError" class="text-sm text-red-600 py-2">{{ closedPeriodsError }}</p>
        <ul v-else class="list-disc list-inside text-sm text-slate-600 min-h-[2rem]">
          <li v-for="p in closedPeriods" :key="p.id">{{ formatPeriodDate(p.period_start) }} – {{ formatPeriodDate(p.period_end) }}</li>
          <li v-if="!closedPeriods.length" class="text-slate-500">None yet. Close a period above to lock it.</li>
        </ul>
      </template>
    </div>

    <!-- Webhooks -->
    <div v-show="activeTab === 'webhooks'" class="max-w-2xl">
      <div v-if="!can('settings.view')" class="text-slate-500">No permission.</div>
      <template v-else>
        <h2 class="font-semibold text-slate-800 mb-4">Webhooks</h2>
        <p class="text-sm text-slate-500 mb-4">Receive HTTP POST when events occur (e.g. invoice.paid, bill.paid). Add a webhook below.</p>
        <form @submit.prevent="addWebhook" class="flex flex-wrap gap-4 items-end mb-6">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">URL</label>
            <input v-model="webhookForm.url" type="url" required placeholder="https://..." class="rounded-lg border border-slate-300 px-3 py-2 w-64" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Event</label>
            <select v-model="webhookForm.event" class="rounded-lg border border-slate-300 px-3 py-2">
              <option value="invoice.paid">invoice.paid</option>
              <option value="bill.paid">bill.paid</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Secret (optional)</label>
            <input v-model="webhookForm.secret" type="text" placeholder="Signing secret" class="rounded-lg border border-slate-300 px-3 py-2 w-40" />
          </div>
          <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700">Add</button>
        </form>
        <p v-if="webhookError" class="text-sm text-red-600 mb-4">{{ webhookError }}</p>
        <p v-if="webhooksError" class="text-sm text-red-600 mb-4">Could not load webhooks. {{ webhooksError }}</p>
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
              <tr>
                <th class="text-left px-4 py-3 font-medium">URL</th>
                <th class="text-left px-4 py-3 font-medium">Event</th>
                <th class="text-right px-4 py-3 font-medium w-24">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="w in webhooks" :key="w.id">
                <td class="px-4 py-3">{{ w.url }}</td>
                <td class="px-4 py-3">{{ w.event }}</td>
                <td class="px-4 py-3 text-right"><button type="button" @click="deleteWebhook(w)" class="text-red-600 hover:text-red-800">Delete</button></td>
              </tr>
              <tr v-if="!webhooks.length && !webhooksLoading">
                <td colspan="3" class="px-4 py-8 text-center text-slate-500">No webhooks yet. Add one above to receive events.</td>
              </tr>
              <tr v-if="webhooksLoading">
                <td colspan="3" class="px-4 py-6 text-center text-slate-500">Loading…</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>

    <!-- Audit log -->
    <div v-show="activeTab === 'audit'" class="max-w-2xl">
      <h2 class="font-semibold text-slate-800 mb-4">{{ t('Audit log') }}</h2>
      <p class="text-slate-600 text-sm mb-4">{{ t('Audit log description') }}</p>
      <router-link to="/audit-log" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm">
        {{ t('View audit log') }} →
      </router-link>
    </div>

    <!-- Role add/edit modal -->
    <div v-if="roleFormOpen" class="fixed inset-0 bg-black/50 flex items-center justify-center z-20 p-4" @click.self="roleFormOpen = false">
          <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6">
            <h3 class="font-semibold text-lg mb-4">{{ roleFormId ? t('Edit role') : t('New role') }}</h3>
            <form @submit.prevent="saveRole" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Name (key)</label>
                <input v-model="roleForm.name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="e.g. accountant" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Display name</label>
                <input v-model="roleForm.display_name" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Accountant" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Permissions</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-64 overflow-y-auto border border-slate-200 rounded-lg p-3">
                  <label v-for="item in permissionList" :key="item.key" class="flex items-center gap-2 text-sm">
                    <input v-model="roleForm.permissions" type="checkbox" :value="item.key" class="rounded border-slate-300" />
                    <span>{{ item.label }}</span>
                  </label>
                </div>
              </div>
              <p v-if="roleFormError" class="text-sm text-red-600">{{ roleFormError }}</p>
              <div class="flex gap-2">
                <button type="button" @click="roleFormOpen = false" class="px-4 py-2 border border-slate-300 rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700">Save</button>
              </div>
            </form>
          </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { api, profileApi } from '../api';
import { usePermissions } from '../composables/usePermissions';
import { useI18n } from '../i18n';
import { useToast } from '../composables/useToast';

const { can, load: loadPerms, reset: resetPerms } = usePermissions();
const { t } = useI18n();
const toast = useToast();

const tabs = [
  { id: 'company', label: 'Company' },
  { id: 'profile', label: 'Profile' },
  { id: 'password', label: 'Password' },
  { id: 'preferences', label: 'Preferences' },
  { id: 'team', label: 'Team' },
  { id: 'roles', label: 'Roles' },
  { id: 'period-closing', label: 'Period closing' },
  { id: 'webhooks', label: 'Webhooks' },
  { id: 'audit', label: 'Audit log' },
];
const activeTab = ref('company');

const company = ref({
  name: '',
  email: '',
  address: '',
  default_currency: 'USD',
  fiscal_year_start: '01-01',
  tax_id: '',
  settings: {},
});
const companyId = ref(null);
const companies = ref([]);
const companyLoading = ref(true);
const companySaving = ref(false);
const companyError = ref('');

const profile = ref({ name: '', email: '', locale: 'en', email_verified_at: null });
const profileLoading = ref(true);
const profileSaving = ref(false);
const profileError = ref('');
const verificationSending = ref(false);
const twoFaEnabled = ref(false);
const twoFaLoading = ref(false);
const twoFaEnabling = ref(false);
const twoFaQrUrl = ref('');
const twoFaConfirmCode = ref('');
const twoFaConfirming = ref(false);
const twoFaError = ref('');
const twoFaDisablePassword = ref('');
const twoFaDisabling = ref(false);

const passwordForm = ref({ current_password: '', password: '', password_confirmation: '' });
const passwordSaving = ref(false);
const passwordError = ref('');
const passwordSuccess = ref(false);

const preferences = ref({
  date_format: 'Y-m-d',
  number_format: '1,234.56',
  first_day_of_week: '1',
});
const preferencesSaving = ref(false);
const preferencesError = ref('');

const currentCompanyId = ref(localStorage.getItem('companyId'));
const myRoleId = computed(() => {
  const c = companies.value.find((x) => String(x.id) === String(currentCompanyId.value));
  return c?.pivot?.role_id ?? '';
});

const newCompany = ref({ name: '', default_currency: 'USD' });
const newCompanyError = ref('');
const newCompanySaving = ref(false);

const addCompanyForm = ref({ name: '', default_currency: 'USD' });
const addCompanyError = ref('');
const addCompanySaving = ref(false);

const roles = ref([]);
const rolesLoading = ref(false);
const roleFormOpen = ref(false);
const roleFormId = ref(null);
const roleForm = ref({ name: '', display_name: '', permissions: [] });
const roleFormError = ref('');
const permissionDefs = ref({});
const members = ref([]);
const membersLoading = ref(false);
const closedPeriods = ref([]);
const closedPeriodsLoading = ref(false);
const closedPeriodsError = ref('');
const periodForm = ref({ period_start: '', period_end: '' });
const periodClosing = ref(false);
const periodError = ref('');
const webhooks = ref([]);
const webhooksLoading = ref(false);
const webhooksError = ref('');
const webhookForm = ref({ url: '', event: 'invoice.paid', secret: '' });
const webhookError = ref('');
const permissionList = computed(() => {
  const out = [];
  const defs = permissionDefs.value;
  for (const [module, perms] of Object.entries(defs)) {
    for (const [perm, label] of Object.entries(perms)) {
      out.push({ key: `${module}.${perm}`, label });
    }
  }
  return out;
});

async function loadCompanies() {
  try {
    const { data } = await api().get('/companies');
    companies.value = data.data || data;
    const cid = localStorage.getItem('companyId');
    if (cid && companies.value.length) {
      companyId.value = cid;
      const res = await api().get(`/companies/${cid}`);
      const c = res.data.data || res.data;
      company.value = {
        name: c.name ?? '',
        email: c.email ?? '',
        address: c.address ?? '',
        default_currency: c.default_currency ?? 'USD',
        fiscal_year_start: c.fiscal_year_start ?? '01-01',
        tax_id: c.tax_id ?? '',
        settings: c.settings ?? {},
      };
      const s = c.settings || {};
      preferences.value = {
        date_format: s.date_format ?? 'Y-m-d',
        number_format: s.number_format ?? '1,234.56',
        first_day_of_week: String(s.first_day_of_week ?? '1'),
      };
      localStorage.setItem('preferences', JSON.stringify(preferences.value));
    } else if (companies.value.length) {
      companyId.value = companies.value[0].id;
      localStorage.setItem('companyId', companyId.value);
      await loadCompanyDetail(companyId.value);
    }
  } catch (e) {
    companyError.value = 'Failed to load company';
  } finally {
    companyLoading.value = false;
  }
}

async function loadCompanyDetail(id) {
  const res = await api().get(`/companies/${id}`);
  const c = res.data.data || res.data;
  company.value = {
    name: c.name ?? '',
    email: c.email ?? '',
    address: c.address ?? '',
    default_currency: c.default_currency ?? 'USD',
    fiscal_year_start: c.fiscal_year_start ?? '01-01',
    tax_id: c.tax_id ?? '',
    settings: {
    ...(c.settings ?? {}),
    payment_url_template: (c.settings && c.settings.payment_url_template) ? c.settings.payment_url_template : '',
    payment_reminders_enabled: c.settings?.payment_reminders_enabled ?? true,
    payment_reminders_days_after: (c.settings && c.settings.payment_reminders_days_after != null) ? c.settings.payment_reminders_days_after : '0, 7, 30',
    payment_reminders_days_before: (c.settings && c.settings.payment_reminders_days_before != null) ? String(c.settings.payment_reminders_days_before) : '',
  },
  };
  const s = c.settings || {};
  preferences.value = {
    date_format: s.date_format ?? 'Y-m-d',
    number_format: s.number_format ?? '1,234.56',
    first_day_of_week: String(s.first_day_of_week ?? '1'),
  };
  localStorage.setItem('preferences', JSON.stringify(preferences.value));
}

async function loadProfile() {
  const stored = localStorage.getItem('user');
  if (stored) {
    try {
      const u = JSON.parse(stored);
      profile.value = { name: u.name ?? '', email: u.email ?? '', locale: u.locale ?? 'en' };
    } catch (_) {}
  }
  try {
    const { data } = await profileApi().get('/user');
    profile.value = {
      name: data.name ?? profile.value.name ?? '',
      email: data.email ?? profile.value.email ?? '',
      locale: data.locale ?? profile.value.locale ?? 'en',
      email_verified_at: data.email_verified_at ?? null,
    };
    if (data.email) localStorage.setItem('user', JSON.stringify({ name: profile.value.name, email: profile.value.email, locale: profile.value.locale }));
    if (activeTab.value === 'profile') load2FaStatus();
  } catch (e) {
    profileError.value = 'Failed to load profile';
  } finally {
    profileLoading.value = false;
  }
}

async function load2FaStatus() {
  try {
    const { data } = await profileApi().get('/user/2fa');
    twoFaEnabled.value = data.enabled === true;
  } catch (_) {
    twoFaEnabled.value = false;
  }
}

async function resendVerification() {
  verificationSending.value = true;
  try {
    await profileApi().post('/email/verification-notification');
    profileError.value = '';
    toast.show('Verification link sent. Check your email.', 'info');
  } catch (e) {
    profileError.value = e.response?.data?.message || 'Failed to send';
  } finally {
    verificationSending.value = false;
  }
}

async function startEnable2Fa() {
  twoFaError.value = '';
  twoFaLoading.value = true;
  try {
    const { data } = await profileApi().post('/user/2fa/enable');
    twoFaQrUrl.value = data.qr_code_url || '';
    twoFaEnabling.value = true;
  } catch (e) {
    twoFaError.value = e.response?.data?.message || 'Failed to start 2FA';
  } finally {
    twoFaLoading.value = false;
  }
}

async function confirm2Fa() {
  twoFaError.value = '';
  twoFaConfirming.value = true;
  try {
    await profileApi().post('/user/2fa/confirm', { code: twoFaConfirmCode.value });
    twoFaEnabling.value = false;
    twoFaEnabled.value = true;
    twoFaConfirmCode.value = '';
  } catch (e) {
    twoFaError.value = e.response?.data?.message || 'Invalid code';
  } finally {
    twoFaConfirming.value = false;
  }
}

async function disable2Fa() {
  twoFaError.value = '';
  twoFaDisabling.value = true;
  try {
    await profileApi().post('/user/2fa/disable', { password: twoFaDisablePassword.value });
    twoFaEnabled.value = false;
    twoFaDisablePassword.value = '';
  } catch (e) {
    twoFaError.value = e.response?.data?.message || 'Failed to disable';
  } finally {
    twoFaDisabling.value = false;
  }
}

async function saveCompany() {
  if (!companyId.value) return;
  companyError.value = '';
  companySaving.value = true;
  try {
    await api().put(`/companies/${companyId.value}`, {
      name: company.value.name,
      email: company.value.email || null,
      address: company.value.address || null,
      default_currency: company.value.default_currency,
      fiscal_year_start: company.value.fiscal_year_start || '01-01',
      tax_id: company.value.tax_id || null,
      settings: {
        ...company.value.settings,
        payment_url_template: company.value.settings?.payment_url_template || null,
        payment_reminders_enabled: company.value.settings?.payment_reminders_enabled ?? true,
        payment_reminders_days_after: company.value.settings?.payment_reminders_days_after ?? '0, 7, 30',
        payment_reminders_days_before: company.value.settings?.payment_reminders_days_before ?? '',
      },
    });
  } catch (e) {
    companyError.value = e.response?.data?.message || 'Failed to save';
  } finally {
    companySaving.value = false;
  }
}

async function saveProfile() {
  profileError.value = '';
  profileSaving.value = true;
  try {
    await profileApi().put('/user', {
      name: profile.value.name,
      email: profile.value.email,
      locale: profile.value.locale,
    });
  } catch (e) {
    profileError.value = e.response?.data?.message || (e.response?.data?.errors ? Object.values(e.response.data.errors).flat().join(' ') : 'Failed to save');
  } finally {
    profileSaving.value = false;
  }
}

async function savePassword() {
  passwordError.value = '';
  passwordSuccess.value = false;
  if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
    passwordError.value = 'Passwords do not match';
    return;
  }
  passwordSaving.value = true;
  try {
    await profileApi().put('/user/password', {
      current_password: passwordForm.value.current_password,
      password: passwordForm.value.password,
      password_confirmation: passwordForm.value.password_confirmation,
    });
    passwordForm.value = { current_password: '', password: '', password_confirmation: '' };
    passwordSuccess.value = true;
  } catch (e) {
    passwordError.value = e.response?.data?.message || 'Failed to update password';
  } finally {
    passwordSaving.value = false;
  }
}

async function savePreferences() {
  if (!companyId.value) return;
  preferencesError.value = '';
  preferencesSaving.value = true;
  try {
    await api().put(`/companies/${companyId.value}`, {
      settings: {
        ...company.value.settings,
        date_format: preferences.value.date_format,
        number_format: preferences.value.number_format,
        first_day_of_week: parseInt(preferences.value.first_day_of_week, 10),
      },
    });
    localStorage.setItem('preferences', JSON.stringify({
      date_format: preferences.value.date_format,
      number_format: preferences.value.number_format,
      first_day_of_week: preferences.value.first_day_of_week,
    }));
  } catch (e) {
    preferencesError.value = e.response?.data?.message || 'Failed to save';
  } finally {
    preferencesSaving.value = false;
  }
}

async function switchCompany(id) {
  try {
    await api().post('/company/switch', { company_id: parseInt(id, 10) });
    localStorage.setItem('companyId', id);
    currentCompanyId.value = id;
    companyId.value = id;
    await loadCompanyDetail(id);
  } catch (e) {
    companyError.value = 'Failed to switch company';
  }
}

async function createFirstCompany() {
  newCompanyError.value = '';
  newCompanySaving.value = true;
  try {
    const { data } = await api().post('/companies', {
      name: newCompany.value.name,
      default_currency: newCompany.value.default_currency,
    });
    const c = data.data || data;
    companyId.value = c.id;
    currentCompanyId.value = c.id;
    localStorage.setItem('companyId', String(c.id));
    await loadCompanies();
    newCompany.value = { name: '', default_currency: 'USD' };
  } catch (e) {
    newCompanyError.value = e.response?.data?.message || 'Failed to create company';
  } finally {
    newCompanySaving.value = false;
  }
}

async function setMyRole(roleId) {
  if (!companyId.value) return;
  try {
    await api().put(`/companies/${companyId.value}/my-role`, { role_id: roleId || null });
    await loadCompanies();
    resetPerms();
    loadPerms();
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to update role', 'error');
  }
}

async function addCompany() {
  addCompanyError.value = '';
  addCompanySaving.value = true;
  try {
    const { data } = await api().post('/companies', {
      name: addCompanyForm.value.name,
      default_currency: addCompanyForm.value.default_currency,
    });
    const c = data.data || data;
    await loadCompanies();
    addCompanyForm.value = { name: '', default_currency: 'USD' };
    currentCompanyId.value = c.id;
    companyId.value = c.id;
    localStorage.setItem('companyId', String(c.id));
    await loadCompanyDetail(c.id);
  } catch (e) {
    addCompanyError.value = e.response?.data?.message || 'Failed to add company';
  } finally {
    addCompanySaving.value = false;
  }
}

async function loadRoles() {
  rolesLoading.value = true;
  try {
    const { data } = await api().get('/roles');
    roles.value = data.data || data;
  } catch (e) {
    console.error(e);
  } finally {
    rolesLoading.value = false;
  }
}

async function loadPermissionDefs() {
  try {
    const { data } = await api().get('/permissions/definitions');
    permissionDefs.value = data.data || data;
  } catch (e) {
    permissionDefs.value = {};
  }
}

function openRoleForm(role = null) {
  roleFormId.value = role ? role.id : null;
  roleForm.value = {
    name: role ? role.name : '',
    display_name: role ? (role.display_name || '') : '',
    permissions: role && role.permissions ? [...role.permissions] : [],
  };
  roleFormError.value = '';
  roleFormOpen.value = true;
  if (permissionList.value.length === 0) loadPermissionDefs();
}

async function saveRole() {
  roleFormError.value = '';
  try {
    if (roleFormId.value) {
      await api().put('/roles/' + roleFormId.value, roleForm.value);
    } else {
      await api().post('/roles', roleForm.value);
    }
    roleFormOpen.value = false;
    loadRoles();
  } catch (e) {
    roleFormError.value = e.response?.data?.message || (e.response?.data?.errors ? JSON.stringify(e.response.data.errors) : 'Failed to save');
  }
}

async function deleteRole(r) {
  if (!(await toast.showConfirm(`Delete role "${r.display_name || r.name}"?`))) return;
  try {
    await api().delete('/roles/' + r.id);
    loadRoles();
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to delete', 'error');
  }
}

async function loadMembers() {
  if (!companyId.value) return;
  membersLoading.value = true;
  try {
    const { data } = await api().get(`/companies/${companyId.value}/members`);
    members.value = data.data || data;
  } catch (e) {
    members.value = [];
  } finally {
    membersLoading.value = false;
  }
}

async function setMemberRole(userId, roleId) {
  if (!companyId.value) return;
  try {
    await api().put(`/companies/${companyId.value}/members/${userId}`, { role_id: roleId || null });
    const m = members.value.find((x) => x.user_id === userId);
    if (m) m.role_id = roleId || null;
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to update role', 'error');
  }
}

function formatPeriodDate(d) {
  if (!d) return '—';
  if (typeof d === 'string') return d.slice(0, 10);
  if (d && d.date) return String(d.date).slice(0, 10);
  return String(d);
}
async function loadClosedPeriods() {
  closedPeriodsLoading.value = true;
  closedPeriodsError.value = '';
  try {
    const { data } = await api().get('/period-closing?per_page=50');
    closedPeriods.value = Array.isArray(data) ? data : (data?.data ?? []);
  } catch (e) {
    closedPeriods.value = [];
    closedPeriodsError.value = e.response?.data?.message || 'Failed to load closed periods.';
  } finally {
    closedPeriodsLoading.value = false;
  }
}
async function closePeriod() {
  periodError.value = '';
  periodClosing.value = true;
  try {
    await api().post('/period-closing/close', periodForm.value);
    loadClosedPeriods();
  } catch (e) {
    periodError.value = e.response?.data?.message || 'Failed to close period';
  } finally {
    periodClosing.value = false;
  }
}
async function loadWebhooks() {
  webhooksLoading.value = true;
  webhooksError.value = '';
  try {
    const { data } = await api().get('/webhooks');
    webhooks.value = Array.isArray(data) ? data : (data?.data ?? []);
  } catch (e) {
    webhooks.value = [];
    webhooksError.value = e.response?.data?.message || 'Failed to load webhooks.';
  } finally {
    webhooksLoading.value = false;
  }
}
async function addWebhook() {
  webhookError.value = '';
  try {
    await api().post('/webhooks', webhookForm.value);
    webhookForm.value = { url: '', event: 'invoice.paid', secret: '' };
    loadWebhooks();
  } catch (e) {
    webhookError.value = e.response?.data?.message || 'Failed to add';
  }
}
async function deleteWebhook(w) {
  if (!(await toast.showConfirm('Delete this webhook?'))) return;
  try {
    await api().delete('/webhooks/' + w.id);
    loadWebhooks();
  } catch (_) {}
}
watch(activeTab, (tab) => {
  if (tab === 'roles' && can('settings.roles')) {
    loadRoles();
    loadPermissionDefs();
  }
  if (tab === 'profile') load2FaStatus();
  if (tab === 'team' && can('settings.roles')) {
    loadMembers();
    if (!roles.value.length) loadRoles();
  }
  if (tab === 'period-closing') loadClosedPeriods();
  if (tab === 'webhooks') loadWebhooks();
});

onMounted(() => {
  loadCompanies();
  loadProfile();
  loadRoles();
});
</script>
