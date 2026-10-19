<?php

return [
    // Publication must be explicitly enabled after the launch review.
    'indexable' => env('SEO_INDEXABLE', false) && env('APP_ENV') === 'production' && !env('PREVIEW_REQUIRE_LOGIN', false),
    'url' => 'https://mitamurafarm.com/',
    'title' => '三田村農園｜福井・越前、日野山の麓で育てるコシヒカリ',
    'description' => '福井県越前市の三田村農園。日野山の麓、先祖代々の田んぼで農薬・肥料を使わず育てるコシヒカリ。玄米・白米・分づき米、米粉・米ぬかをご案内。送料を含む金額をご確認後に注文確定となります。',
];
