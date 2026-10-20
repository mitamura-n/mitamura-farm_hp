#!/bin/bash
set -euo pipefail
umask 077
base=/home/mitamurafarm/apps/mitamura-production
exec 9>"$base/backups/daily.lock"
if ! /usr/bin/flock -n 9; then exit 0; fi
status="$base/backups/daily-status.json"
temporary="$status.tmp"
fail_backup() {
  printf '{"finished_at":"%s","success":false,"failure_stage":"%s"}\n' "$(date -Iseconds)" "$1" >"$temporary"
  mv "$temporary" "$status"
  if [ -f "$base/backups/alert-enabled" ]; then
    /usr/bin/php8.4 "$base/current/scripts/notify-backup-failure.php" >"$base/backups/alert-last-run.log" 2>&1 || true
  fi
  exit 1
}
if ! /usr/bin/php8.4 "$base/current/scripts/backup-production.php" --rotate >"$base/backups/daily-last-run.log" 2>&1; then
  fail_backup local_backup
fi
if [ -f "$base/offsite/enabled" ]; then
  snapshot=$(sed -n 's/^PRODUCTION_BACKUP=//p' "$base/backups/daily-last-run.log")
  if ! /bin/bash "$base/backup-production-offsite.sh" "$snapshot" >"$base/offsite/last-run.log" 2>&1; then
    fail_backup offsite_transfer
  fi
  offsite=true
else
  offsite=false
fi
printf '{"finished_at":"%s","success":true,"offsite_verified":%s}\n' "$(date -Iseconds)" "$offsite" >"$temporary"
mv "$temporary" "$status"
