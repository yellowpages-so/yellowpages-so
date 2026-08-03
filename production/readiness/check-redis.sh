#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="${PROJECT_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
cd "$PROJECT_ROOT/backend"

php artisan tinker --execute="use Illuminate\Support\Facades\Cache; Cache::put('production-readiness','ok',60); if (Cache::get('production-readiness') !== 'ok') { throw new RuntimeException('Cache verification failed.'); } echo 'Cache verification OK'.PHP_EOL;"

echo "Redis checks passed."
