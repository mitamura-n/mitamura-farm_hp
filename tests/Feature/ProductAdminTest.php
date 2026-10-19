<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductAdminTest extends TestCase
{
    use RefreshDatabase;

    private function changes(Product $product): array
    {
        return ['unit_kg'=>5, 'unit_label'=>'玄米重量5kg分', 'max_units'=>3, 'price_yen'=>5500, 'active'=>1, 'version'=>$product->getRawOriginal('updated_at')];
    }

    public function test_guests_and_non_admins_cannot_edit_products(): void
    {
        $p = Product::first();
        $this->get('/admin/products')->assertRedirect('/admin/login');
        $this->put('/admin/products/'.$p->id, $this->changes($p))->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get('/admin/products')->assertForbidden();
        $this->put('/admin/products/'.$p->id, $this->changes($p))->assertForbidden();
        $this->assertSame(10, $p->fresh()->unit_kg);
    }

    public function test_admin_login_edit_public_reflection_stale_edit_and_logout(): void
    {
        $user = User::factory()->create(['is_admin'=>true]);
        $this->post('/admin/login', ['email'=>$user->email, 'password'=>'password'])->assertRedirect('/admin/products');
        $this->get('/admin/products')->assertOk()->assertSee('数量の上限');
        $p = Product::first(); $changes = $this->changes($p);
        $this->put('/admin/products/'.$p->id, $changes)->assertSessionHasNoErrors();
        $this->assertSame(5, $p->fresh()->unit_kg);
        $this->get('/')->assertOk()->assertSee('玄米重量5kg分')->assertSee('5,500');
        $this->put('/admin/products/'.$p->id, $changes)->assertSessionHasErrors('version');
        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->get('/admin/products')->assertRedirect('/admin/login');
    }

    public function test_invalid_admin_changes_and_non_admin_login_are_rejected(): void
    {
        $user = User::factory()->create();
        $this->post('/admin/login', ['email'=>$user->email,'password'=>'password'])->assertSessionHasErrors('email');
        $this->actingAs(User::factory()->create(['is_admin'=>true]));
        $p=Product::first();
        $this->put('/admin/products/'.$p->id, array_replace($this->changes($p), ['unit_kg'=>0,'max_units'=>-1,'price_yen'=>-1]))->assertSessionHasErrors(['unit_kg','max_units','price_yen']);
    }

    public function test_server_enforces_master_rules_and_keeps_order_snapshot(): void
    {
        config(['farm.inquiries_enabled'=>true]); Mail::fake();
        $token = (string) Str::uuid();
        $data=['submission_key'=>$token,'name'=>'テスト','email'=>'test@example.com','kind'=>'注文の相談','items'=>[['product'=>'玄米','quantity'=>'19','milling'=>null]],'region'=>'福井県','privacy'=>true];
        $this->withSession(['inquiry_token'=>$token]);
        $this->postJson('/inquiries',$data)->assertUnprocessable()->assertJsonValidationErrors('items.0.quantity');
        $p=Product::where('name','玄米')->first(); $p->update(['max_units'=>2]);
        $data['items'][0]['quantity']='20';
        $this->postJson('/inquiries',$data)->assertCreated();
        $snapshot=Inquiry::first()->items;
        $p->update(['unit_kg'=>5,'unit_label'=>'5kg入り']);
        $this->assertSame(10,$snapshot[0]['unit_kg']);
        $this->assertSame($snapshot,Inquiry::first()->items);
        $data['submission_key']=(string) Str::uuid(); $this->withSession(['inquiry_token'=>$data['submission_key']]);
        $data['items']=[['product'=>'玄米','quantity'=>'10','milling'=>null],['product'=>'玄米','quantity'=>'5','milling'=>null]];
        $this->postJson('/inquiries',$data)->assertUnprocessable();
        $data['items']=[['product'=>'米ぬか','quantity'=>'1','milling'=>null]];
        Product::where('name','米ぬか')->first()->update(['active'=>false]);
        $this->postJson('/inquiries',$data)->assertUnprocessable()->assertJsonValidationErrors('items.0.product');
    }
}
