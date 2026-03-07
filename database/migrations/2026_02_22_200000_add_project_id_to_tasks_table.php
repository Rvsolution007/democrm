<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('entity_id')->constrained()->nullOnDelete();
        });

        // Update entity_type enum to include 'project'
        DB::statement("ALTER TABLE tasks MODIFY COLUMN entity_type ENUM('lead', 'client', 'quote', 'project') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert entity_type enum
        DB::statement("ALTER TABLE tasks MODIFY COLUMN entity_type ENUM('lead', 'client', 'quote') NULL");

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });
    }
};
