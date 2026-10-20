<?php

namespace App\Http\Controllers;

class LegacySiteController extends Controller
{
    public function show(string $path = '')
    {
        abort_unless(auth()->user()?->is_admin, 403);
        if ($path === '') return redirect('/admin/legacy-site/index.htm');
        $base = realpath(storage_path('app/private/legacy-site'));
        abort_unless($base, 404);
        $file = realpath($base.'/'.$path);
        abort_unless($file && str_starts_with($file, $base.DIRECTORY_SEPARATOR) && is_file($file), 404);
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $types = ['htm'=>'text/html', 'html'=>'text/html', 'css'=>'text/css',
            'jpg'=>'image/jpeg', 'jpeg'=>'image/jpeg', 'gif'=>'image/gif',
            'png'=>'image/png', 'webp'=>'image/webp', 'ico'=>'image/x-icon', 'pdf'=>'application/pdf'];
        abort_unless(isset($types[$extension]), 404);
        $headers = ['Cache-Control'=>'private, no-store', 'X-Robots-Tag'=>'noindex, nofollow, noarchive',
            'X-Content-Type-Options'=>'nosniff', 'Referrer-Policy'=>'no-referrer',
            'Content-Security-Policy'=>"default-src 'none'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; form-action 'none'; base-uri 'none'; frame-ancestors 'self'"];
        if (in_array($extension, ['htm', 'html', 'css'], true)) {
            $content = file_get_contents($file);
            $encoding = mb_detect_encoding($content, ['UTF-8', 'SJIS-win', 'EUC-JP', 'ISO-2022-JP'], true) ?: 'SJIS-win';
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            $content = preg_replace('/charset\s*=\s*["\']?[-\w]+/i', 'charset=UTF-8', $content);
            $content = preg_replace('~(?:https?:)?//(?:www\.)?mitamurafarm\.com/~i', '/admin/legacy-site/', $content);
            // Root-relative links must stay inside the protected historical copy.
            $content = preg_replace('~((?:href|src|action)\s*=\s*["\'])/(?!/|admin/legacy-site/)~i', '$1/admin/legacy-site/', $content);
            return response($content, 200, $headers + ['Content-Type'=>$types[$extension].'; charset=UTF-8']);
        }
        return response()->file($file, $headers + ['Content-Type'=>$types[$extension]]);
    }
}
