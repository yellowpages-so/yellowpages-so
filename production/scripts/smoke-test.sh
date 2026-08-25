#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-https://yellowpages.so}"

curl --fail --silent "$BASE_URL/api/health" \
  | grep -Eq '"status":"(healthy|ok)"'

curl --fail --silent "$BASE_URL/api/v1/platform/live" \
  | grep -q '"status":"healthy"'

curl --fail --silent "$BASE_URL/" >/dev/null

echo "Production smoke tests passed."
