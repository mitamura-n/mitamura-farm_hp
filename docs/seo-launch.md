# SEO・旧URL移行の公開前手順

## 準備済み
- タイトル・説明文・OGPを共通化。OGPは提供された農園ロゴを使用。
- canonicalとsitemap.xmlは公開承認後のみ有効化する。SEO_INDEXABLEは既定false。productionかつPREVIEW_REQUIRE_LOGIN=falseも必要。
- プレビューはnoindexとログイン制限を維持。公開設定を切り替えない。
- サイトマップは単一ページのトップURLだけを掲載。ページ内アンカー、管理画面、個人データは掲載しない。

## 旧URLの対応案（未適用）
過去の取得資料docs/legacy-content-audit.mdに基づく候補。公開前にサーバー内の実ファイル・アクセス実績も確認する。今回は外部閲覧ツールで旧HPを取得できなかったため、現行URLの存在は再検証が必要。

| 旧パス | 新しい対応先の案 |
|---|---|
| /index.htm | / |
| /okome2.htm | /#story |
| /order.htm | /#guide |
| /otoiawase.htm | /#contact |
| /qa.htm | /#faq（新HPのよくあるご質問） |
| /komeko/komeko.htm | /#products |
| /komenuka/komenuka_Top.htm | /#products |
| /low.htm | 特商法表記の内容・移転先が完成するまで保留 |

## 公開時
1. 旧サイト全体のバックアップと復元方法を確認。
2. 正式な販売条件・特商法表示・個人情報運用を完成させ、ユーザーの公開承認を得る。
3. 新サイト本番URL・TLS・wwwの統一先を検証。
4. 旧パスから対応する内容へ301/308転送を設定。画像や資料などを無差別にトップへ転送しない。旧実ファイルがLaravelより先に配信される構成なので、Webサーバー側の優先順も確認。
5. 新サイト本番でSEO_INDEXABLE=trueを明示設定、production・APP_URL・認証制限解除を確認しconfig:cache。プレビューは変更しない。
6. 本番のcanonical、OGP画像の取得、XML、noindexヘッダーやタグの解除、robots.txtに阻害がないことを確認。管理画面のnoindexは維持。
7. Search Consoleの権限を確認して本番サイトマップを送信。外部アカウント操作や公開は別途承認範囲を確認。
8. 旧URL転送・404・問い合わせ・メールの監視手順を実施。解析タグは現在追加しない。

OGP画像は暫定ロゴ。横長の共有画像を後日制作可能。構造化データは販売主体・表示根拠が未確定のため今回は追加しない。順位上昇や検索登録は保証しない。

参考：Google https://developers.google.com/search/docs/crawling-indexing/site-move-with-url-changes?hl=ja

## 2026-10-20：転送設定案の準備（未適用）

主要旧HTMLの実ファイル存在はサーバー内で確認済み。deployment/production/README.mdとlegacy-rewrites.confにGET/HEADの転送先、旧POST/CGIの410停止案を整理。qa.htmとlow.htmは対応先の完成まで保留。画像・PDFの一括転送はしない。独立したPCのApache構文検査は成功したが、Xserverへの設定適用やHTTP動作検証は未実施。
