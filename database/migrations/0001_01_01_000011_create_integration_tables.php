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
        // Lead sources configuration
        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->enum('source_type', ['indiamart', 'facebook', 'website', 'whatsapp', 'other']);
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable(); // field mapping & options
            $table->timestamps();

            $table->unique(['company_id', 'source_type']);
        });

        // Integration settings for external providers
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->enum('provider', ['indiamart', 'facebook', 'whatsapp', 'email']);
            $table->enum('status', ['active', 'inactive', 'error'])->default('inactive');
            $table->json('settings'); // Encrypted credentials & runtime options
            $table->timestamp('last_sync_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'provider']);
        });

        // External leads for dedupe and audit
        Schema::create('external_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->enum('provider', ['indiamart', 'facebook']);
            $table->string('external_id')->index();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->json('payload'); // Original raw payload for audit
            $table->timestamp('received_at');
            $table->timestamps();

            $table->unique(['company_id', 'provider', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_leads');
        Schema::dropIfExists('integrations');
        Schema::dropIfExists('lead_sources');
    }
};
