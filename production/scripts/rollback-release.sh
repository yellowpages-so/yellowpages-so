#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="${PROJECT_ROOT:-/srv/yellowpages-so}"
PREVIOUS_RELEASE="${1:?Provide previous Git tag or commit}"

cd "$PROJECT_ROOT"

git fetch --all --tags
git checkout "$PREVIOUS_RELEASE"

"$PROJECT_ROOT/production/scripts/deploy.sh"

echo "Rollback completed to $PREVIOUS_RELEASE."
