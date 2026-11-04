<section id="sales-info" class="section">
    <p class="eyebrow">SHOP INFORMATION</p>
    @unless ($standalone ?? false)
        <h2>販売についてのご案内</h2>
    @endunless
    <dl>
        <dt>農園名</dt>
        <dd>三田村農園</dd>
        <dt>販売業者</dt>
        <dd>三田村 憲侑</dd>
        <dt>所在地</dt>
        <dd>{{ config('farm.address') }}</dd>
        <dt>電話番号</dt>
        <dd><a href="tel:0778235856">0778-23-5856</a></dd>
        <dt>お問い合わせ</dt>
        <dd><a href="mailto:{{ config('farm.email') }}">{{ config('farm.email') }}</a><br>ご注文の相談は、記録が残るフォームまたはメールで承ります。
        </dd>
    </dl>
    <p>商品価格・精米料は税込です。通常のご注文では袋代はかかりません。<br>送料と、銀行振込・郵便振替・代引きの決済手数料はお客様負担です。代引き手数料は330円（税込）です。</p>
    <p>在庫・お届け先・精米方法などを確認して合計金額をご案内し、<br>ご了承後に注文を確定します。<a href="/#products">商品・価格</a>、<a
            href="/shipping">送料・発送・返品・キャンセルの条件</a>をご確認ください。</p>
    <p>銀行振込・郵便振替は注文確定のご案内から7日以内にお支払いください。<br>難しい場合はご連絡ください。代引きは商品のお受け取り時のお支払いです。</p>
    <p>銀行振込・郵便振替・クレジットカード払いで、お支払い期限までにお支払いが確認できない場合は、ご注文を取り消させていただくことがあります。</p>
    <p>銀行振込・郵便振替・クレジットカード払いはお支払い確認後、代引きは注文確定後、<br>通常2〜3営業日以内（土日・祝日を除く）に発送します。遅れる場合はメールでご案内します。</p>
    @include('cod-return-policy')
    <section id="card-payment" class="card-payment-guide" aria-labelledby="card-payment-title">
        <h2 id="card-payment-title">クレジットカード決済の流れ</h2>
        <ol>
            <li><strong>お見積もりのご確認</strong>
                <p>ご注文のご相談を受け付けた後、商品代金・精米料・送料などを含む合計金額をご案内します。</p>
            </li>
            <li><strong>Square請求書の送付</strong>
                <p>お見積もりにご了承いただいた後、注文を確定し、Square請求書をメールでお送りします。</p>
            </li>
            <li><strong>お支払い手続き</strong>
                <p>請求書メールの送信から<strong>7日以内</strong>に、メール内の「カードで支払う」からお手続きください。</p>
                <p>クレジットカード決済手数料は当農園が負担します。</p>
            </li>
            <li><strong>商品の発送</strong>
                <p>お支払い確認後、通常<strong>2〜3営業日以内</strong>（土日・祝日を除く）に発送します。発送が遅れる場合は、メールでご案内します。</p>
            </li>
        </ol>
    </section>
</section>
