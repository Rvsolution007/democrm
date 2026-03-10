<?php
require 'vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Starting data cleanup...\n";

// Disable foreign key checks to allow truncation
Schema::disableForeignKeyConstraints();

// Clear the tables completely and reset auto-increment IDs
DB::table('purchases')->truncate();
DB::table('tasks')->truncate();
DB::table('micro_tasks')->truncate(); // Also clear micro_tasks since they belong to tasks
DB::table('projects')->truncate();
DB::table('quote_items')->truncate(); // Need to clear quote items before quotes
DB::table('quotes')->truncate();

// Enable foreign key checks back
Schema::enableForeignKeyConstraints();

echo "Successfully cleared: purchases, projects, quotes, quote_items, tasks, and micro_tasks.\n";
echo "Auto-increment counters reset to 1.\n";
echo "Note: Products, categories, leads, and clients were NOT modified.\n";
