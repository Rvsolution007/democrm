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
        Schema::table('catalogue_custom_columns', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('is_combo');
            $table->boolean('is_active')->default(true)->after('is_system');
            $table->json('connected_modules')->nullable()->after('is_active');
            $table->boolean('show_on_list')->default(false)->after('connected_modules'); // Whether to show on Product Index table
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('catalogue_custom_columns', function (Blueprint $table) {
            $table->dropColumn(['is_system', 'is_active', 'connected_modules', 'show_on_list']);
        });
    }
};
