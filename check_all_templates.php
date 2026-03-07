<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ServiceTemplate;

echo "All Templates (including trashed):\n";
$templates = ServiceTemplate::withTrashed()->get();
foreach ($templates as $t) {
    echo "ID: {$t->id}, Name: {$t->name}, Product ID: {$t->product_id}, Active: {$t->is_active}, Trashed: " . ($t->trashed() ? 'Yes' : 'No') . "\n";
}
