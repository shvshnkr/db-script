#!/bin/bash
# L4: limited user ACL — login OK, editor/admin/dblinker denied.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck source=scripts/smoke-lib.sh
source "$ROOT/scripts/smoke-lib.sh"

smoke_init "${1:-http://127.0.0.1:8080}" "${2:-/var/www/html}"

echo "=== smoke-acl ==="

php "$ROOT/scripts/smoke-setup-limited-user.php" || smoke_fail "setup LIMITED user"

smoke_login "LIMITED" "LIMITED"

deny_check() {
    local label="$1"
    local url="$2"
    smoke_get "$label" "$url"
    smoke_assert_denied_or_login "$label"
}

deny_check "LIMITED w.php" "$SMOKE_BASE_URL/w.php"
deny_check "LIMITED dblinker.php" "$SMOKE_BASE_URL/dblinker.php"

smoke_get "LIMITED admin.php" "$SMOKE_BASE_URL/admin.php"
if grep -qE 'Fatal error|Uncaught Error' "$SMOKE_OUT"; then
    smoke_fail "LIMITED admin fatal"
fi
if grep -qE 'notright|PLVL_LOW|RES_DIS|disable' "$SMOKE_OUT"; then
    echo "OK: LIMITED admin denied"
elif grep -qE 'My profile|A_MY_PROF|profile|LOGINUSER|displayconfig|gmlimitcfg' "$SMOKE_OUT"; then
    echo "OK: LIMITED admin profile-only (gmlimitcfg)"
else
    smoke_fail "LIMITED admin unexpected response"
fi

smoke_get "LIMITED admin cmd=test blocked" "$SMOKE_BASE_URL/admin.php?cmd=test" 120
if grep -qE 'Critical:|A_T_ALLERR|Total errors:' "$SMOKE_OUT"; then
    smoke_fail "LIMITED user must not run admin self-test"
fi
if grep -qE 'notright|PLVL_LOW|RES_DIS|disable|ERR_AUTH' "$SMOKE_OUT"; then
    echo "OK: LIMITED self-test blocked"
else
    echo "OK: LIMITED self-test not available (no test output)"
fi

smoke_get "LIMITED filemgr.php" "$SMOKE_BASE_URL/filemgr.php"
if grep -qE 'Fatal error|Uncaught Error' "$SMOKE_OUT"; then
    smoke_fail "LIMITED filemgr fatal"
fi
if grep -qE 'notright|NOTRIGHTS|disable|login\.php' "$SMOKE_OUT"; then
    echo "OK: LIMITED filemgr denied"
else
    echo "OK: LIMITED filemgr reachable (filemgr may allow low-level users)"
fi

echo "ALL OK: ACL limited user"
