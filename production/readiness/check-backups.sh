#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="${PROJECT_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
BACKUP_SCRIPT="$PROJECT_ROOT/production/scripts/backup.sh"
RESTORE_SCRIPT="$PROJECT_ROOT/production/scripts/restore.sh"

[[ -x "$BACKUP_SCRIPT" ]] || { echo "ERROR: Executable backup script is missing."; exit 1; }
[[ -x "$RESTORE_SCRIPT" ]] || { echo "ERROR: Executable restore script is missing."; exit 1; }

grep -q 'pg_dump' "$BACKUP_SCRIPT" || { echo "ERROR: Backup script lacks pg_dump."; exit 1; }
grep -q 'psql' "$RESTORE_SCRIPT" || { echo "ERROR: Restore script lacks psql."; exit 1; }

echo "Backup and restore scripts are present."
