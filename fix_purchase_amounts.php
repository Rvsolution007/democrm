<?php
/**
 * Script to check and fix corrupted purchase amounts.
 * 
 * The auto-created purchases from quote conversion had their total_amount 
 * wrongly divided by 100 during quote update sync.
 * This script re-syncs the total_amount from quote_items.purchase_amount.
 */

// Bootstrap Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Purchase;
use App\Models\Project;
use App\Models\Quote;
use App\Models\QuoteItem;

echo "=== Purchase Amount Fix Script ===\n\n";

// Find all purchases that have a project_id (auto-created from quote conversion)
$purchases = Purchase::whereNotNull('project_id')->whereNotNull('product_id')->get();

echo "Found " . $purchases->count() . " auto-generated purchases to check.\n\n";

$fixed = 0;

foreach ($purchases as $purchase) {
    $project = Project::find($purchase->project_id);
    if (!$project || !$project->quote_id) {
        // Try to find quote via lead_id
        if ($project && $project->lead_id) {
            $quote = Quote::where('lead_id', $project->lead_id)->first();
        } else {
            continue;
        }
    } else {
        $quote = Quote::find($project->quote_id);
    }

    if (!$quote) continue;

    // Find the matching quote item
    $quoteItem = QuoteItem::where('quote_id', $quote->id)
        ->where('product_id', $purchase->product_id)
        ->first();

    if (!$quoteItem) continue;

    // Expected total_amount should be the purchase_amount from the quote item (in paise)
    $expectedAmount = $quoteItem->purchase_amount;

    // If purchase_amount is 0, fall back to unit_price * qty
    if ($expectedAmount <= 0) {
        $expectedAmount = $quoteItem->unit_price * max(1, $quoteItem->qty);
    }

    if ($purchase->total_amount != $expectedAmount) {
        echo "Purchase #{$purchase->purchase_no} (Client: {$purchase->client_id}, Product: {$purchase->product_id})\n";
        echo "  Current total_amount (paise): {$purchase->total_amount} (displays as ₹" . number_format($purchase->total_amount / 100, 2) . ")\n";
        echo "  Expected total_amount (paise): {$expectedAmount} (displays as ₹" . number_format($expectedAmount / 100, 2) . ")\n";
        
        $purchase->update(['total_amount' => $expectedAmount]);
        echo "  ✅ FIXED!\n\n";
        $fixed++;
    } else {
        echo "Purchase #{$purchase->purchase_no}: OK (₹" . number_format($purchase->total_amount / 100, 2) . ")\n";
    }
}

echo "\n=== Done! Fixed {$fixed} purchases. ===\n";
