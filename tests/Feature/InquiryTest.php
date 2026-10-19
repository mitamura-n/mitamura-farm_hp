<?php

namespace Tests\Feature;

use App\Mail\InquiryReceived;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class InquiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['farm.inquiries_enabled' => true]);
        Mail::fake();
    }

    private function payload(): array
    {
        return ['submission_key' => (string) Str::uuid(), 'name' => '受付テスト', 'email' => 'inquiry-test@example.com', 'kind' => '注文の相談', 'items' => [['product' => '白米', 'quantity' => '10', 'milling' => null], ['product' => '米粉', 'quantity' => '1', 'milling' => null]], 'region' => '福井県', 'message' => '開発確認用です。', 'privacy' => true];
    }

    private function send(array $data)
    {
        return $this->withSession(['inquiry_token' => $data['submission_key']])->postJson('/inquiries', $data);
    }

    public function test_saves_multiple_items_encrypted_and_prepares_two_emails(): void
    {
        $data = $this->payload();
        $response = $this->send($data)->assertCreated()->assertJsonStructure(['receipt']);
        $record = Inquiry::firstOrFail();
        $this->assertSame($response->json('receipt'), $record->receipt);
        $this->assertCount(2, $record->items);
        $this->assertSame('受付テスト', $record->name);
        $this->assertNotSame($data['name'], DB::table('inquiries')->value('name'));
        $this->assertNotSame($data['email'], DB::table('inquiries')->value('email'));
        $this->assertSame('received', $record->status);
        Mail::assertSent(InquiryReceived::class, fn ($mail) => $mail->hasTo(config('farm.email')) && ! $mail->forCustomer);
        Mail::assertSent(InquiryReceived::class, fn ($mail) => $mail->hasTo($data['email']) && $mail->forCustomer);
        Mail::assertSentCount(2);
        $customerMail = new InquiryReceived($record, true);
        $this->assertStringContainsString('注文は確定していません', $customerMail->render());
        $this->assertTrue($customerMail->hasReplyTo(config('farm.email')));
    }

    public function test_duplicate_submission_does_not_create_or_notify_twice(): void
    {
        $data = $this->payload();
        $receipt = $this->send($data)->assertCreated()->json('receipt');
        $this->send($data)->assertOk()->assertJson(['receipt' => $receipt, 'duplicate' => true]);
        $this->assertDatabaseCount('inquiries', 1);
        Mail::assertSentCount(2);
        $data['name'] = '変更された入力';
        $this->send($data)->assertConflict();
    }

    public function test_invalid_fields_and_missing_consent_are_rejected(): void
    {
        $data = $this->payload();
        $data['email'] = 'not-an-email';
        $data['items'][0]['quantity'] = -1;
        $data['privacy'] = false;
        $this->send($data)->assertUnprocessable()->assertJsonValidationErrors(['email', 'items.0.quantity', 'privacy']);
        $this->assertDatabaseCount('inquiries', 0);
        Mail::assertNothingSent();
    }

    public function test_order_requires_products_and_region_but_general_question_does_not(): void
    {
        $data = $this->payload();
        $data['items'] = []; $data['region'] = null;
        $this->send($data)->assertUnprocessable()->assertJsonValidationErrors(['items','region']);
        $data['kind'] = 'その他のお問い合わせ';
        $this->send($data)->assertCreated();
    }

    public function test_unsupported_product_milling_and_bot_field_are_rejected(): void
    {
        $data = $this->payload();
        $data['website'] = 'spam';
        $data['items'][0]['product'] = '不明な商品';
        $this->send($data)->assertUnprocessable();
        unset($data['website']);
        $data['items'][0]['product'] = '白米'; $data['items'][0]['milling'] = '5分';
        $this->send($data)->assertUnprocessable()->assertJsonValidationErrors('items.0.milling');
    }

    public function test_submission_key_from_another_session_is_rejected(): void
    {
        $this->withSession(['inquiry_token' => (string) Str::uuid()])->postJson('/inquiries', $this->payload())->assertStatus(419);
        $this->assertDatabaseCount('inquiries', 0);
    }

    public function test_mail_failure_keeps_the_record_and_reports_pending_notification(): void
    {
        Mail::shouldReceive('to')->twice()->andThrow(new \RuntimeException('Transport unavailable'));
        $this->send($this->payload())->assertCreated()->assertJson(['notification_pending' => true]);
        $record = Inquiry::firstOrFail();
        $this->assertSame('failed', $record->admin_notice);
        $this->assertSame('failed', $record->customer_notice);
    }

    public function test_more_than_ten_items_and_disabled_intake_are_rejected(): void
    {
        $data = $this->payload(); $data['items'] = array_fill(0, 11, $data['items'][0]);
        $this->send($data)->assertUnprocessable();
        config(['farm.inquiries_enabled' => false]);
        $this->send($this->payload())->assertServiceUnavailable();
    }

    public function test_repeated_requests_are_rate_limited(): void
    {
        $data = $this->payload();
        for ($i = 0; $i < 5; $i++) $this->send($data)->assertSuccessful();
        $this->send($data)->assertStatus(429);
    }

    public function test_prefectures_have_47_unique_entries(): void
    {
        $this->assertCount(47, config('farm.prefectures'));
        $this->assertCount(47, array_unique(config('farm.prefectures')));
    }

    public function test_preview_real_mail_only_goes_to_approved_recipients(): void
    {
        config(['mail.default'=>'smtp','farm.restrict_mail_recipients'=>true,'farm.mail_allowed_recipients'=>[config('farm.email')]]);
        $this->send($this->payload())->assertCreated()->assertJson(['notification_pending'=>true]);
        $record=Inquiry::firstOrFail();
        $this->assertSame('sent',$record->admin_notice);
        $this->assertSame('failed',$record->customer_notice);
        Mail::assertSentCount(1);
        Mail::assertSent(InquiryReceived::class, fn ($mail)=>$mail->hasTo(config('farm.email')));
    }
}
