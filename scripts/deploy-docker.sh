#!/usr/bin/env bash
# Build and roll out the production stack. Run from the repo checkout (the GitHub runner does this).
# Never touches /home/deploy/pcbd/.env, the database volume or uploads. Backs up the database first when one exists.
set -euo pipefail
cd "$(dirname "$0")/.."
export PCBD_ENV_FILE="${PCBD_ENV_FILE:-/home/deploy/pcbd/.env}"
[ -f "$PCBD_ENV_FILE" ] || { echo "Missing $PCBD_ENV_FILE — create it from .env.production.example first"; exit 1; }
dc() { docker compose -p pcbd -f docker-compose.prod.yml --env-file "$PCBD_ENV_FILE" "$@"; }

echo "==> Backup (if database exists)"
if dc ps --status running db 2>/dev/null | grep -q db; then bash scripts/backup-docker.sh; else echo "no running db yet, skipping"; fi

echo "==> Build"
dc build --pull

echo "==> Database"
dc up -d --wait db
dc run --rm --no-deps app php artisan migrate --force
dc run --rm --no-deps app php artisan db:seed --class='Database\Seeders\RolesAndPermissionsSeeder' --force
dc run --rm --no-deps app php artisan db:seed --class='Database\Seeders\ReferenceDataSeeder' --force   # reference data only; NEVER DemoSeeder

echo "==> Roll out"
dc up -d --remove-orphans
dc exec -T app php artisan queue:restart || true

echo "==> Preflight"
dc exec -T app php artisan pcbd:preflight

# Old images pile up on every deploy; this removes only dangling (untagged, unused) images, never tagged or running ones.
echo "==> Tidy unused images"
docker image prune --force --filter "label!=keep" >/dev/null 2>&1 || true
