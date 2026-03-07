<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('micro_tasks', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('task_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('micro_tasks', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
