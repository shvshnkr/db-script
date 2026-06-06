#!/bin/bash
# L5: protected dirs return 403; install.php blocked after install.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck source=scripts/smoke-lib.sh
source "$ROOT/scripts/smoke-lib.sh"

smoke_init "${1:-http://127.0.0.1:8080}" "${2:-/var/www/html}"

echo "=== smoke-security ==="

smoke_assert_http_code "_conf/sitedata.cfg denied" \
    "$SMOKE_BASE_URL/_conf/sitedata.cfg" "403"
smoke_assert_http_code "_logs/log.dat denied" \
    "$SMOKE_BASE_URL/_logs/log.dat" "403"
smoke_assert_http_code "_local/ denied" \
    "$SMOKE_BASE_URL/_local/" "403"
smoke_assert_http_code "_data/ denied" \
    "$SMOKE_BASE_URL/_data/" "403"

smoke_assert_http_code "info.php denied unauth" \
    "$SMOKE_BASE_URL/info.php" "403"

smoke_get "install.php after install" "$SMOKE_BASE_URL/install.php" 20
if grep -qE 'already installed|Fatal error' "$SMOKE_OUT"; then
    if grep -q 'Fatal error' "$SMOKE_OUT"; then
        smoke_fail "install.php fatal on installed site"
    fi
    echo "OK: install.php reports already installed"
else
    smoke_fail "install.php should report already installed"
fi

echo "ALL OK: security (403 + install guard)"
