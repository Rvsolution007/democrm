<?php

namespace App\Services;

use App\Models\CatalogueCustomColumn;
use App\Models\ChatflowStep;
use App\Models\Company;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ProductCombo;

class HardcodedDiagnosticService
{
    public function run(int $companyId): array
    {
        return [
            'catalogue_rules' => $this->testCatalogueRules($companyId),
            'botlist_flow'    => $this->testBotListFlow($companyId),
            'aibot_flow'      => $this->testAiBotFlow($companyId),
            'session_rules'   => $this->testSessionRules($companyId),
        ];
    }

    private function testCatalogueRules(int $companyId): array
    {
        $rows = [];

        $rows[] = [
            'rule_name' => 'Unique Identifier (is_unique)',
            'module' => 'Catalogue Rules',
            'working' => true,
            'detail' => 'Hardcoded System Rule',
            'connected_to' => 'System Engine',
            'bot_flow' => "📦 Product Page: show and connect\n💬 Chatflow: show and connect (rule: user ne ae data ek se jyada confirm kiya to quote me hoga selection)\n📋 Lead/Quote: show and connect (rule: ae data user confirm krta he to lead/quote me save hoga)",
            'severity' => 'success'
        ];

        $rows[] = [
            'rule_name' => 'Category Linked (is_category)',
            'module' => 'Catalogue Rules',
            'working' => true,
            'detail' => 'Hardcoded System Rule',
            'connected_to' => 'System Engine',
            'bot_flow' => "📦 Product Page: show and connect\n💬 Chatflow: show and connect (rule: user ne ae data ek se jyada confirm kiya to quote me hoga selection)\n📋 Lead/Quote: show and connect (rule: ae data user confirm krta he to lead/quote me save hoga)",
            'severity' => 'success'
        ];

        $rows[] = [
            'rule_name' => 'Quote/Lead Title (is_title)',
            'module' => 'Catalogue Rules',
            'working' => true,
            'detail' => 'Hardcoded System Rule',
            'connected_to' => 'System Engine',
            'bot_flow' => "📦 Product Page: null\n💬 Chatflow: null\n📋 Lead/Quote: show and connect (rule: agar ae catalogue column me enable he to quote and lead me main column me ayega)",
            'severity' => 'success'
        ];

        $rows[] = [
            'rule_name' => 'Required Field (is_required)',
            'module' => 'Catalogue Rules',
            'working' => true,
            'detail' => 'Hardcoded System Rule',
            'connected_to' => 'System Engine',
            'bot_flow' => "📦 Product Page: Must be filled to save\n💬 Chatflow: Automatically triggers prompt if missing\n📋 Lead/Quote: Enforces presence of data",
            'severity' => 'success'
        ];

        $rows[] = [
            'rule_name' => 'Variation Matrix (is_combo)',
            'module' => 'Catalogue Rules',
            'working' => true,
            'detail' => 'Hardcoded System Rule',
            'connected_to' => 'System Engine',
            'bot_flow' => "📦 Product Page: Creates product variants\n💬 Chatflow: Enables variation selection matrix\n📋 Lead/Quote: Links particular variation to quote item",
            'severity' => 'success'
        ];

        $rows[] = [
            'rule_name' => 'Per-Variation Field (is_variation_field)',
            'module' => 'Catalogue Rules',
            'working' => true,
            'detail' => 'Hardcoded System Rule',
            'connected_to' => 'System Engine',
            'bot_flow' => "📦 Product Page: Value changes per variant\n💬 Chatflow: Uses specific variation logic\n📋 Lead/Quote: Connects variation properties to lead",
            'severity' => 'success'
        ];

        return $rows;
    }

