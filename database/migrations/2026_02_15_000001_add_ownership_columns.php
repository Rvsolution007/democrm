<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Leads: add created_by_user_id
        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('created_by_user_id')->nullable()->after('assigned_to_user_id')->constrained('users')->nullOnDelete();
        });

        // Clients: add created_by_user_id + assigned_to_user_id
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('created_by_user_id')->nullable()->after('lead_id')->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->after('created_by_user_id')->constrained('users')->nullOnDelete();
        });

        // Quotes: add assigned_to_user_id
        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('assigned_to_user_id')->nullable()->after('created_by_user_id')->constrained('users')->nullOnDelete();
        });

        // Products: add created_by_user_id
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('created_by_user_id')->nullable()->after('company_id')->constrained('users')->nullOnDelete();
        });

        // Categories: add created_by_user_id
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('created_by_user_id')->nullable()->after('company_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['created_by_user_id']);
            $table->dropColumn('created_by_user_id');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['created_by_user_id']);
            $table->dropForeign(['assigned_to_user_id']);
            $table->dropColumn(['created_by_user_id', 'assigned_to_user_id']);
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['assigned_to_user_id']);
            $table->dropColumn('assigned_to_user_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['created_by_user_id']);
            $table->dropColumn('created_by_user_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['created_by_user_id']);
            $table->dropColumn('created_by_user_id');
        });
    }
};
