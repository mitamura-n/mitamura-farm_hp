<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PreviewSetupController extends Controller
{
    private function verify(string $token): void
    {
        abort_unless(app()->environment('staging') && config('farm.preview_require_login')
            && strlen($token) === 64 && ctype_xdigit($token)
            && config('farm.preview_setup_hash')
            && (int) config('farm.preview_setup_expires') > time()
            && hash_equals(config('farm.preview_setup_hash'), hash('sha256', $token))
            && ! User::where('is_admin', true)->exists(), 404);
    }

    public function show(string $token)
    {
        $this->verify($token);
        return response()->view('admin.setup', ['token'=>$token])->withHeaders([
            'Cache-Control'=>'no-store, private', 'Referrer-Policy'=>'no-referrer',
            'X-Robots-Tag'=>'noindex, nofollow, noarchive',
        ]);
    }

    public function store(Request $request, string $token)
    {
        $this->verify($token);
        $data = $request->validate([
            'email'=>['required','email:rfc','max:254','unique:users,email'],
            'password'=>['required','string','min:12','max:72','confirmed', function ($attribute, $value, $fail) {
                if (strlen($value) > 72) $fail('パスワードはUTF-8で72バイト以内にしてください。');
            }],
        ], ['password.min'=>'パスワードは12文字以上で入力してください。',
            'password.confirmed'=>'確認用パスワードが一致していません。']);

        // A single shared database flag serializes first-admin registration.
        $user = DB::transaction(function () use ($data, $token) {
            DB::table('cache')->insertOrIgnore(['key'=>'preview-admin-setup-lock','value'=>'0','expiration'=>2147483647]);
            DB::table('cache')->where('key','preview-admin-setup-lock')->update(['value'=>'1']);
            $this->verify($token);
            $user = new User(['name'=>'農園管理者','email'=>$data['email'],'password'=>$data['password']]);
            $user->is_admin = true;
            $user->save();
            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        return redirect('/')->with('status','管理者の初期設定が完了しました。');
    }
}
