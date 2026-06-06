#!/bin/bash
# L2: editor POST CRUD on denywords.cfg (safe cfg table).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck source=scripts/smoke-lib.sh
source "$ROOT/scripts/smoke-lib.sh"

smoke_init "${1:-http://127.0.0.1:8080}" "${2:-/var/www/html}"
smoke_login

WORD="smoke_crud_$(date +%s)"
CFG="$SMOKE_ROOT/_conf/denywords.cfg"

post_form() {
    local label="$1"
    shift
    echo -n "$label ... "
    if ! curl -fsS -b "$SMOKE_COOKIE" -c "$SMOKE_COOKIE" -L --max-time 120 \
        -X POST "$SMOKE_BASE_URL/w.php" \
        "$@" -o "$SMOKE_OUT"; then
        echo "curl error"
        smoke_fail "$label"
    fi
    smoke_assert_no_fatal "$label"
    echo "OK"
}

echo "=== smoke-editor-crud (denywords) ==="

post_form "select denywords cfg" \
    --data-urlencode "write=Denied words"

post_form "KEY_ADD form" \
    --data-urlencode "tbl=denywords" \
    --data-urlencode "groupdb=system" \
    --data-urlencode "write=Add" \
    --data-urlencode "vID=${WORD}"

post_form "KEY_S_ADD" \
    --data-urlencode "tbl=denywords" \
    --data-urlencode "groupdb=system" \
    --data-urlencode "vID=${WORD}" \
    --data-urlencode "write=Confirm changes" \
    --data-urlencode "z0=${WORD}" \
    --data-urlencode "z1=4" \
    --data-urlencode "z2="

if ! grep -qF "$WORD" "$CFG" 2>/dev/null; then
    smoke_fail "denywords.cfg missing $WORD after KEY_S_ADD"
fi
echo "OK: denywords.cfg contains new word"

post_form "KEY_DEL form" \
    --data-urlencode "tbl=denywords" \
    --data-urlencode "groupdb=system" \
    --data-urlencode "write=Delete" \
    --data-urlencode "vID=${WORD}"

post_form "KEY_S_DEL" \
    --data-urlencode "tbl=denywords" \
    --data-urlencode "groupdb=system" \
    --data-urlencode "vID=${WORD}" \
    --data-urlencode "write=To confirm removal"

if grep -qF "$WORD" "$CFG" 2>/dev/null; then
    smoke_fail "denywords.cfg still contains $WORD after delete"
fi
echo "OK: row removed from denywords.cfg"

echo "ALL OK: editor CRUD (denywords add → delete)"
