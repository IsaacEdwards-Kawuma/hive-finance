# Render: Node (frontend) + Docker (API)

The repo **render.yaml** defines **two** services:

| Service            | Runtime | Role                          |
|--------------------|---------|-------------------------------|
| **hive-finance**   | Node    | Vue frontend (built with Vite, served with `serve`) |
| **hive-finance-api** | Docker | Laravel API (PHP; Render has no native PHP)          |

---

## Why Node + Docker?

- **Frontend:** Runs as a **Node** web service (no Docker). Render builds with `npm run build:render` and serves the static app with `serve`.
- **API:** Stays **Docker** because Render does not support PHP natively. The Dockerfile installs PHP, Composer, and runs Laravel.

---

## Option A: Use the Blueprint (recommended)

1. **Render Dashboard** → **New +** → **Blueprint**.
2. Connect the repo **IsaacEdwards-Kawuma/hive-finance**.
3. Render will create:
   - **hive-finance** (Node) — frontend
   - **hive-finance-api** (Docker) — API
4. Set **secret / sync: false** env vars in the dashboard:

   **Frontend (hive-finance):**
   - **VITE_API_URL** — API base URL, e.g. `https://hive-finance-api.onrender.com` (no trailing slash). Set this **before** the first deploy so the build bakes it in.

   **API (hive-finance-api):**
   - **APP_KEY** — from `php artisan key:generate --show`
   - **APP_URL** — `https://hive-finance-api.onrender.com` (your API service URL)
   - **DB_URL** — Neon PostgreSQL connection string (raw URI only)
   - **FRONTEND_URL** — frontend URL for CORS, e.g. `https://hive-finance.onrender.com` (your Node service URL)

5. **Deploy.** Frontend and API will build and run. After the API is up, run migrations in the API Shell if they don’t run automatically:
   ```bash
   php artisan migrate --force
   php artisan db:seed --class=RolePresetsSeeder --force
   ```

---

## Option B: One service (API only, frontend on Vercel)

If you keep the frontend on **Vercel** and only want the API on Render:

1. Create a **single** Web Service from the repo.
2. Choose **Docker** as runtime; use the repo’s **Dockerfile**.
3. Set env vars as in NEON_SETUP.md (APP_KEY, APP_URL, DB_URL, FRONTEND_URL, etc.).
4. Set **FRONTEND_URL** to your Vercel URL (e.g. `https://your-app.vercel.app`).

You can still use **render.yaml** but delete or ignore the Node service in the Blueprint, or use a copy of render.yaml with only the Docker service.

---

## After deploy

- **Frontend:** `https://hive-finance.onrender.com` (or the URL Render shows for the Node service).
- **API:** `https://hive-finance-api.onrender.com`. The frontend calls it using **VITE_API_URL** (baked in at build time).
- **CORS:** API’s **FRONTEND_URL** must match the frontend origin exactly (no trailing slash).
