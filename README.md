# 三田村農園 新公式HP

Laravel 13 / PHP 8.4。農園・商品の紹介と、フォームでの注文相談受付を行うサイトです。フォーム送信時点では注文は確定しません。

## 開発の現在地

完成状況と優先順位は [docs/roadmap.md](docs/roadmap.md)、実装状況は [docs/status.md](docs/status.md) を確認してください。旧HPは継続公開中で、新HPは認証付きプレビューです。

## プレビュー

- サイト：https://preview.mitamurafarm.com/
- 商品管理：https://preview.mitamurafarm.com/admin/products
- 問い合わせ管理：https://preview.mitamurafarm.com/admin/inquiries

管理者ログインが必要です。テスト用の情報を使ってください。メールはlog設定のため外部配送しません。

## 作業手順

1. 要件・現在の実装・未コミット変更を確認する。
2. ソース変更後に `./dev.ps1 test`（Windows）または `php artisan test`（PHP8.4の環境）を実行。
3. 変更をコミットし、Windowsでは `./scripts/deploy-preview.ps1` でプレビューへ反映。
4. 実ブラウザーで確認。GitHub保存は別途pushする。

Windowsのdev.ps1はプロジェクト外のローカルPHP/Composerを参照します。別環境ではPHP8.4と必要拡張・Composerを導入し、composer install、.env準備、APP_KEY生成（新環境のみ）、DB接続・migrationを行ってください。初期設定の詳細は開発環境の各Markdownを参照。クラウドからこのWindows専用の配置スクリプトはそのまま使えません。

## 機能・運用資料

- [商品管理](docs/product-master.md)
- [受付実装](docs/inquiry-intake.md)
- [受付管理](docs/inquiry-administration.md)
- [Xserver配置・更新](docs/xserver-preview.md)
- [要件](docs/requirements.md)

.env・秘密鍵・認証情報・問い合わせDB・ログ・vendor・node_modulesはコミットしません。APP_KEYは暗号化データの復号に必要なため運用環境で不用意に再生成しないでください。正式公開・実メール配送・本番MariaDB・販売条件・公開用表示とSEOの仕上げは残作業です。

## 重要情報の確認窓口

メール・DB・決済・サーバー・バックアップの現在の設定と保留事項は [重要情報・設定一覧](docs/important-settings.md) を参照してください。重要な変更の都度更新し、GitHubの履歴でも変更点を確認できます。秘密情報は含めません。
