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
        Schema::table('micro_tasks', function (Blueprint $table) {
            $table->dateTime('follow_up_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('micro_tasks', function (Blueprint $table) {
            $table->date('follow_up_date')->nullable()->change();
        });
    }
};
