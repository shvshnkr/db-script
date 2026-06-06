#!/bin/bash
# Shared helpers for Dbscript HTTP smoke tests.
set -euo pipefail

SMOKE_BASE_URL="${SMOKE_BASE_URL:-http://127.0.0.1:8080}"
SMOKE_ROOT="${SMOKE_ROOT:-/var/www/html}"
SMOKE_COOKIE=""
SMOKE_OUT=""
SMOKE_HDR=""

smoke_init() {
    SMOKE_BASE_URL="${1:-$SMOKE_BASE_URL}"
    SMOKE_ROOT="${2:-$SMOKE_ROOT}"
    SMOKE_COOKIE="$(mktemp)"
    SMOKE_OUT="$(mktemp)"
    SMOKE_HDR="$(mktemp)"
    trap 'smoke_cleanup' EXIT
}

smoke_cleanup() {
    rm -f "${SMOKE_COOKIE:-}" "${SMOKE_OUT:-}" "${SMOKE_HDR:-}"
}

smoke_fail() {
    echo "FAIL: $1"
    if [[ -f "${SMOKE_OUT:-}" ]]; then
        grep -E 'Fatal error|Uncaught Error|Uncaught TypeError' "$SMOKE_OUT" | head -5 || true
        head -c 800 "$SMOKE_OUT" || true
        echo ""
    fi
    exit 1
}

smoke_assert_no_fatal() {
    local label="$1"
    if grep -qE 'Fatal error|Uncaught Error|Uncaught TypeError' "$SMOKE_OUT"; then
        echo "php fatal"
        smoke_fail "$label"
    fi
}

smoke_get() {
    local label="$1"
    local url="$2"
    local timeout="${3:-60}"
    echo -n "$label ... "
    if ! curl -fsS -b "$SMOKE_COOKIE" -c "$SMOKE_COOKIE" -L --max-time "$timeout" \
        "$url" -o "$SMOKE_OUT"; then
        echo "curl error"
        smoke_fail "$label"
    fi
    smoke_assert_no_fatal "$label"
    echo "OK"
}

smoke_post() {
    local label="$1"
    local url="$2"
    local data="$3"
    local timeout="${4:-120}"
    echo -n "$label ... "
    if ! curl -fsS -b "$SMOKE_COOKIE" -c "$SMOKE_COOKIE" -L --max-time "$timeout" \
        -X POST "$url" -d "$data" -o "$SMOKE_OUT"; then
        echo "curl error"
        smoke_fail "$label"
    fi
    smoke_assert_no_fatal "$label"
    echo "OK"
}

smoke_post_save_headers() {
    local label="$1"
    local url="$2"
    local data="$3"
    local timeout="${4:-120}"
    echo -n "$label ... "
    if ! curl -fsS -b "$SMOKE_COOKIE" -c "$SMOKE_COOKIE" -L --max-time "$timeout" \
        -X POST "$url" -d "$data" -o "$SMOKE_OUT" -D "$SMOKE_HDR"; then
        echo "curl error"
        smoke_fail "$label"
    fi
    smoke_assert_no_fatal "$label"
    echo "OK"
}

smoke_http_code() {
    local url="$1"
    curl -sS -o /dev/null -w '%{http_code}' -b "$SMOKE_COOKIE" --max-time 20 "$url"
}

smoke_assert_http_code() {
    local label="$1"
    local url="$2"
    local expected="$3"
    local code
    code="$(smoke_http_code "$url")"
    if [[ "$code" != "$expected" ]]; then
        smoke_fail "$label (HTTP $code, expected $expected)"
    fi
    echo "OK: $label HTTP $code"
}

smoke_login() {
    local user="${1:-TEST}"
    local pass="${2:-TEST}"
    smoke_post "login ${user}/${pass}" "$SMOKE_BASE_URL/login.php" \
        "dbs_log=${user}&dbs_psw=${pass}&loginstate=To+enter" 60
    if ! grep -q 'editor\.png\|w\.php\|WELCOME\|MNU_2\|login\.php' "$SMOKE_OUT"; then
        smoke_fail "login response"
    fi
    if ! grep -q 'dbsa' "$SMOKE_COOKIE" 2>/dev/null; then
        echo "WARN: dbsa cookie not in jar (may still be session-only)"
    fi
}

smoke_assert_pattern() {
    local label="$1"
    local pattern="$2"
    if ! grep -qE "$pattern" "$SMOKE_OUT"; then
        grep -E 'login\.php|notright|notuser|disable|Fatal' "$SMOKE_OUT" | head -5 || true
        smoke_fail "$label content ($pattern)"
    fi
}

smoke_check_url() {
    local label="$1"
    local url="$2"
    local pattern="$3"
    smoke_get "$label" "$url"
    smoke_assert_pattern "$label" "$pattern"
}

smoke_admin_test_crit_zero() {
    if grep -qE 'Critical:\s*[1-9][0-9]*' "$SMOKE_OUT"; then
        grep -E 'Critical:|No critical|Fixed' "$SMOKE_OUT" | head -5 || true
        smoke_fail "admin.php?cmd=test has critical errors (A_T_CRIT > 0)"
    fi
}
