<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PreviewSetupTest extends TestCase
{
    use RefreshDatabase;

    private function prepare(): string
    {
        $this->app['env']='staging';
        $this->withSession(['_token'=>'setup-csrf-test']);
        $token=str_repeat('a',64);
        config(['farm.preview_require_login'=>true,'farm.preview_setup_hash'=>hash('sha256',$token),'farm.preview_setup_expires'=>time()+3600]);
        return '/preview-setup/'.$token;
    }

    public function test_setup_is_private_expiring_and_disabled_outside_staging(): void
    {
        $url=$this->prepare();
        $this->get('/preview-setup/'.str_repeat('b',64))->assertNotFound();
        $this->get($url)->assertOk()->assertHeader('Referrer-Policy','no-referrer');
        config(['farm.preview_setup_expires'=>time()-1]);
        $this->get($url)->assertNotFound();
        config(['farm.preview_setup_expires'=>time()+3600]); $this->app['env']='production';
        $this->get($url)->assertNotFound();
    }

    public function test_setup_creates_one_hashed_admin_and_invalidates_link(): void
    {
        $url=$this->prepare();
        $data=['_token'=>'setup-csrf-test','email'=>'preview-test@example.com','password'=>'Test-only-password-123','password_confirmation'=>'Test-only-password-123'];
        $this->post($url,$data)->assertRedirect('/');
        $user=User::firstOrFail();
        $this->assertTrue((bool)$user->is_admin);
        $this->assertTrue(Hash::check($data['password'],$user->password));
        $this->get('/')->assertOk();
        $this->get($url)->assertNotFound();
        $data['_token']=session()->token();
        $this->post($url,$data)->assertNotFound();
        $this->assertDatabaseCount('users',1);
    }

    public function test_invalid_password_does_not_create_admin(): void
    {
        $url=$this->prepare();
        $this->post($url,['_token'=>'setup-csrf-test','email'=>'preview-test@example.com','password'=>'short','password_confirmation'=>'different'])->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users',0);
    }
}
