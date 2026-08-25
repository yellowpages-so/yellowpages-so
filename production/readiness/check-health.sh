#!/usr/bin/env bash
set -euo pipefail

HEALTH_URL="${HEALTH_URL:-http://127.0.0.1:8000/api/health}"
response="$(curl --silent --show-error --fail --max-time 15 "$HEALTH_URL")"

echo "$response"
echo "$response" | grep -Eq '"status":"(ok|healthy)"' || {
  echo "ERROR: Health endpoint is not healthy."
  exit 1
}

echo "Health endpoint check passed."
