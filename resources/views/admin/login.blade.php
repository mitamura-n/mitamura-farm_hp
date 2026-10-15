@extends('admin.layout')
@section('content')
<article><h2>管理者ログイン</h2><p>管理者専用です。初回は開発環境で管理者アカウントを作成してください。</p><form method="post" action="/admin/login">@csrf<label>メールアドレス<input name="email" type="email" autocomplete="username" required value="{{ old('email') }}"></label><label>パスワード<input name="password" type="password" autocomplete="current-password" required></label><button>ログイン</button></form></article>
@endsection
