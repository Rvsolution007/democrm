<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add is_variation_field flag to catalogue_custom_columns
        if (!Schema::hasColumn('catalogue_custom_columns', 'is_variation_field')) {
            Schema::table('catalogue_custom_columns', function (Blueprint $table) {
                $table->boolean('is_variation_field')->default(false)->after('is_combo');
            });
        }

        // Add custom_fields JSON to product_variations
        if (!Schema::hasColumn('product_variations', 'custom_fields')) {
            Schema::table('product_variations', function (Blueprint $table) {
                $table->json('custom_fields')->nullable()->after('discount');
            });
        }
    }

    public function down(): void
    {
        Schema::table('catalogue_custom_columns', function (Blueprint $table) {
            $table->dropColumn('is_variation_field');
        });
        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropColumn('custom_fields');
        });
    }
};
