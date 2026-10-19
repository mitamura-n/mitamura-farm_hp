<?php

if (PHP_SAPI!=='cli') { http_response_code(404);exit(1); }
require dirname(__DIR__).'/vendor/autoload.php';
$app=require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!app()->environment('production') || config('database.default')!=='mariadb' || config('database.connections.mariadb.database')!=='mitamurafarm_prod') throw new RuntimeException('Unexpected environment.');
umask(0077);
$base='/home/mitamurafarm/apps/mitamura-production';
$directory=$base.'/backups/snapshot-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4));
if (!mkdir($directory,0700)) throw new RuntimeException('Backup directory failed.');
$credentials=$directory.'/client.cnf';
$quote=fn ($s)=>'"'.str_replace(["\\",'"',"\n","\r"],["\\\\",'\\"','\\n','\\r'],(string)$s).'"';
$db=config('database.connections.mariadb');
try {
    file_put_contents($credentials,"[client]\nhost=localhost\nuser=mitamurafarm_prd\npassword=".$quote($db['password'])."\n");
    chmod($credentials,0600);
    $process=proc_open(['/usr/bin/mysqldump','--defaults-extra-file='.$credentials,'--single-transaction','--quick','--skip-lock-tables','--hex-blob','mitamurafarm_prod'],[
        0=>['file','/dev/null','r'],1=>['file',$directory.'/database.sql','w'],2=>['file',$directory.'/dump-error.log','w'],
    ],$pipes);
    if (!is_resource($process) || proc_close($process)!==0 || filesize($directory.'/database.sql')===0) throw new RuntimeException('Database dump failed; inspect private diagnostics.');
    if (!copy($base.'/shared/.env',$directory.'/environment.env')) throw new RuntimeException('Key backup failed.');
    chmod($directory.'/environment.env',0600);
    file_put_contents($directory.'/manifest.json',json_encode([
        'created_at'=>date(DATE_ATOM),'database'=>'mitamurafarm_prod','sha256'=>hash_file('sha256',$directory.'/database.sql'),
        'restore_verified'=>false,
    ],JSON_PRETTY_PRINT));
    echo 'PRODUCTION_BACKUP='.basename($directory).PHP_EOL;
    echo "RESTORE_VERIFICATION_PENDING\n";
} finally { if (is_file($credentials)) unlink($credentials); }
