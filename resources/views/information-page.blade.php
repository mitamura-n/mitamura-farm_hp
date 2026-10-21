@php($publicCopy = app()->environment('production') && (!config('farm.preview_require_login') || config('farm.verification_mode')))
<!doctype html><html lang="ja"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">@include('seo-head')<link rel="stylesheet" href="/style.css"><link rel="stylesheet" href="/brand-theme.css?v=17"><link rel="icon" href="/images/brand/favicon-transparent.png?v=3"></head><body>
<header><a class="brand brand-image" href="/" aria-label="三田村農園 ホーム"><img src="/images/brand/mitamura-main-transparent.png?v=1" alt="三田村農園 MITAMURA FARM" width="1462" height="1076"></a><a class="information-link" href="/#products">お米と商品へ →</a></header>
<main class="information-page"><a href="/">ホーム</a><h1>{{ $page['title'] }}</h1>@include($page['view'], ['standalone'=>true])<a class="information-link" href="/#contact">お問い合わせ・注文相談へ →</a></main>
<footer><p>三田村農園</p><nav class="information-footer" aria-label="ご利用案内"><a href="/tokushoho">特定商取引法に基づく表記</a><a href="/privacy">プライバシーポリシー</a><a href="/shipping">送料・発送・返品</a><a href="/#contact">お問い合わせ</a></nav><small>© 三田村農園</small></footer>
</body></html>
