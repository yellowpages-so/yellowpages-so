#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="${PROJECT_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
ENV_FILE="$PROJECT_ROOT/backend/.env.production"

[[ -f "$ENV_FILE" ]] || { echo "ERROR: backend/.env.production missing."; exit 1; }

required=(
  APP_ENV APP_DEBUG APP_KEY APP_URL
  DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD
  CACHE_STORE QUEUE_CONNECTION SESSION_DRIVER
)

for key in "${required[@]}"; do
  grep -q "^${key}=" "$ENV_FILE" || {
    echo "ERROR: $key missing."
    exit 1
  }
done

grep -q '^APP_ENV=production$' "$ENV_FILE" || {
  echo "ERROR: APP_ENV must be production."
  exit 1
}

grep -q '^APP_DEBUG=false$' "$ENV_FILE" || {
  echo "ERROR: APP_DEBUG must be false."
  exit 1
}

if grep -Eq 'CHANGE_ME|YOUR_PASSWORD|password123' "$ENV_FILE"; then
  echo "ERROR: Placeholder secret found."
  exit 1
fi

echo "Production environment validated."
