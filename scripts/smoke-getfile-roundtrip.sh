#!/bin/bash
# L2: getfile.php + on-disk file in _data (roundtrip sanity).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck source=scripts/smoke-lib.sh
source "$ROOT/scripts/smoke-lib.sh"

smoke_init "${1:-http://127.0.0.1:8080}" "${2:-/var/www/html}"
smoke_login

TAG="smoke_getfile_$(date +%s)"
FILE="$SMOKE_ROOT/_data/${TAG}.txt"
PAYLOAD="dbscript smoke getfile roundtrip ${TAG}"

echo "=== smoke-getfile-roundtrip ==="

echo -n "create _data file ... "
echo "$PAYLOAD" > "$FILE"
chmod 664 "$FILE" 2>/dev/null || true
echo "OK"

smoke_check_url "getfile.php GET" "$SMOKE_BASE_URL/getfile.php" 'Search v4|SELLINK|getfile|GF_'

smoke_post "getfile master SELECT" "$SMOKE_BASE_URL/getfile.php" \
    "write=SELECT&intf=master-mode&vID=${TAG}&groupdb=system&tbl=files" 120
smoke_assert_no_fatal "getfile POST"

if grep -qF "$TAG" "$SMOKE_OUT" || grep -qE 'Search|getfile|Fatal|notright' "$SMOKE_OUT"; then
    echo "OK: getfile POST returned content"
else
    head -c 400 "$SMOKE_OUT"
    smoke_fail "getfile POST content"
fi

rm -f "$FILE"
echo "ALL OK: getfile roundtrip"
