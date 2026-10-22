#!/usr/bin/env bash
# Invoked over SSH by deploy-production.py. No environment or schema changes.
set -Eeuo pipefail
umask 077
base=/home/mitamurafarm/apps/mitamura-production
public=/home/mitamurafarm/mitamurafarm.com/public_html
mode=${1:?mode required}
revision=${2:?revision required}
expected=${3:?expected release required}
[[ $revision =~ ^[a-f0-9]{40}$ && $expected =~ ^[a-f0-9]{7,40}$ ]]
php=/usr/bin/php8.4
release=$base/releases/$revision
ops=$base/offsite/ops-stage/update-$revision
previous=$(readlink "$base/current")
[[ $previous = "$base/releases/$expected" ]]
test -f "$base/production-http-enabled"
test -f "$public/farm-index.php"
grep -Fq "\$root = '$previous';" "$public/farm-index.php"

if [[ $mode = check ]]; then
    printf 'CURRENT=%s\n' "$expected"
    "$php" -r 'echo "PHP=".PHP_VERSION.PHP_EOL;'
    # The checker comes from stdin, not from the old release which may predate it.
    cd "$previous"
    "$php"
    exit
fi
[[ $mode = deploy || $mode = rollback ]]
exec 8>"$base/routine-deploy.lock"
flock -n 8
# Recheck after acquiring the lock, in case another publisher has just finished.
[[ $(readlink "$base/current") = "$previous" ]]

restore() {
    local target asset
    target=$(cat "$ops/current.before")
    [[ $target =~ ^/home/mitamurafarm/apps/mitamura-production/releases/[a-f0-9]{7,40}$ ]]
    test -d "$target"
    while IFS= read -r asset; do
        if [[ -f "$ops/assets/$asset" ]]; then
            cp "$ops/assets/$asset" "$public/$asset.restore-$revision"
            chmod 644 "$public/$asset.restore-$revision"
            mv -f "$public/$asset.restore-$revision" "$public/$asset"
        fi
    done < "$ops/assets.list"
    cp "$ops/farm-index.before.php" "$public/farm-index.restore-$revision.php"
    chmod 644 "$public/farm-index.restore-$revision.php"
    ln -s "$target" "$base/current.restore-$revision"
    mv -Tf "$base/current.restore-$revision" "$base/current"
    mv -f "$public/farm-index.restore-$revision.php" "$public/farm-index.php"
    echo 'Previous entry and existing assets restored; database unchanged.'
}
if [[ $mode = rollback ]]; then
    test -f "$ops/ready"
    restore
    exit
fi

test ! -e "$release"
test ! -e "$ops"
test -f "$base/source-$revision.tar.gz"
# Backup success must be from this invocation, not a stale previous success.
before=$(stat -c '%y' "$base/backups/daily-status.json")
bash "$base/backup-production-daily.sh"
[[ $(stat -c '%y' "$base/backups/daily-status.json") != "$before" ]]
"$php" -r '$s=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR); if(empty($s["success"]) || empty($s["offsite_verified"])) exit(1);' "$base/backups/daily-status.json"
mkdir -p "$release" "$ops/assets"
tar -xzf "$base/source-$revision.tar.gz" -C "$release"
cmp "$release/composer.lock" "$previous/composer.lock"
ln -s "$base/shared/.env" "$release/.env"
mv "$release/storage" "$release/storage-skeleton"
ln -s "$base/shared/storage" "$release/storage"
cp -a "$previous/vendor" "$release/vendor"
chmod 755 "$release" "$release/bootstrap" "$release/bootstrap/cache"
cd "$previous"
"$php" "$release/scripts/production-health.php" > "$ops/settings.before.json"
cd "$release"
"$php" artisan config:cache > "$ops/config-cache.log" 2>&1
"$php" scripts/production-health.php > "$ops/settings.after.json"
cmp "$ops/settings.before.json" "$ops/settings.after.json"
"$php" artisan view:cache > "$ops/view-cache.log" 2>&1
# Blade compilation checks syntax, but does not submit a form or send mail.
"$php" -r '$s=str_replace("__PRODUCTION_RELEASE__",getcwd(),file_get_contents("deployment/production/farm-index.php.template")); file_put_contents($argv[1],$s);' "$ops/farm-index.ready.php"
"$php" -l "$ops/farm-index.ready.php"
cp "$public/farm-index.php" "$ops/farm-index.before.php"
printf '%s\n' "$previous" > "$ops/current.before"
cp deployment/production/assets.list "$ops/assets.list"
while IFS= read -r asset; do
    # The client validates the tracked asset paths; repeat the boundary check here.
    [[ $asset != /* && $asset != *..* && $asset =~ ^[a-zA-Z0-9_./-]+$ ]]
    [[ $asset =~ ^images/[a-zA-Z0-9_./-]+\.(png|jpg|jpeg|webp|avif|gif|svg|ico)$ || $asset =~ ^[a-zA-Z0-9_-]+\.(css|js)$ || $asset = favicon.ico || $asset = robots.txt ]]
    test -f "public/$asset"
    target="$public/$asset"
    # Never follow existing symlinks, including a symlink in a parent directory.
    [[ $(realpath -m "$target") = "$target" ]]
    if [[ -e "$target" ]]; then
        test -f "$target"
        mkdir -p "$(dirname "$ops/assets/$asset")"
        cp "$target" "$ops/assets/$asset"
    fi
done < "$ops/assets.list"
touch "$ops/ready"
rollback_on_error() {
    trap - ERR
    echo 'Publishing failed. Restoring previous entry and assets.' >&2
    restore
    exit 1
}
trap rollback_on_error ERR
while IFS= read -r asset; do
    install -d -m 755 "$(dirname "$public/$asset")"
    cp "public/$asset" "$public/$asset.next-$revision"
    chmod 644 "$public/$asset.next-$revision"
    mv -f "$public/$asset.next-$revision" "$public/$asset"
done < "$ops/assets.list"
cp "$ops/farm-index.ready.php" "$public/farm-index.next-$revision.php"
chmod 644 "$public/farm-index.next-$revision.php"
ln -s "$release" "$base/current.next-$revision"
mv -Tf "$base/current.next-$revision" "$base/current"
mv -f "$public/farm-index.next-$revision.php" "$public/farm-index.php"
trap - ERR
printf 'PUBLISHED=%s\n' "$revision"
