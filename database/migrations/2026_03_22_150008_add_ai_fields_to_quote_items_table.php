<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->foreignId('variation_id')->nullable()->after('product_id')
                  ->constrained('product_variations')->nullOnDelete();
            $table->json('selected_combination')->nullable()->after('variation_id');
            // Stores: {"finish":"Black","size":"Large"} — the specific combo selection
        });
    }

    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->dropForeign(['variation_id']);
            $table->dropColumn(['variation_id', 'selected_combination']);
        });
    }
};
