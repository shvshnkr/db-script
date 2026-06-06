#!/bin/bash
# L2: wx.php POST CRUD on denywords.cfg (parity with w.php smoke-editor-crud).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck source=scripts/smoke-lib.sh
source "$ROOT/scripts/smoke-lib.sh"

smoke_init "${1:-http://127.0.0.1:8080}" "${2:-/var/www/html}"
smoke_login

WORD="smoke_wx_$(date +%s)"
CFG="$SMOKE_ROOT/_conf/denywords.cfg"

post_wx() {
    local label="$1"
    shift
    echo -n "$label ... "
    if ! curl -fsS -b "$SMOKE_COOKIE" -c "$SMOKE_COOKIE" -L --max-time 120 \
        -X POST "$SMOKE_BASE_URL/wx.php" \
        "$@" -o "$SMOKE_OUT"; then
        echo "curl error"
        smoke_fail "$label"
    fi
    smoke_assert_no_fatal "$label"
    echo "OK"
}

echo "=== smoke-wx-post (denywords) ==="

smoke_check_url "wx.php GET" "$SMOKE_BASE_URL/wx.php" 'Editor v4|WF_WELCOM|KEY_EDIT|id="edit"'

post_wx "wx select denywords" \
    --data-urlencode "write=Denied words"

post_wx "wx KEY_ADD" \
    --data-urlencode "tbl=denywords" \
    --data-urlencode "groupdb=system" \
    --data-urlencode "write=Add" \
    --data-urlencode "vID=${WORD}"

post_wx "wx KEY_S_ADD" \
    --data-urlencode "tbl=denywords" \
    --data-urlencode "groupdb=system" \
    --data-urlencode "vID=${WORD}" \
    --data-urlencode "write=Confirm changes" \
    --data-urlencode "z0=${WORD}" \
    --data-urlencode "z1=4" \
    --data-urlencode "z2="

if ! grep -qF "$WORD" "$CFG" 2>/dev/null; then
    smoke_fail "denywords.cfg missing $WORD after wx KEY_S_ADD"
fi
echo "OK: wx add persisted"

post_wx "wx KEY_DEL" \
    --data-urlencode "tbl=denywords" \
    --data-urlencode "groupdb=system" \
    --data-urlencode "write=Delete" \
    --data-urlencode "vID=${WORD}"

post_wx "wx KEY_S_DEL" \
    --data-urlencode "tbl=denywords" \
    --data-urlencode "groupdb=system" \
    --data-urlencode "vID=${WORD}" \
    --data-urlencode "write=To confirm removal"

if grep -qF "$WORD" "$CFG" 2>/dev/null; then
    smoke_fail "denywords.cfg still has $WORD after wx delete"
fi

echo "ALL OK: wx.php POST CRUD"
