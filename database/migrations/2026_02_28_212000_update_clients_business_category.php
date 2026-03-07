<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'category_id')) {
                try {
                    $table->dropForeign(['category_id']);
                } catch (\Exception $e) {
                    // Ignore if foreign key dropping fails
                }
                $table->dropColumn('category_id');
            }

            if (!Schema::hasColumn('clients', 'business_category')) {
                $table->string('business_category')->nullable()->after('company_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('business_category');
        });
    }
};
