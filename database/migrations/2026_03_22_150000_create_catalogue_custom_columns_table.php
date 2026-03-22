<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalogue_custom_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');                    // e.g., "Material", "Color", "Finish"
            $table->string('slug');                    // e.g., "material", "color", "finish"
            $table->enum('type', ['text', 'number', 'select', 'multiselect', 'boolean'])->default('text');
            $table->json('options')->nullable();       // For select/multiselect: ["Oak","Teak","Pine"]
            $table->boolean('is_required')->default(false);
            $table->boolean('is_unique')->default(false); // Admin picks one column as unique identifier
            $table->boolean('is_combo')->default(false);  // Used for variation combos
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'slug']);
            $table->index(['company_id', 'is_combo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogue_custom_columns');
    }
};
