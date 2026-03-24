<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add conversation_state & catalogue_sent to ai_chat_sessions
        Schema::table('ai_chat_sessions', function (Blueprint $table) {
            $table->string('conversation_state', 30)->default('new')->after('status');
            $table->boolean('catalogue_sent')->default(false)->after('conversation_state');
        });

        // 2. Add show_in_ai to catalogue_custom_columns
        Schema::table('catalogue_custom_columns', function (Blueprint $table) {
            $table->boolean('show_in_ai')->default(true)->after('show_on_list');
        });

        // 3. Create ai_token_logs table
        Schema::create('ai_token_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('session_id')->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->tinyInteger('tier')->default(1); // 1 = lightweight, 2 = full
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->string('model_used', 50)->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('session_id');
            $table->index('phone_number');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('ai_chat_sessions', function (Blueprint $table) {
            $table->dropColumn(['conversation_state', 'catalogue_sent']);
        });

        Schema::table('catalogue_custom_columns', function (Blueprint $table) {
            $table->dropColumn('show_in_ai');
        });

        Schema::dropIfExists('ai_token_logs');
    }
};
