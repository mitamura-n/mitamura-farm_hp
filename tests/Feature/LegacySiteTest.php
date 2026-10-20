<?php

namespace Tests\Feature;

use App\Http\Controllers\LegacySiteController;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LegacySiteTest extends TestCase
{
    public function test_archive_requires_login(): void
    {
        $this->get('/admin/legacy-site/index.htm')->assertRedirect('/admin/login');
    }

    public function test_private_archive_is_read_only_and_rejects_nonstatic_files(): void
    {
        $this->actingAs((new \App\Models\User())->forceFill(['id'=>1,'is_admin'=>true]));
        $temporary = sys_get_temp_dir().'/farm-legacy-'.bin2hex(random_bytes(8));
        mkdir($temporary.'/app/private/legacy-site', 0700, true);
        $original = app()->storagePath();
        app()->useStoragePath($temporary);
        try {
            $base = $temporary.'/app/private/legacy-site';
            file_put_contents($base.'/index.htm', '<html><a href="https://mitamurafarm.com/order.htm">注文</a><img src="/top.jpg"><form action="formmail.cgi"></form></html>');
            file_put_contents($base.'/formmail.cgi', 'not executable');
            file_put_contents($temporary.'/app/private/outside.htm', 'private');
            $controller = new LegacySiteController();
            $response = $controller->show('index.htm');
            $this->assertStringContainsString('/admin/legacy-site/order.htm', $response->getContent());
            $this->assertStringContainsString('/admin/legacy-site/top.jpg', $response->getContent());
            $this->assertStringContainsString("form-action 'none'", $response->headers->get('Content-Security-Policy'));
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            foreach (['formmail.cgi', '../outside.htm', '.env'] as $path) {
                try { $controller->show($path); $this->fail('Unsafe file was served.'); }
                catch (\Symfony\Component\HttpKernel\Exception\HttpException $error) { $this->assertSame(404, $error->getStatusCode()); }
            }
        } finally {
            app()->useStoragePath($original);
            File::deleteDirectory($temporary);
        }
    }
}
