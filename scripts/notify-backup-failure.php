<?php

// Invoked by the private backup runner, never by an HTTP request.
if (PHP_SAPI!=='cli') {http_response_code(404);exit(1);}
umask(0077);
$directory='/home/mitamurafarm/apps/mitamura-production/backups';
if (!is_file($directory.'/alert-enabled')) {echo "BACKUP_ALERT_DISABLED\n";exit(0);}
set_exception_handler(function (Throwable $error) {fwrite(STDERR,'BACKUP_ALERT_FAILED: '.get_class($error).PHP_EOL);exit(1);});
$status=json_decode(file_get_contents($directory.'/daily-status.json'),true,512,JSON_THROW_ON_ERROR);
if (($status['success'] ?? null)!==false) {echo "BACKUP_ALERT_NOT_NEEDED\n";exit(0);}
$finished=new DateTimeImmutable($status['finished_at']);
$stamp=$directory.'/alert-last-sent.json';
$previous=is_file($stamp) ? json_decode(file_get_contents($stamp),true) : [];
if (($previous['run'] ?? '')===$status['finished_at'] || time()-(int)($previous['sent_at'] ?? 0)<86400) {echo "BACKUP_ALERT_RATE_LIMITED\n";exit(0);}
$preview='/home/mitamurafarm/apps/mitamura-preview/current';
require $preview.'/vendor/autoload.php';
$app=require $preview.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error) {fwrite(STDERR,'BACKUP_ALERT_FAILED: '.get_class($error).PHP_EOL);exit(1);});
// Console bootstrap must not serialize the decrypted password into config cache.
$password=app(App\Services\SmtpCredentialStore::class)->password();
if (!$password) throw new RuntimeException('SMTP credential unavailable.');
if (config('farm.email')!=='mitamura.farm@gmail.com' || !in_array('mitamura.farm@gmail.com',config('farm.mail_allowed_recipients'),true)) throw new RuntimeException('Unexpected notification recipient.');
config(['mail.mailers.smtp.password'=>$password]);
$when=$finished->setTimezone(new DateTimeZone('Asia/Tokyo'))->format('Y/m/d H:i:s');
$body="三田村農園の本番DBバックアップが完了しませんでした。\n実行日時：{$when}（日本時間）\n分類：バックアップまたは世代管理の処理失敗\nサーバー内の実行結果を確認してください。顧客情報・認証情報はこの通知に含めていません。";
Illuminate\Support\Facades\Mail::mailer('smtp')->raw($body,function ($message) {
    $message->to('mitamura.farm@gmail.com')->subject('【三田村農園】バックアップ失敗のお知らせ');
});
file_put_contents($stamp.'.tmp',json_encode(['run'=>$status['finished_at'],'sent_at'=>time()],JSON_THROW_ON_ERROR));
rename($stamp.'.tmp',$stamp);
echo "BACKUP_ALERT_ACCEPTED\n";
