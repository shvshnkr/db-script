#!/bin/bash
# Verify PHP syntax and guard against known PHP 8 blockers.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

FAIL=0
GREP="${GREP:-grep}"

scan_active() {
    local pat="$1"
    find . \( -name '*.php' -o -name 'dbscore.lib' \) -not -path './.git/*' -not -path './scripts/port-mechanical.php' -print0 \
        | xargs -0 "$GREP" -nE "$pat" 2>/dev/null \
        | "$GREP" -vE '^\./[^:]+:[0-9]+:\s*(//|/\*|\*)' || true
}

echo "=== PHP syntax lint ==="
if ! command -v php >/dev/null 2>&1; then
    echo "WARN: php CLI not found — skip syntax lint (run inside dev container)."
else
    while IFS= read -r -d '' f; do
        if ! php -l "$f" >/dev/null 2>&1; then
            php -l "$f"
            FAIL=1
        fi
    done < <(find . -name '*.php' -not -path './.git/*' -print0)

    if [[ -f dbscore.lib ]]; then
        if ! php -l dbscore.lib >/dev/null 2>&1; then
            php -l dbscore.lib
            FAIL=1
        fi
    fi
    echo "Syntax OK."
fi

echo ""
echo "=== PHP 8 blocker grep guards (non-comment lines) ==="

if matches=$(scan_active 'import_request_variables\s*\('); [[ -n "$matches" ]]; then
    echo "$matches"; echo "FAIL: import_request_variables"; FAIL=1
fi

if matches=$(scan_active 'get_magic_quotes'); [[ -n "$matches" ]]; then
    echo "$matches"; echo "FAIL: get_magic_quotes"; FAIL=1
fi

if matches=$(scan_active '\beach\s*\(\s*\$'); [[ -n "$matches" ]]; then
    echo "$matches"; echo "FAIL: each() on variables"; FAIL=1
fi

if matches=$(scan_active '\bsplit\s*\('); [[ -n "$matches" ]]; then
    echo "$matches"; echo "FAIL: split()"; FAIL=1
fi

if matches=$(scan_active '\bmysql_[a-z_]+\s*\('); [[ -n "$matches" ]]; then
    echo "$matches"; echo "FAIL: mysql_* API"; FAIL=1
fi

if matches=$(scan_active '\bcreate_function\s*\('); [[ -n "$matches" ]]; then
    echo "$matches"; echo "FAIL: create_function()"; FAIL=1
fi

if matches=$(scan_active '\b(ereg|eregi|eregi_replace|ereg_replace|spliti)\s*\('); [[ -n "$matches" ]]; then
    echo "$matches"; echo "FAIL: ereg/spliti API"; FAIL=1
fi

if matches=$(scan_active '\b(__autoload|session_register|session_unregister|session_is_registered)\s*\('); [[ -n "$matches" ]]; then
    echo "$matches"; echo "FAIL: removed session/autoload API"; FAIL=1
fi

if matches=$(grep -nE '/etc/init\.d/' admin.php 2>/dev/null || true); [[ -n "$matches" ]]; then
    echo "$matches"; echo "FAIL: hardcoded /etc/init.d in admin.php"; FAIL=1
fi

if [[ -f scripts/dbs-servicectl.sh ]]; then
    if command -v shellcheck >/dev/null 2>&1; then
        if ! shellcheck -x scripts/dbs-servicectl.sh; then
            FAIL=1
        fi
    else
        echo "WARN: shellcheck not found — skip dbs-servicectl.sh"
    fi
fi

if [[ "$FAIL" -eq 0 ]]; then
    echo "All guards passed."
    exit 0
fi

exit 1
