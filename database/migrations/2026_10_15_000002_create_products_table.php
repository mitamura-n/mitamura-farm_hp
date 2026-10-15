<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40)->unique();
            $table->unsignedInteger('unit_kg');
            $table->string('unit_label', 80);
            $table->unsignedInteger('max_units')->nullable();
            $table->unsignedInteger('price_yen')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('milling')->default(false);
            $table->timestamps();
        });
        foreach (['玄米', '白米', '分づき米', '米粉', '米ぬか'] as $name) {
            $rice = in_array($name, ['玄米', '白米', '分づき米']);
            DB::table('products')->insert(['name'=>$name, 'unit_kg'=>$rice ? 10 : 1,
                'unit_label'=>$rice ? '玄米重量10kg分' : '1kg入り1袋',
                'price_yen'=>$rice ? 10000 : ($name === '米粉' ? 1500 : 1000),
                // 米ぬかの袋規格は未確認のため、確認後に管理画面から受付開始する。
                'active'=>$name !== '米ぬか', 'milling'=>$name === '分づき米',
                'created_at'=>now(), 'updated_at'=>now()]);
        }
        Schema::table('users', fn (Blueprint $table) => $table->boolean('is_admin')->default(false));
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_admin'));
        Schema::dropIfExists('products');
    }
};
