<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FixProductCompanyIds extends Command
{
    protected $signature = 'fix:product-company-ids';
    protected $description = 'Fix products and categories that have mismatched company_id (assigns based on creator user company)';

    public function handle()
    {
        $this->info('=== Checking company_id mismatches ===');

        // Fix Products: set company_id = creator's company_id
        $products = Product::withTrashed()->get();
        $fixed = 0;

        foreach ($products as $product) {
            $creator = User::find($product->created_by_user_id);
            if (!$creator) {
                $this->warn("Product #{$product->id} ({$product->name}): No creator found (user_id={$product->created_by_user_id})");
                continue;
            }

            if ($product->company_id !== $creator->company_id) {
                $oldCo = $product->company_id;
                $newCo = $creator->company_id;
                $product->update(['company_id' => $newCo]);
                $this->info("FIXED Product #{$product->id} ({$product->name}): company {$oldCo} -> {$newCo}");
                $fixed++;
            }
        }

        $this->info("Products fixed: {$fixed}");

        // Fix Categories: set company_id = creator's company_id
        $categories = Category::withTrashed()->get();
        $fixedCats = 0;

        foreach ($categories as $cat) {
            if (!$cat->created_by_user_id) continue;
            $creator = User::find($cat->created_by_user_id);
            if (!$creator) continue;

            if ($cat->company_id !== $creator->company_id) {
                $oldCo = $cat->company_id;
                $newCo = $creator->company_id;
                $cat->update(['company_id' => $newCo]);
                $this->info("FIXED Category #{$cat->id} ({$cat->name}): company {$oldCo} -> {$newCo}");
                $fixedCats++;
            }
        }

        $this->info("Categories fixed: {$fixedCats}");

        // Summary
        $this->info('');
        $this->info('=== Current State ===');
        $companies = DB::table('companies')->get();
        foreach ($companies as $co) {
            $productCount = Product::where('company_id', $co->id)->where('status', 'active')->count();
            $catCount = Category::where('company_id', $co->id)->where('status', 'active')->count();
            $this->info("Company #{$co->id} ({$co->name}): {$productCount} active products, {$catCount} active categories");
        }

        $this->info('Done!');
    }
}
