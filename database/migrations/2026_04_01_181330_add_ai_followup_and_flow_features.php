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
        // Add session state variables
        Schema::table('ai_chat_sessions', function (Blueprint $table) {
            $table->timestamp('last_bot_message_at')->nullable()->after('last_message_at');
            $table->string('followup_status')->default('active')->after('status');
        });

        // Create scheduled followups table
        Schema::create('chat_followup_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            $table->integer('delay_minutes')->default(120); // Delay before sending the follow up
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_followup_schedules');
        
        Schema::table('ai_chat_sessions', function (Blueprint $table) {
            $table->dropColumn(['last_bot_message_at', 'followup_status']);
        });
    }
};
