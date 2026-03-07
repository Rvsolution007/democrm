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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('gstin', 15)->nullable();
            $table->string('pan', 10)->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('email')->nullable();
            $table->string('logo')->nullable();
            $table->json('address')->nullable(); // {line1, line2, landmark, city, district, state, pincode, country}
            $table->unsignedTinyInteger('default_gst_percent')->default(18);
            $table->boolean('gst_inclusive')->default(false);
            $table->string('quote_prefix', 10)->default('Q');
            $table->string('quote_fy_format', 10)->default('YY-YY'); // YY-YY, YYYY-YY, YYYY
            $table->text('terms_and_conditions')->nullable();
            $table->string('language', 5)->default('en'); // en, hi, gu
            $table->string('timezone', 50)->default('Asia/Kolkata');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
