#!/bin/bash
# Smoke cold entry paths after login (wx, dblinker, filemgr, getfile, main).
set -euo pipefail

BASE_URL="${1:-http://127.0.0.1:8080}"
COOKIE="$(mktemp)"
OUT="$(mktemp)"
trap 'rm -f "$COOKIE" "$OUT"' EXIT

fail() {
    echo "FAIL: $1"
    grep -E 'Fatal error|Uncaught Error' "$OUT" | head -5 || true
    head -c 600 "$OUT" || true
    echo ""
    exit 1
}

check_url() {
    local label="$1"
    local url="$2"
    local pattern="$3"
    echo -n "$label ... "
    if ! curl -fsS -b "$COOKIE" -L --max-time 60 "$url" -o "$OUT"; then
        echo "curl error"
        fail "$label"
    fi
    if grep -qE 'Fatal error|Uncaught Error' "$OUT"; then
        echo "php fatal"
        fail "$label"
    fi
    if grep -qE "$pattern" "$OUT"; then
        echo "OK"
    else
        grep -E 'login\.php|notright|notuser|disable|Fatal' "$OUT" | head -5
        fail "$label content"
    fi
}

curl -fsS -c "$COOKIE" -b "$COOKIE" -X POST "$BASE_URL/login.php" \
    -d 'dbs_log=TEST&dbs_psw=TEST&loginstate=To+enter' -o "$OUT" || fail "login"

check_url "wx.php" "$BASE_URL/wx.php" 'Editor v4|WF_WELCOM|KEY_EDIT|id="edit"'
check_url "dblinker.php" "$BASE_URL/dblinker.php" 'Dbmanager v4|GEN_DB_TBL|GEN_DB_CON|server:'
check_url "filemgr.php" "$BASE_URL/filemgr.php" 'Filemgr|FMG_|filemgr'
check_url "getfile.php" "$BASE_URL/getfile.php" 'Search v4|SELLINK|getfile|GF_'
check_url "main.php" "$BASE_URL/main.php" 'AUTHOR|REGTO|Dj--alex|help'
check_url "index.php" "$BASE_URL/index.php" 'index|login|str0|Dbscript'
check_url "ajax2.php" "$BASE_URL/ajax2.php" 'get\(|script|my\.php'
check_url "r.php .help" "$BASE_URL/r.php?vID=.help&base=0" 'help|Help|\.help|F1_'
check_url "indexmenu.php" "$BASE_URL/indexmenu.php" 'logo1|menu1|str0|index'
check_url "str0.php p=0" "$BASE_URL/str0.php?p=0" 'str0|index|login|Fatal'
check_url "info.php" "$BASE_URL/info.php" 'PHP Version|phpinfo|Configuration'

echo "ALL OK: cold entry paths"
