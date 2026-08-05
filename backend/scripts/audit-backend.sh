#!/usr/bin/env bash
set -euo pipefail

BACKEND_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REPORT_DIR="$BACKEND_ROOT/storage/audit"
RAW_DIR="$REPORT_DIR/raw"

mkdir -p "$RAW_DIR"
cd "$BACKEND_ROOT"

php -v > "$RAW_DIR/php-version.txt" 2>&1 || true
php artisan --version > "$RAW_DIR/laravel-version.txt" 2>&1 || true
php artisan about > "$RAW_DIR/artisan-about.txt" 2>&1 || true
php artisan route:list --json > "$RAW_DIR/routes.json" 2>&1 || true
php artisan migrate:status --env=testing > "$RAW_DIR/migrations.txt" 2>&1 || true
php artisan schedule:list > "$RAW_DIR/schedule.txt" 2>&1 || true
php artisan test > "$RAW_DIR/tests.txt" 2>&1 || true

composer validate --no-check-publish \
  > "$RAW_DIR/composer-validate.txt" 2>&1 || true

composer audit \
  > "$RAW_DIR/composer-audit.txt" 2>&1 || true

composer outdated --direct \
  > "$RAW_DIR/composer-outdated.txt" 2>&1 || true

if [[ -x vendor/bin/pint ]]; then
  vendor/bin/pint --test > "$RAW_DIR/pint.txt" 2>&1 || true
fi

if [[ -x vendor/bin/phpstan ]]; then
  vendor/bin/phpstan analyse > "$RAW_DIR/phpstan.txt" 2>&1 || true
fi

grep -RInE \
  --exclude-dir=vendor \
  --exclude-dir=node_modules \
  --exclude-dir=.git \
  "TODO|FIXME|HACK|XXX" \
  app database routes tests config \
  > "$RAW_DIR/todos.txt" 2>&1 || true

grep -RInE \
  --exclude-dir=vendor \
  --exclude-dir=node_modules \
  --exclude-dir=.git \
  "dd\\(|dump\\(|var_dump\\(|print_r\\(" \
  app routes tests \
  > "$RAW_DIR/debug-code.txt" 2>&1 || true

grep -RInE \
  --exclude-dir=vendor \
  --exclude-dir=node_modules \
  --exclude-dir=.git \
  "DB::statement|DB::unprepared|selectRaw|whereRaw|orderByRaw|havingRaw" \
  app database routes \
  > "$RAW_DIR/raw-sql.txt" 2>&1 || true

grep -RInE \
  --exclude-dir=vendor \
  --exclude-dir=node_modules \
  --exclude-dir=.git \
  "Schema::hasTable|Schema::hasColumn" \
  app \
  > "$RAW_DIR/runtime-schema-checks.txt" 2>&1 || true

python3 "$BACKEND_ROOT/scripts/analyze_backend.py" \
  "$BACKEND_ROOT" \
  "$REPORT_DIR"

echo
echo "Audit complete."
echo "Markdown: $REPORT_DIR/backend-audit.md"
echo "JSON: $REPORT_DIR/backend-audit.json"
