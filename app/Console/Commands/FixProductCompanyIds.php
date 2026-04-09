<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixProductCompanyIds extends Command
{
    protected $signature = 'fix:product-company-ids {--target-company= : Target company ID to assign orphaned products to} {--from-company= : Source company ID to move products from}';
    protected $description = 'Fix products and categories company_id assignments';

    public function handle()
    {
        $targetCompany = $this->option('target-company');
        $fromCompany = $this->option('from-company');

        // Show current state first
        $this->info('=== Current State ===');
        $companies = DB::table('companies')->get();
        foreach ($companies as $co) {
            $productCount = Product::where('company_id', $co->id)->count();
            $activeCount = Product::where('company_id', $co->id)->where('status', 'active')->count();
            $catCount = Category::where('company_id', $co->id)->count();
            $userCount = User::where('company_id', $co->id)->count();
            $this->info("Company #{$co->id} ({$co->name}): {$activeCount} active / {$productCount} total products, {$catCount} categories, {$userCount} users");
        }

        $this->info('');
        $this->info('=== All Products ===');
        foreach (Product::withTrashed()->get() as $p) {
            $del = $p->deleted_at ? ' [DELETED]' : '';
            $this->info("  #{$p->id}: {$p->name} | company={$p->company_id} | creator={$p->created_by_user_id} | status={$p->status}{$del}");
        }

        $this->info('');
        $this->info('=== All Categories ===');
        foreach (Category::withTrashed()->get() as $c) {
            $del = $c->deleted_at ? ' [DELETED]' : '';
            $this->info("  #{$c->id}: {$c->name} | company={$c->company_id} | creator={$c->created_by_user_id}{$del}");
        }

        // If target company specified, reassign products
        if ($targetCompany && $fromCompany) {
            $targetCo = DB::table('companies')->where('id', $targetCompany)->first();
            $fromCo = DB::table('companies')->where('id', $fromCompany)->first();

            if (!$targetCo || !$fromCo) {
                $this->error('Invalid company IDs');
                return;
            }

            // Find admin user of target company
            $targetAdmin = User::where('company_id', $targetCompany)->first();

            $this->info('');
            $this->warn("Moving products from Company #{$fromCompany} ({$fromCo->name}) -> Company #{$targetCompany} ({$targetCo->name})");

            // Move products
            $products = Product::withTrashed()->where('company_id', $fromCompany)->get();
            foreach ($products as $p) {
                $p->company_id = (int) $targetCompany;
                if (!$p->created_by_user_id && $targetAdmin) {
                    $p->created_by_user_id = $targetAdmin->id;
                }
                $p->saveQuietly();
                $this->info("  MOVED Product #{$p->id} ({$p->name}) -> Company #{$targetCompany}");
            }

            // Move categories
            $cats = Category::withTrashed()->where('company_id', $fromCompany)->get();
            foreach ($cats as $c) {
                $c->company_id = (int) $targetCompany;
                if (!$c->created_by_user_id && $targetAdmin) {
                    $c->created_by_user_id = $targetAdmin->id;
                }
                $c->saveQuietly();
                $this->info("  MOVED Category #{$c->id} ({$c->name}) -> Company #{$targetCompany}");
            }

            // Also move related settings (catalogue columns, chatflow steps)
            // Handle duplicates: delete source records that already exist in target
            $sourceColumns = DB::table('catalogue_custom_columns')->where('company_id', $fromCompany)->get();
            foreach ($sourceColumns as $col) {
                $exists = DB::table('catalogue_custom_columns')
                    ->where('company_id', $targetCompany)
                    ->where('slug', $col->slug)
                    ->exists();

                if ($exists) {
                    // Target already has this column — delete the source duplicate
                    DB::table('catalogue_custom_columns')->where('id', $col->id)->delete();
                    $this->warn("  SKIPPED Column #{$col->id} ({$col->slug}) — already exists in target");
                } else {
                    DB::table('catalogue_custom_columns')->where('id', $col->id)
                        ->update(['company_id' => $targetCompany]);
                    $this->info("  MOVED Column #{$col->id} ({$col->slug}) -> Company #{$targetCompany}");
                }
            }

            try {
                DB::table('chatflow_steps')->where('company_id', $fromCompany)
                    ->update(['company_id' => $targetCompany]);
                $this->info("  MOVED Chatflow steps -> Company #{$targetCompany}");
            } catch (\Exception $e) {
                $this->warn("  SKIPPED Chatflow steps (duplicates exist)");
            }

            // Move settings — handle each individually to avoid duplicates
            foreach (['whatsapp', 'list_bot', 'ai_bot'] as $group) {
                $sourceSettings = DB::table('settings')
                    ->where('company_id', $fromCompany)
                    ->where('group', $group)
                    ->get();

                foreach ($sourceSettings as $setting) {
                    $exists = DB::table('settings')
                        ->where('company_id', $targetCompany)
                        ->where('group', $group)
                        ->where('key', $setting->key)
                        ->exists();

                    if ($exists) {
                        $this->warn("  SKIPPED Setting {$group}.{$setting->key} — already in target");
                    } else {
                        DB::table('settings')->where('id', $setting->id)
                            ->update(['company_id' => $targetCompany]);
                        $this->info("  MOVED Setting {$group}.{$setting->key} -> Company #{$targetCompany}");
                    }
                }
            }

            $this->info('');
            $this->info('=== After Fix ===');
            foreach (DB::table('companies')->get() as $co) {
                $pc = Product::where('company_id', $co->id)->where('status', 'active')->count();
                $cc = Category::where('company_id', $co->id)->count();
                $this->info("Company #{$co->id} ({$co->name}): {$pc} active products, {$cc} categories");
            }
        } else {
            $this->info('');
            $this->warn('To move products, run:');
            $this->warn('  php artisan fix:product-company-ids --from-company=1 --target-company=2');
        }

        $this->info('Done!');
    }
}
