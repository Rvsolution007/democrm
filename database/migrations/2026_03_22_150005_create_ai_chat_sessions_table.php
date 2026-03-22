<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('phone_number', 20);
            $table->string('instance_name');
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('current_step_id')->nullable(); // Current chatflow step
            $table->json('collected_answers')->nullable();   // {"product_id":5,"finish":"Black","size":"Large"}
            $table->json('optional_asked')->nullable();      // ["city","name"] — tracks which optional questions already asked
            $table->integer('current_step_retries')->default(0);
            $table->enum('status', ['active', 'completed', 'abandoned'])->default('active');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'phone_number', 'status']);
            $table->index(['instance_name', 'phone_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_sessions');
    }
};
