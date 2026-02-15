# Deploy frontend to Vercel

## 1. Open Vercel

- Go to **[vercel.com](https://vercel.com)** and sign in (or sign up with GitHub).

## 2. Import your repo

- Click **Add New… → Project**.
- Under **Import Git Repository**, select **IsaacEdwards-Kawuma/hive-finance** (or connect GitHub if it’s not listed).
- Click **Import**.

## 3. Configure the project

Vercel will read `vercel.json` from the repo. Confirm or set:

| Field | Value |
|--------|--------|
| **Framework Preset** | Other (or leave as detected) |
| **Root Directory** | *(leave blank – repo root)* |
| **Build Command** | `npm run build:vercel` |
| **Output Directory** | `dist` |
| **Install Command** | `npm install` |

## 4. Add environment variable

Before deploying, open **Environment Variables** and add:

| Name | Value |
|------|--------|
| `VITE_API_URL` | Your **Render** API URL, e.g. `https://your-service-name.onrender.com` *(no trailing slash)* |

- If you **don’t have Render yet**, leave it empty for now. The app will try to use the same origin; after you deploy the API on Render, add this variable and redeploy on Vercel.

## 5. Deploy

- Click **Deploy**.
- Wait for the build to finish. Your app will be at a URL like `https://hive-finance-xxx.vercel.app`.

## 6. Connect to your API (Render)

- After your **Laravel API is live on Render**, set **Render → Environment**:
  - `FRONTEND_URL` = your Vercel URL (e.g. `https://hive-finance-xxx.vercel.app`).
- In **Vercel → Settings → Environment Variables**, set:
  - `VITE_API_URL` = your Render URL (e.g. `https://your-api.onrender.com`).
- **Redeploy** the Vercel project (Deployments → ⋮ → Redeploy) so the new `VITE_API_URL` is used.

Done. The Vue app on Vercel will then talk to your Laravel API on Render.
