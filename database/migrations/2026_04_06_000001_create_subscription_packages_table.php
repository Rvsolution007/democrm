<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscription_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                  // "Starter", "Professional", "Enterprise"
            $table->string('slug')->unique();                        // "starter", "professional", "enterprise"
            $table->text('description')->nullable();
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->decimal('yearly_price', 10, 2)->default(0);
            $table->integer('default_max_users')->default(3);        // SA can override per subscription
            $table->integer('max_leads_per_month')->default(0);      // 0 = unlimited
            $table->json('features')->nullable();                    // ["leads","quotes","whatsapp_connect",...]
            $table->json('module_permissions')->nullable();           // {"leads": true, "whatsapp_connect": false}
            $table->integer('trial_days')->default(14);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_packages');
    }
};
