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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Polymorphic relationship
            $table->enum('entity_type', ['lead', 'client', 'quote']);
            $table->unsignedBigInteger('entity_id');

            $table->enum('type', ['call', 'whatsapp', 'email', 'note', 'meeting', 'task'])->default('note');
            $table->string('subject')->nullable();
            $table->text('summary');

            $table->timestamp('next_action_at')->nullable();
            $table->string('next_action_type')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'entity_type', 'entity_id']);
            $table->index(['company_id', 'created_by_user_id']);
            $table->index(['company_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
