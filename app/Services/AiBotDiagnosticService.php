<?php

namespace App\Services;

use App\Models\AiChatSession;
use App\Models\AiChatMessage;
use App\Models\AiTokenLog;
use App\Models\AiBotTestQuestion;
use App\Models\ChatflowStep;
use App\Models\CatalogueCustomColumn;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Lead;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

// Unique column cache for display name resolution
use App\Models\CatalogueCustomValue;

class AiBotDiagnosticService
{
    private int $companyId;
    private int $userId;
    private VertexAIService $vertexAI;
    private AIChatbotService $chatbotService;
    private string $simPhone = '919999999992';
    private array $diagnosticResults = [];

    public function __construct(int $companyId, int $userId)
    {
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->vertexAI = new VertexAIService($companyId);
        $this->chatbotService = new AIChatbotService($companyId, $userId);
    }

    /**
     * Run full diagnostic test with user-defined questions.
     */
    public function run(callable $log): void
    {
        try {
            // ═══════════════════════════════════════
            // PHASE 1: PRE-FLIGHT CONFIG CHECKS
            // ═══════════════════════════════════════
            $log('info', '═══════════════════════════════════════');
            $log('info', '📋 PHASE 1: Pre-Flight Config Checks');
            $log('info', '═══════════════════════════════════════');
            $this->runConfigChecks($log);
            sleep(1);

            // ═══════════════════════════════════════
            // PHASE 2: MODULE HEALTH CHECKS
            // ═══════════════════════════════════════
            $log('info', '');
            $log('info', '═══════════════════════════════════════');
            $log('info', '🔌 PHASE 2: Module Health Checks');
            $log('info', '═══════════════════════════════════════');
            $this->runModuleChecks($log);
            sleep(1);

            // ═══════════════════════════════════════
            // PHASE 3: PROCESS FLOW DIAGNOSTIC
            // ═══════════════════════════════════════
            $log('info', '');
            $log('info', '═══════════════════════════════════════');
            $log('info', '🔬 PHASE 3: Process Flow Diagnostic');
            $log('info', '═══════════════════════════════════════');
            $this->cleanup();
            $this->runProcessFlowDiagnostic($log);
            sleep(1);

            // ═══════════════════════════════════════
            // PHASE 4: DIAGNOSTIC SUMMARY
            // ═══════════════════════════════════════
            $log('info', '');
            $log('info', '═══════════════════════════════════════');
            $log('info', '📊 PHASE 4: Diagnostic Summary');
            $log('info', '═══════════════════════════════════════');
            $this->runSummary($log);

            // Cleanup
            $log('info', '');
            $log('info', 'Cleaning up diagnostic data...');
            $this->cleanup();
            $log('success', '🎉 Diagnostic Complete!');

        } catch (\Exception $e) {
            $log('error', "Fatal Diagnostic Error: " . $e->getMessage() . " on line " . $e->getLine());
        }
    }

    // ═══════════════════════════════════════════════════════
    // PHASE 1: CONFIG CHECKS
    // ═══════════════════════════════════════════════════════

