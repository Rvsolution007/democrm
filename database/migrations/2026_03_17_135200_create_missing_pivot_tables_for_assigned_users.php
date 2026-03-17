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
            'project' => 'projects',
            'task' => 'tasks',
            'lead' => 'leads',
            'client' => 'clients'
        ];

        foreach ($tables as $entity => $tableName) {
            $pivotName = "{$entity}_user";
            
            // 1. Create pivot table if it doesn't exist at all
            if (!Schema::hasTable($pivotName)) {
                Schema::create($pivotName, function (Blueprint $table) use ($entity) {
                    $table->id();
                    $table->foreignId("{$entity}_id")->constrained()->onDelete('cascade');
                    $table->foreignId('user_id')->constrained()->onDelete('cascade');
                    $table->timestamps();
                    $table->unique(["{$entity}_id", 'user_id']);
                });
            } else {
                // 2. If it exists but might be empty (like project_user), let's add columns if missing.
                Schema::table($pivotName, function (Blueprint $table) use ($entity, $pivotName) {
                    if (!Schema::hasColumn($pivotName, "{$entity}_id")) {
                        $table->foreignId("{$entity}_id")->constrained()->onDelete('cascade');
                    }
                    if (!Schema::hasColumn($pivotName, 'user_id')) {
                        $table->foreignId('user_id')->constrained()->onDelete('cascade');
                        // try to make unique
                        try {
                            $table->unique(["{$entity}_id", 'user_id']);
                        } catch (\Exception $e) {
                            // ignore if already unique
                        }
                    }
                });
            }

            // 3. Migrate existing data for entities that have 'assigned_to_user_id' column
            if (Schema::hasColumn($tableName, 'assigned_to_user_id')) {
                $records = DB::table($tableName)
                    ->whereNotNull('assigned_to_user_id')
                    ->get(['id', 'assigned_to_user_id']);

                foreach ($records as $record) {
                    DB::table($pivotName)->insertOrIgnore([
                        "{$entity}_id" => $record->id,
                        'user_id' => $record->assigned_to_user_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_user');
        Schema::dropIfExists('task_user');
        Schema::dropIfExists('lead_user');
        Schema::dropIfExists('client_user');
    }
};
