<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quote_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('amount')->default(0); // in paise
            $table->enum('payment_type', ['cash', 'online', 'cheque', 'upi', 'bank_transfer'])->default('cash');
            $table->dateTime('payment_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['quote_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_payments');
    }
};
