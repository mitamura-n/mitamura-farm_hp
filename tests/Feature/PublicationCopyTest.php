<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicationCopyTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_keeps_test_notices(): void
    {
        $this->get('/')->assertOk()->assertSee('制作プレビュー')->assertSee('テスト用の情報');
    }

    public function test_private_production_has_public_copy_but_disabled_form(): void
    {
        $this->app['env']='production';
        config(['farm.preview_require_login'=>false,'farm.inquiries_enabled'=>false]);
        $this->get('/')->assertOk()->assertDontSee('制作プレビュー')->assertDontSee('公開前に最終確認')
            ->assertSee('現在フォーム受付を停止しています')->assertSee('<fieldset disabled',false)
            ->assertSee('プライバシーポリシー')->assertSee('クレジットカード決済は準備中');
    }

    public function test_public_form_uses_customer_copy_without_development_notices(): void
    {
        $this->app['env']='production';
        config(['farm.preview_require_login'=>false,'farm.inquiries_enabled'=>true,'mail.default'=>'smtp','farm.restrict_mail_recipients'=>false]);
        $this->get('/')->assertOk()->assertDontSee('制作プレビュー')->assertDontSee('テスト用の情報')
            ->assertDontSee('現在フォーム受付を停止しています')->assertDontSee('<fieldset disabled',false)
            ->assertSee('送信時点では注文は確定せず')->assertSee('受付メールを送信します');
    }
}
