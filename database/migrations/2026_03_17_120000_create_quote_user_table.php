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
        if (!Schema::hasTable('quote_user')) {
            Schema::create('quote_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quote_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->timestamps();

                $table->unique(['quote_id', 'user_id']);
            });
        }

        // Migrate existing data: if quotes table has assigned_to_user_id column, copy data to pivot table
        if (Schema::hasColumn('quotes', 'assigned_to_user_id')) {
            $quotes = \DB::table('quotes')
                ->whereNotNull('assigned_to_user_id')
                ->get(['id', 'assigned_to_user_id']);

            foreach ($quotes as $quote) {
                \DB::table('quote_user')->insertOrIgnore([
                    'quote_id' => $quote->id,
                    'user_id' => $quote->assigned_to_user_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_user');
    }
};
