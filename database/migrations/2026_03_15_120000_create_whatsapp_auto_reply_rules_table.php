<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_auto_reply_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->default(1);
            $table->unsignedBigInteger('user_id');
            $table->string('instance_name');
            $table->string('name');
            $table->enum('match_type', ['exact', 'contains', 'any_message', 'first_message'])->default('any_message');
            $table->json('keywords')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->integer('reply_delay_seconds')->default(5);
            $table->boolean('is_one_time')->default(true);
            $table->integer('cooldown_hours')->default(24);
            $table->boolean('business_hours_only')->default(false);
            $table->time('business_hours_start')->nullable();
            $table->time('business_hours_end')->nullable();
            $table->integer('max_replies_per_day')->default(3);
            $table->integer('priority')->default(5);
            $table->boolean('is_active')->default(true);
            $table->integer('total_triggered')->default(0);
            $table->integer('total_sent')->default(0);
            $table->integer('total_skipped')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('template_id')->references('id')->on('whatsapp_templates')->onDelete('set null');
            $table->index(['user_id', 'is_active', 'priority']);
            $table->index('instance_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_auto_reply_rules');
    }
};
