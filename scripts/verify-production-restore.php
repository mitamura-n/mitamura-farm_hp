<?php

if (PHP_SAPI!=='cli') {http_response_code(404);exit(1);}
require dirname(__DIR__).'/vendor/autoload.php';
$app=require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error) {fwrite(STDERR,'RESTORE_FAILED: '.get_class($error).PHP_EOL);exit(1);});
if (!app()->environment('production') || config('database.default')!=='mariadb' || config('database.connections.mariadb.database')!=='mitamurafarm_prod') throw new RuntimeException('Unexpected environment.');
umask(0077);
$base='/home/mitamurafarm/apps/mitamura-production/backups';
$name=$argv[1] ?? '';
if (!preg_match('/^snapshot-[0-9]{8}-[0-9]{6}-[a-f0-9]{8}$/D',$name)) throw new RuntimeException('Invalid backup ID.');
$dir=$base.'/'.$name;
$manifest=json_decode(file_get_contents($dir.'/manifest.json'),true,512,JSON_THROW_ON_ERROR);
if (!hash_equals($manifest['sha256'],hash_file('sha256',$dir.'/database.sql'))) throw new RuntimeException('Backup hash mismatch.');
$sql=file_get_contents($dir.'/database.sql');
if (preg_match('/^\s*(?:USE\s|CREATE\s+DATABASE)/mi',$sql)) throw new RuntimeException('Database-qualified dump is not allowed.');
$settings=config('database.connections.mariadb');
$options=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION];
$source=new PDO('mysql:host=localhost;dbname=mitamurafarm_prod;charset=utf8mb4',$settings['username'],$settings['password'],$options);
$target=new PDO('mysql:host=localhost;dbname=mitamurafarm_chk;charset=utf8mb4',$settings['username'],$settings['password'],$options);
$resume=($argv[2] ?? '')==='--resume-canary';
if (!$resume && (int)$target->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()')->fetchColumn()!==0) throw new RuntimeException('Restore DB must be empty.');
$credential=$dir.'/restore-client.cnf';
$quote=fn($s)=>'"'.str_replace(["\\",'"',"\n","\r"],["\\\\",'\\"','\\n','\\r'],(string)$s).'"';
$run=function(array $command,string $input,string $output) use ($dir) {
    $process=proc_open($command,[0=>['file',$input,'r'],1=>['file',$output,'w'],2=>['file',$dir.'/restore-error.log','a']],$pipes);
    if (!is_resource($process) || proc_close($process)!==0) throw new RuntimeException('Restore tool failed; private diagnostics retained.');
};
$digest=function(PDO $db,string $table) {
    if (!preg_match('/^[a-z_]+$/D',$table)) throw new RuntimeException('Unexpected table name.');
    $rows=$db->query('SELECT * FROM `'.$table.'`')->fetchAll(PDO::FETCH_ASSOC);
    $encoded=array_map(fn($row)=>json_encode($row,JSON_THROW_ON_ERROR),$rows);
    sort($encoded,SORT_STRING);
    return hash('sha256',implode("\n",$encoded));
};
try {
    file_put_contents($credential,"[client]\nhost=localhost\nuser=mitamurafarm_prd\npassword=".$quote($settings['password'])."\n");
    chmod($credential,0600);
    if (!$resume) $run(['/usr/bin/mysql','--defaults-extra-file='.$credential,'--default-character-set=utf8mb4','mitamurafarm_chk'],$dir.'/database.sql',$dir.'/restore-output.log');
    $tables=$source->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $restored=$target->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    if ($resume) $restored=array_values(array_diff($restored,['restore_canary']));
    sort($tables);sort($restored);
    if ($tables!==$restored) throw new RuntimeException('Restored table list mismatch.');
    foreach ($tables as $table) {
        if (!hash_equals($digest($source,$table),$digest($target,$table))) throw new RuntimeException('Restored data mismatch.');
        if ($target->query('CHECK TABLE `'.$table.'`')->fetchAll(PDO::FETCH_ASSOC)[0]['Msg_text']!=='OK') throw new RuntimeException('Table check failed.');
    }
    // Independently exercise encrypted backup recovery in the isolated DB only.
    if ($resume) $target->exec('DROP TABLE IF EXISTS restore_canary');
    $target->exec('CREATE TABLE restore_canary (id INT PRIMARY KEY, ciphertext TEXT NOT NULL)');
    $canary='restore-verification-'.bin2hex(random_bytes(16));
    $cipher=Illuminate\Support\Facades\Crypt::encryptString($canary);
    $target->prepare('INSERT INTO restore_canary VALUES (1,?)')->execute([$cipher]);
    $run(['/usr/bin/mysqldump','--defaults-extra-file='.$credential,'--single-transaction','--skip-lock-tables','mitamurafarm_chk','restore_canary'],'/dev/null',$dir.'/encrypted-canary.sql');
    $target->exec('DELETE FROM restore_canary WHERE id=1');
    $run(['/usr/bin/mysql','--defaults-extra-file='.$credential,'--default-character-set=utf8mb4','mitamurafarm_chk'],$dir.'/encrypted-canary.sql',$dir.'/canary-output.log');
    // Read the backed-up APP_KEY rather than assuming the live key is recoverable.
    $env=Dotenv\Dotenv::parse(file_get_contents($dir.'/environment.env'));
    $key=$env['APP_KEY'];
    if (str_starts_with($key,'base64:')) $key=base64_decode(substr($key,7),true);
    if (!is_string($key)) throw new RuntimeException('Invalid backup key encoding.');
    $encrypter=new Illuminate\Encryption\Encrypter($key,config('app.cipher'));
    $recovered=$encrypter->decryptString($target->query('SELECT ciphertext FROM restore_canary WHERE id=1')->fetchColumn());
    if (!hash_equals($canary,$recovered)) throw new RuntimeException('Restored key decryption failed.');
    $target->exec('DROP TABLE restore_canary');
    foreach ($tables as $table) if (!hash_equals($digest($source,$table),$digest($target,$table))) throw new RuntimeException('Post-check mismatch.');
    $manifest['restore_verified']=true;
    $manifest['restore_verified_at']=date(DATE_ATOM);
    $manifest['restore_database']='mitamurafarm_chk';
    $manifest['encrypted_canary_restored']=true;
    file_put_contents($dir.'/manifest.json',json_encode($manifest,JSON_PRETTY_PRINT));
    echo 'RESTORE_VERIFIED_TABLES='.count($tables).PHP_EOL;
    echo "ALL_TABLE_DATA_MATCH; ENCRYPTED_BACKUP_KEY_RECOVERY_OK\nPRODUCTION_DATABASE_UNCHANGED\n";
} finally { if (is_file($credential)) unlink($credential); }