    private function runConfigChecks(callable $log): void
    {
        // 1.1 AI Bot Toggle
        $botEnabled = Setting::getValue('ai_bot', 'enabled', false, $this->companyId);
        if ($botEnabled) {
            $log('success', '✅ AI Bot Status: ON');
            $this->addResult('ai_bot_status', true, 'AI Bot is enabled');
        } else {
            $log('error', '❌ AI Bot Status: OFF — Bot will not respond!');
            $this->addResult('ai_bot_status', false, 'AI Bot is disabled');
        }

        // 1.2 Vertex AI Config
        $config = Setting::getValue('ai_bot', 'vertex_config', null, $this->companyId);
        if (!$config || empty($config['project_id'])) {
            $log('error', '❌ Vertex AI: NOT configured');
            $this->addResult('vertex_config', false, 'Vertex AI not configured');
            return;
        }
        $log('success', "✅ Vertex AI: Project={$config['project_id']}, Model={$config['model']}");
        $this->addResult('vertex_config', true, 'Configured');

        // 1.3 Service Account
        if (empty($config['service_account']) || empty($config['service_account']['client_email'])) {
            $log('error', '❌ Service Account: MISSING');
            $this->addResult('service_account', false, 'Missing');
        } else {
            $log('success', "✅ Service Account: {$config['service_account']['client_email']}");
            $this->addResult('service_account', true, 'Present');
        }

        // 1.4 Credential Test
        try {
            if ($this->vertexAI->isConfigured()) {
                $testResult = $this->vertexAI->classifyContent('Reply with OK');
                if (!empty($testResult['text']) && $testResult['text'] !== 'NONE') {
                    $log('success', '✅ Vertex AI Credentials: WORKING');
                    $this->addResult('vertex_credentials', true, 'Credentials verified');
                } else {
                    $log('error', '❌ Vertex AI Credentials: FAILED');
                    $this->addResult('vertex_credentials', false, 'Invalid response');
                }
            }
        } catch (\Exception $e) {
            $log('error', '❌ Vertex AI Credentials: ' . $e->getMessage());
            $this->addResult('vertex_credentials', false, $e->getMessage());
        }

        // 1.5 System Prompt
        $systemPrompt = Setting::getValue('ai_bot', 'system_prompt', '', $this->companyId);
        if (empty(trim($systemPrompt))) {
            $log('error', '❌ System Prompt: NOT SET');
            $this->addResult('system_prompt', false, 'Empty');
        } else {
            $wordCount = str_word_count($systemPrompt);
            $log('success', "✅ System Prompt: Set ({$wordCount} words)");
            $this->addResult('system_prompt', true, "{$wordCount} words");
        }

        // 1.6 Language Setting
        $lang = Setting::getValue('ai_bot', 'reply_language', 'auto', $this->companyId);
        $langLabel = match ($lang) {
            'en' => 'English Only',
            'hi' => 'Hindi Only',
            default => 'Auto-detect (same as user)',
        };
        $log('success', "✅ Reply Language: {$langLabel}");
        $this->addResult('reply_language', true, $langLabel);

        // 1.7 WhatsApp API
        $waConfig = Setting::getValue('whatsapp', 'api_config', ['api_url' => '', 'api_key' => '', 'webhook_base_url' => ''], $this->companyId);
        $waIssues = [];
        if (empty($waConfig['api_url'])) $waIssues[] = 'API URL';
        if (empty($waConfig['api_key'])) $waIssues[] = 'API Key';
        if (empty($waIssues)) {
            $log('success', "✅ WhatsApp API: Configured");
            $this->addResult('whatsapp_config', true, 'Configured');
        } else {
            $log('error', '❌ WhatsApp API: Missing — ' . implode(', ', $waIssues));
            $this->addResult('whatsapp_config', false, 'Missing: ' . implode(', ', $waIssues));
        }
    }

    // ═══════════════════════════════════════════════════════
    // PHASE 2: MODULE HEALTH
    // ═══════════════════════════════════════════════════════

