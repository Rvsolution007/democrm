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
            DB::table('catalogue_custom_columns')->where('company_id', $fromCompany)
                ->update(['company_id' => $targetCompany]);
            $this->info("  MOVED Catalogue columns -> Company #{$targetCompany}");

            DB::table('chatflow_steps')->where('company_id', $fromCompany)
                ->update(['company_id' => $targetCompany]);
            $this->info("  MOVED Chatflow steps -> Company #{$targetCompany}");

            // Move WhatsApp settings
            DB::table('settings')
                ->where('company_id', $fromCompany)
                ->where('group', 'whatsapp')
                ->update(['company_id' => $targetCompany]);
            $this->info("  MOVED WhatsApp settings -> Company #{$targetCompany}");

            // Move list_bot settings
            DB::table('settings')
                ->where('company_id', $fromCompany)
                ->where('group', 'list_bot')
                ->update(['company_id' => $targetCompany]);
            $this->info("  MOVED List Bot settings -> Company #{$targetCompany}");

            // Move ai_bot settings
            DB::table('settings')
                ->where('company_id', $fromCompany)
                ->where('group', 'ai_bot')
                ->update(['company_id' => $targetCompany]);
            $this->info("  MOVED AI Bot settings -> Company #{$targetCompany}");

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
