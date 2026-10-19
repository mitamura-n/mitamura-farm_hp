<?php

namespace App\Http\Controllers;

use App\Services\SmtpConnectionCheck;
use App\Services\SmtpCredentialStore;
use Illuminate\Http\Request;

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
        } catch (\Throwable) {
            return back()->withErrors(['password'=>'SMTP認証を確認できませんでした。メールアカウントのパスワードを確認し、少し待ってからお試しください。']);
        }
        try {
            $store->save($data['password']);
        } catch (\Throwable) {
            return back()->withErrors(['password'=>'安全な保存を完了できませんでした。管理者に確認してください。']);
        }
        return redirect()->route('admin.mail-credentials')->with('status','SMTP認証と暗号化保存が完了しました。メールは送信していません。');
    }
}
