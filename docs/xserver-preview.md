# Xserver公開前プレビュー

URL: https://preview.mitamurafarm.com/ （既存のBasic認証を維持）

## 構成

- アプリ: `/home/mitamurafarm/apps/mitamura-preview/releases/<release-id>`
- 現在のアプリ: 同ディレクトリの `current` シンボリックリンク
- .env・DB・storage: `shared` に保持し、更新時も維持
- 公開先: `/home/mitamurafarm/mitamurafarm.com/public_html/preview.mitamurafarm.com`
- 公開先にはpublicの静的ファイルとprivateアプリへ接続するindex.phpのみを配置
- PHP: `/usr/bin/php8.4`。既定php8.0は使わない
- DB: プレビュー専用SQLite。本番MariaDBへの接続検証は別途必要
- メール: log（実配送なし）。フォームはテスト専用
- APP_ENV=staging / APP_DEBUG=false / Secure Cookie / noindex / no-store

## このWindows PCからの通常更新

変更をテスト・コミットした後、プロジェクトフォルダーで `./scripts/deploy-preview.ps1` を実行します。未コミット変更がある場合は停止します。SSH鍵と照合済みknown_hostsはPC専用フォルダーを使用し、Gitには含めません。別PCでは `-KeyDirectory` で保管先を指定し、先にそのPC用の接続設定を行ってください。依存導入・バックアップ・migration・起動確認・反映まで行います。GitHubへのpushは別操作です。

## 更新の内部手順

1. ソース変更とテストを終える。
2. コミットのソースを `git archive --format=tar.gz` で作成する。秘密情報や依存ディレクトリを含めない。
3. アーカイブをアプリベースの `upload-<release-id>.tar.gz` にSSH/SCPで転送。
4. `scripts/deploy-preview.sh` を転送し、`bash deploy-preview.sh <release-id>` を実行。
5. composer install/check-platform-reqs、DBバックアップ、migration、Laravel描画確認の成功後に反映する。
6. Basic認証の維持、HTTP応答、ブラウザー表示、フォームを確認する。

release-idは半角英数字とハイフンのみ。既存release-idの再使用は禁止。設定ファイルとSQLite DBはWeb非公開。秘密鍵・.env・認証パスワードはGitへ保存しない。

## 管理者作成

接続後 `cd /home/mitamurafarm/apps/mitamura-preview/current` に移動し、`/usr/bin/php8.4 artisan farm:admin` を実行。ユーザー自身が管理用メールと12文字以上のパスワードを対話入力する。Basic認証のアカウントとは別。

## バックアップ・復旧

毎回shared DBをbackupsへコピーし、既存プレビュー公開ファイルをtar保存する。移行処理でDB変更が入るため、アプリだけの切替で復旧できるとは限らない。復旧時には対象release、DBと公開ファイルの整合を確認する。自動的なDB巻き戻し・削除は行わない。バックアップ世代の削除は別途管理する。

## 注意

本番ドメイン直下の旧HPは更新しない。Web側PHPの設定は別管理。クラウドからの直接配置は今回構築しない。このWindows PCの秘密鍵をクラウドへ共有しない。

## 初回配置の確認結果（2026-10-19）

release `20261019-01` を配置。composerのプラットフォームチェックとmigration、LaravelのHTTP Kernelによるroot描画200が成功。自動テスト16件95アサーション成功。HTTPSの外部アクセスは401でBasic認証が維持されている。内蔵ブラウザーは認証入力画面を表示せずXserverの「無効なURLです」ページとなるため、認証後の実HTTP動作確認は未完了。管理者アカウントも未作成。システムComposer2.5.8はPHP8.4で非推奨警告を多数出すが、今回の依存導入自体は成功。


## 閲覧認証の切替（2026-10-19）

内蔵ブラウザーがBasic認証の入力画面を表示しないため、ユーザー承認を得てLaravelの管理者ログインで全ページ・受付APIを保護する方式に変更する。PREVIEW_REQUIRE_LOGIN=trueで有効。静的画像・CSS等は公開取得可能となる。既存Basic設定は非公開フォルダーに保存し復旧可能。

初回管理者は、サーバー側にSHA-256ハッシュと有効期限だけを保存したランダムな一度限りのリンクから登録する。staging環境専用・有効期限1時間・管理者が1件でも存在すれば無効・CSRFと回数制限あり。パスワードは本人が画面で入力する。初期設定リンクやパスワードをGitHubや文書に記録しない。

切替準備テスト23件133アサーション成功。認証後の実ブラウザー確認はユーザーの管理者登録後に行う。

切替実施済み：release `20261019-152129-347a78d`。内蔵ブラウザーでログイン画面と期限付き初期設定画面が表示されることを確認。管理者登録はユーザーによる入力待ち。認証後のサイト表示・フォーム送信確認は次の段階。

2026-10-19 更新反映対策：PHP-FPMのrealpathキャッシュによりcurrentリンク切替後も旧ルートが参照される事象を確認。更新スクリプトは公開index.phpの参照先に新しいリリースの実パスを明示するよう変更。currentリンクだけを戻してもWeb側は戻らないため、復旧時は公開index.phpも対象releaseへ向ける必要がある。
