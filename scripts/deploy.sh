#!/usr/bin/env bash
# Deploy the current checkout. Run on the server from the project root. Safe to re-run.
# It never touches .env or storage/. Take a database backup first (scripts/backup-db.sh).
set -euo pipefail
cd "$(dirname "$0")/.."

echo "==> Checking environment"
[ -f .env ] || { echo ".env missing — copy .env.production.example and fill it in"; exit 1; }

echo "==> Installing PHP dependencies (no dev)"
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

echo "==> Building assets"
npm ci --no-audit --no-fund && npm run build

echo "==> Migrating database"
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\RolesAndPermissionsSeeder --force
php artisan db:seed --class=Database\\Seeders\\ReferenceDataSeeder --force   # idempotent reference data only; NEVER DemoSeeder

echo "==> Linking storage and warming caches"
php artisan storage:link || true
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache

echo "==> Restarting workers"
php artisan queue:restart || true

echo "==> Preflight"
php artisan pcbd:preflight
