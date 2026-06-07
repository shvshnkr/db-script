#!/usr/bin/env bash
# arch-spa: smoke REST auth + editor CRUD
set -eu

BASE="${1:-http://127.0.0.1}"
LOGIN="${2:-admin}"
PASS="${3:-testpass12}"

echo "== smoke-spa @ ${BASE} =="

bash "$(dirname "$0")/smoke-api-auth.sh" "$BASE" "$LOGIN" "$PASS"

login_resp=$(curl -sS -X POST "${BASE}/api/v1/auth/login" \
  -H 'Content-Type: application/json' \
  -d "{\"login\":\"${LOGIN}\",\"password\":\"${PASS}\"}")
token=$(echo "$login_resp" | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo $j["data"]["token"]??"";')

tables=$(curl -sS -H "Authorization: Bearer ${token}" "${BASE}/api/v1/tables")
table_id=$(echo "$tables" | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo $j["data"][0]["id"]??"";')
if [[ -z "$table_id" ]]; then
  echo "FAIL: no tables in /api/v1/tables"
  echo "$tables"
  exit 1
fi
echo "OK tables -> id=${table_id}"

title="spa-$(date +%s)"
create_resp=$(curl -sS -X POST "${BASE}/api/v1/tables/${table_id}/rows" \
  -H "Authorization: Bearer ${token}" \
  -H 'Content-Type: application/json' \
  -d "{\"title\":\"${title}\"}")
pk=$(echo "$create_resp" | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo $j["data"]["pk"]??"";')
if [[ -z "$pk" ]]; then
  echo "FAIL: create row did not return pk"
  echo "$create_resp"
  exit 1
fi
echo "OK create row pk=${pk}"

del_code=$(curl -sS -o /dev/null -w '%{http_code}' -X DELETE \
  -H "Authorization: Bearer ${token}" \
  -H 'Content-Type: application/json' \
  -d "{\"pks\":[\"${pk}\"]}" \
  "${BASE}/api/v1/tables/${table_id}/rows")
if [[ "$del_code" != "200" ]]; then
  echo "FAIL: delete row expected 200, got ${del_code}"
  exit 1
fi
echo "OK delete row"

echo "smoke-spa: PASS"
