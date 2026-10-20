<div class="sales-conditions">
 <div><h3>お届けするお米の年産</h3><p>{{ implode('・', config('farm.harvest_years')) }}をご案内します。在庫とお届けできる年産は、ご希望を受け付けてから確認します。年産のご希望があれば、ご相談内容にお書きください。</p></div>
 <div><h3>精米料</h3><table><caption>精米前の玄米重量と料金（税込）</caption><thead><tr><th scope="col">玄米重量</th><th scope="col">精米料</th></tr></thead><tbody>@foreach(config('farm.milling_fees_yen') as $kg=>$yen)<tr><th scope="row">{{ $kg }}kg</th><td>{{ number_format($yen) }}円</td></tr>@endforeach</tbody></table><small>表にない数量や袋分けがある場合の精米料は、返信でご案内します。</small></div>
 <div><h3>お支払いと送料</h3><p>{{ implode('・', config('farm.payment_methods')) }}に対応します。商品代金・精米料・送料などの合計と、お支払い手順をご案内し、ご了承後に注文を確定します。</p><p>銀行振込・郵便振替は、注文確定のご案内から7日以内にお支払いください。難しい場合はご連絡ください。代金引換は商品のお受け取り時のお支払いです。銀行振込の振込手数料は当農園負担、郵便振替の払込手数料はお客様負担です。代引き手数料は330円（税込・お客様負担）です。</p><p><a href="#delivery">送料・発送・返品について確認する ↓</a></p><small>クレジットカード決済は準備中です。現在はご利用いただけません。</small></div>
</div>
