<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('whatsapp_auto_reply_rules', function (Blueprint $table) {
            $table->text('last_error')->nullable()->after('total_skipped');
            $table->timestamp('last_error_at')->nullable()->after('last_error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_auto_reply_rules', function (Blueprint $table) {
            $table->dropColumn(['last_error', 'last_error_at']);
        });
    }
};
