# Business Layer & Architecture

This document describes the **target** business-layer architecture (what runs where) and how it relates to the current Hive Finances stack.

---

## Target architecture (cloud stack)

| What you run | Where it runs |
|--------------|---------------|
| **Employee dashboard (web app)** — React / Next.js UI | **Vercel** |
| **Business logic** — sales, payroll, inventory, invoices | **Node.js / Python API** → **Render** |
| **Company data** — clients, staff, money, products | **PostgreSQL** → **Neon** |
| **Login, roles, permissions** — Auth + RBAC | **Supabase** |
| **Security, HTTPS, protection** — CDN + firewall | **Cloudflare** |

### Flow

1. **Cloudflare** — Traffic hits Cloudflare first (HTTPS, DDoS protection, optional WAF).
2. **Vercel** — Serves the React/Next.js employee dashboard (and any other frontends).
3. **Render** — Hosts the Node.js or Python API that implements business logic (sales, payroll, inventory, invoices).
4. **Neon** — Managed PostgreSQL for company data (clients, staff, money, products).
5. **Supabase** — Handles authentication, roles, and permissions (can also provide Postgres; you can use Neon for app data and Supabase for auth, or consolidate).

---

## Current stack (Hive Finances today)

| What you run | Where it runs |
|--------------|---------------|
| **All dashboards (web app)** — Vue.js SPA | Same server as API (or static build on any host) |
| **Business logic + API** — Laravel (PHP) | Single Laravel app (VPS, shared hosting, Render, etc.) |
| **Company data** — MySQL/PostgreSQL/SQLite | Same server or managed DB (e.g. PlanetScale, Neon) |
| **Login, roles, permissions** | Laravel Sanctum + custom RBAC (Permissions, roles) |
| **Security, HTTPS** | Web server + optional Cloudflare in front |

So today: **one Laravel app** (PHP + Vue) can be deployed to a single server or PaaS; no React/Next, no separate Node/Python API, no Supabase.

---

## Choosing a path

### Option A — Stay on current stack (Laravel + Vue)

- Deploy the existing Laravel app to **one** host (VPS, Render, Forge, etc.).
- Put **Cloudflare** in front for HTTPS, CDN, and protection.
- Optionally use **Neon** (or any Postgres) as `DB_CONNECTION=pgsql` and point Laravel to Neon’s connection string.
- No need for Vercel, Supabase, or a separate Node/Python API unless you want them later.

See **DEPLOYMENT.md** for steps.

### Option B — Move toward the target stack (Vercel + Render + Neon + Supabase + Cloudflare)

1. **Frontend** — Rebuild the UI in **React/Next.js** and deploy to **Vercel**.
2. **API** — Rewrite business logic in **Node.js** or **Python** and deploy to **Render**; or keep Laravel and run it on Render as a PHP service.
3. **Database** — Use **Neon** (PostgreSQL) for company data; migrate from current DB and point the API to Neon.
4. **Auth** — Replace Laravel Sanctum with **Supabase Auth**; map existing roles/permissions into Supabase (e.g. custom claims or a `roles`/`permissions` table in Neon used by the API).
5. **Security** — Put **Cloudflare** in front of Vercel and the API (and optionally Neon/Supabase as needed).

This is a larger migration: new frontend framework, possibly new API language, new auth provider, and same or new DB.

---

## Quick reference: target services

- **Vercel** — Host React/Next.js frontend: connect repo, set build command, add env vars (e.g. `NEXT_PUBLIC_API_URL`).
- **Render** — Host Node.js or Python API (or PHP/Laravel): connect repo, set start command, add env (e.g. `DATABASE_URL` for Neon).
- **Neon** — Create Postgres, copy connection string, use in API and (if needed) in Supabase.
- **Supabase** — Create project, enable Auth, configure redirect URLs; in app use Supabase client for login/session and enforce RBAC in API or Supabase RLS.
- **Cloudflare** — Add site (your domain), proxy traffic (orange cloud), optionally enable WAF and security rules.

---

## Summary

- **Target architecture**: Employee dashboard (React/Next) on Vercel, business logic (Node/Python) on Render, data in Neon, auth/RBAC in Supabase, security via Cloudflare.
- **Current app**: Laravel + Vue, one deployable app, Sanctum + custom RBAC.

**This project is set up to use Vercel, Render, Neon, and Cloudflare** while keeping Laravel + Vue: frontend deploys to Vercel (standalone Vue build), API to Render, database on Neon, and Cloudflare in front for DNS/HTTPS. See **[DEPLOYMENT_VERCEL_RENDER_NEON_CLOUDFLARE.md](DEPLOYMENT_VERCEL_RENDER_NEON_CLOUDFLARE.md)** for step-by-step deployment.

You can **keep the current stack** and still use Cloudflare and Neon; or **migrate** stepwise toward the full target stack (e.g. Supabase for auth) when you’re ready.
