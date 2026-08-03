#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="${PROJECT_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
BACKEND="$PROJECT_ROOT/backend"
FRONTEND="$PROJECT_ROOT/frontend"
failed=0

if grep -RIn --exclude-dir=vendor --exclude-dir=.git 'APP_DEBUG=true' "$BACKEND" >/tmp/yp-debug-findings.txt 2>/dev/null; then
  cat /tmp/yp-debug-findings.txt
  failed=1
fi

if grep -RIn --exclude-dir=node_modules --exclude-dir=.git 'dangerouslySetInnerHTML' "$FRONTEND" >/tmp/yp-html-findings.txt 2>/dev/null; then
  cat /tmp/yp-html-findings.txt
  failed=1
fi

if [[ "$failed" -ne 0 ]]; then
  echo "Security review requires attention."
  exit 1
fi

echo "Security checks passed."
