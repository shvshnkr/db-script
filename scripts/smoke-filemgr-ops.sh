#!/bin/bash
# L3: file manager via REST API (arch-spa).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck source=scripts/smoke-lib.sh
source "$ROOT/scripts/smoke-lib.sh"

smoke_init "${1:-http://127.0.0.1:8080}" "${2:-/var/www/html}"
smoke_login

echo "=== smoke-filemgr-ops ==="

token=$(curl -sS -X POST "$SMOKE_BASE_URL/api/v1/auth/login" \
  -H 'Content-Type: application/json' \
  -d '{"login":"admin","password":"testpass12"}' \
  | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo $j["data"]["token"]??"";')
if [[ -z "$token" ]]; then
  smoke_fail "api login for filemgr"
fi

list_code=$(curl -sS -o /dev/null -w '%{http_code}' -H "Authorization: Bearer ${token}" "$SMOKE_BASE_URL/api/v1/files")
if [[ "$list_code" != "200" ]]; then
  smoke_fail "files list expected 200, got $list_code"
fi
echo "OK files list"

tmp="$(mktemp --suffix=.html)"
echo "<p>spa smoke upload</p>" > "$tmp"
upload_resp=$(curl -sS -X POST -H "Authorization: Bearer ${token}" -H 'X-Requested-With: DbscriptSPA' \
  -F "file=@${tmp};filename=spa-smoke.html" \
  "$SMOKE_BASE_URL/api/v1/files")
rm -f "$tmp"
hash=$(echo "$upload_resp" | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo $j["data"]["hash"]??"";')
if [[ -z "$hash" ]]; then
  echo "$upload_resp"
  smoke_fail "files upload"
fi
echo "OK files upload hash=${hash}"

dl_code=$(curl -sS -o /dev/null -w '%{http_code}' -H "Authorization: Bearer ${token}" \
  "$SMOKE_BASE_URL/api/v1/files/${hash}/download")
if [[ "$dl_code" != "200" ]]; then
  smoke_fail "files download expected 200, got $dl_code"
fi
echo "OK files download"

del_code=$(curl -sS -o /dev/null -w '%{http_code}' -X DELETE \
  -H "Authorization: Bearer ${token}" \
  -H 'Content-Type: application/json' \
  -H 'X-Requested-With: DbscriptSPA' \
  -d '{}' \
  "$SMOKE_BASE_URL/api/v1/files/${hash}")
if [[ "$del_code" != "200" ]]; then
  smoke_fail "files delete expected 200, got $del_code"
fi
echo "OK files delete"

echo "ALL OK: filemgr API upload/download/delete"
