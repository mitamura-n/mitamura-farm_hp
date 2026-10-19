<?php

namespace App\Http\Controllers;

use App\Services\SmtpConnectionCheck;
use App\Services\SmtpCredentialStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class MailCredentialController extends Controller
{
    public function show(Request $request, SmtpCredentialStore $store)
    {
        abort_unless($request->user()?->is_admin,403);
        return response()->view('admin.mail-credentials',['configured'=>$store->password() !== null])
            ->header('Cache-Control','no-store, private');
    }

    public function store(Request $request, SmtpConnectionCheck $checker, SmtpCredentialStore $store)
    {
        abort_unless($request->user()?->is_admin,403);
        // "password" is excluded from Laravel's flashed old input by default.
        $data=$request->validate(['password'=>['required','string','min:8','max:45']]);
        try {
            $checker->check($data['password']);
        } catch (\Throwable $error) {
            // Never log SMTP messages/debug transcripts: they can contain credentials.
            $reason = 'application';
            if ($error instanceof TransportExceptionInterface) {
                $reason = str_contains($error->getMessage(), 'Failed to authenticate')
                    ? 'authentication' : 'connection';
            }
            Log::warning('SMTP credential check failed', ['reason'=>$reason, 'exception_class'=>get_class($error)]);
            $message = match ($reason) {
                'authentication' => 'メールサーバーが認証を受け付けませんでした。info@mitamurafarm.com のメールアカウント用パスワードをご確認ください。（確認コード：SMTP-AUTH）',
                'connection' => 'メールサーバーとの通信を完了できませんでした。少し待ってからお試しください。（確認コード：SMTP-CONNECTION）',
                default => '接続確認の処理で問題が発生しました。管理者が調査します。（確認コード：SMTP-APP）',
            };
            return back()->withErrors(['password'=>$message]);
        }
        try {
            $store->save($data['password']);
        } catch (\Throwable) {
            return back()->withErrors(['password'=>'安全な保存を完了できませんでした。管理者に確認してください。']);
        }
        return redirect()->route('admin.mail-credentials')->with('status','SMTP認証と暗号化保存が完了しました。メールは送信していません。');
    }
}
