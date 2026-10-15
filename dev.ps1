param([ValidateSet('serve','test','artisan','composer','db-start','db-stop','check')][string]$Task = 'serve', [string[]]$TaskArgs = @())
$ErrorActionPreference = 'Stop'
$phpPath = Join-Path $PSScriptRoot '../../.tools/php84/php.exe'
$composerPath = Join-Path $PSScriptRoot '../../.tools/composer.phar'
$previousPath = $env:PATH
$env:PATH = (Split-Path $phpPath) + ';' + $env:PATH
Push-Location $PSScriptRoot
try {
  switch ($Task) {
    'serve' { & $phpPath artisan serve --host=127.0.0.1 --port=8000 --tries=1 }
    'test' { & $phpPath artisan test @TaskArgs }
    'artisan' { & $phpPath artisan @TaskArgs }
    'composer' { & $phpPath $composerPath @TaskArgs }
    'db-start' { docker compose up -d --wait }
    'db-stop' { docker compose stop }
    'check' {
      & $phpPath -v
      if ($LASTEXITCODE -ne 0) { throw 'PHP check failed' }
      & $phpPath $composerPath check-platform-reqs
      if ($LASTEXITCODE -ne 0) { throw 'PHP platform requirements check failed' }
      & $phpPath artisan about
    }
  }
  if ($LASTEXITCODE -ne 0) { throw "Development command failed: $LASTEXITCODE" }
} finally { Pop-Location; $env:PATH = $previousPath }
