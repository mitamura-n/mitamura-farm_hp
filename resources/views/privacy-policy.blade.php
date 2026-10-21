<section id="privacy-policy" class="section">
 <p class="eyebrow">PRIVACY POLICY</p>@unless($standalone ?? false)<h2>個人情報の取り扱い</h2>@endunless
 @unless($publicCopy ?? false)<p class="pending">制作プレビュー：運用方針を暫定採用した案内です。公開前に管理体制・事業者情報・削除手順を最終確認します。</p>@endunless
 <p>三田村農園は、お問い合わせやご注文に伴いお預かりする個人情報を、以下のとおり取り扱います。</p>
 <details @if($standalone ?? false) open @endif><summary>取得する情報と利用目的</summary>
 <p>フォームでは、お名前、メールアドレス、ご希望の商品・数量・精米の希望、お届け先の都道府県、ご相談内容を取得し、お問い合わせへの回答、在庫・金額のご案内、注文手続きのために利用します。</p>
 <p>注文手続きでは、発送などに必要な範囲で配送先住所、宛名、連絡先、お支払いに関する情報を別途お知らせいただき、商品のお届け、入金確認、返品・交換対応に利用します。カード番号はフォームやメールに記載しないでください。</p></details>
 <details @if($standalone ?? false) open @endif><summary>情報の管理と外部サービス</summary>
 <p>業務に必要な担当者に限って情報を取り扱い、アクセス制限など必要な管理を行います。お問い合わせ内容はサイトの受付データと通知メールとして保存します。サイト運営にはXserver、メール対応にはGmailを使用します。</p>
 <p>配送会社などの業務委託先へ、必要な範囲で情報を提供する場合があります。法令に基づく場合等を除き、ご本人の同意なく個人情報を第三者に提供しません。</p></details>
 <details @if($standalone ?? false) open @endif><summary>保存期間と削除</summary>
 <p>注文に至らなかったお問い合わせは、対応終了後1年を目安に保管の必要性を確認し、不要となった情報を削除する方針です。注文に関する記録は、会計など法令上必要な保存期間や、継続中の対応に必要な期間を考慮して保管します。</p>
 <p class="pending">保存期限の管理・削除手順は整備中です。自動削除は行っていません。</p></details>
 @if(\App\Support\Analytics::enabled())
 <details id="analytics-privacy" open><summary>アクセス解析について</summary>
 <p>サイトの使いやすさやご案内の改善のため、Google LLCのGoogleアナリティクス（GA4）を利用します。解析を許可いただいた場合だけGoogleの計測プログラムを読み込み、Cookieを使用して閲覧ページ、流入元、端末・ブラウザに関する情報、受付完了件数などを計測します。Googleへの通信に伴いIPアドレス等が処理されます。</p>
 <p>氏名、メールアドレス、お問い合わせ本文、受付番号は解析に送信しません。ページURLの検索文字列と「#」以降は除外し、流入元はサイトのドメイン部分だけを送信します。広告配信向けの連携・Googleシグナルは使用しません。受付完了件数は購入・売上の確定件数とは異なります。</p>
 <p>許可しなくてもお問い合わせ・注文相談はご利用いただけます。選択はこのブラウザに180日間保存され、ページ下部の「アクセス解析の設定」からいつでも変更できます。許可を取り消すと、このサイトからの新たな計測を停止し、本サイトの解析Cookieを削除します。すでに送信済みのデータが自動で消えるものではありません。</p>
 <p>Googleアナリティクスのユーザー単位・イベント単位のデータ保持は2か月に設定し、再訪による保持期間の延長は行いません。集計済みの標準レポートには、この保持期間が適用されない場合があります。</p>
 <p>Googleによるデータの取り扱いは<a href="https://policies.google.com/technologies/partner-sites" target="_blank" rel="noopener noreferrer">Googleのサービスを使用するサイトやアプリから収集した情報の使用</a>と<a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">Googleプライバシーポリシー</a>をご確認ください。</p></details>
 @endif
 <h3>開示・訂正・利用停止等のご相談</h3><p>個人情報の開示、訂正、利用停止、消去等をご希望の場合は、<a href="mailto:{{ config('farm.email') }}">{{ config('farm.email') }}</a>へご連絡ください。ご本人確認のうえ、法令に従い対応します。</p>
</section>
