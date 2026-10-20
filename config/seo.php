<?php

return [
    // Publication must be explicitly enabled after the launch review.
    'indexable' => env('SEO_INDEXABLE', false) && env('APP_ENV') === 'production' && !env('PREVIEW_REQUIRE_LOGIN', false),
    'pages' => [
        'tokushoho' => ['title'=>'特定商取引法に基づく表記','description'=>'三田村農園の販売者情報、お支払い方法・手数料、発送、返品・キャンセルに関するご案内です。','view'=>'sales-info'],
        'privacy' => ['title'=>'プライバシーポリシー','description'=>'三田村農園のお問い合わせ・注文に伴う個人情報の取得目的、管理、保存期間、お問い合わせ窓口をご案内します。','view'=>'privacy-policy'],
        'shipping' => ['title'=>'送料・発送・返品について','description'=>'三田村農園の地域別送料、発送の目安、ご注文の変更・キャンセル、返品・交換についてご案内します。','view'=>'delivery-conditions'],
    ],
    'site_verification' => env('GOOGLE_SITE_VERIFICATION', ''),
    'url' => 'https://mitamurafarm.com/',
    'title' => '農薬・肥料を使わないお米・米粉の直販｜福井県越前市 三田村農園',
    'description' => '福井県越前市の三田村農園。コシヒカリ（オーガニック米）を農家直送。農薬・肥料を使わず育てた玄米・白米・分づき米、米粉・米ぬかを直販しています。白米は注文後に精米し、送料込みのお見積もりをご案内します。',
];