    private function runModuleChecks(callable $log): void
    {
        // 2.1 Catalogue Columns
        $columns = CatalogueCustomColumn::where('company_id', $this->companyId)->where('is_active', true)->get();
        $uniqueCol = $columns->firstWhere('is_unique', true);
        if ($columns->count() > 0) {
            $log('success', "✅ Catalogue Columns: {$columns->count()} active");
            if ($uniqueCol) {
                $log('info', "   ↳ Unique Column: {$uniqueCol->name}");
            } else {
                $log('error', "   ↳ ❌ No Unique column — bot won't have product identifier!");
            }
            $this->addResult('catalogue_columns', true, "{$columns->count()} columns");
        } else {
            $log('error', '❌ Catalogue Columns: NONE');
            $this->addResult('catalogue_columns', false, 'No columns');
        }

        // 2.2 Products
        $totalProducts = Product::where('company_id', $this->companyId)->count();
        $activeProducts = Product::where('company_id', $this->companyId)->where('status', 'active')->count();
        if ($totalProducts > 0) {
            $log('success', "✅ Products: {$totalProducts} total, {$activeProducts} active");
            $this->addResult('products', true, "{$activeProducts} active products");
        } else {
            $log('error', '❌ Products: NONE');
            $this->addResult('products', false, 'No products');
        }

        // 2.3 Chatflow Steps
        $steps = ChatflowStep::where('company_id', $this->companyId)->orderBy('sort_order')->get();
        if ($steps->count() > 0) {
            $optionalSteps = $steps->where('is_optional', true)->count();
            $log('success', "✅ Chatflow: {$steps->count()} steps ({$optionalSteps} optional)");
            foreach ($steps as $s) {
                $opt = $s->is_optional ? ' (optional)' : '';
                $log('info', "   ↳ [{$s->step_type}] {$s->name}{$opt} | Max Retries: {$s->max_retries}");
            }
            $this->addResult('chatflow', true, "{$steps->count()} steps");
        } else {
            $log('error', '❌ Chatflow: NONE — Bot will use free AI conversation only');
            $this->addResult('chatflow', false, 'No chatflow steps');
        }

        // 2.4 Test Questions
        $questionCount = AiBotTestQuestion::where('company_id', $this->companyId)->count();
        if ($questionCount > 0) {
            $log('success', "✅ Test Questions: {$questionCount} questions saved");
            $this->addResult('test_questions', true, "{$questionCount} questions");
        } else {
            $log('error', '❌ Test Questions: NONE — Add questions in Conversation Test section first!');
            $this->addResult('test_questions', false, 'No test questions');
        }
    }

    // ═══════════════════════════════════════════════════════
    // PHASE 3: PROCESS FLOW DIAGNOSTIC
    // Uses the EXACT same processMessage() as WhatsApp,
    // then validates results using route trace from AIChatbotService
    // ═══════════════════════════════════════════════════════

    private function runProcessFlowDiagnostic(callable $log): void
    {
        $questions = AiBotTestQuestion::where('company_id', $this->companyId)
            ->orderBy('sort_order')
            ->get();

        if ($questions->isEmpty()) {
            $log('error', '❌ No test questions — Cannot run flow diagnostic.');
            $log('info', '   Add questions in the "AI Bot Conversation Test" section above.');
            $this->addResult('process_flow', false, 'No test questions to diagnose');
            return;
        }

        $log('info', "🔬 Running {$questions->count()} questions through EXACT WhatsApp flow...");
        $log('info', '');

        foreach ($questions as $i => $question) {
            $turnNum = $i + 1;
            $userMsg = trim($question->question);

            $log('info', "── Turn {$turnNum}/{$questions->count()} ──");
            $log('user', $userMsg);

            try {
                $beforeSession = $this->getSessionSnapshot();

                // Use EXACT same processMessage() as WhatsApp webhook
                $botResult = $this->chatbotService->processMessage(
                    'diagnostic_tester_1',
                    $this->simPhone,
                    $userMsg
                );
                $botMsg = $botResult['response'] ?? '';

                // Get route trace — shows EXACTLY which code path was taken
                $routeTrace = $this->chatbotService->getRouteTrace();
                $routeStr = !empty($routeTrace) ? implode(' → ', $routeTrace) : 'unknown';
                $log('info', "   🛤️ Route: {$routeStr}");

                $log('bot', $botMsg);

                $afterSession = $this->getSessionSnapshot();

                // Run diagnostic checks based on ACTUAL route trace (not separate AI)
                $this->diagnoseTurn($turnNum, $userMsg, $botMsg, $routeTrace, $beforeSession, $afterSession, $log);

                // ── CONVERSATION OUTPUT (like Conversation Test) ──
                $this->logSessionState($log);

            } catch (\Exception $e) {
                $log('error', "❌ Bot CRASHED: " . $e->getMessage());
                $this->addResult("turn_{$turnNum}_crash", false, $e->getMessage());
            }

            $log('info', '');
            if ($i < $questions->count() - 1) {
                sleep(2);
            }
        }
    }

