#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="${PROJECT_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
ENV_FILE="$PROJECT_ROOT/backend/.env.production"

queue_connection="$(grep '^QUEUE_CONNECTION=' "$ENV_FILE" | cut -d= -f2-)"

if [[ -z "$queue_connection" || "$queue_connection" == "sync" ]]; then
  echo "ERROR: Production queue must not use sync."
  exit 1
fi

cd "$PROJECT_ROOT/backend"
php artisan queue:monitor "${QUEUE_NAMES:-default}" --max="${QUEUE_MAX_JOBS:-1000}"

echo "Queue checks passed."
