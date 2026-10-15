$ErrorActionPreference = 'Stop'
$envPath = Join-Path $PSScriptRoot '.env'
if (!(Test-Path $envPath)) { Copy-Item (Join-Path $PSScriptRoot '.env.example') $envPath }
$content = [IO.File]::ReadAllText($envPath)
$settings = @{APP_NAME='"Mitamura Farm"'; APP_URL='http://127.0.0.1:8000'; APP_LOCALE='ja'; DB_CONNECTION='mariadb'; DB_HOST='127.0.0.1'; DB_PORT='3307'; DB_DATABASE='mitamura_dev'; DB_USERNAME='mitamura'; MAIL_MAILER='log'; SESSION_DRIVER='database'; CACHE_STORE='database'; QUEUE_CONNECTION='sync'}
foreach ($key in $settings.Keys) {
  $pattern = '(?m)^\s*#?\s*' + $key + '=.*$'
  $line = $key + '=' + $settings[$key]
  if ([regex]::IsMatch($content,$pattern)) { $content=[regex]::Replace($content,$pattern,$line) } else { $content += "`n$line`n" }
}
foreach ($key in @('DB_PASSWORD','DEV_DB_ROOT_PASSWORD')) {
  if (![regex]::IsMatch($content,'(?m)^' + $key + '=\S+\s*$')) {
    $password = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(24))
    $pattern = '(?m)^\s*#?\s*' + $key + '=.*$'
    if ([regex]::IsMatch($content,$pattern)) { $content=[regex]::Replace($content,$pattern,$key+'='+$password) } else { $content += "`n$key=$password`n" }
  }
}
[IO.File]::WriteAllText($envPath,$content,[Text.UTF8Encoding]::new($false))
Write-Output 'Local environment configured. Passwords remain in ignored .env.'
