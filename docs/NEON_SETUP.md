# Set up Neon (PostgreSQL) for Hive Finances

Use **Neon for everything**: app data, sessions, cache, and queues all run on one Neon PostgreSQL database. Follow these steps in order.

---

## What runs on Neon

With the env vars below, Laravel uses the same Neon connection for:

| Laravel feature | Env var | Uses Neon |
|-----------------|---------|-----------|
| Default database (users, companies, invoices, etc.) | `DB_CONNECTION=pgsql`, `DB_URL=<Neon URI>` | ✓ |
| Sessions | `SESSION_DRIVER=database` | ✓ |
| Cache | `CACHE_STORE=database` | ✓ |
| Queues (jobs) | `QUEUE_CONNECTION=database` | ✓ |

One **DB_URL** (your Neon connection string) is all you need; the rest are set in `render.yaml` or below.

---

## 1. Create a Neon account and project

1. Go to **[neon.tech](https://neon.tech)** and sign up (or sign in with GitHub).
2. Click **New Project**.
3. Choose:
   - **Project name:** e.g. `hive-finance`
   - **Region:** pick one close to your Render region (e.g. US East).
   - **PostgreSQL version:** 15 or 16 (default is fine).
4. Click **Create project**.

---

## 2. Get the connection string

1. On the project **Dashboard**, find the **Connection string** section.
2. Select the **Connection string** tab (not “Pooled” unless you want to use pooling later).
3. Copy the URI. It looks like:
   ```text
   postgresql://USER:PASSWORD@ep-XXXX-XXXX.region.aws.neon.tech/neondb?sslmode=require
   ```
4. Keep this secret. You’ll use it as **DB_URL** (or **DATABASE_URL**) on Render.

**Optional:** If you prefer separate variables, use the **Host**, **Database**, **User**, and **Password** from the Neon dashboard and set **DB_HOST**, **DB_DATABASE**, **DB_USERNAME**, **DB_PASSWORD** on Render instead of **DB_URL**. Using the single URL is simpler.

---

## 3. Use it on Render

### Exactly what to put and where

- **Where:** [dashboard.render.com](https://dashboard.render.com) → your Web Service (Laravel API) → **Environment** (left sidebar) → **Environment Variables**.
- **What to set:**

| Key | Value |
|-----|--------|
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | The Neon URI only (see below) |
| `FRONTEND_URL` | Your Vercel app URL, e.g. `https://hive-finance.vercel.app` (no trailing slash). Needed for CORS so the frontend can call the API. Leave empty to allow any origin. |
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `database` |

- **`DB_URL` value:** In Neon go to your project → **Connection string**. Neon often shows:
  ```text
  psql 'postgresql://neondb_owner:...@ep-....neon.tech/neondb?sslmode=require&channel_binding=require'
  ```
  You can paste that **entire line** into Render’s `DB_URL` value; the app strips the `psql '` and trailing `'` and uses the URI. Or paste only the part inside the quotes (starting with `postgresql://`).
- **Do not set** (delete if they exist): `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
- **CORS (login/register blocked):** If your Vercel app cannot call the API, set `FRONTEND_URL` on Render to your Vercel URL exactly, e.g. `https://hive-finance.vercel.app` (no trailing slash). Redeploy after changing env.
- Click **Save Changes** so Render redeploys.

---

1. Open your **Render** dashboard → your **Laravel API service** (Web Service).
2. Go to **Environment** (or **Environment Variables**).
3. Add or update (everything using Neon):

   | Key | Value |
   |-----|--------|
   | `DB_CONNECTION` | `pgsql` |
   | `DB_URL` | *(paste the full Neon connection string)* |
   | `SESSION_DRIVER` | `database` |
   | `CACHE_STORE` | `database` |
   | `QUEUE_CONNECTION` | `database` |

   If you use the repo’s **render.yaml** Blueprint, `SESSION_DRIVER`, `CACHE_STORE`, and `QUEUE_CONNECTION` are already set; you only need to set **DB_URL** (and other sync: false vars). The app reads **DB_URL** first, or **DATABASE_URL** if **DB_URL** is not set.

4. **Save** the environment. Render will redeploy the service so the app uses Neon.

---

## 4. Migrations and seed (automatic)

The **Dockerfile** runs migrations and the role seeder when the container starts, so you don’t need to use the Render Shell:

- Every deploy/restart: `php artisan migrate --force` then `php artisan db:seed --class=RolePresetsSeeder --force`, then the server starts.
- Tables are created in Neon on first run; roles are created for existing companies (safe to run repeatedly).

Your Laravel app then uses Neon for all data (users, companies, invoices, etc.).

---

## 5. Quick checklist

- [ ] Neon project created at [neon.tech](https://neon.tech)
- [ ] Connection string copied
- [ ] **Render** → Environment: `DB_CONNECTION=pgsql`, `DB_URL=<Neon URI>`, and (if not in Blueprint) `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`
- [ ] Service redeployed (migrations and seed run automatically on startup)

---

## Troubleshooting

- **“Connection refused” or “could not connect”:**  
  - Check the Neon URI is correct and has `?sslmode=require` (or `sslmode=require` in the query string).  
  - In Neon, ensure the project isn’t suspended (free tier may spin down after inactivity).

- **“relation does not exist”:**  
  Migrations run automatically when the container starts. If you still see this, trigger a redeploy or run `php artisan migrate --force` in the Render Shell.

- **Laravel still using SQLite:**  
  Ensure **DB_CONNECTION** is set to **pgsql** and **DB_URL** (or **DATABASE_URL**) is set on Render. Redeploy after changing env vars.

- **Error "missing = in connection info string" or "Database: sql 'postgresql://...'":** Laravel is using your full Neon URL as the database name. On Render Environment, set **only** **DB_CONNECTION** = `pgsql` and **DB_URL** = your full Neon connection string. Do **not** put the URL in **DB_DATABASE**. Remove **DB_HOST**, **DB_PORT**, **DB_DATABASE**, **DB_USERNAME**, **DB_PASSWORD** if they are set. Save and redeploy.