    /**
     * Run diagnostic checks for a single turn based on ACTUAL route trace.
     * No separate AI classification — uses what really happened.
     */
    private function diagnoseTurn(int $turn, string $userMsg, string $botMsg, array $routeTrace, ?array $before, ?array $after, callable $log): void
    {
        $routeStr = implode(' → ', $routeTrace);

        // ── TEST 1: Greeting Detection ──
        if (in_array('greeting_intercept', $routeTrace)) {
            $isProductDump = preg_match('/\d+️⃣\s+\*/m', $botMsg)
                || str_contains($botMsg, 'Our Products:')
                || str_contains($botMsg, 'Our Categories:');

            if ($isProductDump) {
                $log('error', '   ❌ GREETING: Bot dumped product list instead of greeting!');
                $this->addResult("turn_{$turn}_greeting", false, 'Bot sent catalogue on greeting');
            } else {
                $log('success', '   ✅ GREETING: Bot greeted properly');
                $this->addResult("turn_{$turn}_greeting", true, 'Proper greeting');
            }
        }

        // ── TEST 2: Product Catalogue Sent ──
        if (str_contains($routeStr, 'sendCatalogue') || str_contains($routeStr, 'sendCategoryList')) {
            $hasNumberedList = preg_match('/\d+️⃣\s+\*/m', $botMsg)
                || str_contains($botMsg, 'Our Products:')
                || str_contains($botMsg, 'Our Categories:');

            if ($hasNumberedList) {
                $log('success', '   ✅ CATALOGUE: PHP-built product/category list sent');
                $this->addResult("turn_{$turn}_catalogue", true, 'Catalogue sent via PHP');
            } else {
                $log('error', '   ❌ CATALOGUE: Expected PHP catalogue but got different response');
                $this->addResult("turn_{$turn}_catalogue", false, 'Catalogue format mismatch');
            }

            // Verify no fabricated products
            $this->verifyNoFakeProducts($botMsg, $turn, $log);
        }

        // ── TEST 3: Product Matching ──
        if (in_array('matchProductFromMessage', $routeTrace)) {
            if ($after && isset($after['answers']['product_id'])) {
                $productName = $after['answers']['product_name'] ?? 'Unknown';
                $log('success', "   ✅ PRODUCT MATCH: Selected \"{$productName}\"");

                // Verify Lead + Quote created
                if (!empty($after['lead_id'])) {
                    $log('success', "   ✅ LEAD: Created #{$after['lead_id']}");
                } else {
                    $log('error', '   ❌ LEAD: NOT created after product selection');
                }
                if (!empty($after['quote_id'])) {
                    $log('success', "   ✅ QUOTE: Created #{$after['quote_id']}");
                    $this->addResult("turn_{$turn}_product_match", true, "Lead + Quote created");
                } else {
                    $log('error', '   ❌ QUOTE: NOT created after product selection');
                    $this->addResult("turn_{$turn}_product_match", false, 'Quote not created');
                }
            } else {
                // Product match attempted but failed
                if (str_contains(strtolower($botMsg), "couldn't match")) {
                    $log('error', "   ❌ PRODUCT MATCH: Bot couldn't match user input to any product");
                    $this->addResult("turn_{$turn}_product_match", false, 'No match found');
                } else {
                    $log('info', "   ℹ️ PRODUCT MATCH: Attempted, result pending");
                }
            }
        }

        // ── TEST 4: Category Matching ──
        if (in_array('matchCategoryFromMessage', $routeTrace)) {
            if ($after && isset($after['answers']['category_id'])) {
                $catName = $after['answers']['category_name'] ?? 'Unknown';
                $log('success', "   ✅ CATEGORY MATCH: Selected \"{$catName}\"");
                $this->addResult("turn_{$turn}_category_match", true, "Category matched");
            } else {
                if (str_contains(strtolower($botMsg), "couldn't match")) {
                    $log('error', "   ❌ CATEGORY MATCH: Bot couldn't match user input");
                    $this->addResult("turn_{$turn}_category_match", false, 'No match');
                }
            }
        }

        // ── TEST 5: Combo Step ──
        if (in_array('handleComboStep', $routeTrace)) {
            $answersBefore = $before['answers'] ?? [];
            $answersAfter = $after['answers'] ?? [];
            $newAnswers = array_diff_key($answersAfter, $answersBefore);

            if (!empty($newAnswers)) {
                foreach ($newAnswers as $key => $val) {
                    $log('success', "   ✅ COMBO: Saved {$key} = {$val}");
                }
                $this->addResult("turn_{$turn}_combo", true, 'Combo value saved');

                // Check quote variation update
                if (!empty($after['quote_id'])) {
                    $quote = Quote::with('items.variation')->find($after['quote_id']);
                    if ($quote) {
                        foreach ($quote->items as $item) {
                            if ($item->variation_id) {
                                $log('success', "   ✅ VARIATION: Price updated to ₹" . number_format($item->rate / 100, 2));
                            }
                            if (!empty($item->selected_combination)) {
                                $combos = is_array($item->selected_combination) ? $item->selected_combination : json_decode($item->selected_combination, true);
                                if ($combos) {
                                    $comboStr = implode(', ', array_map(fn($k, $v) => "{$k}={$v}", array_keys($combos), $combos));
                                    $log('info', "   📋 Combos: {$comboStr}");
                                }
                            }
                        }
                    }
                }
            } else {
                $log('error', '   ❌ COMBO: No value was saved (retry or mismatch)');
                $this->addResult("turn_{$turn}_combo", false, 'No value saved');
            }

            // Show chatflow progress
            $this->showChatflowProgress($after, $log);
        }

        // ── TEST 6: Custom/Optional Step ──
        if (in_array('handleCustomStep', $routeTrace)) {
            $answersBefore = $before['answers'] ?? [];
            $answersAfter = $after['answers'] ?? [];
            $newAnswers = array_diff_key($answersAfter, $answersBefore);

            if (!empty($newAnswers)) {
                foreach ($newAnswers as $key => $val) {
                    $log('success', "   ✅ CUSTOM: Saved {$key} = {$val}");
                }
                $this->addResult("turn_{$turn}_custom", true, 'Answer saved');
            } else {
                $log('info', '   ℹ️ CUSTOM: No new answer recorded (may be skip/retry)');
            }
        }

        // ── TEST 7: Summary Step ──
        if (in_array('handleSummaryStep', $routeTrace)) {
            if (str_contains($botMsg, 'Order Summary') || str_contains($botMsg, '📋')) {
                $log('success', '   ✅ SUMMARY: Order summary generated');
                $this->addResult("turn_{$turn}_summary", true, 'Summary sent');
            } else {
                $log('error', '   ❌ SUMMARY: Expected order summary but got different response');
                $this->addResult("turn_{$turn}_summary", false, 'No summary');
            }
        }

        // ── TEST 8: Product Modify (add/edit/delete) ──
        if (in_array('detectProductModifyIntent', $routeTrace)) {
            $quoteBefore = $before['quote_items_count'] ?? 0;
            $quoteAfter = $after['quote_items_count'] ?? 0;

            if ($quoteBefore !== $quoteAfter) {
                $log('success', "   ✅ MODIFY: Quote items changed ({$quoteBefore} → {$quoteAfter})");
                $this->addResult("turn_{$turn}_modify", true, 'Quote modified');
            } else {
                $log('info', "   ℹ️ MODIFY: Intent detected, quote items unchanged");
            }
        }

        // ── TEST 9: Tier 2 Fallback ──
        if (in_array('handleTier2', $routeTrace) || in_array('handleTier2_fallback', $routeTrace)) {
            // Verify bot didn't crash
            $errorPhrases = ['sorry, i could not generate', 'sorry, an error occurred'];
            $botLower = strtolower($botMsg);
            $isError = false;
            foreach ($errorPhrases as $p) {
                if (str_contains($botLower, $p)) { $isError = true; break; }
            }
            if ($isError) {
                $log('error', "   ❌ TIER 2: AI returned error response");
                $this->addResult("turn_{$turn}_tier2", false, 'AI error response');
            } else {
                $log('success', '   ✅ TIER 2: AI responded successfully');
                $this->addResult("turn_{$turn}_tier2", true, 'AI responded');
            }
        }

        // ── TEST 10: Language Check ──
        $this->checkLanguageMatch($userMsg, $botMsg, $turn, $log);
    }

