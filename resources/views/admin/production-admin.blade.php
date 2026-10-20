@extends('admin.layout')
@section('content')
<article><h2>本番管理者の初期設定</h2>
@if($configured)
<p class="notice">本番管理者は設定済みです。この画面では変更・上書きを行いません。</p>
@else
<p>公開後の管理画面で使うメールアドレスと、新しいパスワードを設定してください。プレビューのアカウントは変更しません。</p>
<form method="post" action="{{ route('admin.production-admin.store') }}">@csrf
<label>管理者メールアドレス<input type="email" name="email" autocomplete="username" value="{{ old('email', config('farm.email')) }}" maxlength="254" required></label>
<label>新しいパスワード（12文字以上）<input type="password" name="password" autocomplete="new-password" minlength="12" maxlength="72" required></label>
<label>パスワードの確認<input type="password" name="password_confirmation" autocomplete="new-password" minlength="12" maxlength="72" required></label>
<button>本番管理者を作成する</button></form>
@endif
<p>保存先：本番専用データベース。パスワードはハッシュ化して保存します。サイト公開や注文受付の開始、メール送信は行いません。</p>
<p>Bitwardenには「農園HP 管理画面（本番）」として保存してください。パスワードをチャットへ送る必要はありません。</p>
</article>
@endsection
