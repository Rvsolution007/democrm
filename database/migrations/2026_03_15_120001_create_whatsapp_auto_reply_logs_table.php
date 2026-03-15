<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_auto_reply_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->default(1);
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('rule_id')->nullable();
            $table->string('instance_name');
            $table->string('phone_number');
            $table->text('incoming_message')->nullable();
            $table->unsignedBigInteger('reply_template_id')->nullable();
            $table->enum('status', ['sent', 'skipped', 'failed'])->default('sent');
            $table->string('skip_reason')->nullable(); // one_time, cooldown, hours, blacklist, max_daily
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('rule_id')->references('id')->on('whatsapp_auto_reply_rules')->onDelete('set null');
            $table->foreign('reply_template_id')->references('id')->on('whatsapp_templates')->onDelete('set null');
            $table->index(['user_id', 'phone_number', 'status']);
            $table->index(['instance_name', 'created_at']);
            $table->index('phone_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_auto_reply_logs');
    }
};
