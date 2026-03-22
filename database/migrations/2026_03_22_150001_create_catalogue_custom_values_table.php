<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalogue_custom_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('column_id')->constrained('catalogue_custom_columns')->onDelete('cascade');
            $table->text('value')->nullable(); // Single value or JSON for multiselect
            $table->timestamps();

            $table->unique(['product_id', 'column_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogue_custom_values');
    }
};
