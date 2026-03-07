<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('group')->index();     // e.g. 'column_visibility'
            $table->string('key')->index();        // e.g. 'products', 'leads'
            $table->json('value')->nullable();     // JSON payload
            $table->timestamps();

            $table->unique(['company_id', 'group', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
