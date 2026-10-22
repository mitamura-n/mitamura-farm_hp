# 公開後の通常更新（Windows / Cursor）

文言・写真・CSS・通常のアプリ修正を、コミット済み・GitHub mainへプッシュ済みの版から反映する手順です。初回公開の切替手順とは別です。2026-10-22に作成。スクリプトによる実公開・実際の切り戻しはまだ実施していません。

## 使い方

1. ローカルで表示を確認し、自分の変更をコミットしてプッシュします。Cursorの変更ならコミット名は `[Cursor] 変更内容`。他方の作業中の変更を含めません。
2. Cursorのターミナル（PowerShell）を、開発フォルダー `mitamura-app` で開きます。ローカル表示用サーバーのターミナルとは別に開いてください。
3. 次で確認だけ実行します。サイトのファイル・設定は変更しません。

```powershell
& "C:\Users\noriy\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe" scripts/deploy-production.py --check
```

4. 確認が成功したら、次でテスト・バックアップ・本番反映へ進みます。

```powershell
& "C:\Users\noriy\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe" scripts/deploy-production.py --deploy
```

5. PHP/JavaScriptのテスト後、`Type publish ...` が出ます。表示された `publish コミット番号` を入力してEnterを押します。これが本番反映の最終確認です。違う入力なら中止します。
6. `SUCCESS` と公開コミット番号が出たら完了です。[公開HP](https://mitamurafarm.com/)を開き、Ctrl+F5で変更箇所を確認します。表示されたコミット番号と変更内容は、重要設定一覧・作業記録へ追記してください。スクリプトはGitへのコミットやプッシュは行いません。

PowerShellの実行ポリシー変更は不要です。Pythonの配置が変わった場合は実際のPython実行ファイルのパスへ読み替えます。通常のGit、SSH/SCP、Node.js、ローカルPHP（開発フォルダーから `../../.tools/php84/php.exe`）が必要です。SSH鍵と検証済みknown_hostsは従来の非公開フォルダーを使います。

## 自動で行うこと

- 未コミット変更、main以外、ローカルHEADとGitHub mainの不一致、現在の本番版から分岐した履歴があれば停止。
- 本番のcurrent参照・公開入口・本番PHPバージョン・受付/SMTP/SEO設定・DB名・未適用migrationを確認。秘密情報や顧客情報を出力しない。
- PHPテストとJavaScriptテストを実行。テスト中にソースが変わっていれば停止。
- 既存の暗号化バックアップ処理を実行し、この回のバックアップとDrive照合が成功したことを確認してから反映。
- 現行vendorを使い、共有環境設定とDBを維持。新リリースで設定比較・Bladeコンパイルを行う。
- 変更のある公開用静的ファイルだけをコピー。公開入口とcurrentを更新し、4公開ページ・管理ログインの計測除外・更新した静的ファイルのチェックサムを検証。
- 公開入口と更新対象の既存静的ファイルは非公開の `offsite/ops-stage/update-公開コミット番号` へ保存。切替中の通常エラー、切替後のHTTP検証失敗では旧版への復帰を試みる。

## この手順で扱わない変更・制限

- DB構造、依存ライブラリ、設定ファイル、公開.htaccess/index.php、ビルド方法の変更や公開ファイル削除は停止させます。個別の反映手順が必要です。
- SMTP・認証・GA4の環境設定、DNS、旧HP保存版、プレビューは変更しません。テスト問い合わせやメールを新たに送信しません。
- CLIのPHPバージョンを表示しますが、Xserverの修正パッチ適用保証やWeb側PHPの脆弱性診断の代わりにはなりません。
- 複数ファイルの一斉更新は完全な原子的切替ではありません。更新中の短時間には旧HTMLと新資材が混在する可能性があります。
- 通信断・強制終了では自動復帰を保証できません。エラー時は繰り返し実行せず、現在の公開版を確認してください。
- 復帰時は追加した新規静的ファイルを削除せず残します。DBと受付内容は巻き戻しません。リリース・バックアップの自動掃除も行いません。
- サーバー側の準備ログは非公開作業フォルダーに保存します。コマンド失敗時の詳細はPCの一時フォルダーに保存し、`Local error log` として場所だけ表示します。ログには設定やエラー詳細が含まれる可能性があるため、Gitへ追加せず、全文をそのまま外部へ貼り付けないでください。

## 通常更新を取り消す場合

このスクリプトで公開した版に限り、直前の入口・既存静的ファイルへ戻せます。`SUCCESS` に表示された40桁の公開コミット番号を指定します（その版が現在も公開中の場合のみ）。

```powershell
& "C:\Users\noriy\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe" scripts/deploy-production.py --rollback ここを40桁の公開コミット番号に置き換える
```

表示される `rollback ...` を入力すると実行します。公開前の旧HPへ戻す機能ではありません。実行後は公開ページを確認し、記録を更新します。復帰の実行試験は未実施です。
