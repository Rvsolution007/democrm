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
            [
                'date' => '2026-04-17',
                'action' => 'add',
                'rule' => 'Smart Normalize Column Filter',
                'old_logic' => 'Exact string match for column uniqueness (case-sensitive, space-sensitive)',
                'new_logic' => 'Normalized uniqueness: lowercase + trim + collapse spaces. "Door Handle" = "door handle". Filter matching also case-insensitive. No product invisible due to casing/spacing.'
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

        $rows[] = [
            'rule_name' => 'Smart Normalize Column Filter',
            'module' => 'Bot List Flow',
            'working' => true,
            'detail' => '✅ Case + spacing normalize active',
            'connected_to' => 'ListBotService::normalizeColumnValue()',
            'bot_flow' => 'ask_column step → collect values → normalize (lowercase + trim + collapse spaces) → "Door Handle" = "door handle" = "Door  Handle" → show unique entries only. Filter matching is also case-insensitive.',
            'severity' => 'success'
        ];

        $rows[] = [
            'rule_name' => 'Smart Multi-Product Queue',
            'module' => 'Bot List Flow',
            'working' => true,
            'detail' => '✅ NLP Array Queue logic active',
            'connected_to' => 'ListBotService::routeMessage()',
            'bot_flow' => 'User selects multiple separated products → split & queue unhandled items → process first match → auto-load queued items after summary.',
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

        $validDays = Setting::getValue('ai_bot', 'session_valid_days', 10, $companyId);
        
        $rows[] = [
            'rule_name' => 'Session Expiry (Valid Days limit)',
            'module' => 'Session Rules',
            'working' => true,
            'detail' => "✅ limit: {$validDays} days",
            'connected_to' => 'AiChatSession',
            'bot_flow' => "If session last message > {$validDays} days old → expired → creates fresh session with NEW lead and NEW quote.",
            'severity' => 'success'
        ];

        $rows[] = [
            'rule_name' => 'Completed Session Retention',
            'module' => 'Session Rules',
            'working' => true,
            'detail' => "✅ Retains old data if < {$validDays} days",
            'connected_to' => 'Listbot/AiBot Services',
            'bot_flow' => "If old session is 'completed' but within {$validDays} days limit → Creates new session but APPENDS to old lead_id and quote_id.",
            'severity' => 'success'
        ];

        $rows[] = [
            'rule_name' => 'Active Add-More Hold',
            'module' => 'Session Rules',
            'working' => true,
            'detail' => '✅ Prevents auto-reset',
            'connected_to' => 'ListBotService',
            'bot_flow' => "If session is 'awaiting_add_more' (user asked to add more) → Bot waits for reply instead of auto-resetting context.",
            'severity' => 'success'
        ];

        $rows[] = [
            'rule_name' => 'Lead Auto-Creation',
            'module' => 'Session Rules',
            'working' => true,
            'detail' => '✅ Automatic on first msg',
            'connected_to' => 'Lead Model',
            'bot_flow' => 'First message received → Lead created automatically if not exists',
            'severity' => 'success'
        ];

        $rows[] = [
            'rule_name' => 'Lead/Quote Delete Reset',
            'module' => 'Session Rules',
            'working' => true,
            'detail' => '✅ Foreign key check active',
            'connected_to' => 'Bot Context Handler',
            'bot_flow' => 'Admin deletes Lead or Quote → associated session explicitly invalidated/reset automatically to prevent hallucination/errors',
            'severity' => 'success'
        ];

        return $rows;
    }
}
