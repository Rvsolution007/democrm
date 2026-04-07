<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_credit_packs', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                // "Starter Pack", "Growth Pack", etc.
            $table->integer('credits');                            // 500, 2000, 5000, 15000, 50000
            $table->decimal('price', 10, 2);                      // ₹499, ₹1499, ₹2999, etc.
            $table->text('description')->nullable();               // "~500 AI conversations"
            $table->boolean('is_popular')->default(false);         // highlight badge on UI
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_credit_packs');
    }
};