    /**
     * Verify bot didn't fabricate non-existent products.
     * Uses display names (from unique column) — same as AIChatbotService.
     */
    private function verifyNoFakeProducts(string $botMsg, int $turn, callable $log): void
    {
        $products = Product::with('customValues')->where('company_id', $this->companyId)
            ->where('status', 'active')
            ->get();

        if ($products->isEmpty()) return;

        // Build list of ALL valid names: raw name + display name (unique column)
        $validNames = [];
        foreach ($products as $product) {
            $validNames[] = strtolower(trim($product->name));
            $displayName = $this->getProductDisplayNameForDiag($product);
            if ($displayName !== $product->name) {
                $validNames[] = strtolower(trim($displayName));
            }
        }
        $validNames = array_unique($validNames);

        preg_match_all('/\*([^*]+)\*/', $botMsg, $starMatches);
        $mentioned = $starMatches[1] ?? [];
        $skipLabels = ['our products:', 'our categories:', 'order summary:'];

        foreach ($mentioned as $m) {
            $mLower = strtolower(trim($m));
            if (in_array($mLower, $skipLabels)) continue;

            $matchFound = false;
            foreach ($validNames as $real) {
                if (str_contains($real, $mLower) || str_contains($mLower, $real) || $real === $mLower) {
                    $matchFound = true;
                    break;
                }
            }

            if (!$matchFound) {
                $log('error', "   ❌ FAKE PRODUCT: \"{$m}\" is not in catalogue!");
                $this->addResult("turn_{$turn}_fake_product", false, "Fabricated: {$m}");
                return;
            }
        }
    }

