<?php

return [
    'preview_require_login' => env('PREVIEW_REQUIRE_LOGIN', false),
    'preview_setup_hash' => env('PREVIEW_SETUP_HASH'),
    'preview_setup_expires' => env('PREVIEW_SETUP_EXPIRES', 0),
    'inquiries_enabled' => env('INQUIRIES_ENABLED', env('APP_ENV') === 'local'),
    'prefectures' => ['北海道','青森県','岩手県','宮城県','秋田県','山形県','福島県','茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県','新潟県','富山県','石川県','福井県','山梨県','長野県','岐阜県','静岡県','愛知県','三重県','滋賀県','京都府','大阪府','兵庫県','奈良県','和歌山県','鳥取県','島根県','岡山県','広島県','山口県','徳島県','香川県','愛媛県','高知県','福岡県','佐賀県','長崎県','熊本県','大分県','宮崎県','鹿児島県','沖縄県'],
    'email' => 'mitamura.farm@gmail.com',
    'address' => '〒915-0856 福井県越前市中平吹町88-1-2',
    'harvest_years' => ['令和7年産', '令和8年産（新米）'],
    'milling_fees_yen' => [5=>100, 10=>200, 20=>300, 30=>400],
    'payment_methods' => ['銀行振込', '郵便振替', '代金引換'],
];
