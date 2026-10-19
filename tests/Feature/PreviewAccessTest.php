<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreviewAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_blocks_content_and_intake_for_guests(): void
    {
        config(['farm.preview_require_login'=>true]);
        $this->get('/')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk();
        $this->postJson('/inquiries', [])->assertUnauthorized();
        $this->assertDatabaseCount('inquiries', 0);
    }

    public function test_only_admin_can_access_preview_and_logout_closes_access(): void
    {
        config(['farm.preview_require_login'=>true]);
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create(['is_admin'=>true]));
        $this->get('/')->assertOk()->assertSee('三田村農園')->assertHeader('X-Robots-Tag','noindex, nofollow, noarchive');
        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->get('/')->assertRedirect('/admin/login');
    }

    public function test_preview_login_returns_to_requested_page(): void
    {
        config(['farm.preview_require_login'=>true]);
        $user = User::factory()->create(['is_admin'=>true]);
        $this->get('/')->assertRedirect('/admin/login');
        $this->post('/admin/login', ['email'=>$user->email,'password'=>'password'])->assertRedirect('/');
        $this->get('/')->assertOk();
    }

    public function test_public_access_remains_available_when_gate_is_off(): void
    {
        config(['farm.preview_require_login'=>false]);
        $this->get('/')->assertOk();
    }
}
