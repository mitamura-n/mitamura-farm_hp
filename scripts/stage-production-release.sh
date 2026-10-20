#!/bin/bash
# Prepare an existing production installation without activating or publishing it.
set -euo pipefail
umask 077
revision="$1"
case "$revision" in *[!a-f0-9]*|'') echo 'Invalid revision'; exit 1;; esac
base=/home/mitamurafarm/apps/mitamura-production
release="$base/releases/$revision"
test -f "$base/shared/.env"
test -d "$base/current/vendor"
test ! -e "$release"
mkdir "$release"
tar -xzf "$base/source-$revision.tar.gz" -C "$release"
cmp "$release/composer.lock" "$base/current/composer.lock"
ln -s "$base/shared/.env" "$release/.env"
mv "$release/storage" "$release/storage-skeleton"
ln -s "$base/shared/storage" "$release/storage"
cp -a "$base/current/vendor" "$release/vendor"
cd "$release"
/usr/bin/php8.4 <<'PHP'
<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!app()->environment('production') || config('database.default')!=='mariadb'
    || config('database.connections.mariadb.database')!=='mitamurafarm_prod'
    || config('farm.inquiries_enabled') || config('seo.indexable') || config('mail.default')!=='log') {
    throw new RuntimeException('Private production safety check failed.');
}
$ran=Illuminate\Support\Facades\DB::table('migrations')->pluck('migration')->all();
foreach (glob('database/migrations/*.php') as $file) {
    if (!in_array(basename($file,'.php'),$ran,true)) throw new RuntimeException('Pending migration; apply separately after review.');
}
$html=view('farm',['products'=>App\Models\Product::orderBy('id')->get()->keyBy('name')])->render();
if (!str_contains($html,'購入希望・お見積もり') || !str_contains($html,'price-estimate')) throw new RuntimeException('Production render check failed.');
echo "PRIVATE_PRODUCTION_RENDER_OK\n";
PHP
printf 'PRODUCTION_RELEASE_STAGED=%s\n' "$revision"
# No current symlink change, migration, SMTP activation, web files or DNS changes.
