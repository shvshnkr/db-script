#!/usr/bin/env bash
# arch-spa full gate: unit tests + REST smoke + index redirect + SPA static build check
set -eu

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BASE="${1:-http://127.0.0.1}"

echo "=== smoke-all-spa ==="

if [[ -f "$ROOT/vendor/bin/phpunit" ]]; then
  (cd "$ROOT" && vendor/bin/phpunit --testsuite unit)
else
  echo "WARN: phpunit not found — run composer install first"
fi

if [[ -f "$ROOT/public/app/index.html" ]]; then
  grep -q '/app/assets/' "$ROOT/public/app/index.html" || {
    echo "FAIL: public/app/index.html missing built assets — run: cd frontend && npm run build"
    exit 1
  }
  echo "OK SPA build artifacts"
else
  echo "FAIL: public/app/index.html missing"
  exit 1
fi

bash "$ROOT/scripts/smoke-api-auth.sh" "$BASE"
bash "$ROOT/scripts/smoke-spa.sh" "$BASE"
bash "$ROOT/scripts/smoke-index-router.sh" "$BASE" "/var/www/html"
bash "$ROOT/scripts/smoke-reader.sh" "$BASE" "/var/www/html"
bash "$ROOT/scripts/smoke-auth.sh" "$BASE" "/var/www/html"
bash "$ROOT/scripts/smoke-filemgr-ops.sh" "$BASE" "/var/www/html"

echo "ALL OK: smoke-all-spa"
