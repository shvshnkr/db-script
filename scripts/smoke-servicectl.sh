#!/bin/bash
# L5: servicectl probe JSON + admin SU block (no real restart required).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck source=scripts/smoke-lib.sh
source "$ROOT/scripts/smoke-lib.sh"

smoke_init "${1:-http://127.0.0.1:8080}" "${2:-/var/www/html}"

echo "=== smoke-servicectl ==="

echo -n "CLI --probe JSON ... "
PROBE="$(bash "$ROOT/scripts/dbs-servicectl.sh" --probe)"
if ! echo "$PROBE" | grep -q '"privilege"'; then
    echo "invalid JSON"
    smoke_fail "probe JSON"
fi
echo "OK"

echo -n "CLI unknown action ... "
if bash "$ROOT/scripts/dbs-servicectl.sh" --resolve "db.nope" 2>/dev/null; then
    smoke_fail "unknown action should fail"
fi
echo "OK"

smoke_login
smoke_get "admin SU servicectl status" "$SMOKE_BASE_URL/admin.php" 60
if grep -qE 'servicectl|privilege|Superuser' "$SMOKE_OUT"; then
    echo "OK: admin shows servicectl block"
else
    smoke_fail "admin servicectl block missing"
fi

echo "ALL OK: servicectl"
