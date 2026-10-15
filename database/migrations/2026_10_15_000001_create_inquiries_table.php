<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('receipt', 40)->unique();
            $table->uuid('submission_key')->unique();
            $table->string('payload_hash', 64);
            $table->string('kind', 40);
            $table->text('name');
            $table->text('email');
            $table->text('items');
            $table->text('region')->nullable();
            $table->text('message')->nullable();
            $table->string('status', 30)->default('received');
            $table->string('admin_notice', 20)->default('pending');
            $table->string('customer_notice', 20)->default('pending');
            $table->timestamp('consented_at');
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('inquiries'); }
};
