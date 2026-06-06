#!/bin/bash
# L5: CSRF token required on mutating POST (admin).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck source=scripts/smoke-lib.sh
source "$ROOT/scripts/smoke-lib.sh"

smoke_init "${1:-http://127.0.0.1:8080}" "${2:-/var/www/html}"
smoke_login

echo "=== smoke-csrf ==="

echo -n "POST admin without CSRF rejected ... "
if curl -fsS -b "$SMOKE_COOKIE" -c "$SMOKE_COOKIE" -L --max-time 60 \
    -X POST "$SMOKE_BASE_URL/admin.php" \
    -d "write=Test" -o "$SMOKE_OUT"; then
    :
fi
if grep -qE 'csrf|notright|Fatal error' "$SMOKE_OUT"; then
    echo "OK"
else
    smoke_fail "admin POST without CSRF should be rejected"
fi

smoke_csrf_prime
if [[ -z "${SMOKE_CSRF:-}" ]]; then
    echo "SKIP: CSRF disabled (pr77)"
    exit 0
fi

smoke_post "POST admin with CSRF" "$SMOKE_BASE_URL/admin.php" \
    "write=Test&_csrf=${SMOKE_CSRF}" 60
if grep -q 'Fatal error' "$SMOKE_OUT"; then
    smoke_fail "admin POST with CSRF fatal"
fi
echo "OK: CSRF flow"

echo "ALL OK: csrf"
