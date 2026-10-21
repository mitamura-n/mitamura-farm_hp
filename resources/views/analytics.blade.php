@if(\App\Support\Analytics::enabled())
<div class="analytics-controls">
 <button type="button" id="analytics-settings" aria-controls="analytics-choice" aria-expanded="false">アクセス解析の設定</button>
</div>
<section id="analytics-choice" class="analytics-choice" aria-labelledby="analytics-heading" hidden>
 <div><h2 id="analytics-heading">サイト改善のためのアクセス解析</h2><p>許可いただいた場合に、閲覧ページ・流入元・受付完了件数をGoogleアナリティクスで計測します。お問い合わせの入力内容は送信しません。<a href="/privacy#analytics-privacy">詳しく見る</a></p></div>
 <div class="analytics-actions"><button type="button" id="analytics-allow">許可する</button><button type="button" id="analytics-decline">許可しない</button></div>
</section>
<script id="analytics-config" type="application/json">@json(['measurementId'=>config('analytics.measurement_id')])</script>
<script src="/analytics.js?v=1" defer></script>
@endif
