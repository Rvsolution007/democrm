<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // ─── 1. Update existing packages to 4-tier structure ──────────────

        // First, add the list_bot module to packages that should have it

        // Package 1: Starter → Core CRM (no WhatsApp)
        $starter = DB::table('subscription_packages')->where('slug', 'starter')->first();
        if ($starter) {
            DB::table('subscription_packages')->where('id', $starter->id)->update([
                'name' => 'Core CRM',
                'slug' => 'starter',
                'description' => 'Essential CRM for small businesses. Lead management, quotes, invoices, payments, products, and team management.',
                'default_max_users' => 3,
                'sort_order' => 1,
                'updated_at' => now(),
            ]);
        }

        // Package 2: Professional → Core CRM + Auto Reply
        $professional = DB::table('subscription_packages')->where('slug', 'professional')->first();
        if ($professional) {
            DB::table('subscription_packages')->where('id', $professional->id)->update([
                'name' => 'Core CRM + Auto Reply',
                'description' => 'Full CRM + WhatsApp Business integration with auto-reply rules, templates, bulk messaging, and analytics.',
                'default_max_users' => 5,
                'sort_order' => 2,
                'updated_at' => now(),
            ]);
        }

        // Package 3: Enterprise → needs to be split into two
        // Current Enterprise becomes "Core CRM + AI Bot" (Package 4)
        $enterprise = DB::table('subscription_packages')->where('slug', 'enterprise')->first();

        // First create the new Package 3: Core CRM + List Bot
        $listBotFeatures = [
            'leads', 'quotes', 'invoices', 'clients', 'payments', 'followups',
            'products', 'categories', 'catalogue_columns', 'users', 'roles',
            'activities', 'profile', 'reports', 'settings',
            'whatsapp_connect', 'whatsapp_campaigns', 'whatsapp_templates',
            'whatsapp_auto_reply', 'whatsapp_analytics',
            'chatflow', 'list_bot',
        ];

        $listBotModules = [];
        foreach ($listBotFeatures as $f) $listBotModules[$f] = true;

        // Check if list_bot package already exists
        $existingListBot = DB::table('subscription_packages')->where('slug', 'list-bot')->first();
        if (!$existingListBot) {
            DB::table('subscription_packages')->insert([
                'name' => 'Core CRM + List Bot',
                'slug' => 'list-bot',
                'description' => 'CRM + WhatsApp + Interactive List Bot. Menu-driven chatbot with zero AI costs. Includes chatflow builder and auto-reply.',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'default_max_users' => 10,
                'max_leads_per_month' => 0,
                'features' => json_encode($listBotFeatures),
                'module_permissions' => json_encode($listBotModules),
                'trial_days' => 14,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Update Enterprise to include list_bot feature too
        if ($enterprise) {
            $currentFeatures = json_decode($enterprise->features, true) ?? [];
            $currentModules = json_decode($enterprise->module_permissions, true) ?? [];

            // Add list_bot to enterprise features
            if (!in_array('list_bot', $currentFeatures)) {
                $currentFeatures[] = 'list_bot';
            }
            $currentModules['list_bot'] = true;

            DB::table('subscription_packages')->where('id', $enterprise->id)->update([
                'name' => 'Core CRM + AI Bot',
                'description' => 'Complete CRM + WhatsApp + AI Chatbot + Interactive List Bot. Includes chatflow builder, AI token wallet, smart matching, and full automation suite.',
                'default_max_users' => 25,
                'features' => json_encode($currentFeatures),
                'module_permissions' => json_encode($currentModules),
                'sort_order' => 4,
                'updated_at' => now(),
            ]);
        }

        // ─── 2. Migrate old ai_bot.enabled setting to new bot_mode ──────

        $aiBotSettings = DB::table('settings')
            ->where('group', 'ai_bot')
            ->where('key', 'enabled')
            ->get();

        foreach ($aiBotSettings as $setting) {
            $isEnabled = json_decode($setting->value, true);

            // Check if bot_mode already exists for this company
            $existing = DB::table('settings')
                ->where('group', 'whatsapp')
                ->where('key', 'bot_mode')
                ->where('company_id', $setting->company_id)
                ->first();

            if (!$existing) {
                DB::table('settings')->insert([
                    'group' => 'whatsapp',
                    'key' => 'bot_mode',
                    'value' => json_encode($isEnabled ? 'ai_bot' : 'auto_reply'),
                    'company_id' => $setting->company_id,
                    'scope' => $setting->scope ?? 'company',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Remove bot_mode settings
        DB::table('settings')
            ->where('group', 'whatsapp')
            ->where('key', 'bot_mode')
            ->delete();

        // Remove list_bot package
        DB::table('subscription_packages')
            ->where('slug', 'list-bot')
            ->delete();

        // Restore original package names
        DB::table('subscription_packages')
            ->where('slug', 'starter')
            ->update(['name' => 'Starter', 'updated_at' => now()]);

        DB::table('subscription_packages')
            ->where('slug', 'professional')
            ->update(['name' => 'Professional', 'updated_at' => now()]);

        DB::table('subscription_packages')
            ->where('slug', 'enterprise')
            ->update(['name' => 'Enterprise', 'updated_at' => now()]);

        // Remove list_bot from enterprise modules
        $enterprise = DB::table('subscription_packages')->where('slug', 'enterprise')->first();
        if ($enterprise) {
            $modules = json_decode($enterprise->module_permissions, true) ?? [];
            unset($modules['list_bot']);
            $features = json_decode($enterprise->features, true) ?? [];
            $features = array_filter($features, fn($f) => $f !== 'list_bot');

            DB::table('subscription_packages')->where('id', $enterprise->id)->update([
                'module_permissions' => json_encode($modules),
                'features' => json_encode(array_values($features)),
                'updated_at' => now(),
            ]);
        }
    }
};
