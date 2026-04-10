<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════
        // Merge 4-tier → 3-tier Package Structure
        // Old: Starter | Professional | List Bot | Enterprise
        // New: Core CRM | Core + Bot List | Core + AI Bot
        // ═══════════════════════════════════════════════════════════

        // ─── 1. Define the Bot List feature set (Tier 2) ──────────
        $botListFeatures = [
            'leads', 'quotes', 'invoices', 'clients', 'payments', 'followups',
            'products', 'categories', 'users', 'roles',
            'activities', 'profile', 'reports', 'settings',
            'whatsapp_connect', 'whatsapp_campaigns', 'whatsapp_templates',
            'whatsapp_auto_reply', 'whatsapp_analytics',
            'chatflow', 'list_bot', 'catalogue_columns',
        ];

        $botListModules = [];
        foreach ($botListFeatures as $f) $botListModules[$f] = true;

        // ─── 2. Update or Create Bot List package (slug: botlist) ──
        $listBotPkg = DB::table('subscription_packages')->where('slug', 'list-bot')->first();
        if ($listBotPkg) {
            // Rename existing list-bot → botlist with merged features
            DB::table('subscription_packages')->where('id', $listBotPkg->id)->update([
                'name' => 'Core CRM + Bot List',
                'slug' => 'botlist',
                'description' => 'Complete CRM + WhatsApp + Interactive Bot List with auto-reply, chatflow builder, bulk sender. Zero AI costs.',
                'features' => json_encode($botListFeatures),
                'module_permissions' => json_encode($botListModules),
                'sort_order' => 2,
                'updated_at' => now(),
            ]);
        } else {
            // Create if doesn't exist
            DB::table('subscription_packages')->insert([
                'name' => 'Core CRM + Bot List',
                'slug' => 'botlist',
                'description' => 'Complete CRM + WhatsApp + Interactive Bot List with auto-reply, chatflow builder, bulk sender. Zero AI costs.',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'default_max_users' => 10,
                'max_leads_per_month' => 0,
                'features' => json_encode($botListFeatures),
                'module_permissions' => json_encode($botListModules),
                'trial_days' => 14,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Get the botlist package ID (after create/update)
        $botListPkgId = DB::table('subscription_packages')
            ->where('slug', 'botlist')
            ->orWhere('slug', 'list-bot')
            ->value('id');

        // ─── 3. Migrate Professional subscribers → Bot List ───────
        $professionalPkg = DB::table('subscription_packages')->where('slug', 'professional')->first();
        if ($professionalPkg && $botListPkgId) {
            // Move all subscriptions from professional → botlist
            DB::table('subscriptions')
                ->where('package_id', $professionalPkg->id)
                ->update([
                    'package_id' => $botListPkgId,
                    'updated_at' => now(),
                ]);

            // Delete the professional package
            DB::table('subscription_packages')->where('id', $professionalPkg->id)->delete();
        }

        // ─── 4. Update Starter package (Core CRM — Tier 1) ───────
        $starter = DB::table('subscription_packages')->where('slug', 'starter')->first();
        if ($starter) {
            DB::table('subscription_packages')->where('id', $starter->id)->update([
                'name' => 'Core CRM',
                'description' => 'Essential CRM for small businesses. Lead management, quotes, invoices, payments, products, and team management.',
                'sort_order' => 1,
                'updated_at' => now(),
            ]);
        }

        // ─── 5. Update Enterprise package (AI Bot — Tier 3) ──────
        // AI Bot includes EVERYTHING from Bot List + AI features
        $aiFeatures = array_merge($botListFeatures, [
            'ai_bot', 'token_analytics', 'chat_history', 'ai_credit_wallet',
        ]);
        $aiModules = [];
        foreach ($aiFeatures as $f) $aiModules[$f] = true;

        $enterprise = DB::table('subscription_packages')->where('slug', 'enterprise')->first();
        if ($enterprise) {
            DB::table('subscription_packages')->where('id', $enterprise->id)->update([
                'name' => 'Core CRM + AI Bot',
                'description' => 'Complete CRM + WhatsApp + AI Chatbot + Bot List. Includes chatflow, AI token wallet, smart matching, and full automation suite.',
                'features' => json_encode($aiFeatures),
                'module_permissions' => json_encode($aiModules),
                'sort_order' => 3,
                'updated_at' => now(),
            ]);
        }

        // ─── 6. Migrate bot_mode: auto_reply → list_bot ──────────
        DB::table('settings')
            ->where('group', 'whatsapp')
            ->where('key', 'bot_mode')
            ->where('value', json_encode('auto_reply'))
            ->update([
                'value' => json_encode('list_bot'),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Restore professional package
        $botListPkg = DB::table('subscription_packages')->where('slug', 'botlist')->first();

        if ($botListPkg) {
            // Rename back to list-bot
            DB::table('subscription_packages')->where('id', $botListPkg->id)->update([
                'slug' => 'list-bot',
                'name' => 'Core CRM + List Bot',
                'sort_order' => 3,
                'updated_at' => now(),
            ]);
        }

        // Re-create professional package
        DB::table('subscription_packages')->insert([
            'name' => 'Core CRM + Auto Reply',
            'slug' => 'professional',
            'description' => 'Full CRM + WhatsApp Business integration with auto-reply rules.',
            'monthly_price' => 0,
            'yearly_price' => 0,
            'default_max_users' => 5,
            'max_leads_per_month' => 0,
            'features' => json_encode([
                'leads', 'quotes', 'invoices', 'clients', 'payments', 'followups',
                'products', 'categories', 'catalogue_columns', 'users', 'roles',
                'activities', 'profile', 'reports', 'settings',
                'whatsapp_connect', 'whatsapp_campaigns', 'whatsapp_templates',
                'whatsapp_auto_reply', 'whatsapp_analytics',
            ]),
            'module_permissions' => json_encode([
                'leads' => true, 'quotes' => true, 'invoices' => true,
                'clients' => true, 'payments' => true, 'followups' => true,
                'products' => true, 'categories' => true, 'catalogue_columns' => true,
                'users' => true, 'roles' => true, 'activities' => true,
                'profile' => true, 'reports' => true, 'settings' => true,
                'whatsapp_connect' => true, 'whatsapp_campaigns' => true,
                'whatsapp_templates' => true, 'whatsapp_auto_reply' => true,
                'whatsapp_analytics' => true,
            ]),
            'trial_days' => 14,
            'is_active' => true,
            'sort_order' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
