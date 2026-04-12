<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_auto_reply_rules', function (Blueprint $table) {
            $table->boolean('create_lead')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_auto_reply_rules', function (Blueprint $table) {
            $table->dropColumn('create_lead');
        });
    }
};
