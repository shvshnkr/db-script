#!/bin/bash
# L1/L2: reader info pages via REST API (arch-spa).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck source=scripts/smoke-lib.sh
source "$ROOT/scripts/smoke-lib.sh"

smoke_init "${1:-http://127.0.0.1:8080}" "${2:-/var/www/html}"
smoke_login

echo "=== smoke-reader ==="

token=$(curl -sS -X POST "$SMOKE_BASE_URL/api/v1/auth/login" \
  -H 'Content-Type: application/json' \
  -d '{"login":"admin","password":"testpass12"}' \
  | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo $j["data"]["token"]??"";')

check_info() {
  local label="$1"
  local slug="$2"
  local pattern="$3"
  local auth="${4:-0}"
  echo -n "$label ... "
  local headers=()
  if [[ "$auth" == "1" ]]; then
    headers+=(-H "Authorization: Bearer ${token}")
  fi
  body=$(curl -sS "${headers[@]}" "$SMOKE_BASE_URL/api/v1/info/${slug}")
  if ! echo "$body" | grep -qE "$pattern"; then
    echo "$body"
    smoke_fail "$label"
  fi
  echo "OK"
}

check_info "info .ver" "ver" 'Dbscript|4\.5|arch-spa|Core' 0
check_info "info .help" "help" 'help|\.ver|\.help' 0
check_info "info .author" "author" 'Dj--alex|dj-alex|Author' 0
check_info "info .info" "info" 'Login|Role|Priority' 1

echo "ALL OK: reader info pages (.ver .help .author .info)"
