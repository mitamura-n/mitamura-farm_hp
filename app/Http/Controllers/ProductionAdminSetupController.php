<?php

namespace App\Http\Controllers;

use App\Services\ProductionAdminSetup;
use Illuminate\Http\Request;

class ProductionAdminSetupController extends Controller
{
    private function authorizeSetup(Request $request): void
    {
        abort_unless($request->user()?->is_admin, 403);
        abort_unless(app()->environment('staging') && config('farm.preview_require_login'), 404);
    }

    public function show(Request $request, ProductionAdminSetup $setup)
    {
        $this->authorizeSetup($request);
        try { $state = $setup->run('status'); }
        catch (\Throwable) { abort(503, '本番の準備状態を確認できませんでした。管理者へお知らせください。'); }
        return response()->view('admin.production-admin', ['configured'=>$state['configured']])->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $request, ProductionAdminSetup $setup)
    {
        $this->authorizeSetup($request);
        $data = $request->validate([
            'email'=>['required','email:rfc','max:254'],
            'password'=>['required','string','min:12','max:72','confirmed', function ($attribute, $value, $fail) {
                if (strlen($value) > 72) $fail('パスワードはUTF-8で72バイト以内にしてください。');
            }],
        ]);
        try { $setup->run('create', $data); }
        catch (\Throwable) { return back()->withErrors(['password'=>'作成できませんでした。既に設定済みの場合は上書きできません。管理者へご確認ください。']); }
        return redirect()->route('admin.production-admin')->with('status', '本番管理者を作成しました。サイト公開・メール送信は行っていません。');
    }
}
