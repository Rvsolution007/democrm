<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_chat_sessions', function (Blueprint $table) {
            $table->string('bot_type', 20)->default('ai_bot')->after('instance_name');
            $table->index(['company_id', 'bot_type']);
        });
    }

    public function down(): void
    {
        Schema::table('ai_chat_sessions', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'bot_type']);
            $table->dropColumn('bot_type');
        });
    }
};
