#!/bin/bash
# Prepare a disabled, fixed-host verification entry. Does not add DNS/SSL or enable it.
set -euo pipefail
umask 077
revision="$1"
case "$revision" in *[!a-f0-9]*|'') exit 1;; esac
base=/home/mitamurafarm/apps/mitamura-production
release="$base/releases/$revision"
web=/home/mitamurafarm/mitamurafarm.com/public_html/check.mitamurafarm.com
test -f "$release/vendor/autoload.php"
test ! -e "$base/http-check-enabled"
test ! -e "$web/index.php"
mkdir -p "$web"
chmod 755 "$web"
for asset in "$release/public/"*; do
  [ "$(basename "$asset")" = index.php ] && continue
  cp -pR "$asset" "$web/"
done
cp "$release/public/.htaccess" "$web/.htaccess"
cat >> "$web/.htaccess" <<'HT'

DirectoryIndex index.php
Options -Indexes
<IfModule mod_headers.c>
Header always set X-Robots-Tag "noindex, nofollow, noarchive"
Header always set Cache-Control "no-store, private"
</IfModule>
SetEnvIf Request_URI ".*" Ngx_Cache_NoCacheMode=on
HT
sed "s|__PRODUCTION_RELEASE__|$release|g" "$release/scripts/production-check-index.php.template" > "$web/index.php"
/usr/bin/php8.4 -l "$web/index.php"
chmod -R u+rwX,go+rX "$web"
echo "PRODUCTION_CHECK_PREPARED_DISABLED=$revision"
