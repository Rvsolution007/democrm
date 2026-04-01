<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_chat_sessions', function (Blueprint $table) {
            $table->string('detected_language', 10)->nullable()->after('last_message_at');
            $table->json('media_sent_keys')->nullable()->after('detected_language');
        });
    }

    public function down(): void
    {
        Schema::table('ai_chat_sessions', function (Blueprint $table) {
            $table->dropColumn(['detected_language', 'media_sent_keys']);
        });
    }
};
