#!/bin/bash
set -euo pipefail
umask 077
base=/home/mitamurafarm/apps/mitamura-production
exec 9>"$base/backups/daily.lock"
if ! /usr/bin/flock -n 9; then exit 0; fi
status="$base/backups/daily-status.json"
temporary="$status.tmp"
if /usr/bin/php8.4 "$base/current/scripts/backup-production.php" --rotate >"$base/backups/daily-last-run.log" 2>&1; then
  printf '{"finished_at":"%s","success":true}\n' "$(date -Iseconds)" >"$temporary"
else
  printf '{"finished_at":"%s","success":false}\n' "$(date -Iseconds)" >"$temporary"
  mv "$temporary" "$status"
  exit 1
fi
mv "$temporary" "$status"
