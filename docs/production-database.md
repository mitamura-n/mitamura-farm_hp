# 本番DBとバックアップ準備

2026-10-19。

## 確認済み
- Xserver管理画面：MariaDB10.11。既存MySQL DB/ユーザーなし。
- 本番専用DB mitamurafarm_prod（utf8mb4）を作成。空・未接続。旧HPとプレビューの接続先は変更なし。
- 専用ユーザー予定mitamurafarm_prd。作成フォームへIDとメモを準備。パスワード設定・登録はユーザー自身の作業待ち。アクセス権付与はまだ実施していない。
- SQLiteプレビューのオンラインバックアップをscripts/verify-preview-backup.phpで検証。復元コピーのintegrity_check、foreign_key_check、主要4テーブル参照、SHA256一致が成功。稼働DBへ上書きなし。
- 非公開保存先：/home/mitamurafarm/apps/mitamura-preview/backups/verified-20261019-094948-2b739a0d。DB・暗号化キーを含む.env・暗号化SMTP認証ファイルを同一時点のバックアップセットとして保存。秘密値は出力しない。
- XserverのSQLiteではVACUUM INTOが非対応だったためSQLite3 online backup APIを使用。初回失敗ディレクトリは成功バックアップではない。verification.txtがあるセットのみ確認済み。

## これから実施すること
1. 本人が専用ユーザーのパスワードを設定・登録する。チャットやGitに載せない。
2. ユーザーのアクセス権は本番専用DBのみ。付与対象を確認して設定。
3. 管理画面でDBホストを確認。本番の非公開.envへ秘密情報を安全に設定。
4. 本番専用の暗号化キー・管理者を用意し、テスト問い合わせやプレビューセッションを無条件にコピーしない。
5. 本番DBのmigration、商品情報、暗号化保存・復号・重複制約・フォーム・管理画面の検証。
6. MariaDBの整合性ある論理バックアップと別の検証DBへの復元、権限、暗号化データ復号を検証。本番復元の実証は今回のSQLite検証とは別。
7. バックアップの保管世代・定期実行・サーバー外保管先を決める。現在の同一サーバー保管だけではサーバー全体の障害に備えられない。外部アップロードは未実施。
8. 復元は対象・停止範囲・バックアップセットを照合し、公開前の切替承認と分けて実施。公開・現行DBへの上書きは今回行わない。

## 2026-10-19 アクセス権付与済み
ユーザーによる専用DBユーザー作成後、実行直前の承認を受け、mitamurafarm_prdをmitamurafarm_prodのアクセス権所有ユーザーへ追加。管理画面の保存結果を確認。DB接続先localhost。パスワードは取得せず、アプリの接続設定・認証試験・migrationはまだ行っていない。

## 本番DBパスワードの安全な入力
プレビュー管理者として/admin/database-credentialsを開く。本人が専用DBユーザーのパスワードを入力し、固定先localhost/mitamurafarm_prodへSELECT DATABASE()とSELECT 1のみ実行。成功時のみstorage/app/private/production-db.encへ暗号化保存する。復号にはプレビューのAPP_KEYが必要。接続先切替・migration・テーブル作成は行わない。POSTは5回/分まで。本番環境またはログイン制限のない環境では画面を無効にする。

本番への設定は別途、サーバー内で安全に認証情報を受け渡す。暗号化ファイルを暗号化キーの違う本番アプリへそのまま移しても復号できない。新しい暗号化認証ファイルもバックアップ対象へ含める必要がある。現時点は本人入力待ち。

## 2026-10-19 接続確認と暗号化保存成功
本人入力後の成功メッセージをユーザーが確認。チェックは本番専用ユーザーで固定DBへのSELECT DATABASE()/SELECT 1が成功した場合だけ保存する。暗号化保存ファイルproduction-db.encの存在と権限600をSSHで確認（内容は表示しない）。プレビューの接続先切替・本番テーブル作成は行っていない。次は本番専用環境への安全な設定、migration、MariaDBバックアップ・復元検証。

