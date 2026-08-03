#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="${PROJECT_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
cd "$PROJECT_ROOT/backend"

php artisan migrate:status --env=production

pending="$(php artisan migrate:status --env=production | grep -E '\| No\s+\|' || true)"

if [[ -n "$pending" ]]; then
  echo "ERROR: Pending production migrations found."
  echo "$pending"
  exit 1
fi

php artisan tinker --execute="use Illuminate\Support\Facades\DB; DB::select('SELECT 1'); echo 'Database connection OK'.PHP_EOL;"

echo "Database checks passed."
