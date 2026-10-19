@extends('admin.layout')
@section('content')
<p><a href="{{ route('admin.inquiries.index') }}">← 受付一覧へ</a></p>
<article><h2>{{ $inquiry->receipt }}</h2>
<p>受付：{{ $inquiry->created_at->timezone('Asia/Tokyo')->format('Y/m/d H:i') }}（日本時間）</p>
<dl><dt>お名前</dt><dd>{{ $inquiry->name }} 様</dd><dt>返信先メール</dt><dd>{{ $inquiry->email }}</dd><dt>ご用件</dt><dd>{{ $inquiry->kind }}</dd><dt>お届け先の都道府県</dt><dd>{{ $inquiry->region ?: '未指定' }}</dd></dl>
<h3>希望の商品</h3><ul>@forelse($inquiry->items as $item)<li>{{ $item['product'] }}：{{ $item['quantity_text'] ?? $item['quantity'].'kg' }} @if(!empty($item['milling']))（{{ $item['milling'] }}）@endif</li>@empty<li>商品指定なし</li>@endforelse</ul>
<h3>相談内容</h3><p style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $inquiry->message ?: '記入なし' }}</p>
<h3>メール通知</h3>
@foreach(['農園への通知'=>$inquiry->admin_notice,'お客様への受付通知'=>$inquiry->customer_notice] as $label=>$notice)<p>{{ $label }}：{{ ['sent'=>'送信済み','logged'=>'開発ログのみ・未送信','failed'=>'送信失敗','pending'=>'処理待ち'][$notice] ?? $notice }}</p>@endforeach
<small>送信済みは送信処理の成功を示し、受信箱への到達を保証するものではありません。</small>
</article>
<article><h2>対応状況</h2><p>農園内の対応記録です。変更してもメール送信や注文確定は行われません。</p>
<form method="post" action="{{ route('admin.inquiries.update',$inquiry) }}">@csrf @method('PUT')<input type="hidden" name="version" value="{{ $inquiry->getRawOriginal('updated_at') }}"><label>対応状況<select name="status">@foreach($statuses as $value=>$label)<option value="{{ $value }}" @selected($inquiry->status===$value)>{{ $label }}</option>@endforeach</select></label><button>対応状況を保存</button></form></article>
@endsection
