<?php

if (PHP_SAPI!=='cli') { http_response_code(404);exit(1); }
require dirname(__DIR__).'/vendor/autoload.php';
$app=require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use App\Models\Inquiry;
if (!app()->environment('production') || config('database.connections.mariadb.database')!=='mitamurafarm_prod' || config('database.default')!=='mariadb') throw new RuntimeException('Unexpected environment.');
if (DB::table('users')->count() || DB::table('inquiries')->count()) throw new RuntimeException('Expected clean production data.');
if (DB::table('products')->count()!==5) throw new RuntimeException('Expected five product definitions.');
DB::beginTransaction();
try {
    $key=(string)Illuminate\Support\Str::uuid();
    $record=Inquiry::create([
        'receipt'=>'VERIFY-'.Illuminate\Support\Str::ulid(),'submission_key'=>$key,'payload_hash'=>str_repeat('a',64),
        'name'=>'本番DB検証','email'=>'verify@example.invalid','kind'=>'商品についての相談',
        'items'=>[['product'=>'米粉','quantity'=>1]],'region'=>'福井県','message'=>'保存・復号の検証専用',
        'consented_at'=>now(),
    ]);
    $fresh=$record->fresh();
    if ($fresh->name!=='本番DB検証' || $fresh->items[0]['product']!=='米粉' || $fresh->message!=='保存・復号の検証専用') throw new RuntimeException('Encrypted round trip failed.');
    $raw=DB::table('inquiries')->where('id',$record->id)->first();
    if ($raw->name===$fresh->name || str_contains($raw->email,'example.invalid')) throw new RuntimeException('Unencrypted private data.');
    $duplicateRejected=false;
    $copy=(array)$raw;
    unset($copy['id']);
    $copy['receipt']='VERIFY-'.Illuminate\Support\Str::ulid();
    try { DB::table('inquiries')->insert($copy); } catch (Illuminate\Database\QueryException $e) { $duplicateRejected=$e->getCode()==='23000'; }
    if (!$duplicateRejected) throw new RuntimeException('Unique constraint check failed.');
    echo "MARIADB_ENCRYPTED_ROUND_TRIP_OK\n";
} finally { DB::rollBack(); }
if (DB::table('inquiries')->count()!==0) throw new RuntimeException('Fixture rollback failed.');
echo "NO_TEST_INQUIRIES_RETAINED\n";
echo "PRODUCTS=5; USERS=0; INQUIRIES=0\n";
