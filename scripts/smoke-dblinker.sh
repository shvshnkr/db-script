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

echo "ALL OK: dblinker list databases"
