#!/usr/bin/env bash
# smoke-arch-modern.sh — HTTP gate for arch-modern path (JWT + TOML + w-arch)
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

echo "== arch-modern smoke: admin =="
code=$(curl -s -b "$COOKIE_JAR" -o /dev/null -w '%{http_code}' "$BASE/admin-arch.php")
[ "$code" = "200" ] || { echo "FAIL admin-arch HTTP $code"; exit 1; }

echo "== arch-modern smoke: russian lang switch =="
body=$(curl -s -b "$COOKIE_JAR" "$BASE/w-arch.php?lang=russian")
echo "$body" | grep -q 'Обзор' || { echo "FAIL lang switch"; exit 1; }

echo "OK arch-modern smoke"
