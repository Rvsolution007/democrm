<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE ai_chat_sessions MODIFY COLUMN status ENUM('active', 'completed', 'abandoned', 'expired') DEFAULT 'active' NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert expired to abandoned before changing enum back
        DB::statement("UPDATE ai_chat_sessions SET status = 'abandoned' WHERE status = 'expired'");
        DB::statement("ALTER TABLE ai_chat_sessions MODIFY COLUMN status ENUM('active', 'completed', 'abandoned') DEFAULT 'active' NOT NULL");
    }
};
