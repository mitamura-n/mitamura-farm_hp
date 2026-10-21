<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function enableAnalytics(): void
    {
        $this->app['env'] = 'production';
        config(['farm.preview_require_login'=>false, 'farm.verification_mode'=>false,
            'analytics.enabled'=>true, 'analytics.measurement_id'=>'G-TEST123']);
    }

    public function test_public_pages_offer_choice_and_privacy_without_loading_google_directly(): void
    {
        $this->enableAnalytics();
        foreach (['/', '/privacy', '/shipping', '/tokushoho'] as $path) {
            $response = $this->get('https://mitamurafarm.com'.$path)->assertOk()
                ->assertSee('id="analytics-config"', false)->assertSee('G-TEST123')
                ->assertSee('id="analytics-choice"', false)->assertSee('許可しない')
                ->assertDontSee('src="https://www.googletagmanager.com', false);
            $this->assertSame(1, substr_count($response->getContent(), 'src="/analytics.js?v=1"'));
        }
        $this->get('https://mitamurafarm.com/privacy')->assertSee('アクセス解析について')
            ->assertSee('180日間')->assertSee('氏名、メールアドレス、お問い合わせ本文、受付番号は解析に送信しません');
    }

    public function test_disabled_preview_verification_and_other_hosts_do_not_track(): void
    {
        $this->enableAnalytics();
        $this->get('https://mitamurafarm.com/admin/login')->assertDontSee('analytics.js', false);
        foreach (['https://preview.mitamurafarm.com/', 'https://check.mitamurafarm.com/', 'http://localhost/'] as $url) {
            $this->get($url)->assertDontSee('analytics.js', false);
        }
        foreach (['analytics.enabled'=>false, 'farm.verification_mode'=>true] as $key=>$value) {
            $this->enableAnalytics();
            config([$key=>$value]);
            $this->get('https://mitamurafarm.com/')->assertDontSee('analytics.js', false);
        }
        $this->enableAnalytics();
        $this->app['env'] = 'local';
        $this->get('https://mitamurafarm.com/privacy')->assertDontSee('アクセス解析について');
    }

    public function test_invalid_measurement_id_cannot_inject_markup(): void
    {
        $this->enableAnalytics();
        config(['analytics.measurement_id'=>'G-TEST</script><script>alert(1)</script>']);
        $this->get('https://mitamurafarm.com/')->assertDontSee('analytics-config', false)->assertDontSee('alert(1)', false);
    }
}
