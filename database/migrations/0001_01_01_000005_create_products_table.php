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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit', 20)->default('Pcs'); // Pcs, Kg, Ltr, Mtr, etc.
            // Using integer paise for accuracy (multiply by 100 to store, divide by 100 to display)
            $table->unsignedBigInteger('mrp')->default(0); // in paise
            $table->unsignedBigInteger('sale_price')->default(0); // in paise
            $table->unsignedTinyInteger('gst_percent')->default(18); // 0, 5, 12, 18, 28
            $table->string('hsn_code', 20)->nullable(); // HSN/SAC code for GST
            $table->integer('stock_qty')->default(0);
            $table->integer('min_stock_qty')->default(0); // For low stock alerts
            $table->string('image')->nullable();
            $table->json('specifications')->nullable(); // Custom key-value specifications
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'sku']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
