<meta name="robots" content="{{ config('seo.indexable') ? 'index,follow' : 'noindex,nofollow' }}">
<title>{{ config('seo.title') }}</title>
<meta name="description" content="{{ config('seo.description') }}">
@if(config('seo.indexable'))<link rel="canonical" href="{{ config('seo.url') }}">@endif
<meta property="og:type" content="website">
<meta property="og:locale" content="ja_JP">
<meta property="og:site_name" content="三田村農園">
<meta property="og:title" content="{{ config('seo.title') }}">
<meta property="og:description" content="{{ config('seo.description') }}">
<meta property="og:url" content="{{ config('seo.indexable') ? config('seo.url') : rtrim(config('app.url'), '/').'/' }}">
<meta property="og:image" content="{{ config('seo.indexable') ? config('seo.url') : rtrim(config('app.url'), '/').'/' }}images/brand/mitamura-main.png">
<meta property="og:image:alt" content="三田村農園のロゴ">
<meta name="twitter:card" content="summary">
