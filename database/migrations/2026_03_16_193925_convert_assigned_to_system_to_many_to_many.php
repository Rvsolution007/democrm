<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'clients' => 'client_id',
            'leads' => 'lead_id',
            'projects' => 'project_id',
            'tasks' => 'task_id',
            'quotes' => 'quote_id'
        ];

        foreach ($tables as $baseTable => $foreignKey) {
            $pivotTable = str_replace('_id', '_user', $foreignKey);

            // 1. Create pivot table if not exists
            if (!Schema::hasTable($pivotTable)) {
                Schema::create($pivotTable, function (Blueprint $table) use ($baseTable, $foreignKey) {
                    $table->id();
                    $table->foreignId($foreignKey)->constrained($baseTable)->onDelete('cascade');
                    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                    $table->timestamps();
                    
                    // Prevent duplicate assignments
                    $table->unique([$foreignKey, 'user_id']);
                });

                // 2. Migrate existing data if assigned_to_user_id still exists
                if (Schema::hasColumn($baseTable, 'assigned_to_user_id')) {
                    DB::statement("
                        INSERT INTO {$pivotTable} ({$foreignKey}, user_id, created_at, updated_at)
                        SELECT id, assigned_to_user_id, NOW(), NOW()
                        FROM {$baseTable}
                        WHERE assigned_to_user_id IS NOT NULL
                    ");
                }
            }

            // 3. Drop old column
            if (Schema::hasColumn($baseTable, 'assigned_to_user_id')) {
                Schema::table($baseTable, function (Blueprint $table) {
                    $table->dropForeign(['assigned_to_user_id']);
                    $table->dropColumn('assigned_to_user_id');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'clients' => 'client_id',
            'leads' => 'lead_id',
            'projects' => 'project_id',
            'tasks' => 'task_id',
            'quotes' => 'quote_id'
        ];

        foreach ($tables as $baseTable => $foreignKey) {
            $pivotTable = str_replace('_id', '_user', $foreignKey);

            // 1. Re-add column
            Schema::table($baseTable, function (Blueprint $table) {
                $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->onDelete('set null');
            });

            // 2. Restore data (picks the first assigned user if multiple exist)
            DB::statement("
                UPDATE {$baseTable} b
                JOIN (
                    SELECT {$foreignKey}, MIN(user_id) as user_id 
                    FROM {$pivotTable} 
                    GROUP BY {$foreignKey}
                ) p ON b.id = p.{$foreignKey}
                SET b.assigned_to_user_id = p.user_id
            ");

            // 3. Drop pivot table
            Schema::dropIfExists($pivotTable);
        }
    }
};
