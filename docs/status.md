# 開発状況

更新：2026-10-19。

最新の完成状況と優先順位は [作業順](roadmap.md) を参照。

## 現在

Xserverの認証付きプレビューが稼働。管理者登録済み。ページ・商品管理・問い合わせフォームとDB保存を実ブラウザーで確認。実メールはlogのみで外部送信なし。正式サイトの旧HPは変更していない。

商品5種の規格・価格・受付状態はDB管理。米ぬか1kg規格を確認し受付を開始。税込価格・年産・精米料と支払い方法を案内に反映。送料は後日共有待ち。WindowsローカルはMariaDB、XserverプレビューはSQLite。

プレビューURL：https://preview.mitamurafarm.com/

商品管理：https://preview.mitamurafarm.com/admin/products

## 次の作業

受付一覧・詳細・対応状況の管理を実装し、自動テスト26件170アサーション成功。プレビューで一覧・詳細・状態保存を確認済み。次は販売条件とメールの実配送を整える。公開前にMariaDB、個人情報/販売表示、SEO、旧URL移行、公開判定を進める。

## 資料

- [商品マスタ](product-master.md)
- [問い合わせ受付](inquiry-intake.md)
- [Xserverプレビュー](xserver-preview.md)
- [作業順](roadmap.md)
