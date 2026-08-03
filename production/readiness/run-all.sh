#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="${PROJECT_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
REPORT_DIR="$PROJECT_ROOT/storage/production-readiness"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
REPORT="$REPORT_DIR/report-$TIMESTAMP.log"

mkdir -p "$REPORT_DIR"
exec > >(tee -a "$REPORT") 2>&1

scripts=(
  check-environment.sh
  check-backend.sh
  check-frontend.sh
  check-database.sh
  check-redis.sh
  check-queue.sh
  check-scheduler.sh
  check-security.sh
  check-backups.sh
  check-health.sh
)

failed=0

echo "YellowPages.so Production Readiness Audit"
echo "Project: $PROJECT_ROOT"

for script in "${scripts[@]}"; do
  echo
  echo "Running $script"
  if "$PROJECT_ROOT/production/readiness/$script"; then
    echo "PASS: $script"
  else
    echo "FAIL: $script"
    failed=1
  fi
done

echo
echo "Report: $REPORT"

if [[ "$failed" -ne 0 ]]; then
  echo "Production readiness checks failed."
  exit 1
fi

echo "All production readiness checks passed."
