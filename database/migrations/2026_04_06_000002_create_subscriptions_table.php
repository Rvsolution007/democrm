<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('subscription_packages');
            $table->enum('status', ['trial', 'active', 'expired', 'cancelled', 'suspended'])->default('trial');
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->integer('max_users')->nullable();             // SA override — null = use package default
            $table->date('starts_at');
            $table->date('expires_at');
            $table->date('trial_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('custom_overrides')->nullable();         // SA can override features per admin
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
