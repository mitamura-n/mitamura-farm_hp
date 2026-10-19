<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Confirmed by the owner on 2026-10-19; acceptance is for an inquiry, not stock allocation.
        DB::table('products')->where('name','米ぬか')->update([
            'unit_kg'=>1,'unit_label'=>'1kg入り1袋','price_yen'=>1000,'active'=>true,'updated_at'=>now(),
        ]);
        DB::table('products')->where('name','米粉')->update([
            'unit_kg'=>1,'unit_label'=>'1kg入り1袋','price_yen'=>1500,'updated_at'=>now(),
        ]);
    }

    public function down(): void
    {
        // Do not undo business data or overwrite later administrator edits on rollback.
    }
};
