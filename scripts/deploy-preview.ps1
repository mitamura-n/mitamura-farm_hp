param(
    [string]$KeyDirectory = (Join-Path $PSScriptRoot '../../../.local-secrets/xserver-preview')
)
$ErrorActionPreference = 'Stop'
$repo = Split-Path $PSScriptRoot
$keyFolder = (Resolve-Path -LiteralPath $KeyDirectory).Path
$keyFile = Join-Path $keyFolder 'id_rsa'
$knownHosts = Join-Path $keyFolder 'known_hosts'
if (!(Test-Path -LiteralPath $keyFile) -or !(Test-Path -LiteralPath $knownHosts)) { throw 'SSH key or verified known_hosts is missing.' }
$sshOptions = @('-i', $keyFile, '-o', 'BatchMode=yes', '-o', 'IdentitiesOnly=yes', '-o', 'StrictHostKeyChecking=yes', '-o', "UserKnownHostsFile=$knownHosts")
$remote = 'mitamurafarm@sv17015.xserver.jp'
$remoteBase = '/home/mitamurafarm/apps/mitamura-preview'
Push-Location $repo
try {
    $pending = git status --porcelain
    if ($LASTEXITCODE -ne 0) { throw 'Git check failed.' }
    if ($pending) { throw 'Commit and test all source changes before deployment.' }
    $revision = git rev-parse --short HEAD
    if ($LASTEXITCODE -ne 0) { throw 'Git revision check failed.' }
    $releaseId = (Get-Date -Format 'yyyyMMdd-HHmmss') + '-' + $revision
    $archiveFile = Join-Path $keyFolder "source-$releaseId.tar.gz"
    git archive --format=tar.gz "--output=$archiveFile" HEAD
    if ($LASTEXITCODE -ne 0) { throw 'Archive failed.' }
    & scp @sshOptions -P 10022 $archiveFile "${remote}:$remoteBase/upload-$releaseId.tar.gz"
    if ($LASTEXITCODE -ne 0) { throw 'Archive upload failed.' }
    & scp @sshOptions -P 10022 (Join-Path $PSScriptRoot 'deploy-preview.sh') "${remote}:$remoteBase/deploy-preview.sh"
    if ($LASTEXITCODE -ne 0) { throw 'Deployment script upload failed.' }
    & ssh @sshOptions -p 10022 $remote "bash $remoteBase/deploy-preview.sh $releaseId"
    if ($LASTEXITCODE -ne 0) { throw 'Deployment failed. Inspect logs before retrying with a new release ID.' }
    Write-Output "Preview deployed: https://preview.mitamurafarm.com/ ($releaseId)"
} finally { Pop-Location }
