<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePreviewLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('farm.preview_require_login') || $request->is('admin/login', 'preview-setup/*')) {
            return $next($request);
        }

        if (! $request->user()?->is_admin) {
            if ($request->expectsJson()) {
                return response()->json(['message'=>'プレビュー閲覧には管理者ログインが必要です。'], 401);
            }
            return redirect()->guest(route('login'));
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        return $response;
    }
}
