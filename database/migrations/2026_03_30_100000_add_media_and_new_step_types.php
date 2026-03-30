<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. chatflow_steps: add media_path for step-level media attachments
        Schema::table('chatflow_steps', function (Blueprint $table) {
            $table->string('media_path', 500)->nullable()->after('question_text');
        });

        // 2. products: add cover_media_url (unique model image) and group_media_url (group series master image)
        Schema::table('products', function (Blueprint $table) {
            $table->string('cover_media_url', 500)->nullable()->after('image');
            $table->string('group_media_url', 500)->nullable()->after('cover_media_url');
        });

        // 3. product_combos: add combo_media_url for variant-specific images
        Schema::table('product_combos', function (Blueprint $table) {
            $table->string('combo_media_url', 500)->nullable()->after('selected_values');
        });

        // 4. Update chatflow_steps step_type enum to include new types and remove ask_custom
        //    Replace ask_product with ask_unique_column, add ask_base_column
        DB::statement("ALTER TABLE chatflow_steps MODIFY COLUMN step_type ENUM('ask_category','ask_product','ask_base_column','ask_unique_column','ask_combo','ask_optional','ask_custom','send_summary') NOT NULL");
    }

    public function down(): void
    {
        Schema::table('chatflow_steps', function (Blueprint $table) {
            $table->dropColumn('media_path');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['cover_media_url', 'group_media_url']);
        });

        Schema::table('product_combos', function (Blueprint $table) {
            $table->dropColumn('combo_media_url');
        });

        DB::statement("ALTER TABLE chatflow_steps MODIFY COLUMN step_type ENUM('ask_category','ask_product','ask_combo','ask_optional','ask_custom','send_summary') NOT NULL");
    }
};