    /**
     * Get product display name using unique column (mirrors AIChatbotService logic)
     */
    private function getProductDisplayNameForDiag(Product $product): string
    {
        static $uniqueColumn = null;
        static $loaded = false;

        if (!$loaded) {
            $uniqueColumn = CatalogueCustomColumn::where('company_id', $this->companyId)
                ->where('is_unique', true)
                ->first();
            $loaded = true;
        }

        if ($uniqueColumn) {
            if ($uniqueColumn->is_system) {
                return $product->{$uniqueColumn->slug} ?: $product->name;
            } else {
                $customVal = $product->customValues->where('column_id', $uniqueColumn->id)->first();
                if ($customVal && !empty($customVal->value)) {
                    $val = json_decode($customVal->value, true);
                    return is_array($val) ? implode(', ', $val) : $customVal->value;
                }
            }
        }

        return $product->name;
    }

    /**
     * Show chatflow progress
     */
    private function showChatflowProgress(array $sessionData, callable $log): void
    {
        $steps = ChatflowStep::where('company_id', $this->companyId)->orderBy('sort_order')->get();
        if ($steps->isEmpty()) return;

        $currentStepId = $sessionData['current_step_id'] ?? null;
        $completed = 0;
        $pending = 0;
        $currentFound = false;

        foreach ($steps as $step) {
            if ($step->id == $currentStepId) $currentFound = true;
            if ($currentFound) { $pending++; } else { $completed++; }
        }

        $total = $steps->count();
        $log('info', "   📊 Chatflow: {$completed}/{$total} done, {$pending} pending");

        if ($currentStepId) {
            $currentStep = $steps->firstWhere('id', $currentStepId);
            if ($currentStep) {
                $log('info', "   ➡️ Next: [{$currentStep->step_type}] {$currentStep->name}");
            }
        }
    }

