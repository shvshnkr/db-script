#!/usr/bin/env bash
# smoke-arch-modern.sh — HTTP gate for arch-modern path (JWT + TOML + w-arch + r-arch + CRUD)
set -eu

BASE="${1:-http://127.0.0.1}"
COOKIE_JAR="${TMPDIR:-/tmp}/dbs-arch-smoke-cookies.txt"
rm -f "$COOKIE_JAR"

echo "== arch-modern smoke: login page =="
code=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/login-arch.php")
[ "$code" = "200" ] || { echo "FAIL login-arch HTTP $code"; exit 1; }

echo "== arch-modern smoke: JWT login POST =="
code=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -o /dev/null -w '%{http_code}' \
  -X POST -d 'login=admin&password=testpass12' "$BASE/login-arch.php")
[ "$code" = "302" ] || { echo "FAIL login POST HTTP $code"; exit 1; }

echo "== arch-modern smoke: editor tables =="
body=$(curl -s -b "$COOKIE_JAR" "$BASE/w-arch.php")
echo "$body" | grep -q 'Demo items' || { echo "FAIL w-arch tables"; echo "$body" | head -5; exit 1; }

echo "== arch-modern smoke: editor list rows =="
body=$(curl -s -b "$COOKIE_JAR" "$BASE/w-arch.php?tbl=1")
echo "$body" | grep -q 'Hello arch-modern' || { echo "FAIL w-arch list"; echo "$body" | head -10; exit 1; }

echo "== arch-modern smoke: editor create row =="
body=$(curl -s -b "$COOKIE_JAR" "$BASE/w-arch.php?tbl=1&action=new")
csrf=$(echo "$body" | sed -n 's/.*name="_csrf" value="\([^"]*\)".*/\1/p' | head -1)
[ -n "$csrf" ] || { echo "FAIL w-arch new form csrf"; exit 1; }
code=$(curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" -o /dev/null -w '%{http_code}' \
  -X POST -d "_csrf=${csrf}&action=save&row[title]=Smoke+CRUD+row" "$BASE/w-arch.php?tbl=1")
[ "$code" = "302" ] || { echo "FAIL w-arch create HTTP $code"; exit 1; }

echo "== arch-modern smoke: editor list after create =="
body=$(curl -s -b "$COOKIE_JAR" "$BASE/w-arch.php?tbl=1")
echo "$body" | grep -q 'Smoke CRUD row' || { echo "FAIL w-arch list after create"; echo "$body" | head -15; exit 1; }

echo "== arch-modern smoke: reader search =="
body=$(curl -s -b "$COOKIE_JAR" "$BASE/r-arch.php?tbl=1&q=Smoke")
echo "$body" | grep -q 'Smoke CRUD row' || { echo "FAIL r-arch search"; echo "$body" | head -10; exit 1; }

echo "== arch-modern smoke: reader view row =="
pk=$(echo "$body" | sed -n 's/.*r-arch.php?tbl=1&pk=\([^"&]*\).*/\1/p' | head -1)
[ -n "$pk" ] || { echo "FAIL r-arch pk extract"; exit 1; }
body=$(curl -s -b "$COOKIE_JAR" "$BASE/r-arch.php?tbl=1&pk=${pk}")
echo "$body" | grep -q 'Smoke CRUD row' || { echo "FAIL r-arch view"; echo "$body" | head -10; exit 1; }

echo "== arch-modern smoke: reader CSV export =="
csv=$(curl -s -b "$COOKIE_JAR" "$BASE/r-arch.php?tbl=1&export=csv")
echo "$csv" | grep -q 'Smoke CRUD row' || { echo "FAIL r-arch csv export"; echo "$csv" | head -5; exit 1; }

echo "== arch-modern smoke: editor SQL select =="
body=$(curl -s -b "$COOKIE_JAR" "$BASE/w-arch.php?action=sql&tbl=1")
csrf=$(echo "$body" | sed -n 's/.*name="_csrf" value="\([^"]*\)".*/\1/p' | head -1)
[ -n "$csrf" ] || { echo "FAIL w-arch sql form csrf"; exit 1; }
body=$(curl -s -b "$COOKIE_JAR" -X POST \
  --data-urlencode "_csrf=${csrf}" \
  --data-urlencode "table_id=1" \
  --data-urlencode "query=SELECT title FROM demo_items WHERE title LIKE '%Smoke CRUD%' LIMIT 1" \
  "$BASE/w-arch.php?action=sql&tbl=1")
echo "$body" | grep -q 'Smoke CRUD row' || { echo "FAIL w-arch sql select"; echo "$body" | head -15; exit 1; }

echo "== arch-modern smoke: editor SQL denyword =="
body=$(curl -s -b "$COOKIE_JAR" -X POST \
  --data-urlencode "_csrf=${csrf}" \
  --data-urlencode "query=GRANT ALL ON demo_items TO smoke_user" \
  "$BASE/w-arch.php?action=sql")
echo "$body" | grep -qi 'Denied word: grant' || { echo "FAIL w-arch sql denyword"; echo "$body" | head -10; exit 1; }

echo "== arch-modern smoke: admin =="
code=$(curl -s -b "$COOKIE_JAR" -o /dev/null -w '%{http_code}' "$BASE/admin-arch.php")
[ "$code" = "200" ] || { echo "FAIL admin-arch HTTP $code"; exit 1; }

echo "== arch-modern smoke: russian lang switch =="
body=$(curl -s -b "$COOKIE_JAR" "$BASE/w-arch.php?lang=russian")
echo "$body" | grep -q 'Обзор' || { echo "FAIL lang switch"; exit 1; }

echo "OK arch-modern smoke"
