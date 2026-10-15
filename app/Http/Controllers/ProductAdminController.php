<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductAdminController extends Controller
{
    public function login() { return view('admin.login'); }

    public function authenticate(Request $request)
    {
        $data = $request->validate(['email'=>'required|email', 'password'=>'required|string']);
        if (! Auth::attempt($data + ['is_admin'=>true])) {
            return back()->withErrors(['email'=>'メールアドレスまたはパスワードが正しくありません。'])->onlyInput('email');
        }
        $request->session()->regenerate();
        return redirect('/admin/products');
    }

    public function logout(Request $request)
    {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect('/admin/login');
    }

    public function index(Request $request)
    {
        abort_unless($request->user()?->is_admin, 403);
        return view('admin.products', ['products'=>Product::orderBy('id')->get()]);
    }

    public function update(Request $request, Product $product)
    {
        abort_unless($request->user()?->is_admin, 403);
        $data = $request->validate([
            'unit_kg'=>'required|integer|min:1|max:1000',
            'unit_label'=>'required|string|max:80',
            'max_units'=>'nullable|integer|min:1|max:999',
            'price_yen'=>'nullable|integer|min:0|max:10000000',
            'active'=>'required|boolean',
            'version'=>'required|string',
        ]);
        $updated = Product::whereKey($product->id)->where('updated_at', $data['version'])->update(
            collect($data)->except('version')->all() + ['updated_at'=>now()->max($product->updated_at->addSecond())]
        );
        if (! $updated) return back()->withErrors(['version'=>'別の画面で更新されています。再読み込みしてから編集してください。']);
        return back()->with('status', $product->name.'を更新しました。');
    }
}