    /**
     * Check if bot reply language matches user input language.
     * Uses PHP-based detection instead of unreliable AI classification.
     * In auto mode: Hindi/Hinglish/English mixed is ALWAYS acceptable.
     */
    private function checkLanguageMatch(string $userMsg, string $botMsg, int $turn, callable $log): void
    {
        $langSetting = Setting::getValue('ai_bot', 'reply_language', 'auto', $this->companyId);
        if ($langSetting !== 'auto') return;

        $userLang = $this->detectLanguageType($userMsg);
        $botLang = $this->detectLanguageType($botMsg);

        // In auto mode: Hinglish/mixed is ALWAYS acceptable
        // Only flag if user writes pure Devanagari Hindi but bot responds in pure English,
        // or user writes pure English but bot responds in pure Devanagari Hindi.
        $isMismatch = false;

        if ($userLang === 'devanagari' && $botLang === 'english') {
            $isMismatch = true; // User wrote Hindi script, bot responded in pure English
        } elseif ($userLang === 'english' && $botLang === 'devanagari') {
            $isMismatch = true; // User wrote pure English, bot responded in Hindi script
        }
        // All other combos (hinglish, mixed, same) are acceptable

        if (!$isMismatch) {
            $log('success', "   ✅ LANGUAGE: OK ({$userLang} → {$botLang})");
            $this->addResult("turn_{$turn}_language", true, "Language OK ({$userLang} → {$botLang})");
        } else {
            $log('error', "   ❌ LANGUAGE: Mismatch ({$userLang} → {$botLang})");
            $this->addResult("turn_{$turn}_language", false, "Language mismatch ({$userLang} → {$botLang})");
        }
    }

    /**
     * Detect language type of a message using character analysis.
     * Returns: 'devanagari', 'english', 'hinglish', or 'mixed'
     */
    private function detectLanguageType(string $text): string
    {
        $clean = preg_replace('/[\s\d\p{P}\p{S}*️⃣🛍📋✅❌🙏💰📊📌📏🏷📂👆🗑✏]+/u', '', $text);
        if (empty($clean)) return 'mixed';

        // Count Devanagari vs Latin characters
        $devanagariCount = preg_match_all('/[\x{0900}-\x{097F}]/u', $clean);
        $latinCount = preg_match_all('/[a-zA-Z]/u', $clean);
        $total = $devanagariCount + $latinCount;

        if ($total === 0) return 'mixed';

        $devPercent = $devanagariCount / $total;

        if ($devPercent > 0.8) return 'devanagari';  // Mostly Hindi script
        if ($devPercent < 0.1) {
            // Check if it looks like Romanized Hindi (Hinglish)
            $hinglishWords = ['hai', 'kya', 'aap', 'mein', 'hoon', 'kaise', 'nahi', 'haan',
                'karo', 'batao', 'btao', 'dikhao', 'chahiye', 'bechte', 'milta',
                'aapka', 'humari', 'madad', 'sakta', 'karein', 'dekh', 'ho',
                'ke', 'ka', 'ki', 'ko', 'se', 'me', 'pe', 'par', 'bhi', 'aur'];
            $words = preg_split('/\s+/', strtolower($text));
            $hinglishCount = 0;
            foreach ($words as $w) {
                $w = preg_replace('/[^a-z]/', '', $w);
                if (in_array($w, $hinglishWords)) $hinglishCount++;
            }
            if (count($words) > 0 && ($hinglishCount / count($words)) > 0.2) {
                return 'hinglish';
            }
            return 'english';
        }

        return 'mixed'; // Mix of Devanagari and Latin
    }

    /**
     * Get current session state snapshot for comparison
     */
    private function getSessionSnapshot(): ?array
    {
        $session = AiChatSession::where('phone_number', $this->simPhone)
            ->where('status', 'active')
            ->first();
        if (!$session) return null;

        $quoteItemsCount = 0;
        if ($session->quote_id) {
            $quoteItemsCount = QuoteItem::where('quote_id', $session->quote_id)->count();
        }

        return [
            'conversation_state' => $session->conversation_state,
            'current_step_id' => $session->current_step_id,
            'current_step_retries' => $session->current_step_retries ?? 0,
            'lead_id' => $session->lead_id,
            'quote_id' => $session->quote_id,
            'answers' => $session->collected_answers ?? [],
            'optional_asked' => $session->optional_asked ?? [],
            'catalogue_sent' => $session->catalogue_sent,
            'quote_items_count' => $quoteItemsCount,
        ];
    }

