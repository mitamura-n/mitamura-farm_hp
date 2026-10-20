<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ProductionAdminSetup;
use Tests\TestCase;

class ProductionAdminSetupTest extends TestCase
{
    private function admin(): void
    {
        $this->app['env'] = 'staging';
        config(['farm.preview_require_login'=>true]);
        $this->actingAs((new User())->forceFill(['id'=>1,'is_admin'=>true]));
    }

    public function test_guest_and_non_admin_cannot_access_setup(): void
    {
        $this->get('/admin/production-admin')->assertRedirect('/admin/login');
        $this->actingAs((new User())->forceFill(['id'=>2,'is_admin'=>false]));
        $this->get('/admin/production-admin')->assertForbidden();
    }

    public function test_setup_is_not_available_in_production(): void
    {
        $this->admin();
        $this->app['env'] = 'production';
        $this->get('/admin/production-admin')->assertNotFound();
    }

    public function test_form_shows_unconfigured_and_configured_states_without_secret(): void
    {
        $this->admin();
        $this->mock(ProductionAdminSetup::class, function ($mock) {
            $mock->shouldReceive('run')->with('status')->once()->andReturn(['configured'=>false]);
        });
        $this->get('/admin/production-admin')->assertOk()->assertSee('本番管理者を作成する')->assertHeader('Cache-Control','no-store, private');
        $this->mock(ProductionAdminSetup::class, function ($mock) {
            $mock->shouldReceive('run')->with('status')->once()->andReturn(['configured'=>true]);
        });
        $this->get('/admin/production-admin')->assertOk()->assertSee('本番管理者は設定済み')->assertDontSee('name="password"', false);
    }

    public function test_password_confirmation_and_safe_handoff(): void
    {
        $this->admin();
        $this->mock(ProductionAdminSetup::class, function ($mock) {
            $mock->shouldReceive('run')->with('create', \Mockery::on(fn ($data) => $data['email']==='test@example.com' && $data['password']==='example-test-password'))
                ->once()->andReturn(['configured'=>true]);
        });
        $this->withSession(['_token'=>'test-csrf'])->from('/admin/production-admin')->post('/admin/production-admin', ['_token'=>'test-csrf','email'=>'test@example.com','password'=>'example-test-password','password_confirmation'=>'different'])
            ->assertSessionHasErrors('password')->assertSessionMissing('_old_input.password');
        $this->post('/admin/production-admin', ['_token'=>'test-csrf','email'=>'test@example.com','password'=>'example-test-password','password_confirmation'=>'example-test-password'])
            ->assertRedirect('/admin/production-admin')->assertSessionHas('status');
    }
}
