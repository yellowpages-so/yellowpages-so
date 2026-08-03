#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="${PROJECT_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
ENV_FILE="$PROJECT_ROOT/backend/.env.production"

fail() { echo "ERROR: $1"; exit 1; }

[[ -f "$ENV_FILE" ]] || fail "backend/.env.production is missing."

required=(
  APP_NAME APP_ENV APP_KEY APP_URL APP_DEBUG
  DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD
  CACHE_STORE QUEUE_CONNECTION SESSION_DRIVER
)

for key in "${required[@]}"; do
  grep -q "^${key}=" "$ENV_FILE" || fail "$key is missing."
done

grep -q '^APP_ENV=production$' "$ENV_FILE" || fail "APP_ENV must equal production."
grep -q '^APP_DEBUG=false$' "$ENV_FILE" || fail "APP_DEBUG must equal false."

if grep -Eq 'CHANGE_ME|password123|secret123' "$ENV_FILE"; then
  fail "Unsafe placeholder value found."
fi

echo "Production environment file is valid."
