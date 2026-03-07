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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Lead source information
            $table->enum('source', ['walk-in', 'reference', 'indiamart', 'facebook', 'website', 'whatsapp', 'call', 'other'])->default('other');
            $table->string('source_provider')->nullable(); // indiamart, facebook, manual, etc.
            $table->string('source_external_id')->nullable()->index(); // External system's lead ID
            $table->json('raw_source_payload')->nullable(); // Original payload from external source

            // Contact information
            $table->string('name');
            $table->string('phone', 15)->nullable();
            $table->string('email')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();

            // Pipeline
            $table->enum('stage', ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost'])->default('new');
            $table->unsignedBigInteger('expected_value')->default(0); // in paise
            $table->timestamp('next_follow_up_at')->nullable();
            $table->text('notes')->nullable();

            // Additional fields for IndiaMART/Facebook
            $table->string('query_type')->nullable(); // IndiaMART: Buy Lead, Call Lead
            $table->text('query_message')->nullable(); // The actual inquiry message
            $table->string('product_name')->nullable(); // What product they inquired about

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'stage']);
            $table->index(['company_id', 'source']);
            $table->index(['company_id', 'assigned_to_user_id']);
            $table->index(['company_id', 'next_follow_up_at']);
            $table->unique(['company_id', 'source_provider', 'source_external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
