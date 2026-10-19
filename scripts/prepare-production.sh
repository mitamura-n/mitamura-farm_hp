#!/bin/bash
set -euo pipefail
umask 077
revision="$1"
case "$revision" in *[!a-f0-9]*|'') echo 'Invalid revision'; exit 1;; esac
base=/home/mitamurafarm/apps/mitamura-production
release="$base/releases/$revision"
php=/usr/bin/php8.4
test -f "$base/shared/.env"
test ! -e "$release"
mkdir "$release"
tar -xzf "$base/source-$revision.tar.gz" -C "$release"
ln -s "$base/shared/.env" "$release/.env"
mv "$release/storage" "$release/storage-skeleton"
ln -s "$base/shared/storage" "$release/storage"
# Reuse exactly the already installed dependency set only if lockfiles match.
cmp "$release/composer.lock" /home/mitamurafarm/apps/mitamura-preview/current/composer.lock
cp -a /home/mitamurafarm/apps/mitamura-preview/current/vendor "$release/vendor"
cd "$release"
"$php" <<'PHP'
<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!app()->environment('production') || config('database.default')!=='mariadb' || config('database.connections.mariadb.database')!=='mitamurafarm_prod' || config('farm.inquiries_enabled') || config('seo.indexable') || config('mail.default')!=='log') throw new RuntimeException('Production safety check failed.');
$db=Illuminate\Support\Facades\DB::connection();
if ((int)$db->selectOne('SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema=DATABASE()')->total!==0) throw new RuntimeException('Database must be empty for initial setup.');
echo "EMPTY_PRODUCTION_DATABASE_CONFIRMED\n";
PHP
"$php" artisan migrate --force
"$php" artisan config:cache
ln -s "$release" "$base/current"
echo "PRIVATE_PRODUCTION_PREPARED=$revision"
# No public document root, HTTP service, SMTP activation or DNS changes.
