<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained();
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['razorpay', 'manual', 'bank_transfer', 'upi', 'cash'])->default('manual');
            $table->string('transaction_id')->nullable();          // razorpay payment_id or manual receipt number
            $table->string('razorpay_order_id')->nullable();
            $table->string('razorpay_signature')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->json('payment_meta')->nullable();              // gateway response, receipt image path, etc.
            $table->text('admin_notes')->nullable();               // SA notes for manual payments
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
