<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL ENUM modification to add 'warning' status
        DB::statement("ALTER TABLE ai_chat_traces MODIFY COLUMN status ENUM('success', 'error', 'skipped', 'warning') DEFAULT 'success'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE ai_chat_traces MODIFY COLUMN status ENUM('success', 'error', 'skipped') DEFAULT 'success'");
    }
};
