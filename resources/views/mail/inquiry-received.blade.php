{{ $forCustomer ? $inquiry->name.' 様' : '三田村農園 ご担当者様' }}

受付番号：{{ $inquiry->receipt }}
ご用件：{{ $inquiry->kind }}

お問い合わせを受け付けました。この時点では注文は確定していません。
在庫・商品代金・精米料・送料などを確認し、メールでご案内します。

@if($forCustomer && in_array($inquiry->kind, ['購入希望・お見積もり', '注文の相談'], true))
銀行振込の振込手数料・郵便振替の払込手数料はお客様負担です。
代引き手数料は330円（税込・お客様負担）です。
お支払いは、農園から注文確定のご案内が届くまでお待ちください。
@endif

@foreach($inquiry->items as $item)
・{{ $item['product'] }} {{ $item['quantity_text'] ?? $item['quantity'].'kg' }}{{ empty($item['milling']) ? '' : '（'.$item['milling'].'）' }}
@endforeach
お届け先の都道府県：{{ $inquiry->region ?: '未指定' }}
ご相談内容：
{{ $inquiry->message ?: '記入なし' }}

@unless($forCustomer)
お名前：{{ $inquiry->name }}
返信先：{{ $inquiry->email }}
@endunless

三田村農園
{{ config('farm.email') }}
※お心当たりのない場合は、このメールを破棄してください。
