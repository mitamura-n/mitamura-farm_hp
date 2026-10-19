<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ProductionDatabaseCheck;
use App\Services\ProductionDatabaseCredentialStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductionDatabaseCredentialTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_preview_admin_can_access_setup(): void
    {
        Storage::fake('local');
        $this->get('/admin/database-credentials')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create());
        $this->get('/admin/database-credentials')->assertForbidden();
        $this->post('/admin/database-credentials',['password'=>'test-password'])->assertForbidden();
        $this->actingAs(User::factory()->create(['is_admin'=>true]));
        config(['farm.preview_require_login'=>false]);
        $this->get('/admin/database-credentials')->assertNotFound();
        config(['farm.preview_require_login'=>true]);
        $this->app->instance('env','production');
        $this->get('/admin/database-credentials')->assertNotFound();
        $this->withSession(['_token'=>'test-csrf-token'])->post('/admin/database-credentials',['password'=>'test-password','_token'=>'test-csrf-token'])->assertNotFound();
    }

    public function test_success_saves_encrypted_secret_without_changing_default_database(): void
    {
        Mail::fake(); Storage::fake('local');
        config(['farm.preview_require_login'=>true]);
        $this->actingAs(User::factory()->create(['is_admin'=>true]));
        $connection=config('database.default');
        $secret='Test-password-12';
        $this->mock(ProductionDatabaseCheck::class)->shouldReceive('check')->once()->with($secret);
        $this->post('/admin/database-credentials',['password'=>$secret])->assertRedirect('/admin/database-credentials');
        $this->assertSame($secret,app(ProductionDatabaseCredentialStore::class)->password());
        $this->assertStringNotContainsString($secret,Storage::disk('local')->get('production-db.enc'));
        $this->get('/admin/database-credentials')->assertOk()->assertSee('保存済み')->assertDontSee($secret)->assertHeader('Cache-Control','no-store, private');
        $this->assertSame($connection,config('database.default'));
        $this->assertNull(session()->getOldInput('password'));
        Mail::assertNothingSent();
    }

    public function test_failed_check_keeps_previous_credential_and_never_exposes_exception(): void
    {
        Storage::fake('local'); config(['farm.preview_require_login'=>true]);
        app(ProductionDatabaseCredentialStore::class)->save('Previous-secret');
        $this->actingAs(User::factory()->create(['is_admin'=>true]));
        $this->mock(ProductionDatabaseCheck::class)->shouldReceive('check')->once()->andThrow(new \RuntimeException('Test-password-12'));
        $this->from('/admin/database-credentials')->post('/admin/database-credentials',['password'=>'Test-password-12'])->assertSessionHasErrors('password');
        $this->assertSame('Previous-secret',app(ProductionDatabaseCredentialStore::class)->password());
        $this->get('/admin/database-credentials')->assertDontSee('Test-password-12');
        $this->assertNull(session()->getOldInput('password'));
    }
}
