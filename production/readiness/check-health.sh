#!/usr/bin/env bash
set -euo pipefail

HEALTH_URL="${HEALTH_URL:-http://127.0.0.1:8000/api/v1/observability/health}"
response="$(curl --silent --show-error --fail --max-time 15 "$HEALTH_URL")"

echo "$response"
echo "$response" | grep -q '"status":"ok"' || { echo "ERROR: Health endpoint is not healthy."; exit 1; }

echo "Health endpoint check passed."
