#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://127.0.0.1:8000}"

curl --fail --silent "$BASE_URL/api/v1/platform/live" \
  | grep -q '"status":"healthy"'

curl --fail --silent "$BASE_URL/api/v1/platform/ready" \
  | grep -Eq '"status":"(healthy|degraded)"'

echo "Enterprise smoke tests passed."
