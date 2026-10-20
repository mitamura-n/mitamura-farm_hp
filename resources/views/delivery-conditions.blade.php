<section id="delivery" class="section">
 <p class="eyebrow">DELIVERY & RETURNS</p><h2>送料・発送・返品について</h2>
 <p>送料は税込・1個口の目安です。梱包後の重量・箱サイズ・組み合わせを確認し、注文確定前に合計金額をご案内します。</p>
 <details><summary>地域別の送料表を見る（税込）</summary>
 <div style="overflow-x:auto" tabindex="0" role="region" aria-label="通常便の送料表（横にスクロールできます）"><table style="min-width:580px;width:100%"><caption>通常便の送料（円）</caption><thead><tr><th scope="col">地域</th>@foreach([60,80,100,120] as $size)<th scope="col">{{ $size }}サイズ</th>@endforeach</tr></thead><tbody>
 @foreach(['関東・信越・北陸・東海・近畿'=>[800,1100,1300,1600],'東北・中国・四国'=>[900,1200,1400,1600],'九州'=>[1000,1300,1500,1700],'北海道'=>[1300,1600,1800,2200],'沖縄'=>[1400,1800,2000,2200]] as $region=>$fees)
 <tr><th scope="row">{{ $region }}</th>@foreach($fees as $fee)<td>{{ number_format($fee) }}</td>@endforeach</tr>@endforeach
 <tr><th scope="row">その他離島</th><td colspan="4">個別にご案内</td></tr></tbody></table></div>
 <p>お米5kgは80サイズ、10kg・20kgは100サイズが目安です。重量の判定には箱・梱包材を含みます。米粉・米ぬかや複数商品の組み合わせは、個別に送料をご案内します。</p>
 <table style="width:100%"><caption>梱包後25kg超〜30kg：重量ゆうパック・120サイズ（税込）</caption><thead><tr><th scope="col">地域</th><th scope="col">送料</th></tr></thead><tbody>
 @foreach(['関東・信越・北陸・東海・近畿'=>2390,'東北・中国・四国'=>2500,'九州'=>2640,'北海道'=>3060,'沖縄'=>3190] as $region=>$fee)<tr><th scope="row">{{ $region }}</th><td>{{ number_format($fee) }}円</td></tr>@endforeach
 <tr><th scope="row">その他離島</th><td>個別にご案内</td></tr></tbody></table>
 <p>30kgを超える場合や複数個口になる場合も個別にご案内します。</p></details>
 <h3>発送の目安</h3><p>銀行振込・郵便振替は入金確認後、代引きは注文確定後、通常2〜3営業日以内に発送します。営業日は土日・祝日を除きます。在庫や精米の状況により遅れる場合は、メールでご案内します。</p>
 <details><summary>返品・交換について</summary>
 <p>食品のため、お客様のご都合による返品・交換は、未開封の場合もお受けしておりません。</p>
 <p>商品に品質上の問題や破損がある場合、またはご注文と異なる商品が届いた場合は、商品到着後7日以内を目安に、お問い合わせフォームまたはメールでお知らせください。内容を確認し、交換または返金にて対応いたします。この場合の返品・再発送にかかる送料は、当農園が負担いたします。</p>
 <p>状態の確認のため、可能であれば写真をお送りください。商品・袋・梱包材は、対応が決まるまで保管し、返送は当農園からのご案内をお待ちください。7日を過ぎて問題に気づいた場合も、まずはご連絡ください。</p>
 </details>
 @unless($publicCopy ?? false)<p class="pending">制作プレビュー：発送・返品条件は暫定の方針です。公開前に最終確認します。</p>@endunless
</section>
