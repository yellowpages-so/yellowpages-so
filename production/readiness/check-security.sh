#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="${PROJECT_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
ENV_FILE="$PROJECT_ROOT/backend/.env.production"
failed=0

if [[ ! -f "$ENV_FILE" ]]; then
  echo "ERROR: backend/.env.production is missing."
  exit 1
fi

if grep -q '^APP_DEBUG=true$' "$ENV_FILE"; then
  echo "ERROR: APP_DEBUG is enabled in production."
  failed=1
fi

if grep -Eq 'CHANGE_ME|password123|secret123' "$ENV_FILE"; then
  echo "ERROR: Unsafe placeholder values found in production environment."
  failed=1
fi

if grep -RIn \
  --exclude-dir=vendor \
  --exclude-dir=node_modules \
  --exclude-dir=.git \
  --exclude='.env*' \
  -E '(api[_-]?key|password|secret)[[:space:]]*=[[:space:]]*["'\''][^"'\'']+["'\'']' \
  "$PROJECT_ROOT/backend/app" \
  "$PROJECT_ROOT/frontend" \
  2>/dev/null \
  | grep -Ev "Str::random|random_bytes|bin2hex|Hash::make|bcrypt|password_hash" \
  >/tmp/yp-hardcoded-secrets.txt
then
  echo "WARNING: Possible hardcoded secrets found:"
  cat /tmp/yp-hardcoded-secrets.txt
  failed=1
fi

if [[ "$failed" -ne 0 ]]; then
  echo "Security review requires attention."
  exit 1
fi

echo "Security checks passed."
