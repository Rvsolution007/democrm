<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->json('ai_custom_data')->nullable()->after('product_name');
            // Stores optional question answers: {"city":"Jaipur","business":"Interior Design","name":"Rahul"}
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('ai_custom_data');
        });
    }
};
