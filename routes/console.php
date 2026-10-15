<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('farm:admin', function () {
    $email = $this->ask('管理者メールアドレス');
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) { $this->error('メールアドレスを確認してください。'); return 1; }
    if (\App\Models\User::where('email', $email)->exists()) { $this->error('既存のユーザーです。上書きは行いません。'); return 1; }
    $password = $this->secret('パスワード（12文字以上）');
    if (mb_strlen($password ?? '') < 12) { $this->error('12文字以上で設定してください。'); return 1; }
    if ($password !== $this->secret('確認のためもう一度入力')) { $this->error('パスワードが一致しません。'); return 1; }
    $user = new \App\Models\User(['name'=>'農園管理者', 'email'=>$email, 'password'=>$password]);
    $user->is_admin = true; $user->save();
    $this->info('管理者を作成しました。/admin/login からログインできます。');
})->purpose('農園の商品管理者を作成（対話入力・既存アカウントの変更なし）');
