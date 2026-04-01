<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalogue_custom_columns', function (Blueprint $table) {
            $table->boolean('is_title')->default(false)->after('is_category');
        });
    }

    public function down(): void
    {
        Schema::table('catalogue_custom_columns', function (Blueprint $table) {
            $table->dropColumn('is_title');
        });
    }
};
