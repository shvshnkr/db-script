#!/bin/bash
# L5: auth gates — unauthenticated access, wrong password, logout.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck source=scripts/smoke-lib.sh
source "$ROOT/scripts/smoke-lib.sh"

smoke_init "${1:-http://127.0.0.1:8080}" "${2:-/var/www/html}"

echo "=== smoke-auth ==="

smoke_get_no_auth "w.php no cookie" "$SMOKE_BASE_URL/w.php"
smoke_assert_denied_or_login "w.php"

smoke_get_no_auth "admin.php no cookie" "$SMOKE_BASE_URL/admin.php"
smoke_assert_denied_or_login "admin.php"

smoke_get_no_auth "filemgr.php no cookie" "$SMOKE_BASE_URL/filemgr.php"
smoke_assert_denied_or_login "filemgr.php"

smoke_get_no_auth "dblinker.php no cookie" "$SMOKE_BASE_URL/dblinker.php"
smoke_assert_denied_or_login "dblinker.php"

smoke_login_expect_fail "BADUSER" "BADPASS"
smoke_login_expect_fail "TEST" "WRONGPASS"

smoke_login "TEST" "TEST"
smoke_get "login hub after auth" "$SMOKE_BASE_URL/login.php"
if ! grep -qE 'editor\.png|w\.php|MNU_2|LOGOUT|Leave' "$SMOKE_OUT"; then
    smoke_fail "authenticated login hub"
fi
echo "OK: authenticated hub"

smoke_post "logout resetcookie" "$SMOKE_BASE_URL/login.php" \
    "resetcookie=Leave" 60

smoke_get_no_auth "w.php after logout" "$SMOKE_BASE_URL/w.php"
smoke_assert_denied_or_login "w.php after logout"

echo "ALL OK: auth gates"
