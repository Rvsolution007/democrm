<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ServiceTemplate;
use App\Models\Quote;

$templates = ServiceTemplate::all();
echo "Templates:\n";
foreach ($templates as $t) {
    echo "ID: {$t->id}, Product ID: {$t->product_id}, Active: {$t->is_active}\n";
    echo "Tasks: " . json_encode($t->getTaskSteps(), JSON_PRETTY_PRINT) . "\n\n";
}

$quoteItems = App\Models\QuoteItem::where('quote_id', 16)->get();
echo "Quote 16 Items:\n";
foreach ($quoteItems as $item) {
    echo "Product ID: {$item->product_id}, Name: {$item->product_name}\n";
    $template = ServiceTemplate::where('product_id', $item->product_id)
        ->where('is_active', true)
        ->first();
    echo "Template Matched? " . ($template ? "YES (ID: {$template->id})" : "NO") . "\n";
    if ($template) {
        $steps = $template->getTaskSteps();
        echo "Steps Count: " . count($steps) . "\n";
        echo "Is empty? " . (empty($steps) ? "YES" : "NO") . "\n";
    }
}
