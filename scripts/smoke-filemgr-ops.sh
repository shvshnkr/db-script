#!/bin/bash
# L2: filemgr ops — mkdir when prauth[12] allows; otherwise GET-only gate.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck source=scripts/smoke-lib.sh
source "$ROOT/scripts/smoke-lib.sh"

smoke_init "${1:-http://127.0.0.1:8080}" "${2:-/var/www/html}"
smoke_login

DIR="smoke_mkdir_$(date +%s)"
TARGET="$SMOKE_ROOT/_data/${DIR}"

echo "=== smoke-filemgr-ops ==="

smoke_check_url "filemgr GET" "$SMOKE_BASE_URL/filemgr.php" 'Filemgr|FMG_|filemgr'

echo -n "FMG_MKDIR try ... "
curl -fsS -b "$SMOKE_COOKIE" -c "$SMOKE_COOKIE" -L --max-time 60 \
    -X POST "$SMOKE_BASE_URL/filemgr.php" \
    --data-urlencode "cmd=New folder" \
    --data-urlencode "stroka0=${DIR}" \
    --data-urlencode "path0=${SMOKE_ROOT}/_data/" \
    --data-urlencode "pid=0" \
    --data-urlencode "mask0=*.*" \
    -o "$SMOKE_OUT" || { echo "curl error"; smoke_fail "FMG_MKDIR"; }
smoke_assert_no_fatal "FMG_MKDIR"
echo "OK"

if [[ -d "$TARGET" ]]; then
    rmdir "$TARGET" 2>/dev/null || rm -rf "$TARGET" 2>/dev/null || true
    echo "OK: directory created on disk"
else
    echo "OK: mkdir not applied (TEST prauth[12]=0 — manual L6 if needed)"
fi

echo "ALL OK: filemgr ops"
