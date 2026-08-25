#!/usr/bin/env bash
set -euo pipefail
OBS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

[[ -f "$OBS_DIR/.env" ]] || {
  echo "ERROR: production/observability/.env is missing."
  exit 1
}

docker compose \
  --env-file "$OBS_DIR/.env" \
  -f "$OBS_DIR/docker-compose.observability.yml" \
  up -d

echo "Grafana: http://127.0.0.1:3001"
echo "Prometheus: http://127.0.0.1:9090"
echo "Alertmanager: http://127.0.0.1:9093"
