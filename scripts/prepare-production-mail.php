<?php

// CLI only. Supply the existing SMTP password through stdin, never argv or files.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit(1); }
umask(0077);
$handler = function (Throwable $error) { fwrite(STDERR, "PRODUCTION_MAIL_PREPARATION_FAILED\n"); exit(1); };
set_exception_handler($handler);
$base = '/home/mitamurafarm/apps/mitamura-production/current';
chdir($base);
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler($handler);
if (!app()->environment('production') || config('database.default') !== 'mariadb'
    || config('database.connections.mariadb.database') !== 'mitamurafarm_prod'
    || config('farm.inquiries_enabled') || config('seo.indexable') || config('mail.default') !== 'log'
    || config('mail.mailers.smtp.host') !== 'sv17015.xserver.jp'
    || (int) config('mail.mailers.smtp.port') !== 465
    || config('mail.mailers.smtp.scheme') !== 'smtps'
    || config('mail.mailers.smtp.username') !== 'info@mitamurafarm.com'
    || config('mail.from.address') !== 'info@mitamurafarm.com'
    || !config('farm.restrict_mail_recipients')
    || config('farm.mail_allowed_recipients') !== ['mitamura.farm@gmail.com']
    || !empty(config('mail.mailers.smtp.password'))
    || !App\Models\User::where('is_admin', true)->exists()) throw new RuntimeException('Unsafe environment.');
$data = json_decode(stream_get_contents(STDIN, 4096), true, 512, JSON_THROW_ON_ERROR);
$password = $data['password'] ?? null;
if (!is_string($password) || strlen($password) < 8 || strlen($password) > 45) throw new RuntimeException('Invalid credential.');
app(App\Services\SmtpConnectionCheck::class)->check($password);
$store = app(App\Services\SmtpCredentialStore::class);
$store->save($password);
if (!hash_equals($password, $store->password() ?? '')) throw new RuntimeException('Readback failed.');
echo "PRODUCTION_SMTP_AUTH_AND_ENCRYPTED_STORAGE_OK\n";
config(['mail.mailers.smtp.password'=>$password]);
Illuminate\Support\Facades\Mail::mailer('smtp')->raw(
    '三田村農園の本番環境からのメール送信確認です。実際の注文や障害の通知ではありません。サイト公開・注文受付は開始していません。',
    function ($message) {
        $message->to('mitamura.farm@gmail.com')->subject('【三田村農園】本番メール送信テスト');
    }
);
echo "PRODUCTION_MAIL_TEST_ACCEPTED\n";
echo "NORMAL_MAIL_REMAINS_LOG_AND_INQUIRIES_DISABLED\n";
