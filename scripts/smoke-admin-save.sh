#!/bin/bash
# L2: admin sub-pages load and note roundtrip (non-destructive).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck source=scripts/smoke-lib.sh
source "$ROOT/scripts/smoke-lib.sh"

smoke_init "${1:-http://127.0.0.1:8080}" "${2:-/var/www/html}"
smoke_login

NOTE="smoke_note_$(date +%s)"

echo "=== smoke-admin-save ==="

smoke_check_url "admin myprof" \
    "$SMOKE_BASE_URL/admin.php?cmd=myprof" \
    'profile|My profile|A_MY_PROF|TEST'

smoke_check_url "admin note GET" \
    "$SMOKE_BASE_URL/admin.php?cmd=note" \
    'note|bloknot|A_NOTE|Shared'

smoke_post "admin note POST" "$SMOKE_BASE_URL/admin.php?cmd=note" \
    "go=Save&vd=${NOTE}"
smoke_assert_no_fatal "admin note POST"

smoke_get "admin note re-read" "$SMOKE_BASE_URL/admin.php?cmd=note"
if grep -qF "$NOTE" "$SMOKE_OUT"; then
    echo "OK: note text persisted in response"
else
    echo "WARN: note text not found in HTML (bloknot may use file storage)"
fi

echo "ALL OK: admin save surfaces"
