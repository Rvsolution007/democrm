<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Fix followup_status from string to integer.
     * 'active' => 0, null => 0, numeric stays as-is.
     */
    public function up(): void
    {
        // First convert existing string values to numeric equivalents
        DB::table('ai_chat_sessions')
            ->where('followup_status', 'active')
            ->update(['followup_status' => '0']);

        DB::table('ai_chat_sessions')
            ->whereNull('followup_status')
            ->update(['followup_status' => '0']);

        // Now alter the column type to integer
        Schema::table('ai_chat_sessions', function (Blueprint $table) {
            $table->integer('followup_status')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_chat_sessions', function (Blueprint $table) {
            $table->string('followup_status')->default('active')->change();
        });
    }
};
