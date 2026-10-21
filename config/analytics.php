<?php

return [
    // Enable only after the Google account, web stream and privacy settings are ready.
    'enabled' => env('GA4_ENABLED', false),
    'measurement_id' => env('GA4_MEASUREMENT_ID', ''),
];
