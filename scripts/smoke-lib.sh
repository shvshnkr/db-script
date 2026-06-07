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
    data="$(smoke_csrf_append "$data")"
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
    data="$(smoke_csrf_append "$data")"
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

smoke_csrf_prime() {
    smoke_get "csrf prime admin" "$SMOKE_BASE_URL/admin.php" 60
    SMOKE_CSRF="$(grep -oE 'name="_csrf" value="[^"]+"' "$SMOKE_OUT" 2>/dev/null | head -1 | sed 's/.*value="//;s/"$//' || true)"
    if [[ -z "${SMOKE_CSRF:-}" ]]; then
        smoke_get "csrf prime w" "$SMOKE_BASE_URL/w.php" 60
        SMOKE_CSRF="$(grep -oE 'name="_csrf" value="[^"]+"' "$SMOKE_OUT" 2>/dev/null | head -1 | sed 's/.*value="//;s/"$//' || true)"
    fi
    if [[ -z "${SMOKE_CSRF:-}" ]]; then
        echo "WARN: CSRF token not found (csrf may be disabled via pr77)"
    fi
}

smoke_csrf_append() {
    local data="$1"
    if [[ -n "${SMOKE_CSRF:-}" ]]; then
        if [[ -n "$data" ]]; then
            echo "${data}&_csrf=${SMOKE_CSRF}"
        else
            echo "_csrf=${SMOKE_CSRF}"
        fi
    else
        echo "$data"
    fi
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
    smoke_csrf_prime
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
        grep -E 'Critical:|Noncritical:|Fixed:' "$SMOKE_OUT" | head -5 || true
        smoke_fail "admin.php?cmd=test has critical errors (A_T_CRIT > 0)"
    fi
}

smoke_admin_test_extended() {
    smoke_admin_test_crit_zero
    local noncrit fixed
    noncrit="$(grep -oE 'Noncritical:\s*[0-9]+' "$SMOKE_OUT" | grep -oE '[0-9]+' | head -1 || echo 0)"
    fixed="$(grep -oE 'Fixed:\s*[0-9]+' "$SMOKE_OUT" | grep -oE '[0-9]+' | head -1 || echo 0)"
    noncrit="${noncrit:-0}"
    fixed="${fixed:-0}"
    echo "admin self-test: Noncritical=$noncrit Fixed=$fixed"
    if [[ "$noncrit" -gt 100 ]]; then
        smoke_fail "admin.php?cmd=test Noncritical > 100 ($noncrit)"
    fi
}

smoke_get_no_auth() {
    local label="$1"
    local url="$2"
    local timeout="${3:-30}"
    local tmp_cookie tmp_out
    tmp_cookie="$(mktemp)"
    tmp_out="$(mktemp)"
    echo -n "$label ... "
    if ! curl -fsS -c "$tmp_cookie" -b "$tmp_cookie" -L --max-time "$timeout" \
        "$url" -o "$tmp_out" 2>/dev/null; then
        rm -f "$tmp_cookie" "$tmp_out"
        echo "curl error"
        smoke_fail "$label"
    fi
    if grep -qE 'Fatal error|Uncaught Error|Uncaught TypeError' "$tmp_out"; then
        rm -f "$tmp_cookie" "$tmp_out"
        echo "php fatal"
        smoke_fail "$label"
    fi
    cp "$tmp_out" "$SMOKE_OUT"
    rm -f "$tmp_cookie" "$tmp_out"
    echo "OK"
}

smoke_assert_denied_or_login() {
    local label="$1"
    if grep -qE 'login\.php|ERR_AUTH|notright|notuser|disable|NOTRIGHTS|Your login as anonymous' "$SMOKE_OUT"; then
        echo "OK: $label denied or redirected"
        return 0
    fi
    smoke_fail "$label should require auth (no login/notright marker)"
}

smoke_login_expect_fail() {
    local user="$1"
    local pass="$2"
    local tmp_cookie tmp_out
    tmp_cookie="$(mktemp)"
    tmp_out="$(mktemp)"
    echo -n "login fail ${user} ... "
    curl -sS -c "$tmp_cookie" -b "$tmp_cookie" -L --max-time 60 \
        -X POST "$SMOKE_BASE_URL/login.php" \
        -d "dbs_log=${user}&dbs_psw=${pass}&loginstate=To+enter" \
        -o "$tmp_out" || true
    if grep -qE 'Fatal error|Uncaught Error' "$tmp_out"; then
        rm -f "$tmp_cookie" "$tmp_out"
        smoke_fail "login fail response fatal"
    fi
    if grep -q 'editor\.png' "$tmp_out"; then
        rm -f "$tmp_cookie" "$tmp_out"
        smoke_fail "wrong credentials must not show editor hub (editor.png)"
    fi
    rm -f "$tmp_cookie" "$tmp_out"
    echo "OK"
}

smoke_log_scan() {
    local logdir="${SMOKE_ROOT}/_logs"
    local found=0
    echo "=== smoke log scan (_logs) ==="
    for f in errorlog.dat log.dat execsqllog.dat; do
        if [[ ! -f "$logdir/$f" ]]; then
            echo "SKIP: $f missing"
            continue
        fi
        if grep -qE 'Fatal error|Uncaught Error|Uncaught TypeError|mysqli_sql_exception' "$logdir/$f" 2>/dev/null; then
            echo "WARN: $f contains error markers:"
            grep -E 'Fatal error|Uncaught Error|Uncaught TypeError|mysqli_sql_exception' "$logdir/$f" | tail -3 || true
            found=1
        else
            echo "OK: $f clean"
        fi
    done
    if [[ "$found" -ne 0 ]]; then
        smoke_fail "error patterns found in _logs after smoke"
    fi
    echo "ALL OK: log scan"
}
