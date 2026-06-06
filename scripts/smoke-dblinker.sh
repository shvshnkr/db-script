#!/bin/bash
# L2: dblinker connect and list databases (no destructive ops).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck source=scripts/smoke-lib.sh
source "$ROOT/scripts/smoke-lib.sh"

smoke_init "${1:-http://127.0.0.1:8080}" "${2:-/var/www/html}"
smoke_login

echo "=== smoke-dblinker ==="

smoke_post "dblinker mysql connect" "$SMOKE_BASE_URL/dblinker.php" \
    "dbtype=mysql&start=Next"
smoke_assert_pattern "db list" 'dbscript_test|Choose database|GEN_DB_SEL|Use db'

if ! grep -q 'dbscript_test' "$SMOKE_OUT"; then
    smoke_fail "dbscript_test not in SHOW DATABASES output"
fi
echo "OK: dbscript_test listed"

echo -n "dblinker select dbscript_test ... "
if ! curl -fsS -b "$SMOKE_COOKIE" -c "$SMOKE_COOKIE" -L --max-time 120 \
    -X POST "$SMOKE_BASE_URL/dblinker.php" \
    --data-urlencode "dbtype=mysql" \
    --data-urlencode "dbselected=dbscript_test" \
    --data-urlencode "start=Use db" \
    -o "$SMOKE_OUT"; then
    echo "curl error"
    smoke_fail "dblinker USE_DB"
fi
smoke_assert_no_fatal "dblinker USE_DB"
if grep -qE 'Choose the tables|GEN_TBL_SEL|SHOW TABLES|tableselected' "$SMOKE_OUT"; then
    echo "OK"
else
    grep -E 'Fatal|notright|login' "$SMOKE_OUT" | head -5 || true
    smoke_fail "dblinker table list"
fi

echo "ALL OK: dblinker list databases + browse tables"
