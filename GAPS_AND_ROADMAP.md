# HiveStack IntelliCash – What’s Missing (vs. Spec)

Compared to the original Akaunting-style SRS, here’s what is **not yet implemented** or only partly done.

**Already implemented:** Roles & permissions (UI + API), client portal (/portal), payment reminders, Stripe Pay now + webhook, multi-currency, App Store UI, API versioning, OpenAPI + Swagger at /api/docs, i18n (en/ar), RTL, Create/Edit Invoice & Bill & Record payment, Journal Entry form, Bank account/transaction forms, Chart of Accounts add/edit, Vendors/Items/Tax rates pages, Recurring invoices & bills, Credit notes, Period closing, Cash flow + Tax summary reports, Invoice PDF, 2FA (API), Audit log, permission checks in UI, Investments + Dashboard charts, global 401 → login.

---

## 1. Frontend – Create/Edit Flows — DONE

All major create/edit flows exist (invoices, bills, payments, journal entries, bank accounts/transactions, chart of accounts, vendors, items, tax rates).

---

## 2. Core Features (Backend + optional UI)

| Missing | Spec ref | Notes |
|--------|----------|--------|
| **Recurring invoices** | AR-05 | No table/jobs for recurring schedules; no “recurring” option when creating invoices. |
| **Recurring bills** | AP-03 | Same for bills. |
| **Credit notes** | AR-08 | No credit note entity or API; no “issue credit” from invoice. |
| **Period closing** | GL-06 | No locking of periods; no “close period” or “retained earnings” automation. |
| **Bank reconciliation** | BNK-05 | No reconciliation flow (match transactions to statement, mark cleared). DB has `reconciled` on `bank_transactions` but no logic/UI. |
| **Cash flow statement** | RPT-06 | Report list has P&L, Balance Sheet, Trial Balance, AR/AP aging; no Cash Flow report. |
| **Tax summary report** | RPT-05 | No report for “sales tax collected vs paid” by rate. |
| **Invoice PDF** | AR-02 | No “download PDF” for an invoice (no template or export). |
| **Multi-currency on invoices** | AR-04 | Invoices have `currency`/`exchange_rate` in DB; UI/UX doesn’t support selecting currency or rate. |

---

## 3. Security & Compliance

| Missing | Spec ref | Notes |
|--------|----------|--------|
| **Two-factor auth (2FA)** | SEC-01, CMP-05 | No TOTP (e.g. Google Authenticator) option. |
| **Audit log** | SEC-04 | No logging of create/update/delete on financial records (who, when, old/new values). |
| **Role/permission checks in UI** | CMP-03 | Roles exist in DB; no permission checks in frontend (e.g. hide “Create invoice” by role). |
| **Fine-grained permissions** | CMP-03 | No UI to configure “who can create/edit/delete” per module. |

---

## 4. Client & Payments

| Missing | Spec ref | Notes |
|--------|----------|--------|
| **Client portal** | AR-10 | No separate portal for customers to log in, view their invoices, and download PDFs. |
| **Online payments** | AR-06 | No Stripe/PayPal (or other) integration; no “Pay now” link on invoices. |
| **Payment reminders** | AR-11 | No automated emails (e.g. X days before/after due). |

---

## 5. Module System & App Store

| Missing | Spec ref | Notes |
|--------|----------|--------|
| **Module loader** | 3.3.1 | No runtime that loads `module.json`, registers providers, or runs module migrations. |
| **App Store UI** | 3.3.2 | No in-app “Apps” page (browse/install modules, license key for paid). |
| **Module hooks** | 3.3.1 | No registration of widgets, reports, or settings from modules. |

---

## 6. API & Docs

| Missing | Spec ref | Notes |
|--------|----------|--------|
| **API versioning** | API-03 | Only v1 exists; no formal versioning strategy or deprecation. |
| **OpenAPI / Swagger** | API-04 | No `/api/documentation` or machine-readable spec. |
| **Webhooks** | API-06 | No event-based outbound notifications (e.g. “invoice paid”). |
| **Rate limiting** | API-05 | No explicit 60/min (or similar) limit per token. |

---

## 7. Internationalization

| Missing | Spec ref | Notes |
|--------|----------|--------|
| **Full UI translations** | I18N-01 | Ensure all new strings are in i18n (en + ar). |
| **Date/number in reports** | I18N-03 | Use format preferences consistently in all report PDFs/tables. |

(RTL via `dir` from locale is done.)

---

## 8. App-Store / Optional Modules (per spec, not in core)

These are **intentionally** out of core; could be separate apps/modules:

- Payroll (gross-to-net, tax forms, direct deposit).
- Inventory (stock, COGS, optional warehouses).
- Expense claims (submit, approve, receipts).
- Budgeting & forecasting.
- Fixed assets & depreciation.
- Bank feeds (Plaid/Yodlee).
- 1099 tracking for vendors.

---

## Suggested priority order

1. **High**  
   - Bank reconciliation (full match-and-clear UI if not complete).

2. **Medium**  
   - Outbound webhooks (e.g. invoice paid).  
   - Fine-grained permissions UI (if needed).  
   - Module loader + hooks.

3. **Later**  
   - i18n completeness; date/number in all reports.

---

*This file is a living gap list. As features are implemented, remove or update items here.*
