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

## 更新

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
