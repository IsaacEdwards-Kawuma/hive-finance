# Deploying HiveStack IntelliCash for Access from Anywhere

This app is a web-based system. Once deployed on a server with a **public URL**, your team members can access it from **anywhere** (home, office, or on the go) using a browser.

## How “access from anywhere” works

- The app is a **single Laravel + Vue SPA**: the same server serves both the web interface and the API.
- Authentication uses **tokens** (Laravel Sanctum) stored in the browser, so each user can log in from any device or location.
- There are **no hardcoded localhost URLs** in the frontend; the API uses relative paths (`/api/v1`), so it works on any domain you deploy to.

## Steps to make it accessible to your team

### 1. Deploy to a server with a public URL

Deploy this Laravel project to a host that gives you a public URL, for example:

- **Shared hosting** (e.g. cPanel) with PHP and MySQL
- **VPS** (DigitalOcean, Linode, Vultr, etc.) and run Laravel there
- **PaaS** (Laravel Forge, Ployer, Railway, Render, etc.)
- **Your own server** with a domain or static IP

You need:

- PHP 8.1+
- Composer
- Node.js & npm (for building the frontend)
- MySQL/PostgreSQL or SQLite
- Web server (Apache/Nginx) pointing to the app’s `public` folder

### 2. Set production environment variables

On the server, copy `.env.example` to `.env`, run `php artisan key:generate`, then set at least:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Use the full URL where the app is accessed (no trailing slash).
# This is the URL you will share with your team.

# Session: keep database (or redis) so multiple users/devices work correctly
SESSION_DRIVER=database

# If you use a custom domain, add it so Sanctum trusts it (optional; APP_URL is often enough)
# SANCTUM_STATEFUL_DOMAINS=your-domain.com,www.your-domain.com

# For HTTPS (recommended), enable secure session cookie
SESSION_SECURE_COOKIE=true
```

Replace `https://your-domain.com` with the actual URL team members will use (e.g. `https://finance.yourcompany.com`). If you serve the app in a subpath (e.g. `https://company.com/app/`), configure your web server to point that path to the `public` directory and use that full URL as `APP_URL`.

### 3. Build the frontend and run migrations

On the server (or in your deploy pipeline):

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

Ensure the web server document root is the `public` directory.

### 4. Share the URL with your team

- Give your team the **APP_URL** (e.g. `https://your-domain.com`).
- Each user opens that URL in a browser, logs in with their account, and can use the app from anywhere.
- Roles (Admin, CEO, Finance, Operations, Secretary, Employee) and permissions apply the same for remote users.

## Security recommendations for remote access

1. **Use HTTPS** – Set up SSL (e.g. Let’s Encrypt) so traffic is encrypted.
2. **Strong passwords** – Rely on good passwords and consider 2FA if you enable it in the app.
3. **Keep software updated** – Regularly update Laravel, PHP, and dependencies.
4. **Restrict admin** – Only grant Admin/IT and other sensitive roles to trusted people.

## Optional: run locally and expose with a tunnel

If you are not ready to deploy to a real server, you can still let others access your **local** instance temporarily:

- Run the app locally: `php artisan serve` and `npm run dev` (or use a local web server).
- Use a tunnel service (e.g. **ngrok**, **Cloudflare Tunnel**, **laravel.valet share**) to expose your machine with a public URL.
- Share that URL with your team. When you stop the tunnel or your PC, the app will no longer be reachable until you deploy to a permanent server.

## Summary

- The app is **already built to be used from anywhere**: no code change is required for “remote” access.
- To make it accessible to your team: **deploy it to a server**, set **APP_URL** (and optionally **SANCTUM_STATEFUL_DOMAINS** and **SESSION_SECURE_COOKIE**), build the frontend, run migrations, and share the URL. Once that is done, people can use the software from any location or device with a browser.
