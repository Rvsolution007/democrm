<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->default(1);
            $table->unsignedBigInteger('user_id');
            $table->string('meta_template_id')->nullable(); // ID returned by Meta API
            $table->string('name'); // lowercase, underscores only (Meta requirement)
            $table->enum('category', ['MARKETING', 'UTILITY', 'AUTHENTICATION'])->default('UTILITY');
            $table->string('language', 10)->default('en');
            $table->enum('status', ['DRAFT', 'PENDING', 'APPROVED', 'REJECTED'])->default('DRAFT');
            $table->text('rejected_reason')->nullable();
            $table->enum('header_type', ['NONE', 'TEXT'])->default('NONE');
            $table->string('header_text')->nullable();
            $table->text('body_text'); // main message with {{1}}, {{2}} variables
            $table->string('footer_text')->nullable();
            $table->json('buttons')->nullable(); // URL, PHONE_NUMBER, QUICK_REPLY
            $table->json('example_values')->nullable(); // example values for variables
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['company_id', 'status']);
            $table->unique(['company_id', 'name', 'language']); // Meta requires unique name+language per WABA
        });

        // Add meta_template_id to auto-reply rules
        Schema::table('whatsapp_auto_reply_rules', function (Blueprint $table) {
            $table->unsignedBigInteger('meta_template_id')->nullable()->after('template_id');
            $table->enum('template_source', ['evolution', 'meta'])->default('evolution')->after('meta_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_auto_reply_rules', function (Blueprint $table) {
            $table->dropColumn(['meta_template_id', 'template_source']);
        });
        Schema::dropIfExists('meta_whatsapp_templates');
    }
};
