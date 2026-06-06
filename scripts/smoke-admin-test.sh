#!/bin/bash
# Smoke admin.php?cmd=test (requires installed site + TEST/TEST user)
set -euo pipefail

BASE_URL="${1:-http://127.0.0.1:8080}"
COOKIE="$(mktemp)"
OUT="$(mktemp)"
trap 'rm -f "$COOKIE" "$OUT"' EXIT

curl -fsS -c "$COOKIE" -b "$COOKIE" -L -X POST "$BASE_URL/login.php" \
  -d 'dbs_log=TEST&dbs_psw=TEST&loginstate=To+enter' -o /dev/null

curl -fsS -b "$COOKIE" -L --max-time 120 "$BASE_URL/admin.php?cmd=test" -o "$OUT"

if grep -qE 'Fatal error|Uncaught Error|Uncaught TypeError' "$OUT"; then
  echo "FAIL: admin.php?cmd=test"
  grep -E 'Fatal error|Uncaught' "$OUT" | head -10
  exit 1
fi

if grep -q 'A_T_ALLERR\|=============================' "$OUT"; then
  echo "OK: admin.php?cmd=test completed"
  grep -E 'A_T_ALLERR|A_T_CRIT|A_T_NOCRIT|A_T_FIXED' "$OUT" | head -5 || true
  exit 0
fi

echo "WARN: unexpected test output"
head -c 600 "$OUT"
echo ""
exit 1
