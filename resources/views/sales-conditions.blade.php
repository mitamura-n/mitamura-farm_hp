<div class="sales-conditions">
 <div><h3>お届けするお米の年産</h3><p>{{ implode('・', config('farm.harvest_years')) }}をご案内します。在庫とお届けできる年産は、ご希望を受け付けてから確認します。年産のご希望があれば、ご相談内容にお書きください。</p></div>
 <div><h3>精米料</h3><table><caption>精米前の玄米重量と料金（税込）</caption><thead><tr><th scope="col">玄米重量</th><th scope="col">精米料</th></tr></thead><tbody>@foreach(config('farm.milling_fees_yen') as $kg=>$yen)<tr><th scope="row">{{ $kg }}kg</th><td>{{ number_format($yen) }}円</td></tr>@endforeach</tbody></table><small>表にない数量や袋分けがある場合の精米料は、返信でご案内します。</small></div>
 <section class="payment-methods" id="payment-methods" aria-labelledby="payment-methods-title">
  <h3 id="payment-methods-title">お支払い方法</h3>
  <p>商品代金・精米料・送料などの合計をご案内し、ご了承後に注文を確定します。</p>
  <ul class="payment-method-list" role="list">
   <li>
    <h4>銀行振込</h4>
    <dl>
     <div><dt>支払時期</dt><dd>注文確定のご案内から7日以内</dd></div>
     <div><dt>手数料</dt><dd>振込手数料はお客様負担</dd></div>
    </dl>
   </li>
   <li>
    <h4>郵便振替</h4>
    <dl>
     <div><dt>支払時期</dt><dd>注文確定のご案内から7日以内</dd></div>
     <div><dt>手数料</dt><dd>払込手数料はお客様負担</dd></div>
    </dl>
   </li>
   <li>
    <h4>代金引換</h4>
    <dl>
     <div><dt>支払時期</dt><dd>商品のお受け取り時</dd></div>
     <div><dt>手数料</dt><dd>330円（税込・お客様負担）</dd></div>
    </dl>
   </li>
   <li>
    <h4>クレジットカード</h4>
    <p class="payment-method-detail">Square請求書でお支払い</p>
    <dl>
     <div><dt>支払時期</dt><dd>請求書メールの送信から7日以内</dd></div>
     <div><dt>手数料</dt><dd>決済手数料は農園負担</dd></div>
    </dl>
    <a class="information-link" href="/tokushoho#card-payment">クレジットカード払いの流れを見る →</a>
   </li>
  </ul>
  <p>期限内のお支払いが難しい場合は、農園へご連絡ください。</p>
  <p>通常のご注文では袋代はかかりません。大口など個別対応のご注文は、内容を確認してお見積もりをご案内します。</p>
  <p><a href="/shipping">送料・発送・返品について確認する ↓</a></p>
 </section>
</div>
