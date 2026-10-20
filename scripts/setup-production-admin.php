<?php

if (PHP_SAPI !== 'cli') { http_response_code(404); exit(1); }
umask(0077);
set_exception_handler(function (Throwable $error) { fwrite(STDERR, "PRODUCTION_ADMIN_SETUP_FAILED\n"); exit(1); });
$operation = $argv[1] ?? '';
if (!in_array($operation, ['status', 'create'], true)) throw new RuntimeException('Invalid operation.');
$base = '/home/mitamurafarm/apps/mitamura-production/current';
chdir($base);
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
// Bootstrap must not replace the safe exception handler with diagnostic output.
set_exception_handler(function (Throwable $error) { fwrite(STDERR, "PRODUCTION_ADMIN_SETUP_FAILED\n"); exit(1); });
if (!app()->environment('production') || config('database.default') !== 'mariadb'
    || config('database.connections.mariadb.database') !== 'mitamurafarm_prod'
    || config('farm.inquiries_enabled') || config('seo.indexable') || config('mail.default') !== 'log') {
    throw new RuntimeException('Unsafe environment.');
}
if ($operation === 'status') {
    echo json_encode(['configured'=>App\Models\User::where('is_admin', true)->exists()]);
    exit(0);
}
$data = json_decode(stream_get_contents(STDIN, 4096), true, 512, JSON_THROW_ON_ERROR);
$email = $data['email'] ?? null;
$password = $data['password'] ?? null;
if (!is_string($email) || strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || !is_string($password) || mb_strlen($password) < 12 || strlen($password) > 72) throw new RuntimeException('Invalid input.');
Illuminate\Support\Facades\DB::transaction(function () use ($email, $password) {
    $db = Illuminate\Support\Facades\DB::class;
    $db::table('cache')->insertOrIgnore(['key'=>'production-admin-setup-lock', 'value'=>'0', 'expiration'=>2147483647]);
    $db::table('cache')->where('key', 'production-admin-setup-lock')->update(['value'=>'1']);
    if (App\Models\User::where('is_admin', true)->exists() || App\Models\User::where('email', $email)->exists()) throw new RuntimeException('Already configured.');
    $user = new App\Models\User(['name'=>'農園管理者', 'email'=>$email, 'password'=>$password]);
    $user->is_admin = true;
    $user->save();
});
echo json_encode(['configured'=>true]);
