#!/usr/bin/env bash
set -euo pipefail
OBS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

docker compose \
  --env-file "$OBS_DIR/.env" \
  -f "$OBS_DIR/docker-compose.observability.yml" \
  down
