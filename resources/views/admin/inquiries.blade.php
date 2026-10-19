@extends('admin.layout')
@section('content')
<h2>問い合わせ受付一覧</h2>
<p>フォームの受付内容と対応状況を確認できます。メール本文や返信履歴の自動同期はありません。</p>
<form method="get" action="{{ route('admin.inquiries.index') }}"><label>対応状況で絞り込む<select name="status"><option value="">すべて</option>@foreach($statuses as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>@endforeach</select></label><button>表示する</button></form>
<p><a href="{{ route('admin.inquiries.index',['retention'=>'due']) }}">保管の見直し対象を見る</a> ／ <a href="{{ route('admin.inquiries.index') }}">すべて見る</a></p><small>注文に至らず対応完了から1年が経過した記録を抽出します。自動削除はしません。メールやバックアップも別途確認が必要です。未確認の注文結果・終了日未記録の記録は対象外です。</small><p>全{{ $inquiries->total() }}件 ／ {{ $inquiries->currentPage() }}ページ目</p>
@forelse($inquiries as $inquiry)
<article><h3><a href="{{ route('admin.inquiries.show',$inquiry) }}">{{ $inquiry->receipt }}</a></h3>
<p>{{ $inquiry->created_at->timezone('Asia/Tokyo')->format('Y/m/d H:i') }}（日本時間） ／ {{ $statuses[$inquiry->status] ?? $inquiry->status }}</p>
<p>{{ $inquiry->name }} 様 ／ {{ $inquiry->kind }}</p>
<small>農園への通知：{{ $inquiry->admin_notice === 'sent' ? '送信済み' : ($inquiry->admin_notice === 'logged' ? '開発ログのみ・未送信' : ($inquiry->admin_notice === 'failed' ? '送信失敗' : '処理待ち')) }}</small></article>
@empty<p class="notice">該当する問い合わせはありません。</p>@endforelse
<nav aria-label="ページ移動">@if($inquiries->previousPageUrl())<a href="{{ $inquiries->previousPageUrl() }}">前のページ</a>@endif @if($inquiries->nextPageUrl())<a href="{{ $inquiries->nextPageUrl() }}">次のページ</a>@endif</nav>
@endsection
