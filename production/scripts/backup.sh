#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="${PROJECT_ROOT:-/srv/yellowpages-so}"
BACKUP_DIR="${BACKUP_DIR:-/srv/backups/yellowpages}"
COMPOSE_FILE="$PROJECT_ROOT/production/docker-compose.production.yml"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"

mkdir -p "$BACKUP_DIR"

cd "$PROJECT_ROOT"

docker compose -f "$COMPOSE_FILE" exec -T postgres \
  pg_dump -U "$POSTGRES_USER" "$POSTGRES_DB" \
  | gzip > "$BACKUP_DIR/database-$TIMESTAMP.sql.gz"

tar -czf \
  "$BACKUP_DIR/storage-$TIMESTAMP.tar.gz" \
  backend/storage/app

find "$BACKUP_DIR" -type f -mtime +30 -delete

echo "Backup completed."
