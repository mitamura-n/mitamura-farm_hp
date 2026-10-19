<?php

// Xserver preview only. Never overwrite the active database or print its contents.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit(1); }
umask(0077);
$base='/home/mitamurafarm/apps/mitamura-preview';
$source=$base.'/shared/database.sqlite';
if (!is_file($source) || !is_dir($base.'/backups')) throw new RuntimeException('Preview paths missing.');
$directory=$base.'/backups/verified-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4));
if (!mkdir($directory,0700)) throw new RuntimeException('Backup directory creation failed.');
$snapshot=$directory.'/database.sqlite';
$restore=$directory.'/restore-check.sqlite';
try {
    $db=new SQLite3($source,SQLITE3_OPEN_READONLY);
    $db->busyTimeout(15000);
    $target=new SQLite3($snapshot);
    // Use SQLite's online backup API; supports the older SQLite on Xserver.
    if (!$db->backup($target)) throw new RuntimeException('SQLite snapshot failed.');
    $target->close();
    $db->close();
    chmod($snapshot,0600);
    if (!copy($snapshot,$restore)) throw new RuntimeException('Restore copy failed.');
    $check=new PDO('sqlite:'.$restore,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    if ($check->query('PRAGMA integrity_check')->fetchColumn() !== 'ok') throw new RuntimeException('Integrity check failed.');
    if ($check->query('PRAGMA foreign_key_check')->fetch() !== false) throw new RuntimeException('Foreign key check failed.');
    foreach (['users','products','inquiries','migrations'] as $table) {
        $check->query('SELECT COUNT(*) FROM "'.$table.'"')->fetchColumn();
    }
    $check=null;
    if (!hash_equals(hash_file('sha256',$snapshot),hash_file('sha256',$restore))) throw new RuntimeException('Restore hash mismatch.');
    // The key and encrypted SMTP storage are needed in addition to the DB.
    if (!copy($base.'/shared/.env',$directory.'/environment.env')) throw new RuntimeException('Private config backup failed.');
    chmod($directory.'/environment.env',0600);
    $credential=$base.'/shared/storage/app/private/mail-smtp.enc';
    if (is_file($credential)) {
        if (!copy($credential,$directory.'/mail-smtp.enc')) throw new RuntimeException('Encrypted credential backup failed.');
        chmod($directory.'/mail-smtp.enc',0600);
    }
    file_put_contents($directory.'/verification.txt',"SQLite integrity / foreign keys / application tables / restored file hash: OK\n".date(DATE_ATOM)."\n");
    echo 'BACKUP_VERIFIED='.basename($directory).PHP_EOL;
    echo "ACTIVE_DATABASE_UNCHANGED\n";
} finally {
    $check=null;
    // Only remove the temporary restore file generated in this invocation.
    if (is_file($restore)) unlink($restore);
}
