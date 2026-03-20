<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change stage from ENUM to VARCHAR so custom stages can be used
        DB::statement("ALTER TABLE leads MODIFY COLUMN stage VARCHAR(100) NOT NULL DEFAULT 'new'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE leads MODIFY COLUMN stage ENUM('new','contacted','qualified','proposal','negotiation','won','lost') NOT NULL DEFAULT 'new'");
    }
};
