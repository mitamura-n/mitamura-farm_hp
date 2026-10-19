@extends('admin.layout')
@section('content')
<article><h2>本番DBの接続準備</h2>
<p>接続先：localhost ／ データベース：mitamurafarm_prod<br>専用ユーザー：mitamurafarm_prd</p>
<p class="notice">{{ $configured ? '本番DBの接続情報は保存済みです。変更する場合だけ再入力してください。' : 'Xserverで作成したDB専用ユーザーのパスワードを入力してください。メールやHP管理者のパスワードとは別です。' }}</p>
<form method="post" action="{{ route('admin.database-credentials.store') }}">@csrf
<label for="database-password">DB専用ユーザーのパスワード<input id="database-password" name="password" type="password" autocomplete="off" minlength="8" maxlength="16" required></label>
<button>接続確認して安全に保存</button></form>
<p>読み取りの接続テストだけを行います。テーブル作成・データ登録・プレビューの接続先変更・メール送信は行いません。</p>
<small>認証情報はサーバーの非公開領域に暗号化保存し、画面・GitHub・ログには出しません。本番環境への設定は、この確認が終わってから行います。この画面はプレビューの管理者専用です。</small>
</article>
@endsection
