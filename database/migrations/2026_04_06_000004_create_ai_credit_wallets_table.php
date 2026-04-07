<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_credit_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('balance', 12, 2)->default(0);            // current credits available
            $table->decimal('total_purchased', 12, 2)->default(0);    // lifetime purchased credits
            $table->decimal('total_consumed', 12, 2)->default(0);     // lifetime consumed credits
            $table->integer('low_balance_threshold')->default(50);     // alert if balance below this
            $table->boolean('low_balance_alert_sent')->default(false); // prevent duplicate alerts
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_credit_wallets');
    }
};
