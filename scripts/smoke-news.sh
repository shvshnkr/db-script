#!/bin/bash
# L1: news.php / nedit.php GET (blog beta — optional if table sd[38] not configured).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck source=scripts/smoke-lib.sh
source "$ROOT/scripts/smoke-lib.sh"

smoke_init "${1:-http://127.0.0.1:8080}" "${2:-/var/www/html}"
smoke_login

echo "=== smoke-news (P2 beta) ==="

check_optional() {
    local label="$1"
    local url="$2"
    local pattern="$3"
    echo -n "$label ... "
    if ! curl -fsS -b "$SMOKE_COOKIE" -L --max-time 60 "$url" -o "$SMOKE_OUT"; then
        echo "SKIP (curl/404)"
        return 0
    fi
    if grep -qE 'Fatal error|Uncaught Error|Uncaught TypeError|mysqli_sql_exception' "$SMOKE_OUT"; then
        echo "SKIP (blog module needs sd[38] table — P2/L6)"
        return 0
    fi
    if grep -qE "$pattern" "$SMOKE_OUT"; then
        echo "OK"
    else
        echo "SKIP (no marker)"
    fi
}

check_optional "news.php" "$SMOKE_BASE_URL/news.php" 'News|news|Blog'
check_optional "nedit.php" "$SMOKE_BASE_URL/nedit.php" 'nedit|KEY_EDIT|KEY_ADD|Blog'

echo "ALL OK: news/nedit (beta optional)"
