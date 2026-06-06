#!/bin/bash
# Orchestrator: full layered smoke (L0–L5) before prod.
# Usage: bash scripts/smoke-all.sh [BASE_URL] [ROOT]
# Env: SMOKE_SKIP_INSTALL=1 — skip fresh install (use existing _conf)
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BASE_URL="${1:-http://127.0.0.1:8080}"
HTML_ROOT="${2:-/var/www/html}"
SKIP_INSTALL="${SMOKE_SKIP_INSTALL:-0}"

run() {
    local script="$1"
    echo ""
    echo "######## $script ########"
    bash "$ROOT/scripts/$script" "$BASE_URL" "$HTML_ROOT"
}

echo "=== smoke-all: Dbscript 4 layered testing ==="
echo "BASE_URL=$BASE_URL ROOT=$HTML_ROOT SKIP_INSTALL=$SKIP_INSTALL"

run verify.sh

if [[ "$SKIP_INSTALL" != "1" ]]; then
    run smoke-install.sh
else
    echo ""
    echo "######## smoke-install.sh (SKIPPED) ########"
    run smoke-curl.sh
fi

run smoke-admin-test.sh
run smoke-cold-paths.sh
run smoke-security.sh
run smoke-index-router.sh
run smoke-editor-crud.sh
run smoke-reader.sh
run smoke-filemgr-ops.sh
run smoke-dblinker.sh
run smoke-admin-save.sh
run smoke-links.sh
run smoke-news.sh

if [[ -x "$ROOT/vendor/bin/phpunit" ]] || [[ -f "$ROOT/vendor/bin/phpunit" ]]; then
    echo ""
    echo "######## phpunit ########"
    (cd "$ROOT" && vendor/bin/phpunit --testsuite unit) || {
        echo "WARN: phpunit unit suite failed or incomplete"
        exit 1
    }
else
    echo ""
    echo "SKIP: vendor/bin/phpunit not found (run: composer install --dev)"
fi

echo ""
echo "=========================================="
echo "ALL OK: smoke-all completed (L0–L5)"
echo "=========================================="
