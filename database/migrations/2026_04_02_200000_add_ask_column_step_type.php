<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'ask_column' to the step_type ENUM
        DB::statement("ALTER TABLE chatflow_steps MODIFY COLUMN step_type ENUM('ask_category','ask_product','ask_base_column','ask_unique_column','ask_column','ask_combo','ask_optional','ask_custom','send_summary') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE chatflow_steps MODIFY COLUMN step_type ENUM('ask_category','ask_product','ask_base_column','ask_unique_column','ask_combo','ask_optional','ask_custom','send_summary') NOT NULL");
    }
};
