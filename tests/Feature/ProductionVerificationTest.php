<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductionVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['farm.verification_mode'=>true,'farm.inquiries_enabled'=>true]);
        Mail::fake();
    }

    private function data(): array
    {
        return ['submission_key'=>(string)Str::uuid(),'kind'=>'購入希望・お見積もり','name'=>'動作確認',
            'email'=>'mitamura.farm@gmail.com','items'=>[['product'=>'白米','quantity'=>'10','milling'=>null]],
            'region'=>'福井県','message'=>'テスト','privacy'=>true];
    }

    public function test_non_admin_cannot_submit_and_management_changes_are_blocked(): void
    {
        $this->postJson('/inquiries',$this->data())->assertForbidden();
        $this->actingAs(User::factory()->create(['is_admin'=>true]));
        $this->put('/admin/products/1',[])->assertNotFound();
        $this->get('/admin/mail-credentials')->assertNotFound();
        $this->get('/admin/products')->assertOk()->assertHeader('X-Robots-Tag','noindex, nofollow, noarchive');
    }

    public function test_other_email_is_rejected_before_storage_or_mail(): void
    {
        $this->actingAs(User::factory()->create(['is_admin'=>true]));
        $data=$this->data();$data['email']='another@example.com';
        $this->withSession(['inquiry_token'=>$data['submission_key']])->postJson('/inquiries',$data)->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertDatabaseCount('inquiries',0);
        Mail::assertNothingSent();
    }

    public function test_test_receipt_is_marked_and_retry_does_not_duplicate(): void
    {
        $this->actingAs(User::factory()->create(['is_admin'=>true]));
        $data=$this->data();
        $receipt=$this->withSession(['inquiry_token'=>$data['submission_key']])->postJson('/inquiries',$data)->assertCreated()->json('receipt');
        $this->postJson('/inquiries',$data)->assertOk()->assertJson(['receipt'=>$receipt,'duplicate'=>true]);
        $this->assertDatabaseCount('inquiries',1);
        $this->assertStringStartsWith('【本番動作確認・実注文ではありません】',Inquiry::firstOrFail()->message);
        Mail::assertSentCount(2);
    }
}
