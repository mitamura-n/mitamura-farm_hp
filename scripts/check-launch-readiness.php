<?php

// Read-only CLI preflight. Does not authorize or perform publication.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit(1); }
set_exception_handler(function (Throwable $error) { fwrite(STDERR,"LAUNCH_PREFLIGHT_FAILED\n");exit(1); });
$base='/home/mitamurafarm/apps/mitamura-production';
$revision=$argv[1] ?? '';
if ($revision!=='' && !preg_match('/^[a-f0-9]{7,40}$/D',$revision)) exit(2);
$release=$revision==='' ? realpath($base.'/current') : realpath($base.'/releases/'.$revision);
if (!$release || !str_starts_with($release,$base.'/releases/')) throw new RuntimeException();
chdir($release);
require $release.'/vendor/autoload.php';
$app=require $release.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error) { fwrite(STDERR,"LAUNCH_PREFLIGHT_FAILED\n");exit(1); });
if (!app()->environment('production') || config('database.default')!=='mariadb'
    || config('database.connections.mariadb.database')!=='mitamurafarm_prod') throw new RuntimeException();
$ran=Illuminate\Support\Facades\DB::table('migrations')->pluck('migration')->all();
$pending=[];
foreach (glob('database/migrations/*.php') as $file) if (!in_array(basename($file,'.php'),$ran,true)) $pending[]=basename($file,'.php');
$web='/home/mitamurafarm/mitamurafarm.com/public_html';
$result=[
    'release'=>basename($release),
    'production_admin_exists'=>App\Models\User::where('is_admin',true)->exists(),
    'smtp_credential_decryptable'=>app(App\Services\SmtpCredentialStore::class)->password()!==null,
    'smtp_file_private'=>is_file($base.'/shared/storage/app/private/mail-smtp.enc') && (fileperms($base.'/shared/storage/app/private/mail-smtp.enc') & 0077)===0,
    'inquiries_disabled'=>!config('farm.inquiries_enabled'),
    'normal_mail_disabled'=>config('mail.default')==='log',
    'recipient_restriction_enabled'=>(bool)config('farm.restrict_mail_recipients'),
    'search_indexing_disabled'=>!config('seo.indexable'),
    'pending_migrations'=>$pending,
    'old_home_still_present'=>is_file($web.'/index.htm'),
    'preview_entry_present'=>is_file($web.'/preview.mitamurafarm.com/index.php'),
    'legacy_archive_present'=>is_file('/home/mitamurafarm/apps/mitamura-legacy/snapshots/20261020-162513/legacy-original.tar.gz'),
    'legacy_private_view_present'=>is_dir('/home/mitamurafarm/apps/mitamura-preview/shared/storage/app/private/legacy-site'),
];
echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
echo "READ_ONLY_CHECK_NOT_PUBLICATION_APPROVAL\n";
