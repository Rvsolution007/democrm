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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Quote number with FY pattern (e.g., Q-24-25-000001)
            $table->string('quote_no', 30)->unique();
            $table->date('date');
            $table->date('valid_till');

            // Totals (all in paise for accuracy)
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('gst_total')->default(0);
            $table->unsignedBigInteger('grand_total')->default(0);

            // Status workflow
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired'])->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->text('notes')->nullable();
            $table->text('terms_and_conditions')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'date']);
        });

        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            $table->string('product_name'); // Snapshot of product name at quote time
            $table->string('description')->nullable();
            $table->string('hsn_code', 20)->nullable();
            $table->unsignedInteger('qty')->default(1);
            $table->string('unit', 20)->default('Pcs');

            // Prices in paise
            $table->unsignedBigInteger('unit_price')->default(0);
            $table->unsignedTinyInteger('gst_percent')->default(18);
            $table->unsignedBigInteger('gst_amount')->default(0);
            $table->unsignedBigInteger('line_total')->default(0); // unit_price * qty + gst_amount

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
    }
};
