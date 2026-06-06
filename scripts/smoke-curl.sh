#!/bin/bash
# HTTP smoke check for install.php (run from host or inside dev container).
set -euo pipefail

BASE_URL="${1:-http://127.0.0.1:8080}"
URL="$BASE_URL/install.php"
OUT="$(mktemp)"
trap 'rm -f "$OUT"' EXIT

curl -fsS --max-time 20 "$URL" -o "$OUT"

if grep -q "Fatal error" "$OUT"; then
    echo "FAIL: install.php returned Fatal error"
    grep -E "Fatal error|Uncaught" "$OUT" | head -5
    exit 1
fi

if grep -q "Unsupported version core" "$OUT"; then
    echo "FAIL: core version rejected by installer"
    exit 1
fi

if grep -q "already installed" "$OUT"; then
    echo "OK: install.php reports site already installed (_conf present)"
    exit 0
fi

if grep -q "Select your language" "$OUT"; then
    echo "OK: install wizard step 0 (language selection)"
    exit 0
fi

if grep -q "This version is supported by this installer" "$OUT"; then
    echo "OK: core loaded, installer running"
    exit 0
fi

echo "WARN: unexpected install.php response (no fatal, no known markers)"
head -c 500 "$OUT"
echo ""
exit 1
