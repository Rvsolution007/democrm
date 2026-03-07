<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendor_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->string('field_name');
            $table->enum('field_type', ['text', 'select'])->default('text');
            $table->json('field_options')->nullable(); // For select: ["opt1","opt2","opt3"]
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('vendor_id');
        });

        Schema::create('purchase_custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->onDelete('cascade');
            $table->foreignId('vendor_custom_field_id')->constrained('vendor_custom_fields')->onDelete('cascade');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['purchase_id', 'vendor_custom_field_id'], 'pcfv_purchase_field_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_custom_field_values');
        Schema::dropIfExists('vendor_custom_fields');
    }
};
