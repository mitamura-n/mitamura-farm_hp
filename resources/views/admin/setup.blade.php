@extends('admin.layout')
@section('content')
<article><h2>プレビュー管理者の初期設定</h2>
<p>初回だけの登録です。このアカウントでプレビュー閲覧と商品管理ができます。登録後、この初期設定リンクは使えなくなります。</p>
<form method="post" action="{{ url('/preview-setup/'.$token) }}">@csrf
<label>管理者メールアドレス<input name="email" type="email" autocomplete="username" maxlength="254" required value="{{ old('email') }}"></label>
<label>パスワード（12文字以上）<input name="password" type="password" autocomplete="new-password" minlength="12" maxlength="128" required></label>
<label>確認用パスワード<input name="password_confirmation" type="password" autocomplete="new-password" minlength="12" maxlength="128" required></label>
<button>管理者を登録してプレビューを開く</button></form>
<p>パスワードはチャットに送らず、この画面で入力してください。</p></article>
@endsection
