#!/bin/bash
# L5: hub link integrity — internal hrefs from login menu.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck source=scripts/smoke-lib.sh
source "$ROOT/scripts/smoke-lib.sh"

smoke_init "${1:-http://127.0.0.1:8080}" "${2:-/var/www/html}"
smoke_login

echo "=== smoke-links ==="

# Extract internal .php links from login hub (skip external http)
mapfile -t LINKS < <(grep -oE 'href="[^"]+\.php[^"]*"' "$SMOKE_OUT" \
    | sed 's/href="//;s/"$//' \
    | grep -vE '^https?://' \
    | grep -vE 'nlogin\.php|news\.php|nedit\.php' \
    | sort -u | head -20)

if [[ ${#LINKS[@]} -eq 0 ]]; then
    smoke_fail "no internal .php links on login hub"
fi

failed=0
for link in "${LINKS[@]}"; do
    local_url="$SMOKE_BASE_URL/${link#/}"
    echo -n "link ${link} ... "
    if ! curl -fsS -b "$SMOKE_COOKIE" -L --max-time 45 "$local_url" -o "$SMOKE_OUT"; then
        echo "curl error"
        failed=1
        continue
    fi
    if grep -qE 'Fatal error|Uncaught Error' "$SMOKE_OUT"; then
        echo "FATAL"
        failed=1
        continue
    fi
    echo "OK"
done

if [[ "$failed" -ne 0 ]]; then
    smoke_fail "one or more hub links failed"
fi

echo "ALL OK: ${#LINKS[@]} hub links checked"
