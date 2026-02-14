# Deploying with Vercel, Render, Neon, and Cloudflare

This guide walks you through running **Hive Finances** with:

- **Vercel** — Vue.js frontend (employee dashboard and all app UI)
- **Render** — Laravel API (business logic, auth, data)
- **Neon** — PostgreSQL (company data)
- **Cloudflare** — DNS, HTTPS, and optional CDN/firewall in front of your domain

You keep your current **Laravel + Vue** stack; only the hosting is split.

---

## 1. Neon (PostgreSQL)

1. Create an account at [neon.tech](https://neon.tech) and a new project.
2. Create a database (or use the default).
3. Copy the **connection string** (e.g. `postgresql://user:pass@ep-xxx.region.aws.neon.tech/neondb?sslmode=require`).

You’ll use this as `DB_URL` on Render.

---

## 2. Render (Laravel API)

1. Create an account at [render.com](https://render.com).
2. **New → Web Service**.
3. Connect your Git repo (GitHub/GitLab). Choose the repo that contains this Laravel app.
4. Configure the service:
   - **Root Directory:** leave empty (or the folder where the Laravel app lives if it’s a monorepo).
   - **Runtime:** `PHP` (or use a **Docker** build if you prefer).
   - **Build Command:**  
     `composer install --no-dev --optimize-autoloader && php artisan key:generate --force`
   - **Start Command:**  
     `php artisan serve --host=0.0.0.0 --port=${PORT:-8000}`
   - **Plan:** Free or paid.

5. **Environment variables** (Render dashboard → Environment):

   | Variable | Value |
   |----------|--------|
   | `APP_ENV` | `production` |
   | `APP_DEBUG` | `false` |
   | `APP_KEY` | Run `php artisan key:generate --show` locally and paste the key |
   | `APP_URL` | `https://YOUR-RENDER-SERVICE.onrender.com` (your Render URL, no trailing slash) |
   | `DB_CONNECTION` | `pgsql` |
   | `DB_URL` or `DATABASE_URL` | Neon connection string from step 1 |
   | `FRONTEND_URL` | Your Vercel app URL, e.g. `https://your-app.vercel.app` (or custom domain later; comma-separated if you have several) |
   | `SESSION_DRIVER` | `database` |
   | `SESSION_SECURE_COOKIE` | `true` (Render uses HTTPS) |

   If Render auto-provides `DATABASE_URL`, you can set `DB_URL` to the same value, or use Laravel’s `config/database.php` to read `DATABASE_URL` if you add it there.

6. After the first deploy, run migrations (Render **Shell** or one-off job):  
   `php artisan migrate --force`

7. (Optional) Seed roles:  
   `php artisan db:seed --class=RolePresetsSeeder --force`

8. Note your API base URL, e.g. `https://YOUR-RENDER-SERVICE.onrender.com` (no `/api/v1`). You’ll use this as `VITE_API_URL` on Vercel.

---

## 3. Vercel (Vue frontend)

1. Create an account at [vercel.com](https://vercel.com).
2. **Add New → Project** and import the same Git repo.
3. Configure the project:
   - **Root Directory:** same as the Laravel app root (where `package.json` and `vercel.json` are).
   - **Framework Preset:** Other (or leave default; we use a custom build).
   - **Build Command:** `npm run build:vercel` (already set in `vercel.json`).
   - **Output Directory:** `dist` (already set in `vercel.json`).
   - **Install Command:** `npm install`

4. **Environment variables** (Vercel → Settings → Environment Variables):

   | Variable | Value |
   |----------|--------|
   | `VITE_API_URL` | Your Render API URL, e.g. `https://YOUR-RENDER-SERVICE.onrender.com` (no trailing slash) |

   Add it for **Production** (and Preview if you want previews to hit the same API).

5. Deploy. Vercel will run `npm run build:vercel`, which builds the Vue app and outputs to `dist`. The app will call the Render API using `VITE_API_URL`.

6. Optional: add a **custom domain** in Vercel (e.g. `app.yourdomain.com`). After that, set `FRONTEND_URL` on Render to that domain (and optionally keep the `.vercel.app` URL in a comma-separated list).

---

## 4. Cloudflare (DNS and optional proxy)

1. Create an account at [cloudflare.com](https://cloudflare.com) and add your domain.
2. Update your domain’s nameservers at the registrar to Cloudflare’s.
3. **DNS** (Cloudflare dashboard):
   - **Frontend:** e.g. `app` (or `@` for root) → CNAME → `cname.vercel-dns.com` (or the target Vercel gives you).
   - **API:** e.g. `api` → CNAME → `YOUR-RENDER-SERVICE.onrender.com` (or the Render hostname).

4. **Optional:** In Cloudflare, turn the proxy **on** (orange cloud) for the frontend and/or API to get HTTPS, caching, and DDoS protection. Ensure “SSL/TLS” is set to “Full” or “Full (strict)” when proxying.

5. Then set:
   - **Vercel:** Custom domain `app.yourdomain.com` (or whatever you chose).
   - **Render:** Custom domain `api.yourdomain.com` (if supported by your plan).
   - **Render `APP_URL`:** `https://api.yourdomain.com`
   - **Render `FRONTEND_URL`:** `https://app.yourdomain.com`
   - **Vercel `VITE_API_URL`:** `https://api.yourdomain.com`

---

## 5. Summary checklist

| Where | What to set |
|-------|-------------|
| **Neon** | Create DB, copy connection string |
| **Render** | `APP_URL`, `DB_CONNECTION`, `DB_URL` (Neon), `FRONTEND_URL` (Vercel URL), run `migrate` (and optional seed) |
| **Vercel** | `VITE_API_URL` = Render URL (or `https://api.yourdomain.com` if using Cloudflare) |
| **Cloudflare** | DNS for `app` and `api`; optional proxy; then point `FRONTEND_URL` and `VITE_API_URL` to those domains |

---

## 6. Local development with the same setup

- **Frontend:** `npm run dev` (Vite). In `.env` (or `.env.local`) at project root, you can add `VITE_API_URL=http://localhost:8000` to hit a local Laravel API.
- **API:** Run Laravel locally (`php artisan serve`) and use Neon or a local PostgreSQL/MySQL for DB. Set `FRONTEND_URL=http://localhost:5173` (or your Vite dev URL) in `.env` so CORS allows the dev server.

---

## 7. Troubleshooting

- **CORS errors:** Ensure `FRONTEND_URL` on Render exactly matches the origin the browser uses (scheme + host + port, no trailing slash). For multiple origins, use a comma-separated list in `FRONTEND_URL` (see `config/cors.php`).
- **401 / auth:** The app uses Bearer tokens in `localStorage`. Ensure `VITE_API_URL` on Vercel points to the same backend users log in against (Render URL or API subdomain).
- **Render free tier:** The service may spin down after inactivity; the first request can be slow. Consider a paid plan or a cron ping if you need always-on.
- **Migrations:** Run them after the first deploy (Render Shell or one-off job). For future deploys, you can add `php artisan migrate --force` to the build command if you want migrations on every deploy (weigh consistency vs. safety).

You now have the app running on **Vercel + Render + Neon**, with **Cloudflare** in front for DNS and optional security.
