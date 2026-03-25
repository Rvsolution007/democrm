<?php

namespace App\Services;

use App\Models\AiChatSession;
use App\Models\AiChatMessage;
use App\Models\AiTokenLog;
use App\Models\ChatflowStep;
use App\Models\CatalogueCustomColumn;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Lead;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSimulationService
{
    private int $companyId;
    private int $userId;
    private VertexAIService $vertexAI;
    private AIChatbotService $chatbotService;
    private string $simPhone = '919999999999';
    private array $diagnosticResults = [];

    public function __construct(int $companyId, int $userId)
    {
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->vertexAI = new VertexAIService($companyId);
        $this->chatbotService = new AIChatbotService($companyId, $userId);
    }

    /**
     * Run the full 5-phase diagnostic and stream output via the callback.
     */
    public function run(string $rules, callable $log)
    {
        try {
            // ═══════════════════════════════════════════════════
            // PHASE 1: PRE-FLIGHT CONFIG CHECKS
            // ═══════════════════════════════════════════════════
            $log('info', '═══════════════════════════════════════');
            $log('info', '📋 PHASE 1: Pre-Flight Config Checks');
            $log('info', '═══════════════════════════════════════');

            $this->runPhase1($log);

            sleep(1);

            // ═══════════════════════════════════════════════════
            // PHASE 2: MODULE HEALTH CHECKS
            // ═══════════════════════════════════════════════════
            $log('info', '');
            $log('info', '═══════════════════════════════════════');
            $log('info', '🔌 PHASE 2: Module Health Checks');
            $log('info', '═══════════════════════════════════════');

            $this->runPhase2($log);

            sleep(1);

            // ═══════════════════════════════════════════════════
            // PHASE 3: INTERACTIVE AI SIMULATION
            // ═══════════════════════════════════════════════════
            $log('info', '');
            $log('info', '═══════════════════════════════════════');
            $log('info', '🤖 PHASE 3: Interactive AI Simulation');
            $log('info', '═══════════════════════════════════════');

            // Clean up old sim data first
            $this->cleanup();

            $this->runPhase3($rules, $log);

            sleep(1);

            // ═══════════════════════════════════════════════════
            // PHASE 4: POST-SIMULATION DATA VERIFICATION
            // ═══════════════════════════════════════════════════
            $log('info', '');
            $log('info', '═══════════════════════════════════════');
            $log('info', '📊 PHASE 4: Post-Simulation Verification');
            $log('info', '═══════════════════════════════════════');

            $this->runPhase4($log);

            sleep(1);

            // ═══════════════════════════════════════════════════
            // PHASE 5: AI-POWERED SUMMARY REPORT
            // ═══════════════════════════════════════════════════
            $log('info', '');
            $log('info', '═══════════════════════════════════════');
            $log('info', '📝 PHASE 5: AI Summary Report');
            $log('info', '═══════════════════════════════════════');

            $this->runPhase5($log);

            // Final cleanup
            $log('info', '');
            $log('info', 'Cleaning up simulation test data...');
            $this->cleanup();
            $log('success', '🎉 All 5 Diagnostic Phases Completed!');

        } catch (\Exception $e) {
            $log('error', "Fatal Simulator Error: " . $e->getMessage() . " on line " . $e->getLine());
        }
    }

    // ═══════════════════════════════════════════════════════
    // PHASE 1: PRE-FLIGHT CONFIG CHECKS
    // ═══════════════════════════════════════════════════════

    private function runPhase1(callable $log): void
    {
        // 1.1 AI Bot Toggle
        $botEnabled = Setting::getValue('ai_bot', 'enabled', false, $this->companyId);
        if ($botEnabled) {
            $log('success', '✅ AI Bot Status: ON');
            $this->addResult('ai_bot_status', true, 'AI Bot is enabled');
        } else {
            $log('error', '❌ AI Bot Status: OFF — Bot will not respond to WhatsApp messages!');
            $this->addResult('ai_bot_status', false, 'AI Bot is disabled. Turn ON in Settings > AI Bot Config.');
        }

        // 1.2 Vertex AI Config
        $config = Setting::getValue('ai_bot', 'vertex_config', null, $this->companyId);
        if (!$config || empty($config['project_id'])) {
            $log('error', '❌ Vertex AI: NOT configured — Project ID is missing');
            $this->addResult('vertex_config', false, 'Vertex AI not configured. Go to Settings > AI Bot Config and set Project ID + Service Account.');
            return; // Can't proceed without AI config
        }
        $log('success', "✅ Vertex AI Config: Project={$config['project_id']}, Location={$config['location']}, Model={$config['model']}");
        $this->addResult('vertex_config', true, 'Vertex AI configured');

        // 1.3 Service Account JSON
        if (empty($config['service_account']) || empty($config['service_account']['client_email'])) {
            $log('error', '❌ Service Account: MISSING — Upload the JSON key file in Settings');
            $this->addResult('service_account', false, 'Service Account JSON is missing or incomplete.');
        } else {
            $log('success', "✅ Service Account: {$config['service_account']['client_email']}");
            $this->addResult('service_account', true, 'Service Account present');
        }

        // 1.4 Vertex AI Credentials Test (actual token generation)
        try {
            if ($this->vertexAI->isConfigured()) {
                $testResult = $this->vertexAI->classifyContent('Reply with OK');
                if (!empty($testResult['text']) && $testResult['text'] !== 'NONE') {
                    $log('success', '✅ Vertex AI Credentials: WORKING — Successfully connected to Google Cloud');
                    $this->addResult('vertex_credentials', true, 'Google Cloud credentials verified');
                } else {
                    $log('error', '❌ Vertex AI Credentials: FAILED — Could not get valid response');
                    $this->addResult('vertex_credentials', false, 'Vertex AI returned empty response. Check credentials.');
                }
            }
        } catch (\Exception $e) {
            $log('error', '❌ Vertex AI Credentials: ERROR — ' . $e->getMessage());
            $this->addResult('vertex_credentials', false, 'Credential test failed: ' . $e->getMessage());
        }

        // 1.5 System Prompt
        $systemPrompt = Setting::getValue('ai_bot', 'system_prompt', '', $this->companyId);
        if (empty(trim($systemPrompt))) {
            $log('error', '❌ System Prompt: NOT SET — Bot will use default behavior without personality rules');
            $this->addResult('system_prompt', false, 'System Prompt is empty. Set it in Settings > AI Bot Config.');
        } else {
            $wordCount = str_word_count($systemPrompt);
            $log('success', "✅ System Prompt: Set ({$wordCount} words)");
            $this->addResult('system_prompt', true, "System Prompt configured ({$wordCount} words)");
        }

        // 1.6 Reply Language
        $lang = Setting::getValue('ai_bot', 'reply_language', 'auto', $this->companyId);
        $langLabel = match ($lang) {
            'en' => 'English Only',
            'hi' => 'Hindi Only',
            default => 'Auto-detect (same as user)',
        };
        $log('success', "✅ Reply Language: {$langLabel}");
        $this->addResult('reply_language', true, "Language set to: {$langLabel}");

        // 1.7 WhatsApp API Config
        $waConfig = Setting::getValue('whatsapp', 'api_config', ['api_url' => '', 'api_key' => '', 'webhook_base_url' => ''], $this->companyId);
        $waIssues = [];
        if (empty($waConfig['api_url'])) $waIssues[] = 'API URL missing';
        if (empty($waConfig['api_key'])) $waIssues[] = 'API Key missing';
        if (empty($waConfig['webhook_base_url'])) $waIssues[] = 'Webhook Base URL missing';

        if (empty($waIssues)) {
            $log('success', "✅ WhatsApp API: Configured (URL: {$waConfig['api_url']})");
            $this->addResult('whatsapp_config', true, 'WhatsApp API fully configured');
        } else {
            $log('error', '❌ WhatsApp API: INCOMPLETE — ' . implode(', ', $waIssues));
            $this->addResult('whatsapp_config', false, 'WhatsApp API issues: ' . implode(', ', $waIssues));
        }
    }

    // ═══════════════════════════════════════════════════════
    // PHASE 2: MODULE HEALTH CHECKS
    // ═══════════════════════════════════════════════════════

    private function runPhase2(callable $log): void
    {
        // 2.1 Catalogue Custom Columns
        $columns = CatalogueCustomColumn::where('company_id', $this->companyId)->where('is_active', true)->get();
        $columnCount = $columns->count();
        $uniqueCol = $columns->firstWhere('is_unique', true);
        $aiVisibleCount = $columns->where('show_in_ai', true)->count();
        $comboColCount = $columns->where('column_type', 'combo')->count();

        if ($columnCount > 0) {
            $log('success', "✅ Catalogue Columns: {$columnCount} active columns found");
            if ($uniqueCol) {
                $log('success', "   ↳ Unique Column: {$uniqueCol->name} (used as product identifier)");
            } else {
                $log('error', "   ↳ ❌ No Unique column set — Bot won't have a reliable product identifier!");
            }
            $log('info', "   ↳ AI-visible columns: {$aiVisibleCount} | Combo columns: {$comboColCount}");
            $this->addResult('catalogue_columns', true, "{$columnCount} active columns, {$comboColCount} combo, unique=" . ($uniqueCol ? $uniqueCol->name : 'NONE'));
        } else {
            $log('error', '❌ Catalogue Columns: NONE — No custom columns created. Bot cannot show product features.');
            $this->addResult('catalogue_columns', false, 'No catalogue columns configured.');
        }

        // 2.2 Products
        $totalProducts = Product::where('company_id', $this->companyId)->count();
        $activeProducts = Product::where('company_id', $this->companyId)->where('status', 'active')->count();
        $productsWithCombos = Product::where('company_id', $this->companyId)->whereHas('combos')->count();
        $totalVariations = ProductVariation::whereHas('product', fn($q) => $q->where('company_id', $this->companyId))->where('status', 'active')->count();

        if ($totalProducts > 0) {
            $log('success', "✅ Products: {$totalProducts} total, {$activeProducts} active");
            $log('info', "   ↳ Products with combos: {$productsWithCombos}");
            $log('info', "   ↳ Active variations: {$totalVariations}");
            if ($productsWithCombos > 0 && $totalVariations === 0) {
                $log('error', "   ↳ ❌ Products have combos but NO variations generated! Prices won't resolve.");
                $this->addResult('products', false, "Products exist but variations are missing. Generate variations in Product settings.");
            } else {
                $this->addResult('products', true, "{$activeProducts} active products, {$totalVariations} variations");
            }
        } else {
            $log('error', '❌ Products: NONE — No products in catalogue. Bot has nothing to show.');
            $this->addResult('products', false, 'No products found. Add products to the catalogue.');
        }

        // 2.3 Chatflow Steps
        $steps = ChatflowStep::where('company_id', $this->companyId)->orderBy('sort_order')->get();
        $stepsCount = $steps->count();
        if ($stepsCount > 0) {
            $comboSteps = $steps->where('step_type', 'ask_combo')->count();
            $textSteps = $steps->where('step_type', 'ask_text')->count();
            $optionalSteps = $steps->where('is_optional', true)->count();
            $summarySteps = $steps->where('step_type', 'send_summary')->count();

            $log('success', "✅ Chatflow: {$stepsCount} steps configured");
            $log('info', "   ↳ Combo: {$comboSteps} | Text: {$textSteps} | Optional: {$optionalSteps} | Summary: {$summarySteps}");
            $this->addResult('chatflow', true, "{$stepsCount} steps ({$comboSteps} combo, {$textSteps} text, {$optionalSteps} optional)");
        } else {
            $log('error', '❌ Chatflow: NONE — No chatflow steps. Bot will default to free AI conversation only.');
            $this->addResult('chatflow', false, 'No chatflow steps configured. Build your chatflow in Chatflow Builder.');
        }

        // 2.4 Database Tables
        $requiredTables = ['ai_chat_sessions', 'ai_chat_messages', 'ai_token_logs', 'chatflow_steps', 'catalogue_custom_columns', 'products', 'product_variations', 'leads', 'quotes', 'quote_items'];
        $missingTables = [];
        foreach ($requiredTables as $table) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                $missingTables[] = $table;
            }
        }

        if (empty($missingTables)) {
            $log('success', "✅ Database: All " . count($requiredTables) . " required tables exist");
            $this->addResult('database', true, 'All required tables present');
        } else {
            $log('error', '❌ Database: Missing tables — ' . implode(', ', $missingTables));
            $this->addResult('database', false, 'Missing tables: ' . implode(', ', $missingTables) . '. Run php artisan migrate.');
        }

        // 2.5 Evolution API Ping
        $waConfig = Setting::getValue('whatsapp', 'api_config', ['api_url' => '', 'api_key' => ''], $this->companyId);
        if (!empty($waConfig['api_url']) && !empty($waConfig['api_key'])) {
            try {
                $response = Http::withHeaders([
                    'apikey' => $waConfig['api_key'],
                ])->timeout(10)->get(rtrim($waConfig['api_url'], '/') . '/instance/fetchInstances');

                if ($response->successful()) {
                    $instances = $response->json();
                    $instanceCount = is_array($instances) ? count($instances) : 0;
                    $log('success', "✅ Evolution API: REACHABLE — {$instanceCount} instance(s) found");
                    $this->addResult('evolution_api', true, "Evolution API online, {$instanceCount} instances");
                } else {
                    $log('error', "❌ Evolution API: Returned HTTP {$response->status()} — Check API URL and Key");
                    $this->addResult('evolution_api', false, "Evolution API returned HTTP {$response->status()}");
                }
            } catch (\Exception $e) {
                $log('error', '❌ Evolution API: UNREACHABLE — ' . $e->getMessage());
                $this->addResult('evolution_api', false, 'Cannot reach Evolution API: ' . $e->getMessage());
            }
        } else {
            $log('info', 'ℹ️ Evolution API: Skipped (WhatsApp API not configured)');
            $this->addResult('evolution_api', false, 'WhatsApp API not configured, cannot test Evolution API');
        }
    }

    // ═══════════════════════════════════════════════════════
    // PHASE 3: INTERACTIVE AI SIMULATION
    // ═══════════════════════════════════════════════════════

    private function runPhase3(string $rules, callable $log): void
    {
        $log('info', 'Tester AI is reading your Testing Conditions...');

        $testerSystemPrompt = "You are pretending to be a REAL WhatsApp customer testing a CRM bot. Admin's testing rules:\n" . strip_tags($rules) . "\n\nCRITICAL INSTRUCTIONS:\n- Reply with ONLY a short 1-line customer message (like a real person would type on WhatsApp).\n- Do NOT include any thoughts, reasoning, explanations, or internal commentary.\n- Do NOT repeat or translate the bot's message.\n- Do NOT use markdown formatting like **bold** or headers.\n- Push the conversation towards ordering a product step by step.\n- If the bot breaks a rule (e.g. shows price when hidden, wrong language), reply ONLY: FAILURE: [reason]\n- Examples of good replies: 'products dikhao', 'pehla wala', 'haan ye chahiye', '1'\n- Examples of BAD replies: '(Translation of bot message)', '**Thoughts:** I should ask...'\n- Maximum 4 turns.";

        $chatHistory = [];
        $conversationLog = [];
        $simulationPassed = true;

        // Invalid tester messages to detect
        $invalidTesterPhrases = [
            'sorry, i could not generate',
            'i cannot generate',
            'i could not generate a response',
            'i\'m unable to generate',
            'unable to generate a response',
            'i don\'t know what to say',
            'i cannot fulfill',
            'i\'m not able to',
            'as an ai',
            'i am an ai',
        ];

        for ($i = 0; $i < 4; $i++) {
            // Tester AI generates message
            if ($i === 0) {
                $userMsg = "Hi";
            } else {
                $testerResult = $this->vertexAI->generateContent($testerSystemPrompt, $chatHistory);
                $userMsg = $this->cleanTesterResponse(trim($testerResult['text'] ?? ''));

                // Check for empty or invalid tester response
                $isInvalid = empty($userMsg);
                if (!$isInvalid) {
                    $lowerMsg = strtolower($userMsg);
                    foreach ($invalidTesterPhrases as $phrase) {
                        if (str_contains($lowerMsg, $phrase)) {
                            $isInvalid = true;
                            break;
                        }
                    }
                }

                // If invalid, retry ONCE
                if ($isInvalid) {
                    $log('info', "⚠️ Tester AI gave invalid response at turn " . ($i + 1) . ", retrying...");
                    sleep(1);
                    $retryResult = $this->vertexAI->generateContent(
                        $testerSystemPrompt . "\n\nIMPORTANT: You MUST generate a short, realistic WhatsApp customer message. Just 3-5 words maximum. Look at the bot's last message and respond naturally. Examples: 'products dikhao', 'pehla wala do', '1', 'haan'. Do NOT refuse or say sorry.",
                        $chatHistory
                    );
                    $userMsg = $this->cleanTesterResponse(trim($retryResult['text'] ?? ''));

                    // Check again
                    $stillInvalid = empty($userMsg);
                    if (!$stillInvalid) {
                        $lowerMsg = strtolower($userMsg);
                        foreach ($invalidTesterPhrases as $phrase) {
                            if (str_contains($lowerMsg, $phrase)) {
                                $stillInvalid = true;
                                break;
                            }
                        }
                    }

                    if ($stillInvalid) {
                        $log('error', "❌ Tester AI failed to generate a valid response at turn " . ($i + 1) . " (even after retry)");
                        $simulationPassed = false;
                        $conversationLog[] = "ERROR: Tester AI could not generate valid response at turn " . ($i + 1);
                        break;
                    }
                }
            }

            if (str_starts_with($userMsg, 'FAILURE:')) {
                $log('error', "🚫 Bot FAILED conditions: " . $userMsg);
                $simulationPassed = false;
                $conversationLog[] = "TESTER: {$userMsg}";
                break;
            }

            $log('user', $userMsg);
            $chatHistory[] = ['role' => 'user', 'text' => $userMsg];
            $conversationLog[] = "CUSTOMER: {$userMsg}";

            // Bot responds
            try {
                $botResult = $this->chatbotService->processMessage('simulator_1', $this->simPhone, $userMsg);
                $botMsg = $botResult['response'] ?? 'No text response generated.';

                $log('bot', $botMsg);
                $chatHistory[] = ['role' => 'model', 'text' => $botMsg];
                $conversationLog[] = "BOT: {$botMsg}";

                // ══ Bot Error Response Check: Detect if bot returned an error message ══
                $botErrorPhrases = ['sorry, i could not generate', 'sorry, an error occurred', 'sorry, i am unable to process', 'ai bot is not configured'];
                $botLower = strtolower($botMsg);
                foreach ($botErrorPhrases as $errPhrase) {
                    if (str_contains($botLower, $errPhrase)) {
                        $log('error', "🚫 BOT ERROR DETECTED at turn " . ($i + 1) . ": Bot returned error response instead of proper answer!");
                        $simulationPassed = false;
                        $this->addResult('bot_error_response', false, "Bot returned error at turn " . ($i + 1) . ": {$botMsg}");
                        break;
                    }
                }

                // ══ Greeting Validation: First turn "Hi" should get greeting, NOT catalogue dump ══
                if ($i === 0) {
                    $isProductDump = preg_match('/\d+️⃣\s+\*/m', $botMsg) 
                        || str_contains($botMsg, 'Our Products:') 
                        || str_contains($botMsg, 'Our Categories:')
                        || preg_match('/^\d+\.\s+\*\*.+\*\*/m', $botMsg);
                    if ($isProductDump) {
                        $log('error', '🚫 GREETING TEST FAILED: Bot sent product catalogue/category list in response to a simple greeting instead of greeting the customer first!');
                        $simulationPassed = false;
                        $this->addResult('greeting_test', false, 'Bot dumps product catalogue on greeting instead of greeting the customer first');
                    } else {
                        $log('success', '✅ Greeting Test: Bot properly greeted user without dumping catalogue');
                        $this->addResult('greeting_test', true, 'Bot properly handles greeting messages');
                    }
                }

                // ══ Fabricated Product Check: Verify bot only mentions REAL products from DB ══
                $realProducts = Product::where('company_id', $this->companyId)
                    ->where('status', 'active')
                    ->pluck('name')
                    ->map(fn($n) => strtolower(trim($n)))
                    ->toArray();

                if (!empty($realProducts)) {
                    // Check if bot listed any numbered product items
                    $hasProductList = preg_match('/\d+[\.\)️⃣]\s*\*/', $botMsg);
                    if ($hasProductList) {
                        // Extract product-like names from numbered list
                        preg_match_all('/\*([^*]+)\*/', $botMsg, $starMatches);
                        $mentionedNames = $starMatches[1] ?? [];

                        $fakeProducts = [];
                        foreach ($mentionedNames as $mentioned) {
                            $mentionedLower = strtolower(trim($mentioned));
                            // Skip known non-product labels
                            if (in_array($mentionedLower, ['our products:', 'our categories:', 'order summary:'])) continue;
                            
                            $matchFound = false;
                            foreach ($realProducts as $real) {
                                if (str_contains($real, $mentionedLower) || str_contains($mentionedLower, $real) || $real === $mentionedLower) {
                                    $matchFound = true;
                                    break;
                                }
                            }
                            if (!$matchFound) {
                                $fakeProducts[] = $mentioned;
                            }
                        }

                        if (!empty($fakeProducts)) {
                            $fakeList = implode(', ', $fakeProducts);
                            $log('error', "🚫 FAKE PRODUCT DETECTED: Bot mentioned products NOT in catalogue: {$fakeList}");
                            $simulationPassed = false;
                            $this->addResult('product_accuracy', false, "Bot fabricated product names: {$fakeList}");
                        } else {
                            $this->addResult('product_accuracy', true, 'All mentioned products match actual catalogue');
                        }
                    }
                }
            } catch (\Exception $e) {
                $log('error', "AIChatbotService crashed: " . $e->getMessage());
                $simulationPassed = false;
                $conversationLog[] = "ERROR: Bot crashed - " . $e->getMessage();
                break;
            }

            sleep(2);
        }

        $this->addResult('simulation', $simulationPassed, $simulationPassed ? 'Conversation completed without failures' : 'Simulation had issues');
        $this->diagnosticResults['conversation_log'] = $conversationLog;
    }

    // ═══════════════════════════════════════════════════════
    // PHASE 4: POST-SIMULATION DATA VERIFICATION
    // ═══════════════════════════════════════════════════════

    private function runPhase4(callable $log): void
    {
        // 4.1 Session Created
        $session = AiChatSession::where('phone_number', $this->simPhone)->first();
        if ($session) {
            $log('success', "✅ Session: Created (ID: {$session->id}, State: {$session->conversation_state})");
            $this->addResult('session_created', true, "Session ID {$session->id} created with state: {$session->conversation_state}");
        } else {
            $log('error', '❌ Session: NOT created — AiChatSession was not generated during simulation');
            $this->addResult('session_created', false, 'No session was created. This indicates a critical bug in AIChatbotService.');
            return; // Can't check further without session
        }

        // 4.2 Messages Logged
        $messageCount = AiChatMessage::where('session_id', $session->id)->count();
        $userMsgCount = AiChatMessage::where('session_id', $session->id)->where('role', 'user')->count();
        $botMsgCount = AiChatMessage::where('session_id', $session->id)->where('role', 'bot')->count();

        if ($messageCount > 0) {
            $log('success', "✅ Messages: {$messageCount} total ({$userMsgCount} user, {$botMsgCount} bot)");
            $this->addResult('messages_logged', true, "{$messageCount} messages logged");
        } else {
            $log('error', '❌ Messages: NONE logged — Chat messages were not saved to database');
            $this->addResult('messages_logged', false, 'No messages saved to database.');
        }

        // 4.3 Lead Created
        if ($session->lead_id) {
            $lead = Lead::find($session->lead_id);
            if ($lead) {
                $customData = $lead->ai_custom_data ?? [];
                $customCount = count($customData);
                $log('success', "✅ Lead: Auto-created (ID: {$lead->id}, Name: {$lead->name})");
                if ($customCount > 0) {
                    $log('info', "   ↳ Custom data fields saved: {$customCount}");
                }
                $this->addResult('lead_created', true, "Lead ID {$lead->id} created with {$customCount} custom data fields");
            } else {
                $log('error', '❌ Lead: ID exists in session but record NOT found in database');
                $this->addResult('lead_created', false, 'Lead ID referenced but record missing.');
            }
        } else {
            $log('info', 'ℹ️ Lead: Not created — User may not have selected a product during simulation');
            $this->addResult('lead_created', false, 'No lead created. This is normal if the user did not select a product.');
        }

        // 4.4 Quote Created
        if ($session->quote_id) {
            $quote = Quote::with('items')->find($session->quote_id);
            if ($quote) {
                $itemCount = $quote->items->count();
                $log('success', "✅ Quote: Auto-created (ID: {$quote->id}, Items: {$itemCount})");

                // Check if variation was matched
                foreach ($quote->items as $item) {
                    if ($item->variation_id) {
                        $variation = ProductVariation::find($item->variation_id);
                        $log('success', "   ↳ Variation matched: Price ₹{$variation->price}");
                    } else {
                        $log('info', "   ↳ No variation matched yet (combo selection incomplete)");
                    }
                }
                $this->addResult('quote_created', true, "Quote ID {$quote->id} with {$itemCount} item(s)");
            } else {
                $log('error', '❌ Quote: ID exists but record NOT found');
                $this->addResult('quote_created', false, 'Quote ID referenced but record missing.');
            }
        } else {
            $log('info', 'ℹ️ Quote: Not created — Normal if product was not selected');
            $this->addResult('quote_created', false, 'No quote created during simulation.');
        }

        // 4.5 Token Logs
        $tokenLogs = AiTokenLog::where('phone_number', $this->simPhone)->get();
        if ($tokenLogs->count() > 0) {
            $tier1Count = $tokenLogs->where('tier', 1)->count();
            $tier2Count = $tokenLogs->where('tier', 2)->count();
            $totalTokens = $tokenLogs->sum('total_tokens');
            $log('success', "✅ Token Logging: {$tokenLogs->count()} entries — Tier-1: {$tier1Count}, Tier-2: {$tier2Count}, Total Tokens: {$totalTokens}");
            $this->addResult('token_logging', true, "Tier-1: {$tier1Count}, Tier-2: {$tier2Count}, Tokens: {$totalTokens}");
        } else {
            $log('error', '❌ Token Logging: NONE — No token consumption was tracked');
            $this->addResult('token_logging', false, 'Token logs not generated.');
        }

        // 4.6 Collected Answers
        if ($session) {
            $answers = $session->collected_answers ?? [];
            $answerCount = count($answers);
            if ($answerCount > 0) {
                $log('success', "✅ Collected Answers: {$answerCount} answers saved in session");
                foreach ($answers as $key => $value) {
                    $log('info', "   ↳ {$key}: {$value}");
                }
                $this->addResult('collected_answers', true, "{$answerCount} answers stored");
            } else {
                $log('info', 'ℹ️ Collected Answers: None — Normal if chatflow wasn\'t fully navigated');
                $this->addResult('collected_answers', false, 'No answers collected during simulation.');
            }
        }
    }

    // ═══════════════════════════════════════════════════════
    // PHASE 5: AI-POWERED SUMMARY REPORT
    // ═══════════════════════════════════════════════════════

    private function runPhase5(callable $log): void
    {
        $log('info', 'Generating AI-powered diagnostic summary...');

        // Build a text summary of all results
        $resultText = "Below are the diagnostic results from all 5 phases of CRM WhatsApp Bot testing:\n\n";

        foreach ($this->diagnosticResults as $key => $result) {
            if ($key === 'conversation_log') continue;
            $status = $result['passed'] ? '✅ PASS' : '❌ FAIL';
            $resultText .= "- {$status} | {$key}: {$result['detail']}\n";
        }

        // Add conversation snippet
        if (!empty($this->diagnosticResults['conversation_log'])) {
            $resultText .= "\n--- Conversation Log ---\n";
            foreach ($this->diagnosticResults['conversation_log'] as $line) {
                $resultText .= $line . "\n";
            }
        }

        $summaryPrompt = "You are a CRM system diagnostic expert. Write a SHORT summary report in simple Hinglish (Hindi + English mix). For each failed item, explain WHAT is wrong and HOW to fix it in 1 line. Keep it under 15 lines total. Do NOT refuse or say you cannot generate a response.";

        try {
            $summaryResult = $this->vertexAI->generateContent($summaryPrompt, [
                ['role' => 'user', 'text' => $resultText . "\n\nAbove results ka summary report generate karo."],
            ]);

            $summary = $summaryResult['text'] ?? '';
            if (!empty($summary)) {
                // Split by newlines and output each line
                $lines = explode("\n", $summary);
                foreach ($lines as $line) {
                    $trimmed = trim($line);
                    if (!empty($trimmed)) {
                        $log('info', $trimmed);
                    }
                }
            } else {
                $log('error', 'AI Summary could not be generated.');
            }
        } catch (\Exception $e) {
            $log('error', 'Summary generation failed: ' . $e->getMessage());

            // Fallback: manual summary
            $log('info', '--- Manual Summary ---');
            $passed = 0;
            $failed = 0;
            foreach ($this->diagnosticResults as $key => $result) {
                if ($key === 'conversation_log') continue;
                if ($result['passed']) $passed++;
                else $failed++;
            }
            $log('info', "Total Checks: " . ($passed + $failed) . " | ✅ Passed: {$passed} | ❌ Failed: {$failed}");
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

    /**
     * Clean tester AI response — strip thoughts, formatting, translations
     */
    private function cleanTesterResponse(string $msg): string
    {
        if (empty($msg)) return '';

        // Remove **Thoughts:** or **My next message:** blocks
        $msg = preg_replace('/\*\*Thoughts?:\*\*.*?(?=\*\*My\s+(?:next\s+)?message|$)/si', '', $msg);
        $msg = preg_replace('/\*\*My\s+(?:next\s+)?message:\*\*\s*/i', '', $msg);

        // Remove any lines starting with "Thoughts:" or "My next message:"
        $msg = preg_replace('/^Thoughts?:.*$/mi', '', $msg);
        $msg = preg_replace('/^My\s+(?:next\s+)?message:\s*/mi', '', $msg);

        // Remove parenthesized content that looks like translation (e.g. "(Hello! Welcome to...)")
        $msg = preg_replace('/^\(.*\)$/s', '', trim($msg));

        // Remove markdown bold/italic
        $msg = preg_replace('/\*\*([^*]+)\*\*/', '$1', $msg);
        $msg = preg_replace('/\*([^*]+)\*/', '$1', $msg);

        // Take only the first non-empty line (real customer messages are 1 line)
        $lines = array_filter(array_map('trim', explode("\n", $msg)), fn($l) => !empty($l));
        $msg = !empty($lines) ? reset($lines) : '';

        // Remove surrounding quotes if present
        $msg = trim($msg, "\"'`");

        return trim($msg);
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
