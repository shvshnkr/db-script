#!/usr/bin/env bash
# arch-spa: smoke REST auth + editor CRUD + reader/files/menu/i18n/sql
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

menu_code=$(curl -sS -o /dev/null -w '%{http_code}' -H "Authorization: Bearer ${token}" "${BASE}/api/v1/menu")
if [[ "$menu_code" != "200" ]]; then
  echo "FAIL: menu expected 200, got ${menu_code}"
  exit 1
fi
echo "OK menu"

i18n_code=$(curl -sS -o /dev/null -w '%{http_code}' "${BASE}/api/v1/i18n?lang=english")
if [[ "$i18n_code" != "200" ]]; then
  echo "FAIL: i18n expected 200, got ${i18n_code}"
  exit 1
fi
echo "OK i18n"

files_code=$(curl -sS -o /dev/null -w '%{http_code}' -H "Authorization: Bearer ${token}" "${BASE}/api/v1/files")
if [[ "$files_code" != "200" ]]; then
  echo "FAIL: files expected 200, got ${files_code}"
  exit 1
fi
echo "OK files list"

tables=$(curl -sS -H "Authorization: Bearer ${token}" "${BASE}/api/v1/tables")
table_id=$(echo "$tables" | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo $j["data"][0]["id"]??"";')
if [[ -z "$table_id" ]]; then
  echo "FAIL: no tables in /api/v1/tables"
  echo "$tables"
  exit 1
fi
echo "OK tables -> id=${table_id}"

reader_code=$(curl -sS -o /dev/null -w '%{http_code}' -H "Authorization: Bearer ${token}" \
  "${BASE}/api/v1/reader/tables/${table_id}/search")
if [[ "$reader_code" != "200" ]]; then
  echo "FAIL: reader search expected 200, got ${reader_code}"
  exit 1
fi
echo "OK reader search"

export_code=$(curl -sS -o /dev/null -w '%{http_code}' -H "Authorization: Bearer ${token}" \
  "${BASE}/api/v1/reader/tables/${table_id}/export.csv")
if [[ "$export_code" != "200" ]]; then
  echo "FAIL: reader export expected 200, got ${export_code}"
  exit 1
fi
echo "OK reader export"

title="spa-$(date +%s)"
create_resp=$(curl -sS -X POST "${BASE}/api/v1/tables/${table_id}/rows" \
  -H "Authorization: Bearer ${token}" \
  -H 'Content-Type: application/json' \
  -H 'X-Requested-With: DbscriptSPA' \
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
  -H 'X-Requested-With: DbscriptSPA' \
  -d "{\"pks\":[\"${pk}\"]}" \
  "${BASE}/api/v1/tables/${table_id}/rows")
if [[ "$del_code" != "200" ]]; then
  echo "FAIL: delete row expected 200, got ${del_code}"
  exit 1
fi
echo "OK delete row"

sql_code=$(curl -sS -o /dev/null -w '%{http_code}' -X POST \
  -H "Authorization: Bearer ${token}" \
  -H 'Content-Type: application/json' \
  -H 'X-Requested-With: DbscriptSPA' \
  -d '{"query":"SELECT 1 AS one"}' \
  "${BASE}/api/v1/sql/execute")
if [[ "$sql_code" != "200" ]]; then
  echo "FAIL: sql execute expected 200, got ${sql_code}"
  exit 1
fi
echo "OK sql execute"

conv_tables_code=$(curl -sS -o /dev/null -w '%{http_code}' -H "Authorization: Bearer ${token}" \
  "${BASE}/api/v1/converter/tables")
if [[ "$conv_tables_code" != "200" ]]; then
  echo "FAIL: converter tables expected 200, got ${conv_tables_code}"
  exit 1
fi
echo "OK converter tables"

conv_preview_code=$(curl -sS -o /dev/null -w '%{http_code}' -X POST \
  -H "Authorization: Bearer ${token}" \
  -H 'Content-Type: application/json' \
  -H 'X-Requested-With: DbscriptSPA' \
  -d '{"source_id":1,"destination_id":1}' \
  "${BASE}/api/v1/converter/preview")
if [[ "$conv_preview_code" != "422" ]]; then
  echo "FAIL: converter same-engine preview expected 422, got ${conv_preview_code}"
  exit 1
fi
echo "OK converter preview validation"

conv_run_code=$(curl -sS -o /dev/null -w '%{http_code}' -X POST \
  -H "Authorization: Bearer ${token}" \
  -H 'Content-Type: application/json' \
  -H 'X-Requested-With: DbscriptSPA' \
  -d '{"source_id":1,"destination_id":2,"rewrite":true,"verbose":false}' \
  "${BASE}/api/v1/converter/run")
if [[ "$conv_run_code" != "200" ]]; then
  echo "FAIL: converter mysql->fdb run expected 200, got ${conv_run_code}"
  exit 1
fi
echo "OK converter mysql->fdb"

echo "smoke-spa: PASS"
