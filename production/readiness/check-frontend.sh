#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="${PROJECT_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
cd "$PROJECT_ROOT/frontend"

node --version
npm --version
npm ci
npm run lint
npm run typecheck
npm run build

echo "Frontend checks passed."
