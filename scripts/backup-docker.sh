#!/usr/bin/env bash
# Nightly PostgreSQL dump (+ weekly uploads archive) into /srv/pcbd/backups.
# Cron (as the deploy user): 30 2 * * * bash /srv/pcbd/app/scripts/backup-docker.sh
# Copy these files OFF the server too — a backup on the same disk is not a backup.
set -euo pipefail
OUT="${BACKUP_DIR:-/srv/pcbd/backups}"
STAMP=$(date +%Y%m%d-%H%M%S)
mkdir -p "$OUT"
DB=$(docker ps --filter label=com.docker.compose.project=pcbd --filter label=com.docker.compose.service=db --format '{{.Names}}' | head -1)
[ -n "$DB" ] || { echo "db container not running"; exit 1; }
docker exec "$DB" sh -c 'pg_dump -U "$POSTGRES_USER" -Fc "$POSTGRES_DB"' > "$OUT/db-$STAMP.dump"
if [ "$(date +%u)" = "7" ]; then
  docker run --rm -v pcbd_public_storage:/src:ro -v "$OUT":/out alpine tar -czf "/out/uploads-$STAMP.tar.gz" -C /src . || true
fi
echo "Backup written to $OUT/db-$STAMP.dump (retention: prune files older than 30 days manually or via your off-server job)"
