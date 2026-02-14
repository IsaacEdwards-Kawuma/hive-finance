# Next steps: deploy with Vercel, Render, Neon & Cloudflare

Your repo is ready. Follow this order.

---

## 1. Push to GitHub

1. Create a new repository on [GitHub](https://github.com/new) (e.g. `hive-finances`). Do **not** add a README or .gitignore (you already have them).
2. In this folder, add the remote and push:

   ```bash
   git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO.git
   git branch -M main
   git push -u origin main
   ```

3. *(Optional)* Set your Git identity for future commits:
   ```bash
   git config user.name "Your Name"
   git config user.email "your@email.com"
   ```

---

## 2. Neon (database)

1. Sign up at [neon.tech](https://neon.tech).
2. Create a project and copy the **connection string** (PostgreSQL URL).
3. Keep it for step 3 (Render env var `DB_URL`).

---

## 3. Render (Laravel API)

1. Sign up at [render.com](https://render.com).
2. **New → Web Service** → connect your GitHub repo.
3. Settings:
   - **Build command:** `composer install --no-dev --optimize-autoloader && php artisan key:generate --force`
   - **Start command:** `php artisan serve --host=0.0.0.0 --port=${PORT:-8000}`
4. **Environment** (use your real values):
   - `APP_ENV` = `production`
   - `APP_DEBUG` = `false`
   - `APP_KEY` = *(run `php artisan key:generate --show` locally)*
   - `APP_URL` = `https://YOUR-SERVICE-NAME.onrender.com` *(from Render after first deploy)*
   - `DB_CONNECTION` = `pgsql`
   - `DB_URL` = *(Neon connection string from step 2)*
   - `FRONTEND_URL` = *(leave empty for now; set in step 5 after Vercel deploy)*
   - `SESSION_DRIVER` = `database`
   - `SESSION_SECURE_COOKIE` = `true`
5. Deploy, then in **Shell** run: `php artisan migrate --force` and optionally `php artisan db:seed --class=RolePresetsSeeder --force`.
6. Copy your Render URL (e.g. `https://hive-finances-api.onrender.com`) for the next steps.

---

## 4. Vercel (Vue frontend)

1. Sign up at [vercel.com](https://vercel.com).
2. **Add New → Project** → import the same GitHub repo.
3. Vercel will use `vercel.json` (build: `npm run build:vercel`, output: `dist`). Confirm or set:
   - **Build command:** `npm run build:vercel`
   - **Output directory:** `dist`
4. **Environment variable:**
   - `VITE_API_URL` = *(Render URL from step 3, e.g. `https://hive-finances-api.onrender.com`)*
5. Deploy. Copy your Vercel URL (e.g. `https://hive-finances.vercel.app`).

---

## 5. Connect frontend and API

1. **Render** → your service → **Environment** → add or update:
   - `FRONTEND_URL` = *(your Vercel URL, e.g. `https://hive-finances.vercel.app`)*
2. Redeploy the Render service so CORS allows the frontend origin.

---

## 6. Cloudflare (optional)

1. Add your domain at [cloudflare.com](https://cloudflare.com) and point DNS to Cloudflare.
2. In Cloudflare DNS:
   - `app` (or `@`) → CNAME → Vercel’s target.
   - `api` → CNAME → `YOUR-SERVICE-NAME.onrender.com`.
3. In Vercel and Render, add the custom domains (`app.yourdomain.com`, `api.yourdomain.com`).
4. Update **Render** `APP_URL` and **Vercel** `VITE_API_URL` to use `https://api.yourdomain.com` and set **Render** `FRONTEND_URL` to `https://app.yourdomain.com`.

---

## Full guide

Detailed steps and troubleshooting: **[docs/DEPLOYMENT_VERCEL_RENDER_NEON_CLOUDFLARE.md](docs/DEPLOYMENT_VERCEL_RENDER_NEON_CLOUDFLARE.md)**.
