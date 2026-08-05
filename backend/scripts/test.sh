#!/usr/bin/env bash
set -euo pipefail

php artisan optimize:clear
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/routes-*.php
rm -f .phpunit.result.cache

php artisan test
