<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();

            // Business vs Individual
            $table->enum('type', ['business', 'individual'])->default('business');
            $table->string('business_name')->nullable();
            $table->string('contact_name');

            // Contact info
            $table->string('phone', 15)->nullable();
            $table->string('email')->nullable();

            // Indian tax identifiers
            $table->string('gstin', 15)->nullable(); // 15-character GSTIN
            $table->string('pan', 10)->nullable(); // 10-character PAN

            // Addresses stored as JSON
            $table->json('billing_address')->nullable(); // {line1, line2, landmark, city, district, state, pincode, country}
            $table->json('shipping_address')->nullable();

            // Credit & Payment
            $table->unsignedBigInteger('credit_limit')->default(0); // in paise
            $table->unsignedBigInteger('outstanding_amount')->default(0); // in paise
            $table->unsignedTinyInteger('payment_terms_days')->default(30);

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'gstin']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
