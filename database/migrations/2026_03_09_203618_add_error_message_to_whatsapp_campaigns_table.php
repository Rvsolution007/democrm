<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_templates', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->onDelete('cascade');
            }
        });

        Schema::table('whatsapp_campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_campaigns', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->onDelete('cascade');
            }
            if (!Schema::hasColumn('whatsapp_campaigns', 'error_message')) {
                $table->text('error_message')->nullable()->after('status');
            }
        });

        Schema::table('whatsapp_campaign_recipients', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_campaign_recipients', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_campaign_recipients', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_campaign_recipients', 'company_id')) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            }
        });

        Schema::table('whatsapp_campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_campaigns', 'company_id')) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            }
            if (Schema::hasColumn('whatsapp_campaigns', 'error_message')) {
                $table->dropColumn('error_message');
            }
        });

        Schema::table('whatsapp_templates', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_templates', 'company_id')) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            }
        });
    }
};
