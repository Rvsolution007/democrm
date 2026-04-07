<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('user_type', ['super_admin', 'admin', 'staff'])
                ->default('staff')
                ->after('id');
            $table->integer('max_sessions')->default(3)->after('status');
            $table->timestamp('password_changed_at')->nullable()->after('max_sessions');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['user_type', 'max_sessions', 'password_changed_at']);
        });
    }
};
