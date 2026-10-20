<form id="request-form" action="{{ route('inquiries.store') }}" method="post">
 @csrf
 @unless(config('farm.inquiries_enabled'))<p class="notice" role="status">現在フォーム受付を停止しています。</p><fieldset disabled style="border:0;padding:0;margin:0;min-width:0">@endunless
 <input type="hidden" id="submission-key" value="{{ session('inquiry_token') }}">
 <noscript><p>この入力フォームはJavaScriptが必要です。メールでもお問い合わせいただけます。</p></noscript>
 <div id="form-errors" class="form-errors" role="alert" tabindex="-1" hidden></div>
 <div class="request-fields">
  <label for="name">お名前（必須）<input id="name" name="name" autocomplete="name" maxlength="100" required placeholder="例：三田村 太郎"></label>
  <label for="email">返信先メールアドレス（必須）<input id="email" name="email" type="email" autocomplete="email" maxlength="254" required placeholder="例：name@example.com"></label>
 </div>
 <div class="spam-trap" aria-hidden="true"><label for="website">この欄は空欄にしてください<input id="website" type="text" tabindex="-1" autocomplete="off"></label></div>
 <label for="kind">ご用件<select id="kind"><option>購入希望・お見積もり</option><option>商品についてのご質問</option><option>その他のお問い合わせ</option></select></label>
 <p class="form-hint">送信時点では注文は確定せず、お支払いも発生しません。在庫を確認し、商品代金・精米料・送料などを含むお見積もりをメールでご案内します。内容にご了承いただいてから、注文確定となります。</p>
 <p id="selection-status" class="selection-status" role="status" aria-live="polite">商品ごとに数量を入力できます。複数の商品もまとめてお知らせください。</p>
 <div id="request-items"></div>
 <button type="button" id="add-item" class="add-item">＋ 商品を追加する</button>
 <div id="price-estimate" class="price-summary" role="status" aria-live="polite" aria-atomic="true"></div>
 <label for="region">お届け先の都道府県（購入希望・お見積もりでは必須）<select id="region"><option value="">選択してください</option>@foreach(config('farm.prefectures') as $prefecture)<option>{{ $prefecture }}</option>@endforeach</select></label>
 <label for="message">ご相談内容<textarea id="message" rows="4" maxlength="2000" placeholder="袋分けのご希望・商品についてのご質問など"></textarea></label>
 <details class="privacy-notice"><summary>入力情報の取り扱いについて</summary><p>お名前・メールアドレス・ご希望の商品・配送地域・相談内容は、お問い合わせへの回答と注文手続きのために使用します。配送先の詳細はこの段階では不要です。<a href="#privacy-policy">保存期間・個人情報の取り扱いの詳細</a>もご確認ください。@unless($publicCopy ?? false)開発中はテスト用の情報だけをご入力ください。@endunless</p></details>
 <label class="privacy-check"><input id="privacy" type="checkbox" required>入力情報の取り扱いを確認しました（必須）</label>
 <button class="button" type="submit">内容を確認する <span>→</span></button>
 <small><a href="#delivery">送料・発送・返品条件</a>をご確認ください。確認画面で「この内容で受付する」を押すと保存します。注文はまだ確定しません。@include('mail-mode')</small>
 @unless(config('farm.inquiries_enabled'))</fieldset>@endunless
</form>
<template id="item-template">
 <fieldset class="request-item"><legend>ご希望の商品</legend>
  <div class="item-main">
   <label class="item-product-label">商品<select class="item-product"><option value="">選択してください</option>@foreach($products->where('active', true) as $product)<option>{{ $product->name }}</option>@endforeach</select></label>
   <label class="item-quantity-label">数量<select class="item-quantity" disabled><option value="">先に商品を選んでください</option></select></label>
  </div>
  <label class="item-milling-label" hidden>分づきのご希望<select class="item-milling"><option>未定・指定なし</option><option>1分</option><option>3分</option><option>5分</option><option>8分</option></select></label>
  <button class="remove-item" type="button">この商品欄を削除</button>
 </fieldset>
</template>

<script id="product-master" type="application/json">{!! json_encode($products->values()->map->only(['name','unit_kg','unit_label','max_units','active','milling','price_yen']), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
<script id="milling-fees" type="application/json">{!! json_encode(config('farm.milling_fees_yen'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
