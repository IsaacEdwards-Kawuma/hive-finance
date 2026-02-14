# Improvements & optional additions

Ideas to make the app more complete or production-ready. Items marked **Done** are already implemented.

---

## Done in this pass

- **Bills list**: i18n + `useFormats` (same as Invoices); date column uses `formatDate`.
- **Invoices list**: Date column uses `formatDate` for locale-aware display.
- **Reports index**: All report titles and descriptions use `t()` for i18n.
- **Stripe webhook**: `POST /api/stripe/webhook` verifies Stripe signature and on `checkout.session.completed` records a payment and marks the invoice paid (idempotent by `reference` = session id). Configure `STRIPE_WEBHOOK_SECRET` and point Stripe Dashboard → Webhooks to this URL.

---

## Frontend polish

- **Customers / Vendors / Banking / Accounts / Items / Tax rates / Credit notes / Journal entries**: Add `useI18n()` and `t()` for headings, buttons, table headers, and empty states (same pattern as Invoices/Bills).
- **Dashboard**: Replace remaining hardcoded strings with `t()` (e.g. “Dashboard”, “Overview of your business finances”, “Revenue (YTD)”, “Outstanding AR/AP”, “Recent Invoices/Bills”, “Loading dashboard…”).
- **Date formatting**: Use `formatDate()` anywhere a raw date string is shown (e.g. Banking transactions, report date ranges, GL Detail).
- **Toasts**: Replace `alert()` / `confirm()` with a small toast or modal component for success/error and confirmations (better UX and i18n-friendly).
- **Loading states**: Use a single loading/skeleton component where “Loading…” appears.
- **Empty states**: Standardise empty-state copy and optional illustration/CTA across list pages.

---

## Backend & API

- **Stripe success/cancel URLs**: Currently point to `/invoices?stripe=success` and `?stripe=cancel`. Optionally read from company settings or env and show a “Payment successful” message when `stripe=success` is present.
- **Idempotency for payment-link**: Optional idempotency key (e.g. header or body) for `POST /invoices/{id}/payment-link` to avoid creating duplicate sessions on retries.
- **API deprecation headers**: When a deprecated API version is used, add middleware that sets `X-API-Deprecated: true` and `X-API-Sunset: YYYY-MM-DD` on responses.

---

## Testing & quality

- **Feature tests**: For PaymentReminderJob (before-due and after-due), ModuleController (index + update override), InvoiceController `paymentLink`, StripeWebhookController (with mocked Stripe event).
- **E2E**: Critical flows (login → create invoice → record payment, or enable module) with Playwright/Cypress.
- **PHPStan / Psalm**: Static analysis to catch type and null-safety issues.

---

## Security & ops

- **Rate limit webhook**: Throttle `POST /api/stripe/webhook` by IP or signature to reduce abuse (while keeping Stripe’s signature verification as the main check).
- **Audit**: Ensure every mutation (create/update/delete) that should be company-scoped checks `company_id` or uses CompanyScope.
- **Backup / restore**: Document or script DB backup (and `modules_enabled.json` if desired) for production.

---

## Documentation & onboarding

- **README**: Add short sections for: Payment reminders (before/after due), Module enable/disable, Stripe (env vars + webhook URL), and link to `docs/API_VERSIONING.md`.
- **User guide**: Optional in-app or separate doc for: creating first company, first invoice, recording payment, running reports, reconciliation.
- **.env.example**: Ensure every optional feature (Stripe, mail, etc.) has a commented example.

---

## Optional features

- **Bulk actions**: e.g. “Mark selected as reconciled” already exists; extend to bulk export or bulk status change for invoices/bills.
- **Dashboard widgets**: Let modules or settings toggle which KPIs/cards appear.
- **Recurring run history**: Show last run and next run for recurring invoices/bills.
- **Multi-currency**: Already partial (invoice/bill currency); full support (realised gains/losses, reporting currency) if needed.
- **Pagination**: Consistent “Load more” or page size selector on long lists (Invoices/Bills already use per_page; expose in UI if desired).

Use this list as a backlog; pick items by priority and effort.
