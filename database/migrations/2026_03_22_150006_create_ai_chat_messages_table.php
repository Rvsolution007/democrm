<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('ai_chat_sessions')->onDelete('cascade');
            $table->enum('role', ['user', 'bot']);
            $table->text('message');
            $table->string('message_type')->default('text'); // text, image, audio
            $table->string('image_url')->nullable();          // If user sent image
            $table->json('reply_context')->nullable();        // Quoted message data from WhatsApp reply
            $table->timestamps();

            $table->index(['session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_messages');
    }
};
