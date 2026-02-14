# Render: Use Docker (fix “composer: command not found”)

Render has **no native PHP**. Your current service was created as **Node**, so it never runs PHP/Composer. You must use **Docker**.

You have two options.

---

## Option A: Use the Blueprint (recommended)

The repo has a **render.yaml** that defines the Laravel API as a **Docker** web service.

1. **Delete the current Web Service** (the one that’s failing):
   - Render Dashboard → your service → **Settings** → scroll down → **Delete Web Service**.

2. **Create a Blueprint from the repo:**
   - Dashboard → **New +** → **Blueprint**.
   - Connect the same GitHub repo: **IsaacEdwards-Kawuma/hive-finance**.
   - Render will find **render.yaml** and create a service named **hive-finance-api** with **runtime: docker**.

3. **Set the secret env vars** (Render will prompt or you set them in the service):
   - **APP_KEY** — from `php artisan key:generate --show` (local).
   - **APP_URL** — `https://hive-finance-api.onrender.com` (or the URL Render gives after deploy).
   - **DB_URL** — your Neon PostgreSQL connection string.
   - **FRONTEND_URL** — your Vercel app URL.

4. **Deploy.** The build will use the **Dockerfile** (PHP + Composer); no more “composer: command not found”.

---

## Option B: New Web Service and choose Docker

1. **Delete the current Web Service** (Settings → Delete Web Service).

2. **Create a new Web Service:**
   - Dashboard → **New +** → **Web Service**.
   - Connect **IsaacEdwards-Kawuma/hive-finance**.

3. **When Render asks for settings, set:**
   - **Environment** / **Runtime**: choose **Docker** (not Node).
   - **Build Command:** leave **empty**.
   - **Start Command:** leave **empty** (Dockerfile CMD is used).

4. Add the same **Environment Variables** as in the table in NEXT_STEPS.md (APP_KEY, APP_URL, DB_CONNECTION, DB_URL, FRONTEND_URL, etc.).

5. **Create Web Service** and deploy.

---

After either option, run in the **Shell** (after first successful deploy):

```bash
php artisan migrate --force
php artisan db:seed --class=RolePresetsSeeder --force
```
