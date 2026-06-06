#!/bin/bash
# Full install wizard smoke: language → MySQL (host db) → login → w.php
set -euo pipefail

BASE_URL="${1:-http://127.0.0.1:8080}"
ROOT="${2:-/var/www/html}"
COOKIE="$(mktemp)"
OUT="$(mktemp)"
trap 'rm -f "$COOKIE" "$OUT"' EXIT

fail() {
    echo "FAIL: $1"
    grep -E 'Fatal error|Uncaught Error|Unsupported version' "$OUT" | head -5 || true
    head -c 800 "$OUT" || true
    echo ""
    exit 1
}

post() {
    local label="$1"
    local data="$2"
    echo -n "$label ... "
    if ! curl -fsS -c "$COOKIE" -b "$COOKIE" -L --max-time 120 \
        -X POST "$BASE_URL/install.php" -d "$data" -o "$OUT"; then
        echo "curl error"
        fail "$label (curl)"
    fi
    if grep -qE 'Fatal error|Uncaught Error' "$OUT"; then
        echo "php fatal"
        fail "$label"
    fi
    echo "OK"
}

rm -rf "$ROOT/_conf"
mkdir -p "$ROOT/_logs" "$ROOT/_local" "$ROOT/_data"

curl -fsS -c "$COOKIE" -b "$COOKIE" "$BASE_URL/install.php" -o "$OUT" || fail "init GET"

post "step1 language" 'lang=english&step=1&write=Installing+Dbscript+-+1&loginstate=--%3E'
post "step2 mysql" 'lang=english&step=2&LOGINSQL=root&PASSSQL=dbscript_root&IPDEFSERVSQL=db&write=Installing+Dbscript+-+2&loginstate=Next+'
post "step3 superuser" 'lang=english&step=3&LOGINSQL=root&PASSSQL=dbscript_root&IPDEFSERVSQL=db&LOGINUSER=TEST&PASSWORDUSER=TEST&write=Installing+Dbscript+-+3&loginstate=Next+'
post "step4 configs" 'lang=english&step=4&LOGINSQL=root&PASSSQL=dbscript_root&IPDEFSERVSQL=db&LOGINUSER=TEST&PASSWORDUSER=TEST&fmgfldr=&NOMYSQL=&sharedconf=&write=Installing+Dbscript+-+4&loginstate=Next+'

if [[ ! -f "$ROOT/_conf/sitedata.cfg" ]]; then
    echo "FAIL: _conf/sitedata.cfg not created after step 4"
    ls -la "$ROOT/_conf" 2>/dev/null || echo "(no _conf dir)"
    exit 1
fi
echo "OK: _conf/sitedata.cfg exists"

post "step5 skip-shared" 'lang=english&step=5&LOGINSQL=root&PASSSQL=dbscript_root&IPDEFSERVSQL=db&LOGINUSER=TEST&PASSWORDUSER=TEST&fmgfldr=&NOMYSQL=&sharedconf=&write=Installing+Dbscript+-+5&loginstate=Next+'
post "step8 note-db" 'lang=english&step=8&LOGINSQL=root&PASSSQL=dbscript_root&IPDEFSERVSQL=db&LOGINUSER=TEST&PASSWORDUSER=TEST&fmgfldr=&NOMYSQL=&sharedconf=&write=Installing+Dbscript+-+8&loginstate=Next+'

HDR="$(mktemp)"
trap 'rm -f "$COOKIE" "$OUT" "$HDR"' EXIT
echo -n "step9 finish ... "
if ! curl -fsS -c "$COOKIE" -b "$COOKIE" -L --max-time 120 \
    -X POST "$BASE_URL/install.php" \
    -d 'lang=english&step=9&LOGINSQL=root&PASSSQL=dbscript_root&IPDEFSERVSQL=db&LOGINUSER=TEST&PASSWORDUSER=TEST&fmgfldr=&NOMYSQL=&sharedconf=&write=Installing+Dbscript+-+9&loginstate=Finish' \
    -o "$OUT" -D "$HDR"; then
    fail "step9 finish"
fi
if grep -qi '^Location:.*login\.php' "$HDR"; then
    echo "OK (redirect login.php)"
else
    grep -E 'Fatal error|login\.php|INST_READY' "$OUT" | head -3 || true
    echo "WARN: no redirect header; checking login manually"
fi

echo -n "login TEST/TEST ... "
if ! curl -fsS -c "$COOKIE" -b "$COOKIE" -L --max-time 60 \
    -X POST "$BASE_URL/login.php" \
    -d 'dbs_log=TEST&dbs_psw=TEST&loginstate=To+enter' \
    -o "$OUT"; then
    fail "login"
fi
if grep -qE 'Fatal error|Uncaught Error|notuser|incorrect' "$OUT"; then
    fail "login response"
fi
if ! grep -q 'editor\.png\|w\.php\|WELCOME\|MNU_2' "$OUT"; then
    echo "WARN: login page may not show main menu"
    grep -E 'dbs_log|A_USR|Fatal|notuser' "$OUT" | head -5
else
    echo "OK"
fi

echo -n "w.php editor ... "
if ! curl -fsS -b "$COOKIE" -L --max-time 60 "$BASE_URL/w.php" -o "$OUT"; then
    fail "w.php GET"
fi
if grep -qE 'Fatal error|Uncaught Error' "$OUT"; then
    fail "w.php"
fi
if grep -qE 'WF_WELCOM|Editor v4|id="edit"|KEY_EDIT' "$OUT"; then
    echo "OK"
else
    grep -E 'login\.php|notright|Fatal|disable' "$OUT" | head -5
    fail "w.php content"
fi

echo -n "r.php reader ... "
if ! curl -fsS -b "$COOKIE" -L --max-time 60 "$BASE_URL/r.php?viewid=.ver&base=0" -o "$OUT"; then
    fail "r.php GET"
fi
if grep -qE 'Fatal error|Uncaught Error' "$OUT"; then
    fail "r.php"
fi
if grep -qE 'Version|\.ver|Fatal|notright' "$OUT"; then
    echo "OK"
else
    grep -E 'login\.php|notright|Fatal' "$OUT" | head -5
    fail "r.php content"
fi

echo -n "admin.php ... "
if ! curl -fsS -b "$COOKIE" -L --max-time 60 "$BASE_URL/admin.php" -o "$OUT"; then
    fail "admin.php GET"
fi
if grep -qE 'Fatal error|Uncaught Error' "$OUT"; then
    fail "admin.php"
fi
if grep -qE 'Admin v4|A_WELC|cmd=note|Self-test|My profile' "$OUT"; then
    echo "OK"
else
    grep -E 'login\.php|notright|Fatal|disable' "$OUT" | head -5
    fail "admin.php content"
fi

echo "ALL OK: install → login → w.php → r.php → admin.php"