## 2026-10-19 本番専用環境の初期化とバックアップ
- /home/mitamurafarm/apps/mitamura-productionに独立した非公開環境を作成。新しいAPP_KEY、専用.env、storageを用意。公開ドキュメントルートには接続していない。
- 本番DB空を確認後、7 migrations成功。MariaDB10.11.18。商品5件の規格・価格・受付設定がプレビューと一致。
- トランザクション内の架空データで暗号化保存/復号・submission_key重複拒否を検証し、ロールバック。users=0/inquiries=0。
- APP_DEBUG=false、SEO_INDEXABLE=false、INQUIRIES_ENABLED=false、MAIL_MAILER=log。プレビューのSQLite・SMTPは変更なし。
- 本番ソースdf55661、依存ライブラリはcomposer.lockの一致確認後プレビューのno-dev vendorを複製。バックアップ用スクリプトを追加転送。
- 論理バックアップsnapshot-20261019-101811-58cff1a1：database.sql、environment.env、ハッシュ付きmanifest。非公開権限で保存。一時DB認証ファイルは終了時削除。復元確認済みとはまだ扱わない。
- 復元検証専用DB mitamurafarm_chk（utf8mb4）を空で作成。既存ユーザーmitamurafarm_prdへの権限追加は実行直前承認待ち。復元テスト後に権限を残すかも整理する。
- 本番のWeb・フォーム・メールの総合検証、管理者、定期/サーバー外バックアップ、正式公開は未実施。

## 2026-10-19 MariaDB復元検証完了
- ユーザーの実行直前承認後、mitamurafarm_prdへmitamurafarm_chkの権限を追加。
- snapshot-20261019-101811-58cff1a1のSHA256を検証し、空のchkへ復元。データベースを切り替えるUSE/CREATE DATABASE文のないdumpに限定。
- 全11テーブルの一覧、全行のハッシュ、CHECK TABLE成功を確認。本番DBは読み取りのみ。
- chk内の架空暗号化テストデータをdump、削除、再importし、バックアップenvironment.envのAPP_KEYで復号成功。検証テーブルは終了後除去、chkの11テーブルは再び本番と一致。実問い合わせは本番0件、管理者0件。
- 最初のキー復号試験ではライブラリAPI不一致を修正し、照合済みchkで暗号化試験のみ再開した。成功後にmanifestのrestore_verifiedをtrueへ更新。
- 検証後はchkの一時アクセス権を解除し、画面で所有ユーザーなしを確認。本番prodの権限は維持。chkのDBと復元データ自体は保持。再試験時は空の検証DBと権限確認が必要。
- 定期バックアップ、保存世代、サーバー外保管、本番管理者・HTTP総合試験は別の残作業。

## 2026-10-19 定期バックアップ・世代管理
- crontabに毎日03:17（サーバーJSTを実測確認）のジョブを登録。既存crontabなしを確認、変更前を非公開backupsへ退避。
- 実行元：/home/mitamurafarm/apps/mitamura-production/backup-production-daily.sh。flockで重複実行防止、umask077。
- 実行結果：backups/daily-status.json、診断：backups/daily-last-run.log。パスワードやDB内容は出力しない。失敗はsuccess=falseと非0終了。外部通知はまだなし。
- 同じ定期処理を手動実行し2026-10-19 19:29 JST成功。snapshot-20261019-102929-f4851f3c、削除0件。時刻指定での初回実行は2026-10-20 03:17予定、まだ未確認。
- 正常な通常セットの直近30世代を保持。DB・環境設定・manifestを持ち、ハッシュが一致し、既知ファイルだけを含むセットに限定して古いものから処理。
- restore_verified=true、protected=true、未知ファイル、不完全なセット、リンクは自動削除しない。既存の復元検証済みセットは残す。30世代はこれらの保護セットを除く通常セット数。
- 自動削除は保存済みバックアップの古い世代に限る。現在のDB・問い合わせの削除運用とは別。
- サーバー外の保管先、失敗時の外部通知、保護セットの定期的見直しは残作業。cronの将来実行や全バックアップの復元成功は、手動成功と区別する。
- 実装：scripts/backup-production.php、scripts/backup-production-daily.sh、app/Services/BackupRetention.php。世代処理の保護条件をテスト済み（全39件273アサーション成功）。
