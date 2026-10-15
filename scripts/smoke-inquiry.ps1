$ErrorActionPreference = 'Stop'
$site = 'http://127.0.0.1:8000'
$webSession = [Microsoft.PowerShell.Commands.WebRequestSession]::new()
$page = Invoke-WebRequest -Uri $site -WebSession $webSession -UseBasicParsing -TimeoutSec 60
$csrf = [regex]::Match($page.Content, 'name="_token" value="([^"]+)"').Groups[1].Value
$submission = [regex]::Match($page.Content, 'id="submission-key" value="([^"]+)"').Groups[1].Value
if (!$csrf -or !$submission) { throw 'Form tokens not found' }
$payload = @{ submission_key=$submission; name='開発動作確認'; email='inquiry-smoke@example.com'; kind='注文の相談'; items=@(@{product='白米';quantity='10';milling=$null},@{product='米粉';quantity='1';milling=$null}); region='福井県'; message='開発環境の受付確認。実際の注文ではありません。'; privacy=$true }
$json = $payload | ConvertTo-Json -Depth 5 -Compress
$headers = @{Accept='application/json'}
$blocked = Invoke-WebRequest -Uri "$site/inquiries" -Method Post -WebSession $webSession -Headers $headers -ContentType 'application/json; charset=utf-8' -Body $json -SkipHttpErrorCheck -TimeoutSec 60
if ($blocked.StatusCode -ne 419) { throw "CSRF check unexpected status: $($blocked.StatusCode)" }
$headers['X-CSRF-TOKEN'] = $csrf
$first = Invoke-WebRequest -Uri "$site/inquiries" -Method Post -WebSession $webSession -Headers $headers -ContentType 'application/json; charset=utf-8' -Body $json -SkipHttpErrorCheck -TimeoutSec 60
if ($first.StatusCode -ne 201) { throw "Intake check failed: HTTP $($first.StatusCode)" }
$accepted = $first.Content | ConvertFrom-Json
$retry = Invoke-WebRequest -Uri "$site/inquiries" -Method Post -WebSession $webSession -Headers $headers -ContentType 'application/json; charset=utf-8' -Body $json -SkipHttpErrorCheck -TimeoutSec 60
$repeated = $retry.Content | ConvertFrom-Json
if ($retry.StatusCode -ne 200 -or $accepted.receipt -ne $repeated.receipt) { throw 'Duplicate prevention failed' }
[pscustomobject]@{csrf='PASS';intake='PASS';duplicate='PASS';receipt=$accepted.receipt;development=$accepted.development} | ConvertTo-Json
