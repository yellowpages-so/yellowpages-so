#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="${PROJECT_ROOT:-/srv/yellowpages-so}"
COMPOSE_FILE="$PROJECT_ROOT/production/docker-compose.production.yml"

cd "$PROJECT_ROOT"

"$PROJECT_ROOT/production/scripts/validate-env.sh"

docker compose -f "$COMPOSE_FILE" build --pull

docker compose -f "$COMPOSE_FILE" up -d postgres redis

docker compose -f "$COMPOSE_FILE" run --rm php \
  php artisan migrate --force

docker compose -f "$COMPOSE_FILE" run --rm php \
  php artisan optimize

docker compose -f "$COMPOSE_FILE" up -d --remove-orphans

BASE_URL="${BASE_URL:-https://yellowpages.so}" \
  "$PROJECT_ROOT/production/scripts/smoke-test.sh"

docker image prune -f

echo "Deployment completed."
