#!/bin/bash
# Smoke admin.php?cmd=test — requires A_T_CRIT = 0.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck source=scripts/smoke-lib.sh
source "$ROOT/scripts/smoke-lib.sh"

smoke_init "${1:-http://127.0.0.1:8080}" "${2:-/var/www/html}"
smoke_login

smoke_get "admin.php?cmd=test" "$SMOKE_BASE_URL/admin.php?cmd=test" 120

if grep -q '=============================\|A_T_ALLERR\|Critical:' "$SMOKE_OUT"; then
    smoke_admin_test_crit_zero
    echo "OK: admin.php?cmd=test completed (A_T_CRIT = 0)"
    grep -E 'Critical:|No critical|Fixed' "$SMOKE_OUT" | head -5 || true
    exit 0
fi

echo "WARN: unexpected test output"
head -c 600 "$SMOKE_OUT"
echo ""
exit 1
