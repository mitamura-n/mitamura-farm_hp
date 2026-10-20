<meta name="robots" content="{{ config('seo.indexable') ? 'index,follow' : 'noindex,nofollow' }}">
<title>{{ $pageTitle ?? config('seo.title') }}</title>
<meta name="description" content="{{ $pageDescription ?? config('seo.description') }}">
@if(config('seo.indexable'))<link rel="canonical" href="{{ $pageUrl ?? config('seo.url') }}">@endif
<meta property="og:type" content="website">
<meta property="og:locale" content="ja_JP">
<meta property="og:site_name" content="三田村農園">
<meta property="og:title" content="{{ $pageTitle ?? config('seo.title') }}">
<meta property="og:description" content="{{ $pageDescription ?? config('seo.description') }}">
<meta property="og:url" content="{{ config('seo.indexable') ? ($pageUrl ?? config('seo.url')) : url()->current() }}">
<meta property="og:image" content="{{ config('seo.indexable') ? config('seo.url') : rtrim(config('app.url'), '/').'/' }}images/brand/mitamura-main.png">
<meta property="og:image:alt" content="三田村農園のロゴ">
<meta name="twitter:card" content="summary">

@if(config('seo.indexable'))
@if(config('seo.site_verification'))<meta name="google-site-verification" content="{{ config('seo.site_verification') }}">@endif
@php
$websiteStructuredData = ['@context'=>'https://schema.org','@type'=>'WebSite','name'=>'三田村農園','url'=>config('seo.url')];
@endphp
<script type="application/ld+json">@json($websiteStructuredData)</script>
@endif
