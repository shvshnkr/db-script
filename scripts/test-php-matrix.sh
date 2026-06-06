#!/bin/bash
# PHP version compatibility matrix: rebuild dev web image per version, run L0 + PHPUnit (+ optional smoke).
# Usage: bash scripts/test-php-matrix.sh [--quick] [--full] [8.2 8.3 ...]
#   --quick  L0 verify + PHPUnit only (default when no flag)
#   --full   also smoke-all with SMOKE_SKIP_INSTALL=1 (prod gate without wipe)
# Env: DOCKER, COMPOSE_FILE, BASE_URL, WEB_CONTAINER
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if [[ -z "${DOCKER:-}" ]]; then
    for candidate in \
        docker \
        "/c/Program Files/Docker/Docker/resources/bin/docker.exe" \
        "/mnt/c/Program Files/Docker/Docker/resources/bin/docker.exe"; do
        if command -v "$candidate" >/dev/null 2>&1 || [[ -x "$candidate" ]]; then
            DOCKER="$candidate"
            break
        fi
    done
    DOCKER="${DOCKER:-docker}"
fi

# Git Bash on Windows: docker-credential-desktop must be on PATH for compose build.
for bindir in \
    "/c/Program Files/Docker/Docker/resources/bin" \
    "/mnt/c/Program Files/Docker/Docker/resources/bin"; do
    if [[ -d "$bindir" ]]; then
        export PATH="$bindir:$PATH"
        break
    fi
done
COMPOSE_FILE="${COMPOSE_FILE:-dev/docker-compose.yml}"
BASE_URL="${BASE_URL:-http://127.0.0.1}"
WEB_CONTAINER="${WEB_CONTAINER:-dev-web-1}"
MODE="quick"
VERSIONS=()

while [[ $# -gt 0 ]]; do
    case "$1" in
        --quick) MODE="quick"; shift ;;
        --full)  MODE="full"; shift ;;
        -*) echo "Unknown option: $1" >&2; exit 2 ;;
        *) VERSIONS+=("$1"); shift ;;
    esac
done

if [[ ${#VERSIONS[@]} -eq 0 ]]; then
    VERSIONS=(8.2 8.3)
fi

compose() {
    "$DOCKER" compose -f "$COMPOSE_FILE" "$@"
}

wait_db() {
    local i
    for i in $(seq 1 60); do
        if compose ps --status running 2>/dev/null | grep -q "db.*healthy\|db.*running"; then
            if "$DOCKER" exec dev-db-1 mysqladmin ping -h localhost -uroot -pdbscript_root --silent 2>/dev/null; then
                return 0
            fi
        fi
        sleep 2
    done
    echo "FAIL: MySQL not ready" >&2
    return 1
}

run_for_version() {
    local ver="$1"
    local fail=0

    echo ""
    echo "################################################################"
    echo "# PHP matrix: $ver ($MODE)"
    echo "################################################################"

    export PHP_VERSION="$ver"
    if ! compose build web; then
        echo "FAIL: docker compose build web for PHP $ver" >&2
        return 1
    fi
    if ! compose up -d --force-recreate web; then
        echo "FAIL: docker compose up for PHP $ver" >&2
        return 1
    fi

    echo "Waiting for stack..."
    wait_db
    sleep 3

    echo "--- php -v ---"
    "$DOCKER" exec "$WEB_CONTAINER" php -v | head -1
    actual_ver=$("$DOCKER" exec "$WEB_CONTAINER" php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
    if [[ "$actual_ver" != "$ver" ]]; then
        echo "FAIL: expected PHP $ver, container reports PHP $actual_ver" >&2
        return 1
    fi

    echo "--- L0 verify.sh ---"
    if ! "$DOCKER" exec "$WEB_CONTAINER" bash scripts/verify.sh; then
        echo "FAIL: verify.sh on PHP $ver"
        fail=1
    fi

    if [[ ! -x "$ROOT/vendor/bin/phpunit" ]] && [[ ! -f "$ROOT/vendor/bin/phpunit" ]]; then
        "$DOCKER" exec "$WEB_CONTAINER" composer install --no-interaction --working-dir=/var/www/html || true
    fi

    if [[ -f "$ROOT/vendor/bin/phpunit" ]] || "$DOCKER" exec "$WEB_CONTAINER" test -f /var/www/html/vendor/bin/phpunit; then
        echo "--- L3 unit ---"
        if ! "$DOCKER" exec "$WEB_CONTAINER" vendor/bin/phpunit --testsuite unit; then
            echo "FAIL: phpunit unit on PHP $ver"
            fail=1
        fi
        echo "--- L4 integration ---"
        if ! "$DOCKER" exec "$WEB_CONTAINER" vendor/bin/phpunit --testsuite integration; then
            echo "FAIL: phpunit integration on PHP $ver"
            fail=1
        fi
    else
        echo "WARN: phpunit not available — skip L3/L4"
        fail=1
    fi

    if [[ "$MODE" == "full" ]]; then
        echo "--- L0–L5 smoke-all (SMOKE_SKIP_INSTALL=1) ---"
        if ! "$DOCKER" exec -e SMOKE_SKIP_INSTALL=1 "$WEB_CONTAINER" bash scripts/smoke-all.sh "$BASE_URL"; then
            echo "FAIL: smoke-all on PHP $ver"
            fail=1
        fi
    fi

    if [[ "$fail" -eq 0 ]]; then
        echo "PASS: PHP $ver matrix ($MODE)"
    else
        echo "FAIL: PHP $ver matrix ($MODE)"
        return 1
    fi
}

MATRIX_FAIL=0
for ver in "${VERSIONS[@]}"; do
    if ! run_for_version "$ver"; then
        MATRIX_FAIL=1
    fi
done

echo ""
if [[ "$MATRIX_FAIL" -eq 0 ]]; then
    echo "=========================================="
    echo "ALL OK: PHP matrix passed (${VERSIONS[*]}, mode=$MODE)"
    echo "=========================================="
    exit 0
fi

echo "MATRIX FAILED for one or more versions: ${VERSIONS[*]}"
exit 1
