<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change payment_type enum to varchar for quote_payments table
        DB::statement("ALTER TABLE quote_payments MODIFY payment_type VARCHAR(255) NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Altering back to enum safely requires knowing the exact old values or avoiding errors if new values exist.
        // It's mostly safer to leave it as is or fallback to the old ENUM, but for safety in rollbacks:
        DB::statement("ALTER TABLE quote_payments MODIFY payment_type ENUM('cash', 'online', 'cheque', 'upi', 'bank_transfer') DEFAULT 'cash'");
    }
};
