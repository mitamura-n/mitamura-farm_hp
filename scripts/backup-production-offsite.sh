#!/bin/bash
# Explicit snapshot only; scheduling waits for production OAuth configuration.
set -euo pipefail
umask 077
base=/home/mitamurafarm/apps/mitamura-production
snapshot="${1:-}"
if [[ ! "$snapshot" =~ ^snapshot-[0-9]{8}-[0-9]{6}-[a-f0-9]{8}$ ]]; then
  echo OFFSITE_INVALID_SNAPSHOT >&2
  exit 1
fi
exec 9>"$base/offsite/transfer.lock"
/usr/bin/flock -n 9 || exit 1
source_dir="$base/backups/$snapshot"
for file in database.sql environment.env manifest.json; do
  test -f "$source_dir/$file" && test ! -L "$source_dir/$file"
done
test ! -L "$source_dir"
files=(database.sql environment.env manifest.json)
if [ -f "$source_dir/mail-smtp.enc" ]; then
  test ! -L "$source_dir/mail-smtp.enc"
  files+=(mail-smtp.enc)
fi
/usr/bin/php8.4 -r '
  $path=$argv[1];
  $manifest=json_decode(file_get_contents($path."/manifest.json"),true,512,JSON_THROW_ON_ERROR);
  if (($manifest["database"]??null)!=="mitamurafarm_prod" ||
      !hash_equals($manifest["sha256"]??"",hash_file("sha256",$path."/database.sql"))) exit(1);
  foreach (($manifest["files_sha256"]??[]) as $file=>$hash) {
    if (!in_array($file,["database.sql","environment.env","mail-smtp.enc"],true) || !is_string($hash) ||
        !is_file($path."/".$file) || is_link($path."/".$file) || !hash_equals($hash,hash_file("sha256",$path."/".$file))) exit(1);
  }
  if (is_file($path."/mail-smtp.enc") && !isset($manifest["files_sha256"]["mail-smtp.enc"])) exit(1);
' "$source_dir"
temporary=$(mktemp "$base/offsite/transfer-XXXXXXXX.age")
trap 'rm -f -- "$temporary"' EXIT
tar -czf - -C "$source_dir" "${files[@]}" |
  "$base/tools/age" -R "$base/offsite/recipient.txt" >"$temporary"
name="$snapshot.tar.gz.age"
# Restrict operation to the app-created folder. Never sync or delete remote data.
options=(--config "$base/offsite/rclone.conf" --drive-root-folder-id 1BKsguyelct7NDmBENVchxPuNCDDAoB4S)
"$base/tools/rclone" copyto "$temporary" "farmDrive:$name" "${options[@]}" --immutable
local_hash=$(md5sum "$temporary" | cut -d ' ' -f 1)
remote_hash=$("$base/tools/rclone" md5sum "farmDrive:$name" "${options[@]}" | cut -d ' ' -f 1)
test "$local_hash" = "$remote_hash"
printf 'OFFSITE_TRANSFER_VERIFIED=%s\n' "$snapshot"
