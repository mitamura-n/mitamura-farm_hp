<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SmtpConnectionCheck;
use App\Services\SmtpCredentialStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MailCredentialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['mail.mailers.smtp.username'=>'info@mitamurafarm.com']);
    }

    public function test_only_admin_can_read_and_store_credentials(): void
    {
        $this->get('/admin/mail-credentials')->assertRedirect('/admin/login');
        $this->post('/admin/mail-credentials',['password'=>'Test-only-password'])->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create());
        $this->get('/admin/mail-credentials')->assertForbidden();
        $this->post('/admin/mail-credentials',['password'=>'Test-only-password'])->assertForbidden();
        Storage::disk('local')->assertMissing('mail-smtp.enc');
    }

    public function test_authentication_check_saves_encrypted_password_without_sending_mail(): void
    {
        Mail::fake(); $secret='Test-only-password-123'; $mailer=config('mail.default');
        $this->actingAs(User::factory()->create(['is_admin'=>true]));
        $this->mock(SmtpConnectionCheck::class)->shouldReceive('check')->once()->with($secret);
        $this->post('/admin/mail-credentials',['password'=>$secret])->assertRedirect('/admin/mail-credentials');
        $encrypted=Storage::disk('local')->get('mail-smtp.enc');
        $this->assertStringNotContainsString($secret,$encrypted);
        $this->assertSame($secret,app(SmtpCredentialStore::class)->password());
        $this->get('/admin/mail-credentials')->assertOk()->assertSee('設定済み')->assertDontSee($secret)
            ->assertHeader('Cache-Control','no-store, private');
        $this->assertSame($mailer,config('mail.default'));
        Mail::assertNothingSent();
        config(['mail.mailers.smtp.username'=>'different@example.com']);
        $this->assertNull(app(SmtpCredentialStore::class)->password());
    }

    public function test_authentication_failure_is_not_saved_and_does_not_expose_password(): void
    {
        Log::spy();
        $this->actingAs(User::factory()->create(['is_admin'=>true]));
        $this->mock(SmtpConnectionCheck::class)->shouldReceive('check')->andThrow(new \RuntimeException('Test-only-password-123'));
        $this->from('/admin/mail-credentials')->post('/admin/mail-credentials',['password'=>'Test-only-password-123'])->assertSessionHasErrors('password');
        $this->get('/admin/mail-credentials')->assertDontSee('Test-only-password-123');
        $this->assertNull(session()->getOldInput('password'));
        Storage::disk('local')->assertMissing('mail-smtp.enc');
        Log::shouldHaveReceived('warning')->once()->with('SMTP credential check failed', ['reason'=>'application','exception_class'=>\RuntimeException::class]);
    }

    public function test_authentication_rejection_has_a_safe_diagnostic_code(): void
    {
        Log::spy();
        $this->actingAs(User::factory()->create(['is_admin'=>true]));
        $this->mock(SmtpConnectionCheck::class)->shouldReceive('check')->andThrow(
            new \Symfony\Component\Mailer\Exception\TransportException('Failed to authenticate: Test-only-password-123')
        );
        $this->from('/admin/mail-credentials')->post('/admin/mail-credentials',['password'=>'Test-only-password-123'])
            ->assertSessionHasErrors(['password'=>'メールサーバーが認証を受け付けませんでした。info@mitamurafarm.com のメールアカウント用パスワードをご確認ください。（確認コード：SMTP-AUTH）']);
        Log::shouldHaveReceived('warning')->once()->with('SMTP credential check failed', [
            'reason'=>'authentication','exception_class'=>\Symfony\Component\Mailer\Exception\TransportException::class,
        ]);
        Storage::disk('local')->assertMissing('mail-smtp.enc');
    }
}
