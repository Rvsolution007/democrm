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
        Schema::table('quote_items', function (Blueprint $table) {
            $table->integer('rate')->default(0)->after('qty')->comment('Base price before discount (paise)');
            $table->integer('discount')->default(0)->after('rate')->comment('Discount amount (paise)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->dropColumn(['rate', 'discount']);
        });
    }
};
