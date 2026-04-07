<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ─── 1. Seed Default Subscription Packages ──────────────────────────

        $starterFeatures = [
            'leads', 'quotes', 'invoices', 'clients', 'payments', 'followups',
            'products', 'categories', 'catalogue_columns', 'users', 'roles',
            'activities', 'profile', 'reports', 'settings',
        ];
        $professionalFeatures = array_merge($starterFeatures, [
            'whatsapp_connect', 'whatsapp_campaigns', 'whatsapp_templates',
            'whatsapp_auto_reply', 'whatsapp_analytics',
        ]);
        $enterpriseFeatures = array_merge($professionalFeatures, [
            'chatflow', 'ai_bot', 'ai_credit_wallet',
        ]);

        // Module permissions — granular boolean map for feature gating
        $starterModules = [];
        foreach ($starterFeatures as $f) $starterModules[$f] = true;
        
        $professionalModules = [];
        foreach ($professionalFeatures as $f) $professionalModules[$f] = true;
        
        $enterpriseModules = [];
        foreach ($enterpriseFeatures as $f) $enterpriseModules[$f] = true;

        DB::table('subscription_packages')->insert([
            [
                'id' => 1,
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Essential CRM for small businesses. Includes lead management, quotes, invoices, payments, products, and team management.',
                'monthly_price' => 0,    // SA will set actual prices from panel
                'yearly_price' => 0,
                'default_max_users' => 3,
                'max_leads_per_month' => 0,
                'features' => json_encode($starterFeatures),
                'module_permissions' => json_encode($starterModules),
                'trial_days' => 14,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Full CRM + WhatsApp Business integration. Includes bulk messaging, templates, auto-reply rules, and reply analytics.',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'default_max_users' => 10,
                'max_leads_per_month' => 0,
                'features' => json_encode($professionalFeatures),
                'module_permissions' => json_encode($professionalModules),
                'trial_days' => 14,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Complete CRM + WhatsApp + AI Chatbot. Includes chatflow builder, AI token wallet, and full automation suite.',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'default_max_users' => 25,
                'max_leads_per_month' => 0,
                'features' => json_encode($enterpriseFeatures),
                'module_permissions' => json_encode($enterpriseModules),
                'trial_days' => 14,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // ─── 2. Seed Default AI Credit Packs ───────────────────────────────

        DB::table('ai_credit_packs')->insert([
            [
                'name' => 'Starter Pack',
                'credits' => 500,
                'price' => 499.00,
                'description' => '~500 AI conversations. Perfect for trying out AI features.',
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Growth Pack',
                'credits' => 2000,
                'price' => 1499.00,
                'description' => '~2,000 AI conversations. Best value for growing businesses.',
                'is_popular' => true,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Business Pack',
                'credits' => 5000,
                'price' => 2999.00,
                'description' => '~5,000 AI conversations. For active sales teams.',
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Enterprise Pack',
                'credits' => 15000,
                'price' => 6999.00,
                'description' => '~15,000 AI conversations. High volume support.',
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ultimate Pack',
                'credits' => 50000,
                'price' => 14999.00,
                'description' => '~50,000 AI conversations. Maximum value enterprise pack.',
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // ─── 3. Migrate Existing Data ───────────────────────────────────────

        // Set existing user id=1 as super_admin
        DB::table('users')->where('id', 1)->update([
            'user_type' => 'super_admin',
        ]);

        // Set any other existing users as admin (company owners) or staff
        // The user with id=1 is SA. If company has other users, the first non-SA user with admin role becomes 'admin'
        $adminRoles = DB::table('roles')
            ->where(function ($q) {
                $q->where('name', 'LIKE', '%admin%')
                    ->orWhere('permissions', 'LIKE', '%"all"%');
            })->pluck('id')->toArray();

        // Mark users with admin roles as 'admin' type (except user 1 who is super_admin)
        if (!empty($adminRoles)) {
            DB::table('users')
                ->where('id', '!=', 1)
                ->whereIn('role_id', $adminRoles)
                ->update(['user_type' => 'admin']);
        }

        // All remaining users stay as 'staff' (default)

        // Set company owner
        $companies = DB::table('companies')->get();
        foreach ($companies as $company) {
            // Find the admin user for this company
            $owner = DB::table('users')
                ->where('company_id', $company->id)
                ->whereIn('user_type', ['super_admin', 'admin'])
                ->orderBy('id')
                ->first();

            if ($owner) {
                DB::table('companies')->where('id', $company->id)->update([
                    'owner_user_id' => $owner->id,
                ]);
            }
        }

        // ─── 4. Create Enterprise Subscription for Existing Company ────────

        $companies = DB::table('companies')->get();
        foreach ($companies as $company) {
            // Give every existing company an Enterprise trial (365 days)
            DB::table('subscriptions')->insert([
                'company_id' => $company->id,
                'package_id' => 3, // Enterprise
                'status' => 'active',
                'billing_cycle' => 'yearly',
                'amount_paid' => 0,
                'max_users' => null, // use package default (25)
                'starts_at' => now()->toDateString(),
                'expires_at' => now()->addDays(365)->toDateString(),
                'trial_ends_at' => null,
                'custom_overrides' => null,
                'notes' => 'Auto-created during multi-tenant migration. Free Enterprise access for 1 year.',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create AI credit wallet with 10,000 free credits
            DB::table('ai_credit_wallets')->insert([
                'company_id' => $company->id,
                'balance' => 10000,
                'total_purchased' => 10000,
                'total_consumed' => 0,
                'low_balance_threshold' => 50,
                'low_balance_alert_sent' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Record the bonus credit transaction
            $walletId = DB::table('ai_credit_wallets')
                ->where('company_id', $company->id)->value('id');

            DB::table('ai_credit_transactions')->insert([
                'company_id' => $company->id,
                'wallet_id' => $walletId,
                'type' => 'bonus',
                'credits' => 10000,
                'balance_after' => 10000,
                'amount_paid' => 0,
                'ai_tokens_used' => null,
                'description' => 'Welcome bonus — free credits during multi-tenant migration',
                'reference_type' => 'system_migration',
                'reference_id' => null,
                'payment_method' => null,
                'razorpay_payment_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ─── 5. Migrate Global Settings ─────────────────────────────────────

        // Mark AI and WhatsApp settings as global scope
        $globalGroups = ['ai_bot', 'whatsapp'];
        $globalKeys = [
            'vertex_config', 'system_prompt', 'greeting_prompt', 'spell_correction_prompt',
            'tier3_prompt', 'match_min_confidence', 'api_config',
        ];

        DB::table('settings')
            ->where(function ($q) use ($globalGroups, $globalKeys) {
                $q->whereIn('group', $globalGroups)
                    ->whereIn('key', $globalKeys);
            })
            ->update(['scope' => 'global']);

        // ─── 6. Seed Global AI Credit Settings ──────────────────────────────

        $companyId = DB::table('companies')->value('id') ?? 1;

        $creditSettings = [
            ['group' => 'ai_credits', 'key' => 'credits_per_1k_tokens', 'value' => json_encode(1.2)],
            ['group' => 'ai_credits', 'key' => 'min_credits_to_operate', 'value' => json_encode(10)],
            ['group' => 'ai_credits', 'key' => 'low_balance_threshold', 'value' => json_encode(50)],
            ['group' => 'ai_credits', 'key' => 'alert_admin_on_low', 'value' => json_encode(true)],
            ['group' => 'ai_credits', 'key' => 'alert_sa_on_low', 'value' => json_encode(true)],
        ];

        foreach ($creditSettings as $setting) {
            DB::table('settings')->insert(array_merge($setting, [
                'company_id' => $companyId,
                'scope' => 'global',
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        // Remove seeded data (reverse order)
        DB::table('settings')->where('group', 'ai_credits')->delete();
        DB::table('settings')->where('scope', 'global')->update(['scope' => 'company']);
        DB::table('ai_credit_transactions')->truncate();
        DB::table('ai_credit_wallets')->truncate();
        DB::table('subscriptions')->truncate();
        DB::table('ai_credit_packs')->truncate();
        DB::table('subscription_packages')->truncate();

        DB::table('users')->where('id', 1)->update(['user_type' => 'staff']);
        DB::table('companies')->update(['owner_user_id' => null]);
    }
};
