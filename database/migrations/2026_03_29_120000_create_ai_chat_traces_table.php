<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_chat_traces', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id')->index();
            $table->unsignedBigInteger('message_id')->nullable()->index();
            $table->string('node_name', 100);            // e.g. "GreetingDetector", "IntentClassifier", "SpellCorrection"
            $table->string('node_group', 50)->default('routing'); // routing, ai_call, data_update, delivery
            $table->enum('status', ['success', 'error', 'skipped'])->default('success');
            $table->json('input_data')->nullable();       // What went INTO this node
            $table->json('output_data')->nullable();      // What came OUT of this node
            $table->text('error_message')->nullable();
            $table->unsignedInteger('execution_time_ms')->default(0);
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('ai_chat_sessions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_traces');
    }
};
