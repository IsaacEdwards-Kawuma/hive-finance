# Set up Neon (PostgreSQL) for Hive Finances

Use Neon as the database for your Laravel API on Render. Follow these steps in order.

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

1. Open your **Render** dashboard → your **Laravel API service** (Web Service).
2. Go to **Environment** (or **Environment Variables**).
3. Add or update:

   | Key            | Value |
   |----------------|--------|
   | `DB_CONNECTION` | `pgsql` |
   | `DB_URL`        | *(paste the full Neon connection string)* |

   If Render already provides **DATABASE_URL** (e.g. for a Render Postgres add-on), you can set **DB_URL** to that same value. The app reads **DB_URL** first, then **DATABASE_URL**.

4. **Save** the environment. Render will redeploy the service so the app uses Neon.

---

## 4. Run migrations (and optional seed)

After the first deploy that uses Neon:

1. On Render, open your service → **Shell** (or use a one-off job if you use one).
2. Run:
   ```bash
   php artisan migrate --force
   ```
3. (Optional) Seed role presets:
   ```bash
   php artisan db:seed --class=RolePresetsSeeder --force
   ```

Your Laravel app will then be using Neon for all data (users, companies, invoices, etc.).

---

## 5. Quick checklist

- [ ] Neon project created at [neon.tech](https://neon.tech)
- [ ] Connection string copied
- [ ] **Render** → Environment: `DB_CONNECTION=pgsql`, `DB_URL=<Neon URI>`
- [ ] Service redeployed
- [ ] Shell: `php artisan migrate --force`
- [ ] (Optional) Shell: `php artisan db:seed --class=RolePresetsSeeder --force`

---

## Troubleshooting

- **“Connection refused” or “could not connect”:**  
  - Check the Neon URI is correct and has `?sslmode=require` (or `sslmode=require` in the query string).  
  - In Neon, ensure the project isn’t suspended (free tier may spin down after inactivity).

- **“relation does not exist”:**  
  Run migrations: `php artisan migrate --force` in the Render Shell.

- **Laravel still using SQLite:**  
  Ensure **DB_CONNECTION** is set to **pgsql** and **DB_URL** (or **DATABASE_URL**) is set on Render. Redeploy after changing env vars.
