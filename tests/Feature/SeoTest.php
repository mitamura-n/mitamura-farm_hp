<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_remains_excluded_and_has_no_public_sitemap(): void
    {
        $this->get('/')->assertOk()->assertSee('noindex,nofollow', false)
            ->assertSee('og:title',false)->assertDontSee('rel="canonical"',false);
        $this->get('/sitemap.xml')->assertNotFound();
    }

    public function test_enabled_public_metadata_and_sitemap_contain_public_information_pages(): void
    {
        config(['seo.indexable'=>true]);
        $this->get('/')->assertOk()->assertSee('rel="canonical"',false)->assertSee('https://mitamurafarm.com/',false);
        $response=$this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type','application/xml; charset=UTF-8');
        $xml=simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $this->assertSame('https://mitamurafarm.com/',(string)$xml->url->loc);
        $this->assertCount(4,$xml->url);
        $locations = array_map(fn($node)=>(string)$node->loc, iterator_to_array($xml->url, false));
        foreach (['tokushoho','privacy','shipping'] as $path) $this->assertContains('https://mitamurafarm.com/'.$path, $locations);
        $response->assertDontSee('/admin')->assertDontSee('preview.mitamurafarm.com');
    }
}
