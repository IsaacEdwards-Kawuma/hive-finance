# What’s Missing in the Whole Project

A single checklist of gaps across **Hive Finances** (Laravel + Vue). Use this with `GAPS_AND_ROADMAP.md` and `docs/IMPROVEMENTS.md` for prioritization.

---

## 1. Testing

| Gap | Notes |
|-----|--------|
| **No project-level PHP tests** | No `tests/` directory with Feature/Unit tests for app code. Vendor packages (e.g. Stripe) have their own tests. |
| **No E2E tests** | No Playwright/Cypress (or similar) for critical flows (login → create invoice → record payment). |
| **No frontend unit tests** | No Vitest/Jest for Vue components or composables. |

**Suggested first steps:** Add `tests/Feature/` for API endpoints (e.g. login, invoice CRUD, payment recording) and at least one E2E flow.

---

## 2. Frontend – UX & consistency

| Gap | Location / Notes |
|-----|-------------------|
| **No global 401 handling** | `api.js` creates axios instances with token; there is no response interceptor. On 401 (expired/invalid token), the app does not redirect to login or clear storage. Users can get stuck on failed requests. |
| **Hardcoded strings in nav** | `Layout.vue`: “Items”, “Tax rates”, “Audit log”, “Logout” are not wrapped in `t()` for i18n. |
| **Inconsistent empty/loading states** | Some pages use “Loading…”, others “Loading”; empty states vary. Could standardize one loading and one empty-state component. |
| **Pagination in UI** | Invoices/Bills use `per_page` in API; list UIs don’t expose page size or “Load more” consistently. |

---

## 3. Internationalization (i18n)

| Gap | Notes |
|-----|--------|
| **Nav labels** | “Items”, “Tax rates”, “Audit log” in sidebar should use `t()` and be added to `i18n.js` (en + ar). |
| **New feature strings** | New copy (e.g. “Portfolio value”, “View investments”, “Cost vs value”, “Cash flow overview”, chart labels) may exist only in English; add to i18n if you support Arabic. |
| **Date/number in reports** | `useFormats` exists; ensure all report PDFs and tables use it for locale-aware date/number. |

---

## 4. Documentation

| Gap | Notes |
|-----|--------|
| **README** | Does not mention: Investments, Dashboard charts, Client portal URL, or that API docs live at `/api/docs`. |
| **GAPS_AND_ROADMAP.md** | Outdated: many items (invoice/bill forms, credit notes, recurring, 2FA, audit log, client portal, RTL, etc.) are implemented. Worth a pass to mark done and leave only real gaps. |
| **.env.example** | Good; could add a short comment for optional features (e.g. mail for payment reminders, queue for jobs). |
| **Backup/restore** | No documented or scripted DB backup/restore for production. |

---

## 5. Security & operations

| Gap | Notes |
|-----|--------|
| **401 → re-login** | Frontend should react to 401 (e.g. clear token, redirect to `/login`) so expired sessions don’t leave users confused. |
| **Audit of company scope** | IMPROVEMENTS suggests verifying every mutation is company-scoped (CompanyScope / `company_id`). |
| **Webhook rate limit** | Stripe webhook already throttled (e.g. 120/min); no other gaps identified. |

---

## 6. Features / product (from existing docs, still valid)

| Gap | Source | Notes |
|-----|--------|-------|
| **Bank reconciliation UI** | GAPS / IMPROVEMENTS | Backend/DB may support it; full “match and clear” flow and UI may be incomplete. |
| **Recurring run history** | IMPROVEMENTS | Show last/next run for recurring invoices and bills. |
| **Bulk actions** | IMPROVEMENTS | Extend beyond “mark reconciled” (e.g. bulk export, bulk status). |
| **Dashboard widgets** | IMPROVEMENTS | Let modules or settings control which KPI/chart cards appear. |
| **OpenAPI freshness** | GAPS | `public/openapi.json` and `/api/docs` exist; keep spec in sync when adding/changing API. |
| **Webhooks (outbound)** | GAPS | No event-based outbound webhooks (e.g. “invoice.paid”) for third-party integrations. |

---

## 7. Optional / nice-to-have

- **Stripe success/cancel UX**: Dedicated “Payment successful” / “Payment cancelled” message when returning with `?stripe=success` or `?stripe=cancel`.
- **Idempotency**: Optional idempotency key for `POST /invoices/{id}/payment-link` to avoid duplicate Stripe sessions on retry.
- **PHPStan/Psalm**: Static analysis for PHP to catch type and null-safety issues.
- **User guide**: In-app or separate doc for: first company, first invoice, recording payment, reports, reconciliation.

---

## Quick wins (high impact, low effort)

1. **Global 401 handling** – In `api.js` (or where the api client is used), add an axios response interceptor: on 401, clear `token` (and optionally `companyId`), then redirect to `/login`.
2. **i18n for nav** – Wrap “Items”, “Tax rates”, “Audit log”, “Logout” in `t()` and add keys to `i18n.js`.
3. **README update** – Add one line each for: Investments, Dashboard charts, Client portal, link to `/api/docs`.
4. **GAPS_AND_ROADMAP.md** – Go through and mark implemented items as done; keep only current gaps.

---

*Generated as a project-wide gap list. Update as you ship features or change priorities.*
