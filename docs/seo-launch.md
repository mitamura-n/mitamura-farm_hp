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

### 2026-10-20：新HPの一般公開完了

本人が旧HP非公開保存・新HPへの切替を了承した後に実施。公開リリースadb5c45へ本番currentを切替、APP_URLを正式URL、通常受付とSMTP送信とSEO_INDEXABLEを有効化し、一般宛先の制限を本番のみ解除。設定キャッシュへ平文SMTP認証情報を含めず、HTTP時に暗号化ストアから読み込む方式を維持。独立したfarm-index.phpを固定リリースに向け、本番ホスト・HTTPS・公開フラグで制限。プレビューのDB・管理者・メール制限は変更しない。

旧公開ファイル1,519件を公開領域外へ相対パスを保持して退避。preview/check、.user.ini/.htaccessの原本、.well-knownは除外。新資材21件と統合.htaccessの変更前/後ハッシュ、退避計画、変更前.env/current、切り戻し手順を非公開ops-stage/launch-20261020-adb5c45に保持。切り戻し時もDBを巻き戻さず、受付を保持する。切り戻しの実行試験は行っていない。

公開後にトップ200・公開用フォーム・index/follow・sitemap/robots・CSS/JS/画像8件・HTTP/wwwの308・旧HTMLの301・旧CGIの410・機密ファイル404を確認。実注文でないと明記したテスト受付を1件保存し、CSRFの419・再送時の同一受付返却・DB保存・通知2通の送信処理を確認。本人から農園Gmailに2通届いたと回答を受領。テストを実注文として扱わず、追加再送は行わない。

本番管理者用の旧HP保存版参照を非公開に設定し、権限を付与したアプリ内描画は200、未ログインの公開HTTPでは管理者ログインへ302、noindexを確認。check入口は停止503。旧公開画像URLの404も確認し、旧HPの一般公開を終了した。公開後20:58 JSTの暗号化バックアップとDrive照合成功。

残作業：本人指定の農園名だけの表示に対する正式な販売者情報の追記、Square審査/決済導入、Search Console送信、通常運用の受注・入金確認。検索順位や法令適合の全面保証はしない。

### 2026-10-20：SEO改善とGoogleサイトマップ送信完了

公開リリース36f142eへ無停止で切替。検索タイトルを「農薬・肥料を使わないお米・米粉の直販｜福井県越前市 三田村農園」にし、本人回答の有機JAS認証情報をメタ説明文へ自然に記載。証書の独立照合は行っていない。本文では有機JAS・オーガニックを強調しない指定を維持し、実HTMLのbodyに当該語がないことを確認。メタ説明文は検索結果・共有表示・ソース閲覧で見える場合があり、他人から隠す仕組みではないことを説明した。Googleが利用しないmeta keywordsや隠し文字は追加していない。

メインH1とデザインを維持。既存FAQに確認済み発送目安を追加。公開時だけWebSiteのJSON-LDを出力し、構文と正規URLを確認。HTML生成5テスト33アサーション成功。公開URLのトップ200・メタ情報・本文非強調・JSON-LD・サイトマップ1URLを確認。受付・SMTP・本番DB・旧HP非公開は維持し、追加のテストメール送信は行っていない。

本人了承後、農園GmailでSearch ConsoleのURLプレフィックス https://mitamurafarm.com/ の所有権をHTMLメタタグ方式で確認。「所有権を証明しました」を画面確認。確認タグは本番の非公開環境設定で保持し、公開headへ出力する。維持に必要なので削除しない。サイトマップ https://mitamurafarm.com/sitemap.xml を送信し、Google画面で2026/10/20・「成功しました」・検出ページ数1を確認。送信は1回。サイトは1ページ構成のためアンカー別URLや管理画面は送らない。

Search Console管理URL：https://search.google.com/u/1/search-console/sitemaps?resource_id=https%3A%2F%2Fmitamurafarm.com%2F （農園Gmailでログイン）。成功はGoogleによるサイトマップ取得の確認であり、検索インデックス登録完了・表示順位・反映日の保証ではない。Googleビジネスプロフィールは対面営業の実態を本人確認中で、まだ登録していない。
