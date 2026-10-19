#!/bin/bash
set -euo pipefail
umask 077
release_id="$1"
case "$release_id" in *[!a-zA-Z0-9-]*|'') echo 'Invalid release'; exit 1;; esac
base=/home/mitamurafarm/apps/mitamura-preview
web=/home/mitamurafarm/mitamurafarm.com/public_html/preview.mitamurafarm.com
release="$base/releases/$release_id"
php=/usr/bin/php8.4
mkdir -p "$base/releases" "$base/shared/storage/app/private" "$base/shared/storage/app/public" "$base/shared/storage/framework/cache/data" "$base/shared/storage/framework/sessions" "$base/shared/storage/framework/views" "$base/shared/storage/logs" "$base/backups"
if [ -e "$release" ]; then echo 'Release already exists'; exit 1; fi
mkdir "$release"
tar -xzf "$base/upload-$release_id.tar.gz" -C "$release"
if [ ! -f "$base/shared/.env" ]; then
  PREVIEW_BASE="$base" "$php" <<'PHP'
<?php
$base=getenv('PREVIEW_BASE');
$env='APP_NAME="Mitamura Farm Preview"'.PHP_EOL.'APP_ENV=staging'.PHP_EOL.'APP_KEY=base64:'.base64_encode(random_bytes(32)).PHP_EOL;
$env.="APP_DEBUG=false
APP_URL=https://preview.mitamurafarm.com
APP_LOCALE=ja
APP_FALLBACK_LOCALE=ja
INQUIRIES_ENABLED=true
DB_CONNECTION=sqlite
DB_DATABASE=$base/shared/database.sqlite
DB_FOREIGN_KEYS=true
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_COOKIE=mitamura_preview_session
CACHE_STORE=database
QUEUE_CONNECTION=sync
MAIL_MAILER=log
MAIL_FROM_ADDRESS=preview@example.com
MAIL_FROM_NAME=Mitamura-Farm-Preview
LOG_CHANNEL=single
LOG_LEVEL=warning
";
file_put_contents($base.'/shared/.env',$env);
chmod($base.'/shared/.env',0600);
PHP
fi
if [ ! -e "$base/shared/database.sqlite" ]; then touch "$base/shared/database.sqlite"; fi
ln -s "$base/shared/.env" "$release/.env"
# Archive has only the storage placeholder files, no real runtime data.
if [ -d "$release/storage" ] && [ ! -L "$release/storage" ]; then mv "$release/storage" "$release/storage-skeleton"; fi
ln -s "$base/shared/storage" "$release/storage"
chmod 711 /home/mitamurafarm/apps "$base" "$base/releases" "$release"
chmod -R u+rwX,go-rwx "$base/shared"
cd "$release"
"$php" /usr/bin/composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
"$php" /usr/bin/composer check-platform-reqs --no-dev
# Take a DB backup before any migration, including later updates.
cp "$base/shared/database.sqlite" "$base/backups/database-$release_id.sqlite"
"$php" artisan migrate --force
"$php" artisan config:cache
# Do not cache views: a shared compiled-view cache would interfere with rollback.
"$php" artisan about --only=environment
# Check Laravel's protected root without bypassing or changing HTTP authentication.
"$php" <<'PHP'
<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$request=Illuminate\Http\Request::create('https://preview.mitamurafarm.com/');
$kernel=$app->make(Illuminate\Contracts\Http\Kernel::class);
$response=$kernel->handle($request);
if (config('farm.preview_require_login')) {
 if ($response->getStatusCode()!==302 || !str_contains($response->headers->get('Location',''),'/admin/login')) {fwrite(STDERR,'Preview gate check failed');exit(1);}
 echo "Laravel preview gate: login required\n";
} else {
 if ($response->getStatusCode()!==200 || !str_contains($response->getContent(),'product-master')) {fwrite(STDERR,'Laravel root check failed');exit(1);}
 echo "Laravel root render: 200 OK\n";
}
$kernel->terminate($request,$response);
PHP
# Snapshot the existing preview files once per release. The old main website is untouched.
tar -czf "$base/backups/public-$release_id.tar.gz" -C "$web" .
if [ ! -e "$base/shared/basic-auth.htaccess" ]; then cp "$web/.htaccess" "$base/shared/basic-auth.htaccess"; fi
if ! grep -qi 'AuthType BASIC' "$base/shared/basic-auth.htaccess"; then echo 'Basic auth missing'; exit 1; fi
# Make only public assets readable by the web server.
chmod -R u+rwX,go+rX "$release/public"
for asset in "$release/public/"*; do
  [ "$(basename "$asset")" = index.php ] && continue
  cp -pR "$asset" "$web/"
done
gate=$("$php" -r 'require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo config("farm.preview_require_login") ? "yes" : "no";')
# Activate the verified application before removing the old Basic auth layer.
ln -s "$release" "$base/current-next-$release_id"
mv -Tf "$base/current-next-$release_id" "$base/current"
if [ "$gate" = yes ]; then
  cat "$release/public/.htaccess" > "$web/.htaccess"
else
  cat "$base/shared/basic-auth.htaccess" "$release/public/.htaccess" > "$web/.htaccess"
fi
cat >> "$web/.htaccess" <<'HT'

Options -Indexes
<IfModule mod_headers.c>
Header always set X-Robots-Tag "noindex, nofollow, noarchive"
Header always set Cache-Control "no-store, private"
</IfModule>
SetEnvIf Request_URI ".*" Ngx_Cache_NoCacheMode=on
HT
# Front controller points to the private app; never expose .env, vendor or storage.
cat > "$web/index.php" <<'PHP'
<?php
use Illuminate\Http\Request;
define('LARAVEL_START', microtime(true));
$root='__APP_RELEASE__';
if (file_exists($maintenance=$root.'/storage/framework/maintenance.php')) require $maintenance;
require $root.'/vendor/autoload.php';
$app=require_once $root.'/bootstrap/app.php';
$app->handleRequest(Request::capture());
PHP
# Pin the release path to avoid PHP-FPM realpath cache keeping the old symlink target.
sed -i "s|__APP_RELEASE__|$release|g" "$web/index.php"
chmod 644 "$web/index.php" "$web/.htaccess"
if [ -f "$web/index.html" ]; then mv "$web/index.html" "$base/backups/default-index-$release_id.html"; fi
echo "PREVIEW_DEPLOYED=$release_id"
echo "PREVIEW_LOGIN_GATE=$gate"
echo "MAIL_TRANSPORT=log"
