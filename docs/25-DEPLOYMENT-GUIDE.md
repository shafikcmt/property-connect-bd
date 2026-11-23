# 25 — Deployment Guide (Phase 7)

Practical, step-by-step production deployment for a VPS. Complements the strategy in [16](16-DEPLOYMENT-AND-INFRASTRUCTURE.md). **Nothing here has been executed against a real server** — no host, domain or credentials exist yet. Every irreversible production action (DNS changes, going live, database restore) needs the owner's explicit go-ahead.

## 0. Inputs needed from the owner
Hosting (VPS with SSH + PostgreSQL 16 — R-05), domain + DNS access, email/SMTP provider, business contact details (phone, WhatsApp, address) for Admin → Settings, brand logo/fonts, real listing content and photos, legal review of Privacy/Terms/Cookie/Disclaimer pages (R-03), GA4/Meta IDs if wanted.

## 1. Server baseline (Ubuntu LTS)
```bash
apt update && apt install -y nginx postgresql postgresql-contrib php8.3-fpm php8.3-cli php8.3-pgsql php8.3-gd php8.3-intl php8.3-mbstring php8.3-xml php8.3-zip php8.3-bcmath php8.3-curl php8.3-opcache composer git unzip certbot python3-certbot-nginx supervisor ufw fail2ban
# Node 22 for the asset build (or build locally/CI and upload public/build)
ufw allow OpenSSH && ufw allow 'Nginx Full' && ufw enable
```
Create a non-root deploy user; keep the app at `/var/www/pcbd`, owned by that user, with `www-data` able to write `storage/` and `bootstrap/cache/`.

## 2. PostgreSQL
```bash
sudo -u postgres psql -c "CREATE USER pcbd WITH PASSWORD '<strong-password>';" -c "CREATE DATABASE pcbd OWNER pcbd;"
sudo -u postgres psql -d pcbd -c "CREATE EXTENSION IF NOT EXISTS pg_trgm;"
```
Listen on localhost only. The first migration also attempts `CREATE EXTENSION pg_trgm`; creating it beforehand as superuser avoids permission issues.

## 3. Application
```bash
git clone <repo> /var/www/pcbd && cd /var/www/pcbd && git checkout main
cp .env.production.example .env && nano .env     # fill APP_URL, DB_PASSWORD, MAIL_*; then:
php artisan key:generate                          # back up APP_KEY securely
./scripts/deploy.sh
php artisan pcbd:create-super-admin               # prompts for a password; then enrol 2FA at /admin
```
`scripts/deploy.sh` installs dependencies without dev packages, builds assets, migrates, seeds **only** roles/permissions and reference data (never demo data), links storage, caches config/routes/views/events, restarts workers and runs `php artisan pcbd:preflight`. Preflight must show no FAIL before launch.

## 4. nginx (TLS terminated here)
```nginx
server {
    listen 80; server_name example.com www.example.com; return 301 https://www.example.com$request_uri;
}
server {
    listen 443 ssl http2; server_name www.example.com;
    root /var/www/pcbd/public; index index.php;
    client_max_body_size 20M;                         # property photo uploads
    gzip on; gzip_comp_level 5; gzip_min_length 1024; gzip_vary on;
    gzip_types text/css application/javascript application/json image/svg+xml application/xml text/xml;   # measured: desktop Lighthouse 70 → 94–98 on home
    add_header X-Content-Type-Options nosniff always;
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ { include snippets/fastcgi-php.conf; fastcgi_pass unix:/run/php/php8.3-fpm.sock; }
    location ~ /\.(?!well-known) { deny all; }
    location ~* \.(?:css|js|webp|png|jpg|svg|woff2)$ { expires 365d; add_header Cache-Control "public, immutable"; access_log off; }
}
```
SSL: `certbot --nginx -d example.com -d www.example.com` (auto-renews; test with `certbot renew --dry-run`). Enable HTTP/2, set `upload_max_filesize=20M` / `post_max_size=25M` in `php.ini`, enable OPcache.

