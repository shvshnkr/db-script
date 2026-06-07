#!/bin/bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
MOCK="$ROOT/tests/servicectl/mock-bin"
export PATH="$MOCK:$PATH"
export DBS_PROBE_CACHE="/tmp/dbs-probe-test.json"
rm -f "$DBS_PROBE_CACHE"

fail=0
run_case() {
    local name="$1"
    shift
    echo -n "  $name ... "
    if "$@"; then
        echo OK
    else
        echo FAIL
        fail=1
    fi
}

echo "=== servicectl fixture matrix ==="

run_case "ubuntu probe" bash -c \
    'DBS_OS_RELEASE_FILE='"$ROOT"'/tests/servicectl/fixtures/ubuntu-22.04-os-release bash '"$ROOT"'/scripts/dbs-servicectl.sh --probe | grep -q mariadb\|mysql'

run_case "resolve db.restart" bash -c \
    'DBS_OS_RELEASE_FILE='"$ROOT"'/tests/servicectl/fixtures/ubuntu-22.04-os-release bash '"$ROOT"'/scripts/dbs-servicectl.sh --resolve db.restart | grep -q systemctl'

run_case "reject unknown" bash -c \
    '! bash '"$ROOT"'/scripts/dbs-servicectl.sh --resolve evil.action 2>/dev/null'

run_case "privilege none" bash -c \
    'DBS_PRIVILEGE=none DBS_OS_RELEASE_FILE='"$ROOT"'/tests/servicectl/fixtures/ubuntu-22.04-os-release bash '"$ROOT"'/scripts/dbs-servicectl.sh --probe | grep -q "\"privilege\":\"none\""'

exit $fail
