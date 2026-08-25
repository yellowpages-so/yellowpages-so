#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="${PROJECT_ROOT:-/srv/yellowpages-so}"
COMPOSE_FILE="$PROJECT_ROOT/production/docker-compose.production.yml"
BACKUP_FILE="${1:?Provide the database backup file}"

cd "$PROJECT_ROOT"

gunzip -c "$BACKUP_FILE" \
  | docker compose -f "$COMPOSE_FILE" exec -T postgres \
    psql -U "$POSTGRES_USER" "$POSTGRES_DB"

echo "Restore completed."
