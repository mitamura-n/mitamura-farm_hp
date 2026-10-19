<?php

namespace App\Http\Controllers;

use App\Services\ProductionDatabaseCheck;
use App\Services\ProductionDatabaseCredentialStore;
use Illuminate\Http\Request;

class ProductionDatabaseCredentialController extends Controller
{
    private function authorizeSetup(Request $request): void
    {
        abort_unless($request->user()?->is_admin,403);
        abort_unless(config('farm.preview_require_login') && !app()->environment('production'),404);
    }

    public function show(Request $request, ProductionDatabaseCredentialStore $store)
    {
        $this->authorizeSetup($request);
        return response()->view('admin.database-credentials',['configured'=>$store->password() !== null])
            ->header('Cache-Control','no-store, private');
    }

    public function store(Request $request, ProductionDatabaseCheck $checker, ProductionDatabaseCredentialStore $store)
    {
        $this->authorizeSetup($request);
        // Laravel excludes the field named password from flashed old input.
        $data=$request->validate(['password'=>['required','string','min:8','max:16']]);
        try { $checker->check($data['password']); }
        catch (\Throwable) {
            // Do not report raw PDO exceptions: they may contain connection details.
            return back()->withErrors(['password'=>'本番DBへの接続を確認できませんでした。DB専用ユーザーのパスワードとアクセス権をご確認ください。（DB-CONNECTION）']);
        }
        try { $store->save($data['password']); }
        catch (\Throwable) {
            return back()->withErrors(['password'=>'接続は確認できましたが、安全な保存を完了できませんでした。（DB-STORAGE）']);
        }
        return redirect()->route('admin.database-credentials')->with('status','本番DBへの接続確認と暗号化保存が完了しました。接続先の切り替えやテーブルの作成は行っていません。');
    }
}
