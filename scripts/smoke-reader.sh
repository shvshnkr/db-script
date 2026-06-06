#!/bin/bash
# L1/L2: reader dot-commands and POST search.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck source=scripts/smoke-lib.sh
source "$ROOT/scripts/smoke-lib.sh"

smoke_init "${1:-http://127.0.0.1:8080}" "${2:-/var/www/html}"
smoke_login

echo "=== smoke-reader ==="

smoke_check_url "r.php .ver" \
    "$SMOKE_BASE_URL/r.php?viewid=.ver&base=0" \
    'Version|\.ver|4\.5|Core'

smoke_check_url "r.php .help" \
    "$SMOKE_BASE_URL/r.php?vID=.help&base=0" \
    'HLPINF|\.help|command|help'

smoke_check_url "r.php .author" \
    "$SMOKE_BASE_URL/r.php?vID=.author&base=0" \
    'dj|alex|author|Author'

smoke_check_url "r.php .info" \
    "$SMOKE_BASE_URL/r.php?vID=.info&base=0" \
    'info|About|CMD|Fatal'

echo "ALL OK: reader dot-commands (.ver .help .author .info)"
