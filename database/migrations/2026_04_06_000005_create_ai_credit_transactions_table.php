<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('wallet_id')->constrained('ai_credit_wallets')->cascadeOnDelete();
            $table->enum('type', ['recharge', 'consumption', 'refund', 'adjustment', 'bonus']);
            $table->decimal('credits', 12, 2);                   // +ve for recharge/bonus, -ve for consumption
            $table->decimal('balance_after', 12, 2);             // wallet balance after this transaction
            $table->decimal('amount_paid', 10, 2)->nullable();   // money paid (for recharge type)
            $table->integer('ai_tokens_used')->nullable();       // actual API tokens consumed (for consumption)
            $table->string('description')->nullable();
            $table->string('reference_type')->nullable();         // 'chat_message', 'credit_pack', 'manual', etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('payment_method')->nullable();         // for recharge: razorpay, manual
            $table->string('razorpay_payment_id')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_credit_transactions');
    }
};
