@extends('admin.layout')
@section('content')
<article><h2>メール送信アカウントの接続設定</h2>
<p>送信元：<strong>info@mitamurafarm.com</strong><br>接続先：sv17015.xserver.jp ／ SSL/TLS・465番</p>
<p>農園への通知先と、お客様からの返信先は mitamura.farm@gmail.com です。</p>
<p class="notice">{{ $configured ? 'SMTP認証情報は設定済みです。変更する場合だけ再入力してください。' : 'Xserverで作成したメールアカウントのパスワードを、下の欄に入力してください。HP管理者ログインのパスワードとは別です。' }}</p>
<form method="post" action="{{ route('admin.mail-credentials.store') }}">@csrf
<label for="smtp-password">メールアカウントのパスワード<input id="smtp-password" name="password" type="password" autocomplete="off" minlength="8" maxlength="45" required></label>
<button>接続確認して安全に保存</button></form>
<p>この操作ではSMTP認証のみを確認し、メールは送りません。パスワードはサーバーの非公開領域に暗号化して保存し、画面やGitHubには出しません。</p>
<small>実メールの送信テストと有効化は、この接続設定が終わってから行います。</small>
</article>
@endsection
