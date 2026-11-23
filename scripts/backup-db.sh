#!/usr/bin/env bash
# Compressed PostgreSQL dump + weekly storage archive. Reads DB_* from .env. Schedule via cron (see docs/25).
# Copy the resulting files OFF the server (object storage / another machine) — a backup on the same disk is not a backup.
set -euo pipefail
cd "$(dirname "$0")/.."
set -a; . ./.env; set +a
OUT="${BACKUP_DIR:-./storage/backups}"; mkdir -p "$OUT"; STAMP=$(date +%Y%m%d-%H%M%S)
PGPASSWORD="$DB_PASSWORD" pg_dump -h "${DB_HOST:-127.0.0.1}" -p "${DB_PORT:-5432}" -U "$DB_USERNAME" -Fc "$DB_DATABASE" > "$OUT/db-$STAMP.dump"
if [ "$(date +%u)" = "7" ]; then tar -czf "$OUT/storage-$STAMP.tar.gz" -C storage/app public private 2>/dev/null || true; fi
find "$OUT" -type f -mtime +30 -delete
echo "Backup written to $OUT/db-$STAMP.dump"