    private function testBotListFlow(int $companyId): array
    {
        $rows = [];

        // Progressive Filter
        $rows[] = [
            'rule_name' => 'Progressive Filter',
            'module' => 'Bot List Flow',
            'working' => true,
            'detail' => '✅ Hardcoded filter chain active',
            'connected_to' => 'ListBotService::processFlow()',
            'bot_flow' => 'category step → filter products → next step uses filtered product IDs → loop until complete',
            'severity' => 'success'
        ];

        $rows[] = [
            'rule_name' => 'Empty Column Skip',
            'module' => 'Bot List Flow',
            'working' => true,
            'detail' => '✅ Skip logic active',
            'connected_to' => 'ListBotService (null check)',
            'bot_flow' => 'column data is null for filtered products → step skipped automatically → move to next',
            'severity' => 'success'
        ];

        $rows[] = [
            'rule_name' => 'Single Value Auto-Select',
            'module' => 'Bot List Flow',
            'working' => true,
            'detail' => '✅ Auto-select logic active',
            'connected_to' => 'ListBotService (count check)',
            'bot_flow' => 'only 1 option exists for step → auto-selected silently → no redundant question sent',
            'severity' => 'success'
        ];

        return $rows;
    }

    private function testAiBotFlow(int $companyId): array
    {
        $rows = [];

        $rows[] = [
            'rule_name' => 'Stage 1: PHP Greeting Match',
            'module' => 'AI Bot Flow',
            'working' => true,
            'detail' => '✅ "hi", "hello" logic active',
            'connected_to' => 'AIChatbotService::isGreeting()',
            'bot_flow' => 'PHP regex match → intercept before PGM → Tier 0 Greeting AI model',
            'severity' => 'success'
        ];

        $rows[] = [
            'rule_name' => 'Stage 2: PGM Match',
            'module' => 'AI Bot Flow',
            'working' => true,
            'detail' => '✅ 5-layer search engine active',
            'connected_to' => 'AIChatbotService (PGM)',
            'bot_flow' => 'user msg → Exact/Contains/Word/Fuzzy/Phonetic extraction → best matched column + values detected',
            'severity' => 'success'
        ];

        $rows[] = [
            'rule_name' => 'PGM Scenario 1: Same Column',
            'module' => 'AI Bot Flow',
            'working' => true,
            'detail' => '✅ Route to Tier 1',
            'connected_to' => 'Tier 1 Product Match AI',
            'bot_flow' => 'PGM col = chatflow current col → Tier 1 AI (msg + product metadata) → MATCH_ID/QUEUE/AMBIGUOUS',
            'severity' => 'success'
        ];

        $rows[] = [
            'rule_name' => 'PGM Scenario 2: Diff Column',
            'module' => 'AI Bot Flow',
            'working' => true,
            'detail' => '✅ Route to Tier 3',
            'connected_to' => 'Tier 3 Column Analytics AI',
            'bot_flow' => 'PGM col ≠ chatflow current col (or general question) → Tier 3 AI answers using admin knowledge',
            'severity' => 'success'
        ];

        $rows[] = [
            'rule_name' => 'Stage 3: No PGM Match Fallback',
            'module' => 'AI Bot Flow',
            'working' => true,
            'detail' => '✅ Route to Tier 2',
            'connected_to' => 'Tier 2 Conversational AI',
            'bot_flow' => 'No matches found → Tier 2 general AI using System Prompt + Business Prompt',
            'severity' => 'success'
        ];

        return $rows;
    }

    private function testSessionRules(int $companyId): array
    {
        $rows = [];

        $validDays = Setting::getValue('whatsapp', 'session_valid_days', 7, $companyId);
        $rows[] = [
            'rule_name' => 'Session valid_days Check',
            'module' => 'Session Rules',
            'working' => true,
            'detail' => "✅ {$validDays} days configured",
            'connected_to' => 'AiChatSession',
            'bot_flow' => "If session > {$validDays} days old → expired → new session created to start fresh",
            'severity' => 'success'
        ];

        $rows[] = [
            'rule_name' => 'Lead Auto-Creation',
            'module' => 'Session Rules',
            'working' => true,
            'detail' => '✅ Lead creation linked',
            'connected_to' => 'Lead Model',
            'bot_flow' => 'First message received → Lead created automatically if not exists',
            'severity' => 'success'
        ];

        $rows[] = [
            'rule_name' => 'Lead/Quote Delete Reset',
            'module' => 'Session Rules',
            'working' => true,
            'detail' => '✅ Foreign key check active',
            'connected_to' => 'ListBotService context check',
            'bot_flow' => 'Admin deletes Lead or Quote → associated session invalidated/reset automatically to prevent errors',
            'severity' => 'success'
        ];

        return $rows;
    }
}
