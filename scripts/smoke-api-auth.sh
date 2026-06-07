#!/usr/bin/env bash
# arch-spa: smoke REST auth endpoints
set -eu

BASE="${1:-http://127.0.0.1}"
LOGIN="${2:-admin}"
PASS="${3:-testpass12}"

echo "== smoke-api-auth @ ${BASE} =="

code=$(curl -sS -o /tmp/dbs-me.json -w '%{http_code}' "${BASE}/api/v1/auth/me")
if [[ "$code" != "401" ]]; then
  echo "FAIL: GET /api/v1/auth/me expected 401, got ${code}"
  cat /tmp/dbs-me.json
  exit 1
fi
echo "OK unauthenticated /auth/me -> 401"

login_resp=$(curl -sS -X POST "${BASE}/api/v1/auth/login" \
  -H 'Content-Type: application/json' \
  -d "{\"login\":\"${LOGIN}\",\"password\":\"${PASS}\"}")
token=$(echo "$login_resp" | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo $j["data"]["token"]??"";')
if [[ -z "$token" ]]; then
  echo "FAIL: login did not return token"
  echo "$login_resp"
  exit 1
fi
echo "OK login -> token"

me_code=$(curl -sS -o /tmp/dbs-me2.json -w '%{http_code}' \
  -H "Authorization: Bearer ${token}" \
  "${BASE}/api/v1/auth/me")
if [[ "$me_code" != "200" ]]; then
  echo "FAIL: GET /auth/me with Bearer expected 200, got ${me_code}"
  cat /tmp/dbs-me2.json
  exit 1
fi
echo "OK Bearer /auth/me -> 200"

logout_code=$(curl -sS -o /dev/null -w '%{http_code}' -X POST \
  -H "Authorization: Bearer ${token}" \
  "${BASE}/api/v1/auth/logout")
if [[ "$logout_code" != "200" ]]; then
  echo "FAIL: POST /auth/logout expected 200, got ${logout_code}"
  exit 1
fi
echo "OK logout -> 200"

echo "smoke-api-auth: PASS"
