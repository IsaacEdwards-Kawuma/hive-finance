<template>
  <div class="max-w-4xl mx-auto space-y-10">
    <div>
      <h1 class="text-2xl font-bold text-slate-800">About</h1>
      <p class="text-slate-500 text-sm mt-0.5">Company, contacts, terms and support</p>
    </div>

    <nav class="bg-slate-50 rounded-xl p-4 border border-slate-200">
      <p class="text-sm font-medium text-slate-700 mb-2">Jump to section</p>
      <div class="flex flex-wrap gap-2">
        <a v-for="s in sections" :key="s.id" :href="`#${s.id}`" class="text-sm text-slate-600 hover:text-slate-900 hover:underline">{{ s.title }}</a>
      </div>
    </nav>

    <section v-for="s in sections" :key="s.id" :id="s.id" class="scroll-mt-6">
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/50">
          <h2 class="text-lg font-semibold text-slate-800">{{ s.title }}</h2>
          <p v-if="s.subtitle" class="text-sm text-slate-500 mt-0.5">{{ s.subtitle }}</p>
        </div>
        <div class="px-5 py-4 prose prose-slate max-w-none text-sm">
          <div v-html="s.content" class="space-y-3 about-content"></div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
const sections = [
  {
    id: 'company',
    title: 'About the company',
    subtitle: 'HiveStack IntelliCash',
    content: `
      <p>HiveStack IntelliCash is a cloud-based accounting and finance platform designed for small and medium businesses. We help you manage invoices, bills, customers, vendors, bank transactions, and financial reports in one place.</p>
      <p>Our mission is to make professional-grade bookkeeping and reporting simple and accessible, with clear reports, PDF exports, and secure multi-company support. Built with modern technology, IntelliCash keeps your data safe and your workflow efficient.</p>
    `,
  },
  {
    id: 'contacts',
    title: 'Contacts',
    subtitle: 'Get in touch',
    content: `
      <div class="grid gap-3 not-prose">
        <div class="flex items-start gap-3">
          <span class="text-slate-400 font-medium min-w-[80px]">Email</span>
          <a href="mailto:support@hivestack.com" class="text-slate-700 hover:text-slate-900">support@hivestack.com</a>
        </div>
        <div class="flex items-start gap-3">
          <span class="text-slate-400 font-medium min-w-[80px]">General</span>
          <a href="mailto:info@hivestack.com" class="text-slate-700 hover:text-slate-900">info@hivestack.com</a>
        </div>
        <div class="flex items-start gap-3">
          <span class="text-slate-400 font-medium min-w-[80px]">Phone</span>
          <span class="text-slate-700">+1 (555) 123-4567</span>
        </div>
        <div class="flex items-start gap-3">
          <span class="text-slate-400 font-medium min-w-[80px]">Address</span>
          <span class="text-slate-700">123 Business Street, Suite 100<br />City, State 12345</span>
        </div>
      </div>
      <p class="mt-4 text-slate-500">For sales or partnerships, contact <a href="mailto:sales@hivestack.com" class="text-slate-700 hover:underline">sales@hivestack.com</a>.</p>
    `,
  },
  {
    id: 'helplines',
    title: 'Help lines &amp; support',
    subtitle: 'When you need assistance',
    content: `
      <p><strong>Customer support</strong></p>
      <ul class="list-disc list-inside text-slate-600 space-y-1">
        <li>Email: <a href="mailto:support@hivestack.com" class="text-slate-700 hover:underline">support@hivestack.com</a></li>
        <li>Phone: +1 (555) 123-4567</li>
        <li>Hours: Monday–Friday, 9:00 AM – 6:00 PM (local time)</li>
      </ul>
      <p class="mt-4"><strong>In-app help</strong></p>
      <p class="text-slate-600">Use the <a href="/help" class="text-slate-700 font-medium hover:underline">Help</a> page for step-by-step guides on every feature, and the <strong>Take a tour</strong> option in the sidebar for a quick walkthrough.</p>
      <p class="mt-4"><strong>Documentation</strong></p>
      <p class="text-slate-600">API documentation (OpenAPI/Swagger) is available at <a href="/api/docs" target="_blank" rel="noopener" class="text-slate-700 font-medium hover:underline">/api/docs</a> for developers.</p>
    `,
  },
  {
    id: 'terms',
    title: 'Terms and conditions',
    subtitle: 'Use of the service',
    content: `
      <p><strong>1. Acceptance</strong></p>
      <p>By accessing or using HiveStack IntelliCash (“Service”), you agree to be bound by these Terms and Conditions. If you do not agree, do not use the Service.</p>
      <p><strong>2. Use of the Service</strong></p>
      <p>You must use the Service only for lawful purposes and in accordance with these terms. You are responsible for maintaining the confidentiality of your account and for all activity under your account. You must not share your login credentials or allow unauthorized access.</p>
      <p><strong>3. Data and privacy</strong></p>
      <p>Your data is stored securely. We process data in accordance with our Privacy Policy and applicable law. You retain ownership of your data; we do not sell your data to third parties. We may use anonymized or aggregated data to improve the Service.</p>
      <p><strong>4. Availability and changes</strong></p>
      <p>We strive to keep the Service available but do not guarantee uninterrupted access. We may update the Service, features, or these terms from time to time. Continued use after changes constitutes acceptance of the updated terms.</p>
      <p><strong>5. Limitation of liability</strong></p>
      <p>The Service is provided “as is.” We are not liable for indirect, incidental, or consequential damages arising from your use of the Service. Our total liability is limited to the amount you paid for the Service in the twelve months preceding the claim.</p>
      <p><strong>6. Termination</strong></p>
      <p>We may suspend or terminate your access if you breach these terms. You may stop using the Service at any time. Upon termination, you may export your data as permitted by the Service.</p>
      <p><strong>7. Governing law</strong></p>
      <p>These terms are governed by the laws of the jurisdiction in which the company operating the Service is established. Any disputes shall be resolved in the courts of that jurisdiction.</p>
      <p class="text-slate-500 text-xs mt-6">Last updated: 2025. For the full and current version of our Terms and Conditions, please contact <a href="mailto:legal@hivestack.com" class="text-slate-600 hover:underline">legal@hivestack.com</a>.</p>
    `,
  },
];
</script>

<style scoped>
.about-content p { margin: 0.75em 0; }
.about-content p:first-child { margin-top: 0; }
.about-content strong { color: #1e293b; }
.about-content ul { margin: 0.5em 0; }
.about-content a { text-decoration: underline; }
</style>
