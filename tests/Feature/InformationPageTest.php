<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InformationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_information_pages_keep_unique_metadata_and_original_conditions(): void
    {
        $this->app['env']='production';
        config(['farm.preview_require_login'=>false, 'seo.indexable'=>true]);
        foreach (['tokushoho'=>'代引き手数料は330円', 'privacy'=>'自動削除は行っていません', 'shipping'=>'重量ゆうパック'] as $path=>$text) {
            $response=$this->get('/'.$path)->assertOk()->assertSee($text)
                ->assertSee('href="https://mitamurafarm.com/'.$path.'"',false)
                ->assertSee('content="https://mitamurafarm.com/'.$path.'"',false)
                ->assertSee('href="/#contact"',false)->assertDontSee('制作プレビュー');
            $this->assertSame(1, substr_count($response->getContent(), '<h1>'));
        }
    }

    public function test_home_preserves_old_anchors_and_places_voices_after_products(): void
    {
        $response=$this->get('/')->assertOk();
        foreach (['delivery','sales-info','privacy-policy'] as $anchor) $response->assertSee('id="'.$anchor.'"',false);
        $html=$response->getContent();
        $this->assertLessThan(strpos($html,'id="voices"'), strpos($html,'id="products"'));
        $this->assertLessThan(strpos($html,'id="guide"'), strpos($html,'id="voices"'));
        $response->assertDontSee('重量ゆうパック')->assertSee('href="/shipping"',false);
    }

    public function test_private_information_pages_are_not_accessible_without_login(): void
    {
        config(['farm.preview_require_login'=>true]);
        foreach (['tokushoho','privacy','shipping'] as $path) $this->get('/'.$path)->assertRedirect('/admin/login');
    }
}
