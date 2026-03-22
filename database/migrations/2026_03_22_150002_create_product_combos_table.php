<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_combos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('column_id')->constrained('catalogue_custom_columns')->onDelete('cascade');
            $table->json('selected_values');           // Which values from the column are active for this product
            $table->integer('sort_order')->default(0); // Order of combo selection in chatflow
            $table->timestamps();

            $table->unique(['product_id', 'column_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_combos');
    }
};
