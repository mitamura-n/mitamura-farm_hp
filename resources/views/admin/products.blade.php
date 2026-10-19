@extends('admin.layout')
@section('content')
<p class="notice">価格は税込で入力してください。販売中の規格・数量上限・受付状態を管理します。保存するとサイトに反映されます。過去の受付内容は変更されません。</p>
<p>数量は「1口の重量 × 口数」で受け付けます。白米・分づき米は精米前の玄米重量です。価格は1口あたりで、精米料・送料は含みません。</p>
@foreach($products as $product)
<article><h2>{{ $product->name }}</h2><form method="post" action="/admin/products/{{ $product->id }}">@csrf @method('PUT')<input type="hidden" name="version" value="{{ $product->getRawOriginal('updated_at') }}">
<div class="grid"><label>1口の重量（kg）<input type="number" name="unit_kg" required min="1" max="1000" step="1" value="{{ $product->unit_kg }}"></label>
<label>規格の表示名<input name="unit_label" required maxlength="80" value="{{ $product->unit_label }}"><small>例：玄米重量10kg分、1kg入り1袋。重量と一致させてください。</small></label>
<label>数量の上限（口）<input type="number" name="max_units" min="1" max="999" step="1" value="{{ $product->max_units }}"><small>空欄なら販売上限なし（入力は最大999口）。</small></label>
<label>1口の参考価格（円）<input type="number" name="price_yen" min="0" max="10000000" step="1" value="{{ $product->price_yen }}"><small>空欄なら「お問い合わせ」。税込の金額を入力。</small></label>
<label>受付状態<select name="active"><option value="1" @selected($product->active)>受付中</option><option value="0" @selected(!$product->active)>受付停止</option></select></label></div>
<button>{{ $product->name }}を保存</button></form></article>
@endforeach
@endsection
