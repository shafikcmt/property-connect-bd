#!/usr/bin/env bash
# Proves the newest backup can really be restored: loads it into a throw-away database inside the running db
# container, counts a few tables, then drops that scratch database. The live database is never touched.
# Run on the server as the deploy user:  bash scripts/restore-test-docker.sh
set -euo pipefail
OUT="${BACKUP_DIR:-/home/deploy/pcbd/backups}"
LATEST=$(ls -1t "$OUT"/db-*.dump 2>/dev/null | head -1 || true)
[ -n "$LATEST" ] || { echo "No backup found in $OUT — run scripts/backup-docker.sh first"; exit 1; }
DB=$(docker ps --filter label=com.docker.compose.project=pcbd --filter label=com.docker.compose.service=db --format '{{.Names}}' | head -1)
[ -n "$DB" ] || { echo "db container is not running"; exit 1; }

echo "Restoring $(basename "$LATEST") into a scratch database…"
docker exec "$DB" sh -c 'psql -U "$POSTGRES_USER" -d postgres -c "DROP DATABASE IF EXISTS restore_check" -c "CREATE DATABASE restore_check"' >/dev/null
docker exec -i "$DB" sh -c 'pg_restore -U "$POSTGRES_USER" -d restore_check --no-owner' < "$LATEST"

echo "Row counts in the restored copy:"
docker exec "$DB" sh -c 'psql -U "$POSTGRES_USER" -d restore_check -At -c "select '"'"'users'"'"', count(*) from users union all select '"'"'properties'"'"', count(*) from properties union all select '"'"'projects'"'"', count(*) from projects union all select '"'"'leads'"'"', count(*) from leads"'

docker exec "$DB" sh -c 'psql -U "$POSTGRES_USER" -d postgres -c "DROP DATABASE restore_check"' >/dev/null
echo "OK — the backup restores. (Scratch database removed.)"
