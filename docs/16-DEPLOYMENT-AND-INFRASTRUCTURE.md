# 16 — Deployment & Infrastructure

Hosting is not yet decided (open item R-05). Design target: **small, cheap, reliable**; works on a VPS (recommended) or capable shared/cPanel hosting with SSH + PostgreSQL support. If only MySQL hosting is available, escalate — the schema relies on PostgreSQL features (ADR-0004).

## 1. Environments
| Env | Purpose | Notes |
|---|---|---|
| local | Development | PHP 8.3, Composer, Node 22, PostgreSQL 16; `php artisan serve` + `npm run dev`; optional Laravel Sail/Docker **not required** |
| testing/CI | GitHub Actions | PostgreSQL service container |
| staging | Pre-release QA, demo data allowed | Basic auth + `noindex`; separate DB and storage |
| production | Live | Real data only; no demo seeder |

## 2. Recommended production stack
Ubuntu LTS VPS (2 vCPU / 4 GB to start), Nginx, PHP 8.3-FPM (OPcache), PostgreSQL 16, Supervisor (queue worker), cron (`* * * * * php artisan schedule:run`), Certbot (TLS), UFW firewall, fail2ban, unattended security upgrades. CDN/WAF (e.g. Cloudflare free tier) in front: DNS, TLS, caching of static assets, basic bot protection. Object storage (S3-compatible) optional for media later; local disk initially with backups.

## 3. Configuration
`.env` (not in git) with: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, DB creds, mail (SMTP/provider), `QUEUE_CONNECTION=database`, `CACHE_STORE=database|file`, `SESSION_DRIVER=database`, `FILESYSTEM_DISK`, logging. `.env.example` documents every key without secrets. Secrets in server env/GitHub Secrets only.

## 4. CI (GitHub Actions) — `.github/workflows/ci.yml` (Phase 1)
Jobs: setup PHP 8.3 + Node 22, Postgres service, `composer install`, `npm ci && npm run build`, copy `.env.ci`, `php artisan key:generate`, `migrate --force`, `vendor/bin/pint --test`, `php artisan test --parallel`, `composer audit`. Cache composer/npm. Branch protection on `main` (PR + green CI) when team grows.

## 5. Deployment procedure (manual-first, scripted later)
1. Merge to `main`, tag release (`v0.x.y`).
2. On server: `git pull` (or deploy artifact), `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build` (or upload built assets), `php artisan migrate --force`, `php artisan config:cache route:cache view:cache event:cache`, `php artisan queue:restart`, `php artisan storage:link` (first time).
3. Maintenance mode only for risky migrations (`php artisan down --retry=60`).
4. Smoke test: `/up`, home, listing, admin login, form submit on staging first.
5. Rollback: previous release dir symlink swap (zero-downtime layout: `releases/`, `shared/{.env,storage}`, `current`) + `migrate:rollback` only if the migration is reversible; destructive migrations require a backup first. A Deployer/Envoyer-style script is an optional Phase 9 task.
cPanel variant: document root → `public/`; cron + queue via cron `queue:work --stop-when-empty` every minute.

## 6. Backups & recovery
- DB: `pg_dump` (or spatie/laravel-backup) daily, encrypted, off-server (S3/Backblaze/Drive), keep 7 daily + 4 weekly + 6 monthly.
- Storage (`storage/app/public` + private): weekly full + daily incremental or object-storage versioning.
- `.env` and `APP_KEY` stored separately in a password manager.
- **Restore drill** before launch and quarterly: restore to staging, verify counts and app boot. RPO ≤ 24h, RTO ≤ 4h targets.

## 7. Monitoring & maintenance
Uptime check on `/up` (UptimeRobot/Better Stack free tier), error tracking (Sentry free tier or log alerts — decision pending), disk/CPU alerts, failed-job alert (scheduled check emails admin), `laravel.log` rotation, DB size/slow query review monthly, dependency updates monthly (Dependabot), PHP/OS security patches. Backup success notification daily.

## 8. Performance setup
OPcache on, `realpath_cache`, gzip/brotli, HTTP/2, far-future cache headers for hashed assets, image conversions queued, DB indexes per [10](10-DATABASE-ARCHITECTURE.md), `pg_stat_statements` for tuning (Phase 9). Redis only if measured need.

## 9. Domain, email & DNS
Domain/registrar TBD. SPF, DKIM, DMARC configured for transactional email; dedicated sending provider (e.g. SMTP provider with free tier) — decision pending (R-06). Support mailbox on domain.

## 10. Launch checklist (Phase 9)
Prod env hardened · migrations run · roles seeded · super admin created with 2FA · demo data absent · SSL/HSTS · robots/sitemap live · GSC/GA4 connected · backups verified · monitoring live · legal pages published after review · load/perf test · rollback plan · contact numbers/WhatsApp settings filled · cookie banner live.

## 11. Cost awareness
Initial monthly: VPS + domain + (optional) email provider + backup storage. Everything else uses free tiers. No paid maps API at launch (links to external maps; embed via OpenStreetMap/Leaflet later if needed).
