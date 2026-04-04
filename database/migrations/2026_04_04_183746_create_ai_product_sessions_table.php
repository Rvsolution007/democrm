<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_product_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_session_id')->constrained('ai_chat_sessions')->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->uuid('session_uuid')->unique();
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->string('product_name')->nullable();
            $table->json('collected_answers')->nullable();
            $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])->default('pending');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['chat_session_id', 'status']);
            $table->index(['company_id', 'chat_session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_product_sessions');
    }
};
