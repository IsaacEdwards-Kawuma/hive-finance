# HiveStack IntelliCash

Akaunting-style **double-entry accounting platform** built with Laravel and Vue.js. Multi-company, REST API, and modular app-store ready.

## Features (from spec)

- **Multi-company** – Unlimited companies; role-based access per company
- **General Ledger** – Chart of accounts, manual & recurring journal entries, trial balance, period closing
- **AR** – Customers, invoices, payments, credit notes, aging, client portal (API-ready)
- **AP** – Vendors, bills, payments, AP aging
- **Banking** – Bank/cash accounts, transactions, transfers, reconciliation
- **Reporting** – P&L, Balance Sheet, Trial Balance, GL detail, AR/AP aging, cash flow, tax summary
- **Investments** – Track holdings (stocks, bonds, funds, etc.), cost vs value, allocation and performance charts
- **Dashboard** – KPIs (revenue, AR, AP, portfolio value), cash flow and activity charts, quick links
- **Items & Tax** – Product/service catalog, tax rates
- **API** – RESTful v1 with Sanctum; all actions available via API. **API docs:** [OpenAPI spec](/api/documentation) and [Swagger UI](/api/docs).
- **Client portal** – Customers can log in at `/portal`, view their invoices, and download PDFs.
- **Module system** – `modules/` with `module.json` manifest for app-store extensions

## Stack

- **Backend:** Laravel 12, PHP 8.2+
- **Frontend:** Vue 3, Vue Router, Tailwind CSS, Vite
- **Database:** SQLite (default), MySQL/PostgreSQL supported
- **Auth:** Laravel Sanctum (API tokens)

## Setup

1. **Install PHP dependencies (includes Sanctum)**  
   ```bash
   composer update
   ```

2. **Publish Sanctum migrations (if not auto-loaded)**  
   ```bash
   php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
   ```

3. **Environment**  
   Copy `.env.example` to `.env`, then:
   ```bash
   php artisan key:generate
   ```
   For SQLite (default): ensure `DB_CONNECTION=sqlite` and `database/database.sqlite` exists:
   ```bash
   touch database/database.sqlite
   ```

4. **Migrations**  
   ```bash
   php artisan migrate
   ```

5. **Seed (optional – default company + chart of accounts)**  
   ```bash
   php artisan db:seed
   ```
   Creates company "Default Company", user `admin@example.com` / `password`, and default chart of accounts.

6. **Frontend**  
   ```bash
   npm install
   npm run build
   ```
   For development: `npm run dev` (Vite).

7. **Run**  
   ```bash
   php artisan serve
   ```
   Open http://localhost:8000 – Register or log in. Use **Register** to create your own company and user; or log in with `admin@example.com` / `password` after seeding.

## API

- Base URL: `/api`
- Auth: `Authorization: Bearer {token}` and `X-Company-Id: {company_id}` for company-scoped endpoints.
- **Login:** `POST /api/login` with `email`, `password` → returns `token`, `user`, `companies`, `current_company_id`.
- **Register:** `POST /api/register` with `name`, `email`, `password`, `password_confirmation` → creates user + first company, returns token.
- **v1 (company-scoped):** `/api/v1/` – accounts, journal-entries, customers, vendors, invoices, bills, investments, items, tax-rates, bank-accounts, bank-transactions, reports (profit-loss, balance-sheet, trial-balance, gl-detail, ar-aging, ap-aging, cash-flow, tax-summary, customer/vendor-statement), dashboard/summary.
- **API documentation:** OpenAPI JSON at `/api/documentation`, interactive Swagger UI at `/api/docs`.

## Module layout

See `modules/README.md` and the sample `modules/Hive_ExpenseClaims/module.json`. Full module structure (Providers, Routes, Views, etc.) is described in the project’s technical specification.

## Payment reminders

Settings → Company → **Payment reminders**: set **Days after due** and **Days before due** (comma-separated). Mail driver required. Scheduler: `Schedule::job(new \App\Jobs\PaymentReminderJob)->daily();` in `routes/console.php`.

## Stripe

Set `STRIPE_SECRET` in `.env`. **Pay with Stripe** uses `POST /api/v1/invoices/{id}/payment-link`. Optional webhook: `STRIPE_WEBHOOK_SECRET` and endpoint `https://your-domain/api/stripe/webhook` (event `checkout.session.completed`). See `.env.example`.

## API versioning

See `docs/API_VERSIONING.md`.

## License

MIT.
