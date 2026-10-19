<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class InquiryAdminTest extends TestCase
{
    use RefreshDatabase;

    private function inquiry(array $changes=[]): Inquiry
    {
        return Inquiry::create(array_replace([
            'receipt'=>'MF-'.Str::ulid(),'submission_key'=>(string)Str::uuid(),'payload_hash'=>str_repeat('a',64),
            'name'=>'受付管理テスト','email'=>'admin-test@example.com','kind'=>'注文の相談',
            'items'=>[['product'=>'白米','quantity'=>'10','quantity_text'=>'玄米重量10kg分 × 1口']],
            'region'=>'福井県','message'=>'<script>alert(1)</script>','consented_at'=>now(),
            'admin_notice'=>'logged','customer_notice'=>'failed',
        ],$changes));
    }

    public function test_guest_and_non_admin_cannot_read_or_update_inquiries(): void
    {
        $r=$this->inquiry();
        $this->get('/admin/inquiries')->assertRedirect('/admin/login');
        $this->get('/admin/inquiries/'.$r->id)->assertRedirect('/admin/login');
        $this->put('/admin/inquiries/'.$r->id,['status'=>'closed','version'=>$r->getRawOriginal('updated_at')])->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create());
        $this->get('/admin/inquiries')->assertForbidden();
        $this->get('/admin/inquiries/'.$r->id)->assertForbidden();
        $this->put('/admin/inquiries/'.$r->id,['status'=>'closed','version'=>$r->getRawOriginal('updated_at')])->assertForbidden();
        $this->assertSame('received',$r->fresh()->status);
    }

    public function test_admin_can_read_escaped_details_and_filter_status(): void
    {
        $a=$this->inquiry(); $b=$this->inquiry(['status'=>'closed']);
        $this->actingAs(User::factory()->create(['is_admin'=>true]));
        $this->get('/admin/inquiries?status=closed')->assertOk()->assertSee($b->receipt)->assertDontSee($a->receipt);
        $this->get('/admin/inquiries/'.$a->id)->assertOk()->assertSee('玄米重量10kg分 × 1口')->assertSee('送信失敗')
            ->assertSee('&lt;script&gt;',false)->assertDontSee('<script>alert(1)</script>',false)
            ->assertHeader('Cache-Control','no-store, private');
        $this->get('/admin/inquiries?status=unknown')->assertSessionHasErrors('status');
    }

    public function test_status_change_keeps_original_content_and_does_not_send_mail(): void
    {
        Mail::fake(); $r=$this->inquiry(); $before=$r->items;
        $this->actingAs(User::factory()->create(['is_admin'=>true]));
        $data=['status'=>'replied','version'=>$r->getRawOriginal('updated_at')];
        $this->put('/admin/inquiries/'.$r->id,$data)->assertRedirect('/admin/inquiries/'.$r->id);
        $this->assertSame('replied',$r->fresh()->status);
        $this->assertSame($before,$r->fresh()->items);
        $this->assertSame('failed',$r->fresh()->customer_notice);
        $this->put('/admin/inquiries/'.$r->id,$data)->assertSessionHasErrors('version');
        $this->put('/admin/inquiries/'.$r->id,['status'=>'invalid','version'=>$r->fresh()->getRawOriginal('updated_at')])->assertSessionHasErrors('status');
        Mail::assertNothingSent();
    }
}
