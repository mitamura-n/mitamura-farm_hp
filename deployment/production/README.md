# 公開切替用の配信設定案（未適用）

このフォルダーは準備資料です。public/へ自動コピーしません。旧HP・本番の.htaccessは変更していません。

## 配置・統合

- legacy-rewrites.conf：既存Xserver設定を残した本番.htaccessへ統合する断片。単体で.htaccessを置き換えない。一般的なHTTPS/www転送より前、Laravelのフォールバックより前へ挿入する。
- legacy-form-closed.html：公開承認後、公開ルートの同名ファイルへ配置する410エラー案内。新フォームのリンクだけを示し、旧POSTの入力値を引き継がない。
- 旧CGI自体も切替時に非公開退避する。転送ルールだけに停止を依存しない。
- HTTP_HOSTによる対象限定で、preview/checkを対象にしない。既存設定の別の転送ルールまで無効化する仕組みではないため、統合後の全体確認が必要。
- index.phpは本番用の固定リリース入口を別途生成。既存.user.iniのPHP設定は保持する。
- 新規案内HTMLも最終変更一覧とバックアップ/切り戻し対象へ加える。既存.htaccessの旧ハッシュ、統合後ハッシュ、退避先を確定する。

## 決定した対応案

| 旧URL・操作 | 切替後の案 |
|---|---|
| GET/HEAD /index.htm | 301で / |
| GET/HEAD /okome2.htm | 301で /#story |
| GET/HEAD /order.htm | 301で /#guide |
| GET/HEAD /otoiawase.htm | 301で /#contact |
| GET/HEAD /komeko/komeko.htm | 301で /#products |
| GET/HEAD /komenuka/komenuka_Top.htm | 301で /#products |
| 上記旧ページへのGET/HEAD以外の送信 | 410で利用終了案内。新受付へ再送しない |
| /kantan-cgi/formmail.pl（クエリあり・PATH_INFOを含む） | 全メソッド410。旧CGIを実行しない |
| /qa.htm | 対応するFAQ未作成のため保留 |
| /low.htm | 販売者情報を含む正式な移転先が未確定のため保留 |
| その他の画像・PDF・旧ページ | 一括転送しない。残置/退避の判断を別途行う |

旧ページ転送ではクエリ文字列を削除し、フォーム由来の情報を転送先URLへ混ぜない。CGIの410は一般的なHTTPS/www転送より先に判定する。

## 検証状況

2026-10-20、PC内のApacheで、この断片を独立した最小構成へ読み込ませた構文検査はSyntax OK。サーバー起動や既存設定の切替は行っていない。実際のXserverの統合設定・リダイレクト・410エラー内部処理・サブドメインへの継承は未検証で、構文成功を動作確認の代わりにしない。

公開前の検証環境で、各旧URLのGET/HEAD、POST、クエリ付きアクセス、CGIのGET/POSTとPATH_INFO、トップ表示、実在画像、未知URLの404、preview/checkの認証を確認する。テストデータを実CGIへ送らない。410は本文と状態の両方を確認し、内部エラーで200や500にならないことを確認する。

公開承認・販売者情報確定・保留URLの整理・統合設定の検証・直前バックアップが完了するまで適用しない。

参考：[Apache Core（If / ErrorDocument）](https://httpd.apache.org/docs/2.4/mod/core.html)

### PC内ApacheでのHTTP検証（2026-10-20）

127.0.0.1限定の一時Apacheとテスト専用ダミーファイルで、旧URL処理部分を.htaccessとして読み込み、32ケース成功。実サイト・実CGIへは送信していない。一時サーバーは検証後に終了した。

- 旧HTML6パスのGET/HEAD：計12件、対応するアンカーへの301とクエリ除去を確認。
- 同じ旧HTMLへのPOST：6件、410と利用終了HTMLの本文、新受付への転送がないことを確認。
- CGIのGET/HEAD/POSTとクエリ付き・PATH_INFO付き：6件、410。GET/POSTでは案内本文も確認。
- preview/checkホスト各2パス：4件、本番用転送・停止ルールが適用されずダミー原本が返ることを確認。
- 通常ファイル・保留中qa.htm/low.htm：3件、一括転送されないことを確認。
- 未知のファイル：1件、404。

この検証ではHTTPS/www統一のブロック以降を除外している。TLS・トップのindex.php優先・Laravel/既存Xserver設定との統合・実サブドメインへの継承は未検証。この結果を公開先での全体動作確認済みとは扱わない。

### 既存設定とHTTP/HTTPSを組み合わせた検証（2026-10-20）

現行ルート.htaccessを非公開領域へ読み取り保存し、新しい断片・現行設定・Laravel標準ルールを組み合わせてPC内Apacheで検証した。既存設定の原文や環境変数値、テスト用秘密鍵はGitへ保存していない。

統合検証に合わせて案を修正：

- 別ホストを一律にLで終了させず、本番ホストの条件を各ルールへ付与。preview/checkの既存HTTP→HTTPS転送を保持する。
- 410の内部エラー文書はHTTPS/www転送から除外し、HTTPの旧フォーム送信にも410案内を返す。
- トップ優先は本番ホスト内のDirectoryIndex index.phpで指定。既存index.htmを残したダミー環境でも新入口が優先されることを確認。

112ケース成功：

- HTTP/HTTPS × wwwあり/なし × 旧HTML6件のGET/HEAD転送・POST停止：72件。
- 同じ4条件 × CGIのGET/HEAD/POST × クエリ/PATH_INFO：24件。
- HTTPまたはwwwから正規HTTPS URLへのGET/POSTの308：6件。メソッド保持用の状態とLocationを確認し、外部へ転送追従していない。
- preview/check各2パスのHTTPS非干渉と既存HTTP→HTTPSの301保持：8件。
- 本番HTTPSトップで新入口優先、通常ファイルの維持：2件。

接続先は127.0.0.1のみ。テスト証明書はテスト用SSLコンテキストの信頼対象へ明示登録し、証明書検証を無効化していない。検証サーバーは終了済み。

これはWindows版Apacheとダミー入口による配信ルールの統合検証であり、Xserver固有のモジュール・nginx前段・PHP/FastCGI・実LaravelのHTTP動作までは検証していない。実サーバーへの適用前にその環境での確認が必要。旧CGIは実行せず、外部サイトへのPOSTは行っていない。
