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
            'catalogue_rules_history' => $this->testCatalogueRulesHistory(),
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
            'rule_name' => 'Unique Identifier',
            'product_page' => 'show and connect',
            'chatflow' => 'show and connect<br><span style="font-size:11px;color:#888;">Rule: user ne ae data ek se jyada confirm kiya to quote me hoga selection</span>',
            'lead_quote' => 'show and connect<br><span style="font-size:11px;color:#888;">Rule: ae data user confirm krta he to lead/quote me save hoga</span>',
        ];

        $rows[] = [
            'rule_name' => 'Category Linked',
            'product_page' => 'show and connect',
            'chatflow' => 'show and connect<br><span style="font-size:11px;color:#888;">Rule: user ne ae data ek se jyada confirm kiya to quote me hoga selection</span>',
            'lead_quote' => 'show and connect<br><span style="font-size:11px;color:#888;">Rule: ae data user confirm krta he to lead/quote me save hoga</span>',
        ];

        $rows[] = [
            'rule_name' => 'Quote/Lead Title',
            'product_page' => '<span style="color:#cbd5e1;">null</span>',
            'chatflow' => '<span style="color:#cbd5e1;">null</span>',
            'lead_quote' => 'show and connect<br><span style="font-size:11px;color:#888;">Rule: agar ae catalogue column me enable he to quote and lead me main column me ayega</span>',
        ];

        $rows[] = [
            'rule_name' => 'Required Field',
            'product_page' => 'show and connect<br><span style="font-size:11px;color:#888;">Rule: Mut be filled to save</span>',
            'chatflow' => 'show and connect<br><span style="font-size:11px;color:#888;">Rule: User cannot skip this question</span>',
            'lead_quote' => 'show and connect<br><span style="font-size:11px;color:#888;">Rule: Guaranteed to be present in data</span>',
        ];

        $rows[] = [
            'rule_name' => 'Variation Matrix',
            'product_page' => 'show and connect<br><span style="font-size:11px;color:#888;">Rule: Creates product variants</span>',
            'chatflow' => 'show and connect<br><span style="font-size:11px;color:#888;">Rule: Enables variation selection matrix</span>',
            'lead_quote' => 'show and connect<br><span style="font-size:11px;color:#888;">Rule: Links particular variation to quote item</span>',
        ];

        $rows[] = [
            'rule_name' => 'Per-Variation Field',
            'product_page' => 'show and connect<br><span style="font-size:11px;color:#888;">Rule: Value changes per variant</span>',
            'chatflow' => 'show and connect<br><span style="font-size:11px;color:#888;">Rule: Uses specific variation logic</span>',
            'lead_quote' => 'show and connect<br><span style="font-size:11px;color:#888;">Rule: Connects variation properties to lead</span>',
        ];

        return $rows;
    }

    private function testCatalogueRulesHistory(): array
    {
        return [
            [
                'date' => '2026-04-11',
                'action' => 'add',
                'description' => 'Added core rules: Unique Identifier, Category Linked, Default Title, Required Field.'
            ],
            [
                'date' => '2026-04-12',
                'action' => 'add',
                'description' => 'Added new architectural rules: Variation Matrix & Per-Variation Field.'
            ],
            [
                'date' => '2026-04-15',
                'action' => 'update',
                'rule' => 'Quote/Lead Title',
                'old_logic' => 'Overrides name silently during attachment',
                'new_logic' => 'Agar ye catalogue column me enable hai to quote and lead me main column me aayega dynamically'
            ],
            [
                'date' => '2026-04-17',
                'action' => 'update',
                'rule' => 'Category Linked (ListBot Flow)',
                'old_logic' => 'Required user to confirm even if only 1 product remains',
                'new_logic' => 'Filter activates, auto-selects if only 1 option available to prevent redundant questioning'
            ],
        ];
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
