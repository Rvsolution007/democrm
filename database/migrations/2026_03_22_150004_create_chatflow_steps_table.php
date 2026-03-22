<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chatflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');                    // Admin label: "Ask Product", "Ask Finish"
            $table->enum('step_type', [
                'ask_product',        // Show product list from catalogue
                'ask_combo',          // Ask a combo dimension (linked to a combo column)
                'ask_optional',       // Optional question (name, city, business, etc.)
                'ask_custom',         // Free-text custom question
                'send_summary',       // Send order summary to user
            ]);
            $table->foreignId('linked_column_id')->nullable()
                  ->constrained('catalogue_custom_columns')->nullOnDelete(); // For ask_combo steps
            $table->string('question_text')->nullable();  // Custom question text override
            $table->string('field_key')->nullable();      // Where to save: "name","city","business"
            $table->boolean('is_optional')->default(false);
            $table->integer('max_retries')->default(2);   // Max times bot re-asks before skip/escalate
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatflow_steps');
    }
};