    /**
     * Log current session state — mirrors AiConversationTestService output.
     * Shows state, lead, quote, and collected answers after each turn.
     */
    private function logSessionState(callable $log): void
    {
        $session = AiChatSession::where('phone_number', $this->simPhone)
            ->where('status', 'active')
            ->first();
        if (!$session) return;

        $stateInfo = "State: {$session->conversation_state}";
        if ($session->lead_id) $stateInfo .= " | Lead: #{$session->lead_id}";
        if ($session->quote_id) $stateInfo .= " | Quote: #{$session->quote_id}";
        $answers = $session->collected_answers ?? [];
        if (!empty($answers)) {
            $stateInfo .= " | Answers: " . count($answers);
        }
        $log('info', "   📊 {$stateInfo}");
    }

    // ═══════════════════════════════════════════════════════
    // PHASE 4: SUMMARY
    // ═══════════════════════════════════════════════════════

    private function runSummary(callable $log): void
    {
        $passed = 0;
        $failed = 0;
        $details = [];

        foreach ($this->diagnosticResults as $key => $result) {
            if ($result['passed']) {
                $passed++;
            } else {
                $failed++;
                $details[] = "❌ {$key}: {$result['detail']}";
            }
        }

        $total = $passed + $failed;
        $log('info', "");
        $log('info', "Total Checks: {$total} | ✅ Passed: {$passed} | ❌ Failed: {$failed}");

        if ($failed > 0) {
            $log('info', '');
            $log('info', 'Failed Items:');
            foreach ($details as $d) {
                $log('error', "   {$d}");
            }
        }

        // Generate AI summary
        if ($total > 0) {
            try {
                $resultText = '';
                foreach ($this->diagnosticResults as $key => $result) {
                    $status = $result['passed'] ? 'PASS' : 'FAIL';
                    $resultText .= "{$status} | {$key}: {$result['detail']}\n";
                }

                $summaryPrompt = "You are a CRM diagnostic expert. Write a SHORT summary in Hinglish (Hindi+English). For fails, explain WHAT is wrong and HOW to fix in 1 line each. Keep under 10 lines. Do NOT refuse.";
                $summaryResult = $this->vertexAI->generateContent($summaryPrompt, [
                    ['role' => 'user', 'text' => $resultText . "\nSummary report generate karo."],
                ]);

                $summary = $summaryResult['text'] ?? '';
                if (!empty($summary)) {
                    $log('info', '');
                    $log('info', '🤖 AI Summary:');
                    foreach (explode("\n", $summary) as $line) {
                        if (!empty(trim($line))) {
                            $log('info', "   {$line}");
                        }
                    }
                }
            } catch (\Exception $e) {
                // Silent fail for summary
            }
        }
    }

    // ═══════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════

    private function addResult(string $key, bool $passed, string $detail): void
    {
        $this->diagnosticResults[$key] = [
            'passed' => $passed,
            'detail' => $detail,
        ];
    }

    private function cleanup(): void
    {
        $sessions = AiChatSession::where('phone_number', $this->simPhone)->get();
        foreach ($sessions as $session) {
            AiChatMessage::where('session_id', $session->id)->delete();
            if ($session->lead_id) {
                $lead = Lead::find($session->lead_id);
                if ($lead) {
                    $lead->products()->detach();
                    $lead->delete();
                }
            }
            if ($session->quote_id) {
                QuoteItem::where('quote_id', $session->quote_id)->delete();
                Quote::where('id', $session->quote_id)->delete();
            }
            $session->delete();
        }
        AiTokenLog::where('phone_number', $this->simPhone)->delete();
    }
}
