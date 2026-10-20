<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ProductionVerification
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('farm.verification_mode')) return $next($request);
        $allowed = match ($request->method()) {
            'GET', 'HEAD' => $request->is('/', 'admin/login', 'admin/products', 'admin/inquiries', 'admin/inquiries/*'),
            'POST' => $request->is('admin/login', 'admin/logout', 'inquiries'),
            default => false,
        };
        abort_unless($allowed, 404);
        if ($request->is('inquiries')) abort_unless($request->user()?->is_admin, 403);
        $response=$next($request);
        $response->headers->set('Cache-Control','no-store, private');
        $response->headers->set('X-Robots-Tag','noindex, nofollow, noarchive');
        return $response;
    }
}
