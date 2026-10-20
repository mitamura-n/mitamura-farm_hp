<?php

return [
    // Publication must be explicitly enabled after the launch review.
    'indexable' => env('SEO_INDEXABLE', false) && env('APP_ENV') === 'production' && !env('PREVIEW_REQUIRE_LOGIN', false),
    'site_verification' => env('GOOGLE_SITE_VERIFICATION', ''),
    'url' => 'https://mitamurafarm.com/',
    'title' => '農薬・肥料を使わないお米・米粉の直販｜福井県越前市 三田村農園',
    'description' => '福井県越前市の三田村農園。有機JAS認証のコシヒカリ（オーガニック米）を農家直送。農薬・肥料を使わず育てた玄米・白米・分づき米、米粉・米ぬかを直販しています。白米は注文後に精米し、送料込みのお見積もりをご案内します。',
];