## 5. Scheduler and queue
```cron
* * * * * cd /var/www/pcbd && php artisan schedule:run >> /dev/null 2>&1
```
Supervisor program for the queue worker (needed once notifications/email are used):
```ini
[program:pcbd-queue]
command=php /var/www/pcbd/artisan queue:work --sleep=3 --tries=3 --max-time=3600
user=www-data
autostart=true
autorestart=true
stopwaitsecs=3600
```
Shared/cPanel hosting without Supervisor: cron `* * * * * php artisan queue:work --stop-when-empty`.

## 6. Storage and images
Public images live in `storage/app/public` (served via `public/storage`); private documents in `storage/app/private` (never web-accessible). Image derivatives are WebP at 480/960/1600 px. To use a CDN/object store later, set `FILESYSTEM_PUBLIC_URL` and move the public disk to S3-compatible storage (config change only).

## 7. Backups and restore
```cron
15 2 * * * cd /var/www/pcbd && ./scripts/backup-db.sh    # nightly DB dump, weekly storage archive on Sundays, 30-day retention
```
Copy `storage/backups/` **off the server** (rclone to object storage, or another host). Restore drill (do this on staging before launch, then quarterly):
```bash
createdb pcbd_restore && pg_restore -d pcbd_restore --no-owner db-YYYYMMDD-HHMMSS.dump
```
Keep `.env` and `APP_KEY` in a password manager, separate from the backups.

## 8. Monitoring and logging
`LOG_CHANNEL=daily` (14 days kept by default); review `storage/logs`. Add an uptime check on `https://www.example.com/up` (Laravel health route). Optional error tracking (Sentry/Flare) is a backlog decision. Failed jobs are pruned weekly by the scheduler.

## 9. SEO and analytics configuration
- `robots.txt` and `sitemap.xml` are generated by the app (robots blocks everything outside `APP_ENV=production`). Submit the sitemap in Google Search Console and Bing Webmaster Tools.
- Fill Admin → Settings: contact numbers, social links, default meta description, GA4/Meta Pixel IDs. **Analytics scripts are not injected yet** — a consent banner is built in Phase 10 (ADR-0015); until then do not add tags manually.
- Add a Google Business Profile with consistent name/address/phone.

## 10. Security checks before launch
`php artisan pcbd:preflight` clean · `composer audit` and `npm audit` reviewed · default/demo accounts absent · demo data absent (preflight checks) · admin 2FA enrolled · HTTPS + HSTS working · `APP_DEBUG=false` · firewall on, only 22/80/443 open · DB not reachable from the internet · backups restored once · legal pages reviewed and the "draft for legal review" notice confirmed absent in production · CSP (report-only) is a known follow-up (backlog).

## 11. Release procedure and rollback
Routine release: take a DB backup → `git pull` on `main` → `./scripts/deploy.sh` → smoke test (`/up`, home, a property page, an inquiry on staging, `/admin` login).
Rollback: `git checkout <previous-tag>` → `composer install --no-dev` → `npm ci && npm run build` → `php artisan config:cache route:cache view:cache` → `php artisan queue:restart`. If the release included a migration, roll it back with `php artisan migrate:rollback --step=N` **only if its `down()` is non-destructive**; otherwise restore the pre-release backup. Maintenance window: `php artisan down --retry=60` / `php artisan up`.

## 12. Launch checklist
1. Real content loaded via Admin (developers → projects → properties with photos), each verified only after an actual check. 2. Contact settings filled. 3. Legal pages reviewed. 4. DNS pointed (owner action). 5. TLS valid. 6. Preflight green. 7. Backup + restore tested. 8. Search Console sitemap submitted. 9. Inquiry → admin Inquiries inbox → email/WhatsApp follow-up process agreed. 10. Monitoring live.
