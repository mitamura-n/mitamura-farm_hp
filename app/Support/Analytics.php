<?php

namespace App\Support;

final class Analytics
{
    public static function enabled(): bool
    {
        return app()->environment('production')
            && config('analytics.enabled')
            && ! config('farm.preview_require_login')
            && ! config('farm.verification_mode')
            && request()->getHost() === 'mitamurafarm.com'
            && in_array(request()->path(), ['/', 'tokushoho', 'privacy', 'shipping'], true)
            && preg_match('/\AG-[A-Z0-9]+\z/', (string) config('analytics.measurement_id')) === 1;
    }
}
