#!/bin/bash
# L5: auth gates — API + legacy login (arch-spa).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck source=scripts/smoke-lib.sh
source "$ROOT/scripts/smoke-lib.sh"

smoke_init "${1:-http://127.0.0.1:8080}" "${2:-/var/www/html}"

echo "=== smoke-auth ==="

code=$(curl -sS -o /dev/null -w '%{http_code}' "$SMOKE_BASE_URL/api/v1/tables")
if [[ "$code" != "401" ]]; then
  smoke_fail "api/tables without auth expected 401, got $code"
fi
echo "OK api/tables requires auth"

code=$(curl -sS -o /dev/null -w '%{http_code}' "$SMOKE_BASE_URL/api/v1/files")
if [[ "$code" != "401" ]]; then
  smoke_fail "api/files without auth expected 401, got $code"
fi
echo "OK api/files requires auth"

smoke_get_no_auth "admin.php no cookie" "$SMOKE_BASE_URL/admin.php"
smoke_assert_denied_or_login "admin.php"

smoke_get_no_auth "dblinker.php no cookie" "$SMOKE_BASE_URL/dblinker.php"
smoke_assert_denied_or_login "dblinker.php"

smoke_login_expect_fail "BADUSER" "BADPASS"
smoke_login_expect_fail "TEST" "WRONGPASS"

login_resp=$(curl -sS -X POST "$SMOKE_BASE_URL/api/v1/auth/login" \
  -H 'Content-Type: application/json' \
  -d '{"login":"admin","password":"testpass12"}')
token=$(echo "$login_resp" | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo $j["data"]["token"]??"";')
if [[ -z "$token" ]]; then
  smoke_fail "api login"
fi
echo "OK api login"

me_code=$(curl -sS -o /dev/null -w '%{http_code}' -H "Authorization: Bearer ${token}" "$SMOKE_BASE_URL/api/v1/auth/me")
if [[ "$me_code" != "200" ]]; then
  smoke_fail "api/me expected 200, got $me_code"
fi
echo "OK api/me"

logout_code=$(curl -sS -o /dev/null -w '%{http_code}' -X POST \
  -H "Authorization: Bearer ${token}" \
  -H 'X-Requested-With: DbscriptSPA' \
  "$SMOKE_BASE_URL/api/v1/auth/logout")
if [[ "$logout_code" != "200" ]]; then
  smoke_fail "api/logout expected 200, got $logout_code"
fi
echo "OK api/logout"

smoke_login "TEST" "TEST"
smoke_get "login hub after auth" "$SMOKE_BASE_URL/login.php"
if ! grep -qE 'editor\.png|/app/editor|MNU_2|LOGOUT|Leave' "$SMOKE_OUT"; then
  smoke_fail "authenticated login hub"
fi
echo "OK authenticated hub"

echo "ALL OK: auth gates"
