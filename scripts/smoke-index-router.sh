#!/bin/bash
# L5: index.php query-router redirects without fatals.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck source=scripts/smoke-lib.sh
source "$ROOT/scripts/smoke-lib.sh"

smoke_init "${1:-http://127.0.0.1:8080}" "${2:-/var/www/html}"
smoke_login

echo "=== smoke-index-router ==="

check_redirect() {
    local label="$1"
    local qs="$2"
    local expect_fragment="$3"
    local follow="${4:-0}"
    echo -n "$label ... "
    local hdr loc
    hdr="$(mktemp)"
    loc="$(curl -sS -b "$SMOKE_COOKIE" -c "$SMOKE_COOKIE" -o /dev/null -D "$hdr" -w '%{redirect_url}' \
        --max-time 30 "$SMOKE_BASE_URL/index.php?${qs}")"
    if [[ -z "$loc" ]]; then
        loc="$(grep -i '^Location:' "$hdr" | tail -1 | tr -d '\r' | awk '{print $2}')"
    fi
    rm -f "$hdr"
    if [[ -z "$loc" ]]; then
        smoke_fail "$label (no Location redirect)"
    fi
    if [[ "$loc" != *"$expect_fragment"* ]]; then
        echo "redirect=$loc"
        smoke_fail "$label (expected *${expect_fragment}*)"
    fi
    if [[ "$follow" == "1" ]]; then
        curl -fsS -b "$SMOKE_COOKIE" -L --max-time 30 "$loc" -o "$SMOKE_OUT" || smoke_fail "$label follow"
        smoke_assert_no_fatal "$label"
    fi
    echo "OK"
}

check_redirect "index ?w → /app/editor" "wfoo=1" "/app/editor" 1
check_redirect "index ?r → /app/reader" "rtest=1" "/app/reader" 0
check_redirect "index ?f → /app/files" "ftest=1" "/app/files" 1
check_redirect "index ?a → admin-arch.php" "atest=1" "admin-arch.php" 1

echo "ALL OK: index.php router"
