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

class AiBotDiagnosticService
{
    private int $companyId;
    private int $userId;
    private VertexAIService $vertexAI;
    private AIChatbotService $chatbotService;
    private string $simPhone = '919999999992'; // Unique phone for diagnostic
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

        $log('info', "🔬 Running {$questions->count()} questions through process flow...");
        $log('info', '');

        foreach ($questions as $i => $question) {
            $turnNum = $i + 1;
            $userMsg = trim($question->question);

            $log('info', "── Turn {$turnNum}/{$questions->count()} ──");
            $log('user', $userMsg);

            // Step A: Classify user input
            $classification = $this->classifyUserInput($userMsg);
            $log('info', "   🏷️ Classified as: {$classification['type']}");

            // Step B: Send through bot
            try {
                $beforeSession = $this->getSessionSnapshot();

                $botResult = $this->chatbotService->processMessage(
                    'diagnostic_tester_1',
                    $this->simPhone,
                    $userMsg
                );
                $botMsg = $botResult['response'] ?? '';
                $log('bot', $botMsg);

                $afterSession = $this->getSessionSnapshot();

                // Step C: Run diagnostic checks for this turn
                $this->diagnoseTurn($turnNum, $userMsg, $botMsg, $classification, $beforeSession, $afterSession, $log);

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
     * Classify what type of message the user sent
     */
    private function classifyUserInput(string $message): array
    {
        $prompt = <<<PROMPT
Analyze this user message: "{$message}"

Classify it into ONE of these categories:
1. GREETING — hi, hello, namaste, good morning, how are you
2. BUSINESS_QUERY — asking about business info, company details, location, contact, services
3. PRODUCT_INQUIRY — asking about products, catalogue, what do you sell, prices, show me products
4. PRODUCT_CONFIRMATION — confirming a product selection, like "yes", "1", "pehla wala", "haan ye chahiye", agreeing to buy
5. PRODUCT_DETAIL_CONFIRM — confirming a product detail/combo option like a size, color, finish, material
6. PRODUCT_MODIFY — wanting to add another product, remove product, change product, edit quantity
7. SKIP_ANSWER — skipping a question, saying "no", "skip", "nahi chahiye"
8. GENERAL_ANSWER — providing an answer to a question (name, city, email, number etc.)
9. OTHER — anything else

Reply with ONLY the category name (e.g., "GREETING"). Nothing else.
PROMPT;

        try {
            $result = $this->vertexAI->classifyContent($prompt);
            $type = strtoupper(trim($result['text'] ?? 'OTHER'));
            // Sanitize
            $validTypes = ['GREETING', 'BUSINESS_QUERY', 'PRODUCT_INQUIRY', 'PRODUCT_CONFIRMATION', 'PRODUCT_DETAIL_CONFIRM', 'PRODUCT_MODIFY', 'SKIP_ANSWER', 'GENERAL_ANSWER', 'OTHER'];
            if (!in_array($type, $validTypes)) {
                $type = 'OTHER';
            }
            return ['type' => $type];
        } catch (\Exception $e) {
            return ['type' => 'OTHER'];
        }
    }

    /**
     * Run all diagnostic checks for a single turn
     */
    private function diagnoseTurn(int $turn, string $userMsg, string $botMsg, array $classification, ?array $before, ?array $after, callable $log): void
    {
        $type = $classification['type'];

        // ── TEST 1: Greeting Detection ──
        if ($type === 'GREETING') {
            $isProductDump = preg_match('/\d+️⃣\s+\*/m', $botMsg)
                || str_contains($botMsg, 'Our Products:')
                || str_contains($botMsg, 'Our Categories:')
                || preg_match('/^\d+\.\s+\*\*.+\*\*/m', $botMsg);

            if ($isProductDump) {
                $log('error', '   ❌ GREETING TEST: Bot dumped product list instead of greeting!');
                $this->addResult("turn_{$turn}_greeting", false, 'Bot sent catalogue on greeting');
            } else {
                $log('success', '   ✅ GREETING TEST: Bot greeted properly');
                $this->addResult("turn_{$turn}_greeting", true, 'Proper greeting');
            }
        }

        // ── TEST 2: Business Query ──
        if ($type === 'BUSINESS_QUERY') {
            $botLower = strtolower($botMsg);
            $errorPhrases = ['sorry, i could not generate', 'i don\'t have information', 'i cannot help'];
            $isError = false;
            foreach ($errorPhrases as $p) {
                if (str_contains($botLower, $p)) { $isError = true; break; }
            }
            if ($isError) {
                $log('error', '   ❌ BUSINESS QUERY: Bot failed to answer business question');
                $this->addResult("turn_{$turn}_business", false, 'Bot cannot answer business queries');
            } else {
                $log('success', '   ✅ BUSINESS QUERY: Bot responded with business info');
                $this->addResult("turn_{$turn}_business", true, 'Business info provided');
            }
        }

        // ── TEST 3: Product Inquiry — spell fix + find in catalogue ──
        if ($type === 'PRODUCT_INQUIRY') {
            $hasProductList = str_contains($botMsg, '1️⃣')
                || str_contains($botMsg, 'Products:')
                || str_contains($botMsg, 'Categories:')
                || preg_match('/\d+[\.\)]\s+/m', $botMsg);

            // Check no fabricated products
            $realProducts = Product::where('company_id', $this->companyId)
                ->where('status', 'active')
                ->pluck('name')
                ->map(fn($n) => strtolower(trim($n)))
                ->toArray();

            $hasFake = false;
            if ($hasProductList && !empty($realProducts)) {
                preg_match_all('/\*([^*]+)\*/', $botMsg, $starMatches);
                $mentioned = $starMatches[1] ?? [];
                foreach ($mentioned as $m) {
                    $mLower = strtolower(trim($m));
                    if (in_array($mLower, ['our products:', 'our categories:', 'order summary:'])) continue;
                    $matchFound = false;
                    foreach ($realProducts as $real) {
                        if (str_contains($real, $mLower) || str_contains($mLower, $real) || $real === $mLower) {
                            $matchFound = true;
                            break;
                        }
                    }
                    if (!$matchFound) $hasFake = true;
                }
            }

            if ($hasFake) {
                $log('error', '   ❌ PRODUCT INQUIRY: Bot fabricated non-existent products!');
                $this->addResult("turn_{$turn}_product_inquiry", false, 'Fabricated products');
            } elseif ($hasProductList || str_contains(strtolower($botMsg), 'product')) {
                $log('success', '   ✅ PRODUCT INQUIRY: Bot showed real products from catalogue');
                $this->addResult("turn_{$turn}_product_inquiry", true, 'Real products listed');
            } else {
                $log('error', '   ❌ PRODUCT INQUIRY: Bot did not show product list');
                $this->addResult("turn_{$turn}_product_inquiry", false, 'No product list shown');
            }
        }

        // ── TEST 4: Product Confirmation — save to lead/quote ──
        if ($type === 'PRODUCT_CONFIRMATION') {
            $productSaved = false;
            if ($after && isset($after['answers']['product_id'])) {
                $productSaved = true;
                // Verify lead created
                if (!empty($after['lead_id'])) {
                    $log('success', '   ✅ PRODUCT CONFIRM: Product selected → Lead created (#{' . $after['lead_id'] . '})');
                } else {
                    $log('error', '   ❌ PRODUCT CONFIRM: Product selected but Lead NOT created');
                }
                // Verify quote created
                if (!empty($after['quote_id'])) {
                    $log('success', '   ✅ PRODUCT CONFIRM: Quote created (#{' . $after['quote_id'] . '})');
                    $this->addResult("turn_{$turn}_product_confirm", true, 'Lead + Quote created');
                } else {
                    $log('error', '   ❌ PRODUCT CONFIRM: Quote NOT created');
                    $this->addResult("turn_{$turn}_product_confirm", false, 'Quote not created');
                }
            } else {
                $log('info', '   ℹ️ PRODUCT CONFIRM: Product not yet matched (may need product list first)');
                $this->addResult("turn_{$turn}_product_confirm", false, 'Product not matched');
            }
        }

        // ── TEST 5: Product Detail Confirmation (combo step) — spell correct + save + verify chatflow progress ──
        if ($type === 'PRODUCT_DETAIL_CONFIRM') {
            $answersBefore = $before['answers'] ?? [];
            $answersAfter = $after['answers'] ?? [];

            // Check if new answer was added
            $newAnswers = array_diff_key($answersAfter, $answersBefore);
            if (!empty($newAnswers)) {
                foreach ($newAnswers as $key => $val) {
                    $log('success', "   ✅ DETAIL CONFIRM: Saved {$key} = {$val}");
                }

                // Verify chatflow progress
                $this->verifyChatflowProgress($after, $log, $turn);
                $this->addResult("turn_{$turn}_detail_confirm", true, 'Detail saved to session');
            } else {
                // Check if same answer was updated
                $updatedAnswers = [];
                foreach ($answersAfter as $k => $v) {
                    if (isset($answersBefore[$k]) && $answersBefore[$k] !== $v) {
                        $updatedAnswers[$k] = $v;
                    }
                }
                if (!empty($updatedAnswers)) {
                    foreach ($updatedAnswers as $key => $val) {
                        $log('success', "   ✅ DETAIL CONFIRM: Updated {$key} = {$val}");
                    }
                    $this->addResult("turn_{$turn}_detail_confirm", true, 'Detail updated');
                } else {
                    $log('info', '   ℹ️ DETAIL CONFIRM: No new answers recorded (might be retry or mismatch)');
                    $this->addResult("turn_{$turn}_detail_confirm", false, 'No answer recorded');
                }
            }

            // Verify quote was updated
            if (!empty($after['quote_id'])) {
                $quote = Quote::with('items.variation')->find($after['quote_id']);
                if ($quote) {
                    foreach ($quote->items as $item) {
                        if ($item->variation_id) {
                            $log('success', "   ✅ QUOTE UPDATE: Variation matched, Price: ₹" . number_format($item->rate / 100, 2));
                        }
                        if (!empty($item->selected_combination)) {
                            $combos = is_array($item->selected_combination) ? $item->selected_combination : json_decode($item->selected_combination, true);
                            if ($combos) {
                                $comboStr = implode(', ', array_map(fn($k, $v) => "{$k}={$v}", array_keys($combos), $combos));
                                $log('info', "   📋 Selected combos: {$comboStr}");
                            }
                        }
                    }
                }
            }
        }

        // ── TEST 6: Product Modify (add/edit/delete) ──
        if ($type === 'PRODUCT_MODIFY') {
            // Check if the bot detected the modification intent
            $modifyKeywords = ['add', 'remove', 'delete', 'change', 'edit', 'hatao', 'nikalo', 'badlo', 'dusra', 'aur ek'];
            $botDetected = false;
            $botLower = strtolower($botMsg);
            foreach ($modifyKeywords as $kw) {
                if (str_contains($botLower, $kw) || str_contains(strtolower($userMsg), $kw)) {
                    $botDetected = true;
                    break;
                }
            }

            // Check if quote was actually modified
            $quoteBefore = $before['quote_items_count'] ?? 0;
            $quoteAfter = $after['quote_items_count'] ?? 0;

            if ($quoteBefore !== $quoteAfter) {
                $log('success', "   ✅ PRODUCT MODIFY: Quote items changed ({$quoteBefore} → {$quoteAfter})");
                $this->addResult("turn_{$turn}_product_modify", true, 'Quote modified');
            } else {
                $log('error', '   ❌ PRODUCT MODIFY: Bot did not modify quote items');
                $log('info', '   ℹ️ Product add/edit/delete handling may need implementation');
                $this->addResult("turn_{$turn}_product_modify", false, 'Quote not modified');
            }
        }

        // ── TEST 7: Language Detection ──
        $this->checkLanguageMatch($userMsg, $botMsg, $turn, $log);

        // ── TEST 8: Bot Error Check ──
        $errorPhrases = ['sorry, i could not generate', 'sorry, an error occurred', 'sorry, i am unable to process', 'ai bot is not configured'];
        $botLower = strtolower($botMsg);
        foreach ($errorPhrases as $errPhrase) {
            if (str_contains($botLower, $errPhrase)) {
                $log('error', "   ❌ BOT ERROR: Returned error message at turn {$turn}");
                $this->addResult("turn_{$turn}_bot_error", false, 'Bot returned error');
                break;
            }
        }

        // ── TEST 9: Optional Question / Skip Check ──
        if ($type === 'SKIP_ANSWER') {
            $currentStepId = $after['current_step_id'] ?? null;
            $beforeStepId = $before['current_step_id'] ?? null;

            if ($currentStepId !== $beforeStepId) {
                $log('success', '   ✅ SKIP: Bot advanced to next step after skip');
                $this->addResult("turn_{$turn}_skip", true, 'Skipped and advanced');
            } else {
                // Check if it's an optional step
                $step = $currentStepId ? ChatflowStep::find($currentStepId) : null;
                if ($step && $step->isOptionalStep()) {
                    $retries = $after['current_step_retries'] ?? 0;
                    $maxRetries = $step->max_retries ?? 2;
                    if ($retries >= $maxRetries) {
                        $log('error', "   ❌ SKIP: Optional step reached max retries ({$maxRetries}) but didn't advance");
                    } else {
                        $log('info', "   ℹ️ SKIP: Optional step retry {$retries}/{$maxRetries}");
                    }
                    $this->addResult("turn_{$turn}_skip", $retries < $maxRetries, "Retry {$retries}/{$maxRetries}");
                } else {
                    $log('info', '   ℹ️ SKIP: Step did not advance (may be required step)');
                    $this->addResult("turn_{$turn}_skip", false, 'Required step cannot be skipped');
                }
            }
        }
    }

    /**
     * Verify chatflow progress — which steps are done, which pending
     */
    private function verifyChatflowProgress(array $sessionData, callable $log, int $turn): void
    {
        $steps = ChatflowStep::where('company_id', $this->companyId)->orderBy('sort_order')->get();
        if ($steps->isEmpty()) return;

        $currentStepId = $sessionData['current_step_id'] ?? null;
        $answers = $sessionData['answers'] ?? [];

        $completed = 0;
        $pending = 0;
        $currentFound = false;

        foreach ($steps as $step) {
            if ($step->id == $currentStepId) {
                $currentFound = true;
            }
            if ($currentFound) {
                $pending++;
            } else {
                $completed++;
            }
        }

        $total = $steps->count();
        $log('info', "   📊 Chatflow Progress: {$completed}/{$total} completed, {$pending} pending");

        // Check if next step in chatflow matches current step
        if ($currentStepId) {
            $currentStep = $steps->firstWhere('id', $currentStepId);
            if ($currentStep) {
                $log('info', "   ➡️ Next question: [{$currentStep->step_type}] {$currentStep->name}");
            }
        }

        // Verify quote has all confirmed fields
        if (!empty($sessionData['quote_id'])) {
            $quote = Quote::with('items')->find($sessionData['quote_id']);
            if ($quote && $quote->items->count() > 0) {
                $item = $quote->items->first();
                $combos = is_array($item->selected_combination) ? $item->selected_combination : json_decode($item->selected_combination ?? '{}', true);
                $confirmedCount = $combos ? count($combos) : 0;

                // Count combo steps
                $comboSteps = $steps->where('step_type', 'ask_combo')->count();
                $log('info', "   📦 Quote combos confirmed: {$confirmedCount}/{$comboSteps}");

                if ($confirmedCount === $comboSteps && $comboSteps > 0) {
                    $log('success', '   ✅ All combo fields confirmed in quote!');
                    $this->addResult("turn_{$turn}_chatflow", true, 'All combos confirmed');
                }
            }
        }
    }

    /**
     * Check if bot reply language matches user input language
     */
    private function checkLanguageMatch(string $userMsg, string $botMsg, int $turn, callable $log): void
    {
        $langSetting = Setting::getValue('ai_bot', 'reply_language', 'auto', $this->companyId);

        // Only check for auto mode (match user language)
        if ($langSetting !== 'auto') return;

        try {
            $prompt = <<<PROMPT
Analyze these two messages:
User: "{$userMsg}"
Bot: "{$botMsg}"

Are they in the same language? Consider:
- Hindi text (Devanagari or Romanized Hindi/Hinglish both count as Hindi)
- English text
- Mixed is acceptable

Reply with ONLY "MATCH" or "MISMATCH".
PROMPT;

            $result = $this->vertexAI->classifyContent($prompt);
            $match = strtoupper(trim($result['text'] ?? ''));

            if (str_contains($match, 'MATCH') && !str_contains($match, 'MISMATCH')) {
                $log('success', '   ✅ LANGUAGE: Bot replied in same language as user');
                $this->addResult("turn_{$turn}_language", true, 'Language matched');
            } else {
                $log('error', '   ❌ LANGUAGE: Bot replied in different language than user!');
                $this->addResult("turn_{$turn}_language", false, 'Language mismatch');
            }
        } catch (\Exception $e) {
            $log('info', '   ℹ️ LANGUAGE: Could not verify (' . $e->getMessage() . ')');
        }
    }

    /**
     * Get current session state snapshot for comparison
     */
    private function getSessionSnapshot(): ?array
    {
        $session = AiChatSession::where('phone_number', $this->simPhone)->first();
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

        // Generate AI summary in Hinglish
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
        $session = AiChatSession::where('phone_number', $this->simPhone)->first();
        if ($session) {
            AiChatMessage::where('session_id', $session->id)->delete();
            if ($session->lead_id) { Lead::where('id', $session->lead_id)->delete(); }
            if ($session->quote_id) {
                QuoteItem::where('quote_id', $session->quote_id)->delete();
                Quote::where('id', $session->quote_id)->delete();
            }
            $session->delete();
        }
        AiTokenLog::where('phone_number', $this->simPhone)->delete();
    }
}
