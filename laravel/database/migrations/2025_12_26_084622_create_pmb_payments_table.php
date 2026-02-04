<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pmb_payments', function (Blueprint $table) {
            $table->id();

            $table->string('external_id')->unique();
            $table->string('invoice_id')->nullable()->index();
            $table->text('invoice_url')->nullable();

            $table->unsignedInteger('amount');
            $table->string('method', 32)->nullable(); // bank | ewallet (metadata internal)
            $table->string('currency', 8)->default('IDR');

            $table->string('status', 32)->default('PENDING'); // PENDING | PAID | EXPIRED | SETTLED, dll

            $table->string('payer_email')->nullable();
            $table->string('phone')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expiry_date')->nullable();

            $table->json('invoice_payload')->nullable();
            $table->json('webhook_payload')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pmb_payments');
    }
};
