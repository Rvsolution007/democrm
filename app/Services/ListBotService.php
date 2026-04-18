<?php

namespace App\Services;

use App\Models\AiChatSession;
use App\Models\AiChatMessage;
use App\Models\AiChatTrace;
use App\Models\ChatflowStep;
use App\Models\CatalogueCustomColumn;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Lead;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * List Bot Service — Zero AI, Progressive Chatflow Filter
 *
 * Uses chatflow steps to progressively filter products via interactive WhatsApp menus.
 * NO Gemini/AI calls. All selections are via WhatsApp Interactive Lists.
 *
 * Flow:
 * 1. First chatflow question → show distinct values from ALL products for that column
 * 2. User selects value → filter products → check how many match
 * 3. Multiple matches → queue products, take first
 * 4. Next question → get data from CURRENT product (first in queue)
 * 5. Blank column → skip | Single value → auto-select | Multiple values → ask
 * 6. All questions done → Summary
 * 7. Queue has more → next product → repeat from step 4
 */
class ListBotService
{
    private int $companyId;
    private int $userId;
    private WhatsAppService $whatsApp;
    private ?CatalogueCustomColumn $uniqueColumn = null;
    private bool $uniqueColumnLoaded = false;
    private $aiVisibleColumns = null;
    private array $pendingTraces = [];
    private ?int $currentMessageId = null;
    private int $stepMenuDepth = 0;

    public function __construct(int $companyId, int $userId)
    {
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->whatsApp = new WhatsAppService($companyId);

        Log::info('ListBot: Initialized', [
            'company_id' => $companyId,
            'user_id' => $userId,
            'active_products' => Product::where('company_id', $companyId)->where('status', 'active')->count(),
        ]);
    }

    /**
     * Buffer a node trace for n8n-style diagnostic UI.
     */
    private function traceNode(int $sessionId, string $nodeName, string $group, string $status, ?array $input = null, ?array $output = null, ?string $error = null, int $timeMs = 0): void
    {
        $this->pendingTraces[] = [
            'session_id'        => $sessionId,
            'message_id'        => $this->currentMessageId,
            'node_name'         => $nodeName,
            'node_group'        => $group,
            'status'            => $status,
            'input_data'        => $input,
            'output_data'       => $output,
            'error_message'     => $error,
            'execution_time_ms' => $timeMs,
        ];
    }

    /**
     * Flush all buffered traces to the database.
     */
    private function flushTraces(): void
    {
        foreach ($this->pendingTraces as $trace) {
            try {
                AiChatTrace::create($trace);
            } catch (\Exception $e) {
                Log::warning('ListBot: Failed to save trace - ' . $e->getMessage());
            }
        }
        $this->pendingTraces = [];
    }

    // ═══════════════════════════════════════════════════════
    // MAIN ENTRY POINT
    // ═══════════════════════════════════════════════════════

    public function processMessage(
        string $instanceName,
        string $phone,
        string $messageText,
        ?string $listRowId = null
    ): void {
        $this->pendingTraces = [];

        if (!$this->whatsApp->isConfigured()) {
            Log::error('ListBot: WhatsApp API not configured');
            return;
        }

        try {
            $session = AiChatSession::findOrCreateForPhone($this->companyId, $phone, $instanceName);
            // Set bot_type for new sessions
            if ($session->wasRecentlyCreated) {
                $session->update(['bot_type' => 'list_bot', 'last_message_at' => now()]);
            } else {
                $session->update(['last_message_at' => now()]);
            }

            // Session expiry check
            $validDays = (int) Setting::getValue('ai_bot', 'session_valid_days', 10, $this->companyId);
            if (!$session->wasRecentlyCreated && $session->last_message_at) {
                $daysSinceLastMessage = $session->last_message_at->diffInDays(now());
                if ($daysSinceLastMessage >= $validDays) {
                    $this->traceNode($session->id, 'SessionExpired', 'routing', 'warning',
                        ['days_elapsed' => $daysSinceLastMessage, 'limit_days' => $validDays],
                        ['action' => 'expired_old_session', 'started_new_session' => true]);
                    Log::info('ListBot: Session expired', ['session' => $session->id, 'days' => $daysSinceLastMessage]);
                    $session->update(['status' => 'expired']);
                    $session = AiChatSession::create([
                        'company_id' => $this->companyId,
                        'phone_number' => $phone,
                        'instance_name' => $instanceName,
                        'bot_type' => 'list_bot',
                        'status' => 'active',
                        'last_message_at' => now(),
                    ]);
                }
            }

            // Auto-reset completed sessions — user messaging again means they want a fresh start
            if (in_array($session->conversation_state, ['completed']) 
                || $session->status === 'completed') {
                $this->traceNode($session->id, 'AutoResetCompleted', 'routing', 'success',
                    ['old_state' => $session->conversation_state, 'old_status' => $session->status],
                    ['action' => 'completed_session_reset', 'lead_id_retained' => $session->lead_id]);
                
                $oldLeadId = $session->lead_id;
                $oldQuoteId = $session->quote_id;

                $session->update(['status' => 'completed']);
                $session = AiChatSession::create([
                    'company_id' => $this->companyId,
                    'phone_number' => $phone,
                    'instance_name' => $instanceName,
                    'bot_type' => 'list_bot',
                    'lead_id' => $oldLeadId,
                    'quote_id' => $oldQuoteId,
                    'status' => 'active',
                    'last_message_at' => now(),
                ]);
            }

            // Detect orphaned session — lead or quote was deleted externally
            $isOrphaned = false;
            if ($session->lead_id && !Lead::where('id', $session->lead_id)->exists()) {
                $isOrphaned = true;
            } elseif ($session->quote_id && !Quote::where('id', $session->quote_id)->exists()) {
                $isOrphaned = true;
            }

            if ($isOrphaned) {
                $this->traceNode($session->id, 'OrphanedDataReset', 'routing', 'warning',
                    ['deleted_lead_id' => $session->lead_id, 'deleted_quote_id' => $session->quote_id],
                    ['action' => 'reset_due_to_deleted_data']);
                $session->update(['status' => 'expired']);
                $session = AiChatSession::create([
                    'company_id' => $this->companyId,
                    'phone_number' => $phone,
                    'instance_name' => $instanceName,
                    'bot_type' => 'list_bot',
                    'status' => 'active',
                    'last_message_at' => now(),
                ]);
            }

            // Save user message
            $userMsg = AiChatMessage::create([
                'session_id' => $session->id,
                'role' => 'user',
                'message' => $messageText,
                'message_type' => 'text',
            ]);
            $this->currentMessageId = $userMsg->id;

            // Trace: receive message
            $this->traceNode($session->id, 'ReceiveMessage', 'routing', 'success',
                ['phone' => $phone, 'message' => mb_substr($messageText, 0, 200), 'has_list_row_id' => (bool)$listRowId],
                ['session_id' => $session->id, 'message_id' => $userMsg->id, 'is_new_session' => $session->wasRecentlyCreated]);

            // Early lead creation on first message
            if (!$session->lead_id) {
                $lead = Lead::create([
                    'company_id' => $this->companyId,
                    'created_by_user_id' => $this->userId,
                    'source' => 'whatsapp',
                    'name' => $phone,
                    'phone' => $phone,
                    'stage' => 'new',
                ]);
                $session->lead_id = $lead->id;
                $session->save();
                $this->traceNode($session->id, 'LeadCreated', 'database', 'success',
                    ['phone' => $phone, 'trigger' => 'first_message'],
                    ['lead_id' => $lead->id, 'lead_source' => 'whatsapp']);
                Log::info('ListBot: Lead created', ['session' => $session->id, 'lead_id' => $lead->id]);
            }

            // Reset session if user types "reset"
            $trimmed = trim(strtolower($messageText));
            if ($trimmed === 'reset') {
                $this->traceNode($session->id, 'SessionReset', 'routing', 'success',
                    ['trigger' => 'user_typed_reset'],
                    ['action' => 'expired_old_session', 'started_new_session' => true]);
                $session->update(['status' => 'expired', 'is_completed' => true]);
                $session = AiChatSession::create([
                    'company_id' => $this->companyId,
                    'phone_number' => $phone,
                    'instance_name' => $instanceName,
                    'bot_type' => 'list_bot',
                    'status' => 'active',
                    'conversation_state' => 'started',
                    'last_message_at' => now(),
                ]);
                Log::info('ListBot: Session reset by user', ['session' => $session->id]);
                $this->whatsApp->sendText($instanceName, $session->phone_number, "🔄 Reset successful!" . $this->resetFooter());
            }

            // Route the message
            $response = $this->routeMessage($session, $instanceName, $messageText, $listRowId);

            // Save bot response
            if ($response) {
                AiChatMessage::create([
                    'session_id' => $session->id,
                    'role' => 'bot',
                    'message' => $response,
                    'message_type' => 'text',
                ]);
                $session->update(['last_bot_message_at' => now()]);
            }
        } catch (\Exception $e) {
            Log::error('ListBot: Processing failed - ' . $e->getMessage(), [
                'phone' => $phone,
                'trace' => $e->getTraceAsString(),
            ]);
            // Trace the error
            if (isset($session)) {
                $this->traceNode($session->id, 'ProcessingError', 'routing', 'error',
                    ['phone' => $phone, 'message' => mb_substr($messageText, 0, 200)],
                    null, $e->getMessage());
            }
        } finally {
            // Always flush traces — even on exception
            $this->flushTraces();
        }
    }

    // ═══════════════════════════════════════════════════════
    // MESSAGE ROUTING — Simplified State Machine
    // ═══════════════════════════════════════════════════════

    private function routeMessage(AiChatSession $session, string $instanceName, string $messageText, ?string $listRowId): ?string
    {
        $steps = ChatflowStep::with('linkedColumn')->where('company_id', $this->companyId)->orderBy('sort_order')->get();
        $answers = $session->collected_answers ?? [];

        // Parse rowId if present (user tapped a list option)
        $parsed = $listRowId ? WhatsAppService::parseRowId($listRowId) : null;

        // ── Handle list selection (rowId present) ──
        if ($parsed) {
            $this->traceNode($session->id, 'ListSelection', 'routing', 'success',
                ['rowId' => $listRowId, 'type' => $parsed['type'], 'id' => $parsed['id'] ?? null],
                ['route' => 'handleListSelection']);
            return $this->handleListSelection($session, $instanceName, $parsed, $steps);
        }

        // ── Handle text fallback (number/text from rowMap) ──
        $trimmedText = trim($messageText);
        if (isset($answers['_text_menu_rowMap'])) {
            $rowMap = $answers['_text_menu_rowMap'];

            $matchedParsedList = [];

            // 1. Try matching the exact entire string (protects names with "and", e.g. "Wax & Water")
            $fullStringMatched = false;
            if (ctype_digit($trimmedText) && isset($rowMap[$trimmedText])) {
                $entry = $rowMap[$trimmedText];
                $rowId = is_array($entry) ? ($entry['rowId'] ?? '') : $entry;
                $parsed = WhatsAppService::parseRowId($rowId);
                if ($parsed) {
                    $matchedParsedList[] = $parsed;
                    $fullStringMatched = true;
                    $this->traceNode($session->id, 'TextFallbackMatch', 'routing', 'success',
                        ['input' => $trimmedText, 'matched_number' => $trimmedText, 'rowId' => $rowId],
                        ['type' => $parsed['type'], 'id' => $parsed['id'] ?? null]);
                    Log::info('ListBot: Number mapped to rowId', ['number' => $trimmedText, 'rowId' => $rowId]);
                }
            } else {
                $fuzzyMatch = $this->fuzzyMatchFromRowMap($trimmedText, $rowMap);
                if ($fuzzyMatch && $fuzzyMatch['score'] > 85) { // Very confident match for full string
                    $rowId = $fuzzyMatch['rowId'];
                    $parsed = WhatsAppService::parseRowId($rowId);
                    if ($parsed) {
                        $matchedParsedList[] = $parsed;
                        $fullStringMatched = true;
                        $this->traceNode($session->id, 'FuzzyTextMatch', 'routing', 'success',
                            ['input' => $trimmedText, 'matched_title' => $fuzzyMatch['title'], 'score' => $fuzzyMatch['score']],
                            ['type' => $parsed['type'], 'id' => $parsed['id'] ?? null]);
                        Log::info('ListBot: Fuzzy text matched full string', [
                            'input' => $trimmedText, 'matched' => $fuzzyMatch['title'], 'score' => $fuzzyMatch['score']
                        ]);
                    }
                }
            }

            // 2. If full string wasn't strongly matched, try splitting by delimiters
            if (!$fullStringMatched) {
                // Split by common delimiters including 'and', '&', comma, newline, and Hindi 'aur'
                $parts = preg_split('/(\band\b|\baur\b|&|,|\n)/i', $trimmedText);
                
                foreach ($parts as $part) {
                    $part = trim($part);
                    if (empty($part)) continue;
                    
                    if (ctype_digit($part) && isset($rowMap[$part])) {
                        $entry = $rowMap[$part];
                        $rowId = is_array($entry) ? ($entry['rowId'] ?? '') : $entry;
                        $parsed = WhatsAppService::parseRowId($rowId);
                        if ($parsed) $matchedParsedList[] = $parsed;
                        continue;
                    }

                    $fuzzyMatch = $this->fuzzyMatchFromRowMap($part, $rowMap);
                    if ($fuzzyMatch) {
                        $rowId = $fuzzyMatch['rowId'];
                        $parsed = WhatsAppService::parseRowId($rowId);
                        if ($parsed) $matchedParsedList[] = $parsed;
                    }
                }
            }

            // 3. Process the matches
            if (count($matchedParsedList) > 0) {
                unset($answers['_text_menu_rowMap']);
                
                // If more than 1 item matched, queue the rest!
                if (count($matchedParsedList) > 1) {
                    $queue = $answers['_multi_product_queue'] ?? [];
                    $additionalItems = array_slice($matchedParsedList, 1);
                    $queue = array_merge($queue, $additionalItems);
                    $answers['_multi_product_queue'] = $queue;
                    Log::info('ListBot: Queued multiple selections', ['count' => count($additionalItems)]);
                }

                $session->collected_answers = $answers;
                $session->save();
                
                // Process the first match immediately
                return $this->handleListSelection($session, $instanceName, $matchedParsedList[0], $steps);
            }

            // Invalid input — hint
            if (ctype_digit($trimmedText)) {
                $this->whatsApp->sendText($instanceName, $session->phone_number, "❌ Invalid option. Please reply with a valid number from the menu." . $this->resetFooter());
            } else {
                $this->whatsApp->sendText($instanceName, $session->phone_number, "❌ Could not match \"{$trimmedText}\". Please reply with the *number* or type the *exact name*." . $this->resetFooter());
            }
            return null;
        }

        // ── Handle "awaiting_add_more" state ──
        if ($session->conversation_state === 'awaiting_add_more') {
            $choice = trim(strtolower($trimmedText));
            if (in_array($choice, ['1', 'yes', 'haan', 'ha', 'y'])) {
                // Restart fresh for new product selection
                $this->resetForNewProduct($session, $steps);
                $session->save();
                return $this->sendNextChatflowQuestion($session, $instanceName, $steps);
            } else {
                // User is done
                $this->traceNode($session->id, 'OrderCompleted', 'routing', 'success',
                    ['user_choice' => 'no', 'completed_products' => count($answers['_completed_products'] ?? [])],
                    ['lead_id' => $session->lead_id, 'quote_id' => $session->quote_id]);
                $session->conversation_state = 'completed';
                $session->update(['status' => 'completed']);
                $finalMsg = "✅ Order complete! Our team will contact you shortly. 🙏" . $this->resetFooter();
                $this->whatsApp->sendText($instanceName, $session->phone_number, $finalMsg);
                return $finalMsg;
            }
        }

        // ── Handle custom/optional text steps ──
        $currentStep = $session->current_step_id ? $steps->firstWhere('id', $session->current_step_id) : null;
        if ($currentStep && in_array($currentStep->step_type, ['ask_custom', 'ask_optional'])) {
            return $this->handleCustomStep($session, $currentStep, $messageText, $steps, $instanceName);
        }

        // ── Default: Start or continue chatflow ──
        if ($trimmedText === 'reset') {
            // Already handled above, but if we reach here somehow, just send first question
        }

        // If no step is active (new session or just reset), start chatflow
        if (!$session->current_step_id || $session->conversation_state === 'started' || !$session->conversation_state) {
            // Send welcome + first chatflow question
            $welcomeMsg = Setting::getValue('list_bot', 'welcome_message', '', $this->companyId);
            if (!empty($welcomeMsg)) {
                $this->whatsApp->sendText($instanceName, $session->phone_number, $welcomeMsg . $this->resetFooter());
            }
            $this->traceNode($session->id, 'ChatflowStarted', 'routing', 'success',
                ['has_welcome' => !empty($welcomeMsg)],
                ['action' => 'starting_chatflow']);
            $session->conversation_state = 'in_chatflow';
            $this->advanceChatflow($session, $steps);
            $session->save();
            return $this->sendNextChatflowQuestion($session, $instanceName, $steps);
        }

        // If user sends random text while in chatflow (not a custom step), resend current menu
        if ($currentStep) {
            return $this->resendCurrentMenu($session, $instanceName, $steps);
        }

        // Fallback: restart chatflow
        $session->conversation_state = 'in_chatflow';
        $session->save();
        return $this->sendNextChatflowQuestion($session, $instanceName, $steps);
    }

    // ═══════════════════════════════════════════════════════
    // LIST SELECTION HANDLER
    // ═══════════════════════════════════════════════════════

    private function handleListSelection(AiChatSession $session, string $instanceName, array $parsed, $steps): ?string
    {
        switch ($parsed['type']) {
            case 'category':
                return $this->handleCategorySelection($session, $instanceName, $parsed['id'], $steps);

            case 'product':
                return $this->handleProductSelection($session, $instanceName, $parsed['id'], $steps);

            case 'column':
                return $this->handleColumnSelection($session, $instanceName, $parsed['id'], $parsed['value'], $steps);

            case 'combo':
                return $this->handleComboSelection($session, $instanceName, $parsed['id'], $parsed['value'], $steps);

            default:
                return $this->sendNextChatflowQuestion($session, $instanceName, $steps);
        }
    }

    // ═══════════════════════════════════════════════════════
    // CATEGORY SELECTION — Filter products by category
    // ═══════════════════════════════════════════════════════

    private function handleCategorySelection(AiChatSession $session, string $instanceName, int $categoryId, $steps): ?string
    {
        $category = \App\Models\Category::find($categoryId);
        if (!$category || $category->company_id !== $this->companyId) {
            $this->whatsApp->sendText($instanceName, $session->phone_number, "❌ Invalid category." . $this->resetFooter());
            return null;
        }

        // Group related categories (prefix matching)
        $baseName = preg_replace('/[\s\-_]*[\d]+[\s\-_]*$/', '', trim($category->name));
        $baseName = trim($baseName);

        $relatedCatIds = [$categoryId];
        if (!empty($baseName) && $baseName !== $category->name) {
            $relatedCats = \App\Models\Category::where('company_id', $this->companyId)
                ->where('status', 'active')
                ->where('name', 'LIKE', $baseName . '%')
                ->pluck('id')
                ->toArray();
            if (count($relatedCats) > 1) {
                $relatedCatIds = $relatedCats;
            }
        }

        // Save category answer
        $session->setAnswer('category_id', $categoryId);
        $session->setAnswer('category_ids', $relatedCatIds);
        $session->setAnswer('category_name', $baseName ?: $category->name);

        Log::info('ListBot: Category selected', ['session' => $session->id, 'category' => $baseName ?: $category->name]);

        // Now filter products by category and check for queue
        $filteredProducts = $this->getFilteredProducts($session);

        $this->traceNode($session->id, 'CategorySelected', 'routing', 'success',
            ['category_id' => $categoryId, 'category_name' => $baseName ?: $category->name, 'related_cat_ids' => $relatedCatIds],
            ['filtered_product_count' => $filteredProducts->count(), 'product_ids' => $filteredProducts->pluck('id')->toArray()]);

        Log::info('ListBot: Products after category filter', [
            'count' => $filteredProducts->count(),
            'ids' => $filteredProducts->pluck('id')->toArray(),
        ]);

        if ($filteredProducts->isEmpty()) {
            $this->whatsApp->sendText($instanceName, $session->phone_number, "Sorry, no products available in this category." . $this->resetFooter());
            // Reset category selection
            $answers = $session->collected_answers ?? [];
            unset($answers['category_id'], $answers['category_ids'], $answers['category_name']);
            $session->collected_answers = $answers;
            $session->save();
            return $this->sendNextChatflowQuestion($session, $instanceName, $steps);
        }

        if ($filteredProducts->count() === 1) {
            // Single product — set it directly
            $product = $filteredProducts->first();
            $session->setAnswer('product_id', $product->id);
            $session->setAnswer('product_name', $this->getProductDisplayName($product));
            $this->attachProductToLead($session, $product);
            $this->traceNode($session->id, 'AutoSelectProduct', 'routing', 'success',
                ['reason' => 'single_match_after_category', 'product_id' => $product->id],
                ['product_name' => $this->getProductDisplayName($product)]);
            Log::info('ListBot: Auto-selected single product', ['product_id' => $product->id]);
        }

        // Advance past category step
        $this->advanceChatflow($session, $steps);
        $session->save();

        return $this->sendNextChatflowQuestion($session, $instanceName, $steps);
    }

    // ═══════════════════════════════════════════════════════
    // PRODUCT SELECTION — When products are shown explicitly
    // ═══════════════════════════════════════════════════════

    private function handleProductSelection(AiChatSession $session, string $instanceName, int $productId, $steps): ?string
    {
        $product = Product::with(['combos.column', 'activeVariations', 'customValues'])->find($productId);
        if (!$product || $product->company_id !== $this->companyId) {
            $this->whatsApp->sendText($instanceName, $session->phone_number, "❌ Invalid product." . $this->resetFooter());
            return null;
        }

        $session->setAnswer('product_id', $productId);
        $session->setAnswer('product_name', $this->getProductDisplayName($product));
        $this->attachProductToLead($session, $product);

        $this->traceNode($session->id, 'ProductSelected', 'routing', 'success',
            ['product_id' => $productId, 'product_name' => $this->getProductDisplayName($product)],
            ['has_combos' => $product->combos->count() > 0, 'has_variations' => $product->activeVariations->count() > 0]);

        Log::info('ListBot: Product selected', ['session' => $session->id, 'product' => $this->getProductDisplayName($product)]);

        // Clear any product queue since user explicitly picked one
        $answers = $session->collected_answers ?? [];
        unset($answers['_product_queue']);
        $session->collected_answers = $answers;

        // Advance past product step
        $this->advanceChatflow($session, $steps);
        $session->save();

        return $this->sendNextChatflowQuestion($session, $instanceName, $steps);
    }

    // ═══════════════════════════════════════════════════════
    // COLUMN FILTER SELECTION
    // ═══════════════════════════════════════════════════════

    private function handleColumnSelection(AiChatSession $session, string $instanceName, int $columnId, string $value, $steps): ?string
    {
        $session->setAnswer("column_filter_{$columnId}", $value);
        $session->current_step_retries = 0;

        $this->traceNode($session->id, 'ColumnFilterSelected', 'routing', 'success',
            ['column_id' => $columnId, 'value' => $value],
            ['answer_key' => "column_filter_{$columnId}"]);

        Log::info('ListBot: Column filter selected', ['session' => $session->id, 'column' => $columnId, 'value' => $value]);

        // After saving answer, check filtered product count for queue
        $this->checkAndQueueProducts($session);

        // Update quote description if product is set
        $this->updateQuoteIfNeeded($session);

        $this->advanceChatflow($session, $steps);
        $session->save();

        return $this->sendNextChatflowQuestion($session, $instanceName, $steps);
    }

    // ═══════════════════════════════════════════════════════
    // COMBO SELECTION
    // ═══════════════════════════════════════════════════════

    private function handleComboSelection(AiChatSession $session, string $instanceName, $comboSlug, string $value, $steps): ?string
    {
        $session->setAnswer($comboSlug, $value);
        $session->current_step_retries = 0;

        $this->traceNode($session->id, 'ComboSelected', 'routing', 'success',
            ['combo_slug' => $comboSlug, 'value' => $value],
            ['answer_key' => $comboSlug]);

        Log::info('ListBot: Combo selected', ['session' => $session->id, 'slug' => $comboSlug, 'value' => $value]);

        // After saving answer, check filtered product count for queue
        $this->checkAndQueueProducts($session);

        // Update quote description + variation
        $this->updateQuoteIfNeeded($session);

        $this->advanceChatflow($session, $steps);
        $session->save();

        return $this->sendNextChatflowQuestion($session, $instanceName, $steps);
    }

    // ═══════════════════════════════════════════════════════
    // CUSTOM STEP — Accept text as-is
    // ═══════════════════════════════════════════════════════

    private function handleCustomStep(AiChatSession $session, ChatflowStep $step, string $rawMessage, $steps, string $instanceName): ?string
    {
        $fieldKey = $step->field_key;
        if (!$fieldKey) {
            $this->advanceChatflow($session, $steps);
            $session->save();
            return $this->sendNextChatflowQuestion($session, $instanceName, $steps);
        }

        $session->setAnswer($fieldKey, trim($rawMessage));
        $session->current_step_retries = 0;

        if ($step->isOptionalStep()) {
            $optionalAsked = $session->optional_asked ?? [];
            $optionalAsked[$fieldKey] = true;
            $session->optional_asked = $optionalAsked;
        }

        // Update lead with known fields
        if ($session->lead_id) {
            $lead = Lead::find($session->lead_id);
            if ($lead) {
                $directFields = ['name', 'email', 'city', 'state'];
                if (in_array($fieldKey, $directFields)) {
                    $lead->update([$fieldKey => trim($rawMessage)]);
                    $this->traceNode($session->id, 'LeadUpdated', 'database', 'success',
                        ['field' => $fieldKey, 'value' => mb_substr($rawMessage, 0, 100)],
                        ['lead_id' => $lead->id, 'update_type' => 'direct_field']);
                } else {
                    $customData = $lead->ai_custom_data ?? [];
                    $customData[$fieldKey] = trim($rawMessage);
                    $lead->update(['ai_custom_data' => $customData]);
                    $this->traceNode($session->id, 'LeadUpdated', 'database', 'success',
                        ['field' => $fieldKey, 'value' => mb_substr($rawMessage, 0, 100)],
                        ['lead_id' => $lead->id, 'update_type' => 'ai_custom_data']);
                }
            }
        }

        // Update quote description
        $this->updateQuoteIfNeeded($session);

        $this->advanceChatflow($session, $steps);
        $session->save();

        $this->traceNode($session->id, 'CustomAnswerSaved', 'database', 'success',
            ['field_key' => $fieldKey, 'step_type' => $step->step_type, 'value' => mb_substr($rawMessage, 0, 100)],
            ['is_optional' => $step->isOptionalStep()]);

        Log::info('ListBot: Custom answer saved', ['session' => $session->id, 'field' => $fieldKey, 'value' => mb_substr($rawMessage, 0, 50)]);

        return $this->sendNextChatflowQuestion($session, $instanceName, $steps);
    }

    // ═══════════════════════════════════════════════════════
    // CORE: SEND NEXT CHATFLOW QUESTION
    // Progressive filtering logic
    // ═══════════════════════════════════════════════════════

    private function sendNextChatflowQuestion(AiChatSession $session, string $instanceName, $steps): ?string
    {
        // Safety guard: prevent infinite recursion
        $this->stepMenuDepth++;
        if ($this->stepMenuDepth > 20) {
            Log::warning('ListBot: Max recursion depth reached, forcing summary', ['session' => $session->id]);
            $this->stepMenuDepth = 0;
            if ($session->getAnswer('product_id')) {
                return $this->handleSummaryStep($session, $instanceName);
            }
            $msg = "✅ All done! Our team will contact you shortly. 🙏" . $this->resetFooter();
            $this->whatsApp->sendText($instanceName, $session->phone_number, $msg);
            return $msg;
        }

        $nextStep = $session->current_step_id ? $steps->firstWhere('id', $session->current_step_id) : null;

        if (!$nextStep) {
            // Chatflow complete → send summary
            $this->stepMenuDepth = 0;
            if ($session->getAnswer('product_id')) {
                return $this->handleSummaryStep($session, $instanceName);
            }
            $msg = "✅ All done! Our team will contact you shortly. 🙏" . $this->resetFooter();
            $this->whatsApp->sendText($instanceName, $session->phone_number, $msg);
            return $msg;
        }

        // Step-level media
        if ($nextStep->hasMedia()) {
            $mediaKey = "step_{$nextStep->id}";
            if (!$session->hasMediaBeenSent($mediaKey)) {
                $this->sendMediaToWhatsApp($session, $nextStep->media_path, $instanceName);
                $session->markMediaSent($mediaKey);
                $session->save();
            }
        }

        switch ($nextStep->step_type) {
            case 'ask_category':
                return $this->sendCategoryQuestion($session, $instanceName, $nextStep, $steps);

            case 'ask_product':
            case 'ask_unique_column':
                return $this->sendProductQuestion($session, $instanceName, $nextStep, $steps);

            case 'ask_combo':
                return $this->sendComboQuestion($session, $instanceName, $nextStep, $steps);

            case 'ask_column':
                return $this->sendColumnQuestion($session, $instanceName, $nextStep, $steps);

            case 'ask_custom':
            case 'ask_optional':
                $this->stepMenuDepth = 0;
                $question = $nextStep->question_text ?: "Please provide your {$nextStep->field_key}:";
                $fullMsg = "📝 {$question}" . $this->resetFooter();
                $this->whatsApp->sendText($instanceName, $session->phone_number, $fullMsg);
                return $question;

            case 'send_summary':
                $this->stepMenuDepth = 0;
                return $this->handleSummaryStep($session, $instanceName);

            default:
                // Unknown step type — skip
                $this->markStepSkipped($session, $nextStep);
                $this->advanceChatflow($session, $steps);
                $session->save();
                return $this->sendNextChatflowQuestion($session, $instanceName, $steps);
        }
    }

    // ═══════════════════════════════════════════════════════
    // CATEGORY QUESTION — Show categories as options
    // ═══════════════════════════════════════════════════════

    private function sendCategoryQuestion(AiChatSession $session, string $instanceName, ChatflowStep $step, $steps): ?string
    {
        // Get categories with active products
        $categories = \App\Models\Category::where('company_id', $this->companyId)
            ->where('status', 'active')
            ->whereHas('products', function ($q) {
                $q->where('status', 'active');
            })
            ->withCount(['products' => function ($q) {
                $q->where('status', 'active');
            }])
            ->orderBy('name')
            ->get();

        $totalActive = Product::where('company_id', $this->companyId)->where('status', 'active')->count();

        // Smart category dedup (group by prefix if too many 1:1 categories)
        if ($categories->count() > 5 && $totalActive > 0) {
            $avgProductsPerCat = $totalActive / max($categories->count(), 1);
            if ($avgProductsPerCat <= 1.5) {
                $grouped = $this->groupCategoriesByPrefix($categories);
                if ($grouped && $grouped->count() < $categories->count()) {
                    $categories = $grouped;
                }
            }
        }

        if ($categories->isEmpty()) {
            // No categories → skip this step
            Log::info('ListBot: No categories found, skipping category step');
            $this->markStepSkipped($session, $step);
            $this->advanceChatflow($session, $steps);
            $session->save();
            return $this->sendNextChatflowQuestion($session, $instanceName, $steps);
        }

        // Auto-select if single category
        if ($categories->count() === 1) {
            $cat = $categories->first();
            Log::info('ListBot: Auto-selecting single category', ['category' => $cat->name]);
            return $this->handleCategorySelection($session, $instanceName, $cat->id, $steps);
        }

        // Build category menu
        $sections = WhatsAppService::buildCategorySections($categories, $this->getCategoryFieldLabel());
        $question = $step->question_text ?: 'Select Category:';
        $buttonText = Setting::getValue('list_bot', 'menu_button_text', '🛍 Menu', $this->companyId);

        $sent = $this->whatsApp->sendList(
            $instanceName,
            $session->phone_number,
            mb_substr($question, 0, 60),
            $question . $this->resetFooter(),
            mb_substr($buttonText, 0, 20),
            $sections,
            'Tap an item to select'
        );

        // Store rowMap for text fallback
        if (is_array($sent) && !empty($sent['rowMap'])) {
            $session->setAnswer('_text_menu_rowMap', $sent['rowMap']);
        }

        $session->current_step_id = $step->id;
        $session->save();

        Log::info('ListBot: Sent category question', ['categories' => $categories->count()]);
        return null;
    }

    // ═══════════════════════════════════════════════════════
    // PRODUCT QUESTION — Show products as options (from filtered pool)
    // ═══════════════════════════════════════════════════════

    private function sendProductQuestion(AiChatSession $session, string $instanceName, ChatflowStep $step, $steps): ?string
    {
        $products = $this->getFilteredProducts($session);

        if ($products->isEmpty()) {
            Log::info('ListBot: No products after filter, skipping product step');
            $this->markStepSkipped($session, $step);
            $this->advanceChatflow($session, $steps);
            $session->save();
            return $this->sendNextChatflowQuestion($session, $instanceName, $steps);
        }

        // Auto-select if single product
        if ($products->count() === 1) {
            $product = $products->first();
            Log::info('ListBot: Auto-selecting single product', ['product_id' => $product->id]);
            return $this->handleProductSelection($session, $instanceName, $product->id, $steps);
        }

        // Build product list menu
        $sections = WhatsAppService::buildProductSections($products, fn($p) => $this->getProductDisplayName($p));
        $question = $step->question_text ?: 'Select Product:';
        $buttonText = Setting::getValue('list_bot', 'menu_button_text', '🛍 Menu', $this->companyId);

        $sent = $this->whatsApp->sendList(
            $instanceName,
            $session->phone_number,
            mb_substr($question, 0, 60),
            $question . $this->resetFooter(),
            mb_substr($buttonText, 0, 20),
            $sections,
            'Tap to select'
        );

        if (is_array($sent) && !empty($sent['rowMap'])) {
            $session->setAnswer('_text_menu_rowMap', $sent['rowMap']);
        }

        $session->current_step_id = $step->id;
        $session->save();

        Log::info('ListBot: Sent product question', ['products' => $products->count()]);
        return null;
    }

    // ═══════════════════════════════════════════════════════
    // COMBO QUESTION — Show combo values from current product
    // ═══════════════════════════════════════════════════════

    private function sendComboQuestion(AiChatSession $session, string $instanceName, ChatflowStep $step, $steps): ?string
    {
        $column = $step->linkedColumn;
        if (!$column) {
            Log::warning('ListBot: Combo step has no linked column, skipping', ['step_id' => $step->id]);
            $this->markStepSkipped($session, $step);
            $this->advanceChatflow($session, $steps);
            $session->save();
            return $this->sendNextChatflowQuestion($session, $instanceName, $steps);
        }

        // Get values from filtered products (or current product if set)
        $comboValues = $this->getValuesForColumn($session, $column, 'combo');

        Log::info('ListBot: Combo question data', [
            'column' => $column->name,
            'values_count' => count($comboValues),
            'values' => array_slice($comboValues, 0, 5),
        ]);

        // No data → skip
        if (empty($comboValues)) {
            Log::info('ListBot: No combo data, skipping', ['column' => $column->name]);
            $this->markStepSkipped($session, $step);
            $this->advanceChatflow($session, $steps);
            $session->save();
            return $this->sendNextChatflowQuestion($session, $instanceName, $steps);
        }

        // Single value → auto-select (show confirmation but don't ask)
        if (count($comboValues) === 1) {
            Log::info('ListBot: Auto-selecting single combo value', ['column' => $column->name, 'value' => $comboValues[0]]);
            return $this->handleComboSelection($session, $instanceName, $column->slug, $comboValues[0], $steps);
        }

        // Multiple values → send menu
        $sections = WhatsAppService::buildOptionSections($comboValues, $column->name, "combo_{$column->slug}_");
        $question = $step->question_text ?: "Select {$column->name}:";
        $buttonText = Setting::getValue('list_bot', 'menu_button_text', '🛍 Menu', $this->companyId);

        $sent = $this->whatsApp->sendList(
            $instanceName,
            $session->phone_number,
            mb_substr($question, 0, 60),
            $question . $this->resetFooter(),
            mb_substr($buttonText, 0, 20),
            $sections,
            'Tap your choice'
        );

        if (is_array($sent) && !empty($sent['rowMap'])) {
            $session->setAnswer('_text_menu_rowMap', $sent['rowMap']);
            $session->save();
        }

        return null;
    }

    // ═══════════════════════════════════════════════════════
    // COLUMN QUESTION — Show column filter values from filtered pool
    // ═══════════════════════════════════════════════════════

    private function sendColumnQuestion(AiChatSession $session, string $instanceName, ChatflowStep $step, $steps): ?string
    {
        $column = $step->linkedColumn;
        $colId = $step->linked_column_id;

        if (!$column || !$colId) {
            $this->markStepSkipped($session, $step);
            $this->advanceChatflow($session, $steps);
            $session->save();
            return $this->sendNextChatflowQuestion($session, $instanceName, $steps);
        }

        // Get values from filtered products
        $valuesList = $this->getValuesForColumn($session, $column, 'column');

        Log::info('ListBot: Column question data', [
            'column' => $column->name,
            'values_count' => count($valuesList),
            'values' => array_slice($valuesList, 0, 5),
        ]);

        // No data → skip
        if (empty($valuesList)) {
            Log::info('ListBot: No column data, skipping', ['column' => $column->name]);
            $this->markStepSkipped($session, $step);
            $this->advanceChatflow($session, $steps);
            $session->save();
            return $this->sendNextChatflowQuestion($session, $instanceName, $steps);
        }

        // Single value → auto-select
        if (count($valuesList) === 1) {
            Log::info('ListBot: Auto-selecting single column value', ['column' => $column->name, 'value' => $valuesList[0]]);
            return $this->handleColumnSelection($session, $instanceName, $colId, $valuesList[0], $steps);
        }

        // Multiple values → send menu
        $sections = WhatsAppService::buildOptionSections($valuesList, $column->name, "col_{$colId}_");
        $question = $step->question_text ?: "Select {$column->name}:";
        $buttonText = Setting::getValue('list_bot', 'menu_button_text', '🛍 Menu', $this->companyId);

        $sent = $this->whatsApp->sendList(
            $instanceName,
            $session->phone_number,
            mb_substr($question, 0, 60),
            $question . $this->resetFooter(),
            mb_substr($buttonText, 0, 20),
            $sections,
            'Tap your choice'
        );

        if (is_array($sent) && !empty($sent['rowMap'])) {
            $session->setAnswer('_text_menu_rowMap', $sent['rowMap']);
            $session->save();
        }

        return null;
    }

    // ═══════════════════════════════════════════════════════
    // NORMALIZE COLUMN VALUE — Case + spacing normalization
    // ═══════════════════════════════════════════════════════

    /**
     * Normalize a column value for comparison.
     * "Door Handle" = "door handle" = "Door  Handle" = " Door Handle "
     * But "Door" ≠ "Door Handle" (different words = different entry)
     */
    private function normalizeColumnValue(string $val): string
    {
        $val = trim($val);
        $val = mb_strtolower($val);
        $val = preg_replace('/\s+/', ' ', $val); // collapse multiple spaces
        return $val;
    }

    // ═══════════════════════════════════════════════════════
    // CORE: GET FILTERED PRODUCTS — Progressive filter engine
    // ═══════════════════════════════════════════════════════

    private function getFilteredProducts(AiChatSession $session): Collection
    {
        $answers = $session->collected_answers ?? [];
        $query = Product::where('company_id', $this->companyId)->where('status', 'active');

        // Filter by category if set
        if (isset($answers['category_ids'])) {
            $query->whereIn('category_id', $answers['category_ids']);
        } elseif (isset($answers['category_id'])) {
            $query->where('category_id', $answers['category_id']);
        }

        // Apply column filters (case-insensitive + whitespace-normalized + category fallback)
        foreach ($answers as $key => $val) {
            if (str_starts_with($key, 'column_filter_')) {
                $colId = str_replace('column_filter_', '', $key);
                $normalizedVal = $this->normalizeColumnValue($val);
                $query->where(function ($q) use ($colId, $val, $normalizedVal) {
                    // Match products that have the custom value
                    $q->whereHas('customValues', function ($subQ) use ($colId, $val, $normalizedVal) {
                        $subQ->where('column_id', $colId)
                            ->where(function ($innerQ) use ($val, $normalizedVal) {
                                $innerQ->whereRaw("LOWER(TRIM(REPLACE(value, '  ', ' '))) = ?", [$normalizedVal])
                                    ->orWhereRaw("LOWER(value) LIKE ?", ['%"' . $normalizedVal . '"%']);
                            });
                    })
                    // Fallback: match products with NO custom value for this column
                    // but whose category name matches the selected value
                    ->orWhere(function ($fallbackQ) use ($colId, $normalizedVal) {
                        $fallbackQ->whereDoesntHave('customValues', function ($subQ) use ($colId) {
                            $subQ->where('column_id', $colId)
                                 ->where('value', '!=', '')
                                 ->whereNotNull('value');
                        })
                        ->whereHas('category', function ($catQ) use ($normalizedVal) {
                            $catQ->whereRaw("LOWER(TRIM(name)) = ?", [$normalizedVal]);
                        });
                    });
                });
            }
        }

        // Apply combo filters (slug-based answers)
        $steps = ChatflowStep::with('linkedColumn')
            ->where('company_id', $this->companyId)
            ->where('step_type', 'ask_combo')
            ->get();

        foreach ($steps as $step) {
            if ($step->linkedColumn && isset($answers[$step->linkedColumn->slug])) {
                $slug = $step->linkedColumn->slug;
                $colId = $step->linked_column_id;
                $val = $answers[$slug];

                $query->where(function ($q) use ($colId, $val) {
                    // Check product_combos.selected_values contains this value
                    $q->whereHas('combos', function ($subQ) use ($colId, $val) {
                        $subQ->where('column_id', $colId)
                            ->where('selected_values', 'LIKE', '%"' . $val . '"%');
                    })
                    // Also check custom_values
                    ->orWhereHas('customValues', function ($subQ) use ($colId, $val) {
                        $subQ->where('column_id', $colId)
                            ->where(function ($innerQ) use ($val) {
                                $innerQ->where('value', $val)
                                    ->orWhere('value', 'LIKE', '%"' . $val . '"%');
                            });
                    });
                });
            }
        }

        return $query->with(['customValues', 'category', 'combos.column'])->orderBy('name')->get();
    }

    // ═══════════════════════════════════════════════════════
    // GET VALUES FOR COLUMN — From filtered products or current product
    // ═══════════════════════════════════════════════════════

    private function getValuesForColumn(AiChatSession $session, CatalogueCustomColumn $column, string $type): array
    {
        $answers = $session->collected_answers ?? [];
        $productId = $answers['product_id'] ?? null;
        $colId = $column->id;

        // If we have a specific product, get values from THAT product only
        if ($productId) {
            $product = Product::with(['combos.column', 'customValues'])->find($productId);
            if (!$product) return [];

            if ($type === 'combo') {
                // Strategy 1: ProductCombo.selected_values
                $combo = $product->combos->firstWhere('column_id', $colId);
                $values = $combo ? ($combo->selected_values ?? []) : [];

                // Strategy 2: Product custom value
                if (empty($values)) {
                    $customVal = $product->customValues->firstWhere('column_id', $colId);
                    if ($customVal && !empty($customVal->value)) {
                        $decoded = json_decode($customVal->value, true);
                        if (is_array($decoded) && count($decoded) > 0) {
                            $values = array_values(array_filter($decoded));
                        } elseif (!empty($customVal->value)) {
                            $values = [$customVal->value];
                        }
                    }
                }

                return $values;
            } else {
                // Column type — get from custom values
                $customVal = $product->customValues->firstWhere('column_id', $colId);
                if (!$customVal || empty($customVal->value)) return [];

                $decoded = json_decode($customVal->value, true);
                if (is_array($decoded)) {
                    return array_values(array_filter($decoded));
                }
                return [$customVal->value];
            }
        }

        // No specific product yet — get distinct values from FILTERED product pool
        $filteredProducts = $this->getFilteredProducts($session);
        if ($filteredProducts->isEmpty()) return [];

        $availableValues = []; // normalized_key => display_value (first-seen, properly cased)
        foreach ($filteredProducts as $p) {
            if ($type === 'combo') {
                // Check combos first
                $combo = $p->combos->firstWhere('column_id', $colId);
                if ($combo && !empty($combo->selected_values)) {
                    foreach ($combo->selected_values as $v) {
                        if (!empty(trim($v))) {
                            $normalized = $this->normalizeColumnValue($v);
                            if (!isset($availableValues[$normalized])) {
                                $availableValues[$normalized] = trim($v);
                            }
                        }
                    }
                }
            }

            // Always check custom values
            $rawVal = $p->customValues->firstWhere('column_id', $colId)?->value;
            if (!empty($rawVal)) {
                $decoded = json_decode($rawVal, true);
                $values = is_array($decoded) ? $decoded : [$rawVal];
                foreach ($values as $v) {
                    if (empty(trim((string)$v))) continue;
                    $normalized = $this->normalizeColumnValue($v);
                    if (!isset($availableValues[$normalized])) {
                        $availableValues[$normalized] = trim($v); // Keep first-seen display version
                    }
                }
            } elseif ($type === 'column' && $p->category && !empty($p->category->name)) {
                // Fallback: product has NO custom value for this column
                // Use category name so the product is never invisible
                $catName = trim($p->category->name);
                $normalized = $this->normalizeColumnValue($catName);
                if (!isset($availableValues[$normalized])) {
                    $availableValues[$normalized] = $catName;
                }
            }
        }

        $valuesList = array_values($availableValues);
        sort($valuesList);
        return $valuesList;
    }

    // ═══════════════════════════════════════════════════════
    // CHECK AND QUEUE PRODUCTS — After each answer
    // ═══════════════════════════════════════════════════════

    private function checkAndQueueProducts(AiChatSession $session): void
    {
        $answers = $session->collected_answers ?? [];

        // Only check if no product is set yet
        if (isset($answers['product_id'])) return;

        $filtered = $this->getFilteredProducts($session);

        if ($filtered->count() === 1) {
            // Single product found — set it directly
            $product = $filtered->first();
            $session->setAnswer('product_id', $product->id);
            $session->setAnswer('product_name', $this->getProductDisplayName($product));
            $this->attachProductToLead($session, $product);
            Log::info('ListBot: Filter narrowed to single product', ['product_id' => $product->id]);
        }
    }

    // ═══════════════════════════════════════════════════════
    // START PRODUCT CHATFLOW — For queued products
    // ═══════════════════════════════════════════════════════

    private function startProductChatflow(AiChatSession $session, string $instanceName, int $productId, $steps): ?string
    {
        $product = Product::with(['combos.column', 'customValues', 'category'])->find($productId);
        if (!$product) {
            Log::warning('ListBot: Queued product not found', ['product_id' => $productId]);
            return $this->sendNextChatflowQuestion($session, $instanceName, $steps);
        }

        // Keep category + personal info answers, clear product-specific ones
        $answers = $session->collected_answers ?? [];
        $keepPrefixes = ['category_', '_product_queue', '_completed_products'];
        $personalKeys = ['name', 'email', 'city', 'state', 'phone', 'address'];
        $newAnswers = [];

        foreach ($answers as $k => $v) {
            // Keep personal fields
            if (in_array($k, $personalKeys)) { $newAnswers[$k] = $v; continue; }
            // Keep category
            if (str_starts_with($k, 'category_')) { $newAnswers[$k] = $v; continue; }
            // Keep internal queue/completed keys
            if ($k === '_product_queue' || $k === '_completed_products') { $newAnswers[$k] = $v; continue; }
            // Keep skipped step ids
            if ($k === '_skipped_step_ids') { continue; } // Clear skipped — they apply to previous product
            // Drop everything else (column filters, combo answers, product_id, etc.)
        }

        // Set new product
        $newAnswers['product_id'] = $productId;
        $newAnswers['product_name'] = $this->getProductDisplayName($product);

        $session->collected_answers = $newAnswers;
        $session->conversation_state = 'in_chatflow';
        $session->current_step_id = null;

        // Advance to first unanswered step (skipping category + product steps that are already done)
        $this->advanceChatflow($session, $steps);
        $session->save();

        $this->attachProductToLead($session, $product);

        $this->traceNode($session->id, 'QueueNextProduct', 'routing', 'success',
            ['product_id' => $productId, 'product_name' => $this->getProductDisplayName($product)],
            ['remaining_in_queue' => count($session->getAnswer('_product_queue') ?? [])]);

        Log::info('ListBot: Starting chatflow for queued product', [
            'session' => $session->id,
            'product_id' => $productId,
            'product_name' => $this->getProductDisplayName($product),
        ]);

        return $this->sendNextChatflowQuestion($session, $instanceName, $steps);
    }

    // ═══════════════════════════════════════════════════════
    // SUMMARY STEP
    // ═══════════════════════════════════════════════════════

    private function handleSummaryStep(AiChatSession $session, string $instanceName): string
    {
        // Guarantee that the latest collected answers (including auto-advanced steps) 
        // are saved to the Quote and Lead records before finalizing.
        $this->updateQuoteIfNeeded($session);

        $answers = $session->collected_answers ?? [];
        $msg = "📋 *Order Summary:*\n\n";

        $productId = $answers['product_id'] ?? null;
        $product = $productId ? Product::with(['combos.column', 'customValues', 'category'])->find($productId) : null;

        if ($product) {
            $msg .= "🛍️ *Product:* " . $this->getProductDisplayName($product) . "\n";

            if ($product->category) {
                $msg .= "📂 *Category:* {$product->category->name}\n";
            }

            // Show ALL product data — every column value (selected + auto + from product)
            $visibleColumns = $this->getAiVisibleColumns();
            foreach ($visibleColumns as $col) {
                if ($col->is_category || $col->is_title) continue;

                $value = null;

                // Check session answers first
                if (isset($answers["column_filter_{$col->id}"])) {
                    $value = $answers["column_filter_{$col->id}"];
                } elseif (isset($answers[$col->slug])) {
                    $value = $answers[$col->slug];
                }

                // If not in answers, get from product data
                if (!$value) {
                    $customVal = $product->customValues->firstWhere('column_id', $col->id);
                    if ($customVal && !empty($customVal->value)) {
                        $decoded = json_decode($customVal->value, true);
                        $value = is_array($decoded) ? implode(', ', $decoded) : $customVal->value;
                    }
                }

                // Check combo values
                if (!$value && $col->is_combo) {
                    $combo = $product->combos->firstWhere('column_id', $col->id);
                    if ($combo && !empty($combo->selected_values)) {
                        $value = implode(', ', $combo->selected_values);
                    }
                }

                if ($value) {
                    $msg .= "📌 *{$col->name}:* {$value}\n";
                }
            }

            // Show sale price
            if ($product->sale_price > 0) {
                $msg .= "\n💰 *Price:* ₹" . number_format($product->sale_price / 100, 2) . "\n";
            }
        }

        // Show custom answers (name, phone, address, etc.)
        $internalKeys = ['product_id', 'product_name', 'category_id', 'category_ids', 'category_name', 'selected_product_group', 'product_price'];
        foreach ($answers as $key => $val) {
            if (in_array($key, $internalKeys)) continue;
            if (str_starts_with($key, 'column_filter_')) continue;
            if (str_starts_with($key, '_')) continue;
            if ($product && $product->combos->pluck('column.slug')->contains($key)) continue;
            if (is_array($val)) continue;
            $msg .= "📝 *" . ucfirst(str_replace('_', ' ', $key)) . ":* {$val}\n";
        }

        $this->whatsApp->sendText($instanceName, $session->phone_number, $msg);

        $this->traceNode($session->id, 'SummaryGenerated', 'delivery', 'success',
            ['product_id' => $productId, 'product_name' => $answers['product_name'] ?? 'Unknown'],
            ['lead_id' => $session->lead_id, 'quote_id' => $session->quote_id, 'completed_count' => count($answers['_completed_products'] ?? []) + 1]);

        // Save this product to completed list
        $completed = $answers['_completed_products'] ?? [];
        $completed[] = [
            'product_id' => $productId,
            'product_name' => $answers['product_name'] ?? 'Unknown',
        ];
        $session->setAnswer('_completed_products', $completed);

        // Check if there are products remaining in the multi_product_queue
        $queue = $session->getAnswer('_multi_product_queue') ?? [];
        if (!empty($queue)) {
            $nextParsed = array_shift($queue);
            $session->setAnswer('_multi_product_queue', $queue);
            $session->save();

            $waitMsg = "⏳ _Processing next product into your order..._";
            $this->whatsApp->sendText($instanceName, $session->phone_number, $waitMsg);

            $this->resetForNewProduct($session, $steps);
            $session->save();

            // Directly process the queued selection
            return $this->handleListSelection($session, $instanceName, $nextParsed, $steps);
        }

        $addMoreMsg = "";
        $addMoreMsg .= "🛒 *Kya aap aur product add karna chahte hain?*\n\n";
        $addMoreMsg .= "1️⃣ ✅ Haan, aur product add karo\n";
        $addMoreMsg .= "2️⃣ ❌ Nahi, order complete karo";
        $addMoreMsg .= $this->resetFooter();

        $this->whatsApp->sendText($instanceName, $session->phone_number, $addMoreMsg);

        // Set state to awaiting add-more decision
        $session->conversation_state = 'awaiting_add_more';
        $session->save();

        return $msg;
    }

    // ═══════════════════════════════════════════════════════
    // RESET FOR NEW PRODUCT (Add More)
    // ═══════════════════════════════════════════════════════

    private function resetForNewProduct(AiChatSession $session, $steps): void
    {
        $answers = $session->collected_answers ?? [];
        $personalKeys = ['name', 'email', 'city', 'state', 'phone', 'address', 'category_id', 'category_ids'];
        $newAnswers = [];

        foreach ($answers as $k => $v) {
            if (in_array($k, $personalKeys)) $newAnswers[$k] = $v;
            if ($k === '_completed_products' || $k === '_multi_product_queue') $newAnswers[$k] = $v;
        }

        $session->current_step_id = null;
        $session->conversation_state = 'in_chatflow';
        $session->collected_answers = $newAnswers;
        
        // Find the first step again
        $this->advanceChatflow($session, $steps);
        
        $this->traceNode($session->id, 'AddAnotherProduct', 'routing', 'success',
            ['previous_completed' => count($newAnswers['_completed_products'] ?? [])],
            ['action' => 'reset_filters_keep_completed']);
    }

    // ═══════════════════════════════════════════════════════
    // RESEND CURRENT MENU
    // ═══════════════════════════════════════════════════════

    private function resendCurrentMenu(AiChatSession $session, string $instanceName, $steps): ?string
    {
        $msg = "Please select from the menu below 👇" . $this->resetFooter();
        $this->whatsApp->sendText($instanceName, $session->phone_number, $msg);
        return $this->sendNextChatflowQuestion($session, $instanceName, $steps);
    }

    // ═══════════════════════════════════════════════════════
    // SHARED HELPERS
    // ═══════════════════════════════════════════════════════

    /**
     * Advance to the next unanswered chatflow step.
     */
    private function advanceChatflow(AiChatSession $session, $steps): void
    {
        $answers = $session->collected_answers ?? [];
        $nextStep = null;

        foreach ($steps as $step) {
            $isAnswered = false;

            // Check if step was explicitly skipped (no data)
            $skippedIds = $answers['_skipped_step_ids'] ?? [];
            if (in_array($step->id, $skippedIds)) {
                $isAnswered = true;
            } elseif ($step->step_type === 'ask_combo' && $step->linkedColumn) {
                if (isset($answers[$step->linkedColumn->slug])) {
                    $isAnswered = true;
                }
            } elseif (in_array($step->step_type, ['ask_product', 'ask_unique_column'])) {
                if (isset($answers['product_id'])) {
                    $isAnswered = true;
                }
            } elseif ($step->step_type === 'ask_column' && $step->linkedColumn) {
                if (isset($answers['column_filter_' . $step->linked_column_id])) {
                    $isAnswered = true;
                }
            } elseif ($step->step_type === 'ask_category') {
                if (isset($answers['category_id'])) {
                    $isAnswered = true;
                }
            } elseif (in_array($step->step_type, ['ask_custom', 'ask_optional'])) {
                if (isset($answers[$step->field_key])) {
                    $isAnswered = true;
                } elseif ($step->isOptionalStep() && isset(($session->optional_asked ?? [])[$step->field_key])) {
                    $isAnswered = true;
                }
            }

            if (!$isAnswered && $step->step_type !== 'send_summary') {
                $nextStep = $step;
                break;
            }
        }

        if (!$nextStep && $steps->contains('step_type', 'send_summary')) {
            $nextStep = $steps->firstWhere('step_type', 'send_summary');
        }

        if ($nextStep) {
            $session->current_step_id = $nextStep->id;
            $session->current_step_retries = 0;
            $session->conversation_state = 'in_chatflow';
        } else {
            $session->current_step_id = null;
            $session->conversation_state = 'completed';
            $session->update(['status' => 'completed']);
        }
    }



    /**
     * Attach product to lead and create/update quote.
     */
    private function attachProductToLead(AiChatSession $session, Product $product): void
    {
        try {
            // Step 1: Attach product to lead
            if ($session->lead_id) {
                $lead = Lead::find($session->lead_id);
                if ($lead) {
                    if (!$lead->products()->where('product_id', $product->id)->exists()) {
                        $lead->products()->attach($product->id, [
                            'quantity' => 1, 
                            'price' => $product->sale_price,
                            'description' => \Illuminate\Support\Str::limit($product->getDynamicDescription($session->collected_answers ?? [], true), 250)
                        ]);
                    }
                    $lead->update(['product_name' => $this->getProductDisplayName($product)]);
                }
            }

            // Step 2: Create or update Quote
            $existingQuote = $session->quote_id ? Quote::find($session->quote_id) : null;
            if (!$existingQuote) {
                $company = \App\Models\Company::find($this->companyId);
                if (!$company) {
                    Log::error('ListBot: Company not found for quote creation', ['company_id' => $this->companyId]);
                    return;
                }

                // Build quote data — exclude client_id entirely so DB default handles it
                $quoteData = [
                    'company_id' => $this->companyId,
                    'lead_id' => $session->lead_id,
                    'created_by_user_id' => $this->userId,
                    'quote_no' => Quote::generateQuoteNumber($company),
                    'date' => now(),
                    'valid_till' => now()->addDays(30),
                    'subtotal' => $product->sale_price ?? 0,
                    'discount' => 0,
                    'gst_total' => 0,
                    'grand_total' => $product->sale_price ?? 0,
                    'status' => 'draft',
                ];

                // Try with client_id = null first (if migration was run, this works)
                // If it fails due to NOT NULL constraint, retry with a fallback client
                try {
                    $quoteData['client_id'] = null;
                    $quote = Quote::create($quoteData);
                } catch (\Illuminate\Database\QueryException $qe) {
                    // client_id NOT NULL constraint failed — migration not applied
                    Log::warning('ListBot: client_id null failed, creating fallback client', [
                        'error' => $qe->getMessage(),
                    ]);
                    
                    // Create a minimal client from lead data for this quote
                    $lead = $session->lead_id ? Lead::find($session->lead_id) : null;
                    $client = \App\Models\Client::create([
                        'company_id' => $this->companyId,
                        'contact_name' => $lead->name ?? $session->phone_number,
                        'phone' => $lead->phone ?? $session->phone_number,
                        'business_name' => $lead->name ?? ('WhatsApp Lead ' . $session->phone_number),
                        'status' => 'active',
                    ]);
                    $quoteData['client_id'] = $client->id;
                    $quoteData['quote_no'] = Quote::generateQuoteNumber($company); // Regenerate to avoid unique collision
                    $quote = Quote::create($quoteData);
                }

                // Create the QuoteItem
                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'product_id' => $product->id,
                    'product_name' => $this->getProductDisplayName($product),
                    'description' => \Illuminate\Support\Str::limit($product->getDynamicDescription($session->collected_answers ?? [], true), 250),
                    'hsn_code' => $product->hsn_code,
                    'qty' => 1,
                    'rate' => $product->sale_price ?? 0,
                    'unit' => $product->unit ?? 'nos',
                    'unit_price' => $product->sale_price ?? 0,
                    'discount' => 0,
                    'purchase_amount' => 0,
                    'gst_percent' => $product->gst_percent ?? 0,
                    'sort_order' => 1,
                ]);
                $session->quote_id = $quote->id;
                $session->save();

                $this->traceNode($session->id, 'QuoteCreated', 'database', 'success',
                    ['product_id' => $product->id, 'product_name' => $this->getProductDisplayName($product)],
                    ['quote_id' => $quote->id, 'quote_no' => $quote->quote_no, 'grand_total' => $product->sale_price]);
            } else {
                // Add to existing quote
                $existingItem = QuoteItem::where('quote_id', $existingQuote->id)
                    ->where('product_id', $product->id)
                    ->first();
                if (!$existingItem) {
                    $maxSort = QuoteItem::where('quote_id', $existingQuote->id)->max('sort_order') ?? 0;
                    QuoteItem::create([
                        'quote_id' => $existingQuote->id,
                        'product_id' => $product->id,
                        'product_name' => $this->getProductDisplayName($product),
                        'description' => \Illuminate\Support\Str::limit($product->getDynamicDescription($session->collected_answers ?? [], true), 250),
                        'hsn_code' => $product->hsn_code,
                        'qty' => 1,
                        'rate' => $product->sale_price ?? 0,
                        'unit' => $product->unit ?? 'nos',
                        'unit_price' => $product->sale_price ?? 0,
                        'discount' => 0,
                        'purchase_amount' => 0,
                        'gst_percent' => $product->gst_percent ?? 0,
                        'sort_order' => $maxSort + 1,
                    ]);
                    $existingQuote->recalculateTotals();

                    $this->traceNode($session->id, 'QuoteItemAdded', 'database', 'success',
                        ['product_id' => $product->id, 'product_name' => $this->getProductDisplayName($product), 'quote_id' => $existingQuote->id],
                        ['sort_order' => $maxSort + 1, 'new_total' => $existingQuote->grand_total]);
                }
            }
        } catch (\Exception $e) {
            Log::error('ListBot: Error attaching product to quote', [
                'session_id' => $session->id,
                'product_id' => $product->id,
                'lead_id' => $session->lead_id,
                'quote_id' => $session->quote_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Update quote description and variation after an answer.
     */
    private function updateQuoteIfNeeded(AiChatSession $session): void
    {
        $productId = $session->getAnswer('product_id');
        if (!$productId || !$session->quote_id) return;

        $product = Product::with(['combos.column', 'activeVariations', 'customValues'])->find($productId);
        if (!$product) return;

        // Update description
        $sessionAnswers = $session->collected_answers ?? [];
        $fullDesc = $product->getDynamicDescription($sessionAnswers);
        if (!empty($fullDesc)) {
            $quoteItem = QuoteItem::where('quote_id', $session->quote_id)
                ->where('product_id', $product->id)
                ->first();
            if ($quoteItem) {
                $quoteItem->update(['description' => \Illuminate\Support\Str::limit($fullDesc, 250)]);
            }

            if ($session->lead_id) {
                $lead = Lead::find($session->lead_id);
                if ($lead && $lead->products()->where('product_id', $product->id)->exists()) {
                    $lead->products()->updateExistingPivot($product->id, ['description' => $fullDesc]);
                }
            }
        }

        // Update variation if all combo values are set
        $allSelected = true;
        $combination = [];
        foreach ($product->combos as $combo) {
            $slug = $combo->column->slug;
            $val = $session->getAnswer($slug);
            if (!$val) { $allSelected = false; break; }
            $combination[$slug] = $val;
        }

        if ($allSelected && !empty($combination)) {
            $key = ProductVariation::generateKey($combination);
            $variation = ProductVariation::where('product_id', $product->id)
                ->where('combination_key', $key)
                ->where('status', 'active')
                ->first();

            if ($variation) {
                $quoteItem = QuoteItem::where('quote_id', $session->quote_id)
                    ->where('product_id', $product->id)
                    ->first();
                if ($quoteItem) {
                    $quoteItem->update([
                        'variation_id' => $variation->id,
                        'selected_combination' => $combination,
                        'rate' => $variation->price,
                        'unit_price' => $variation->price,
                    ]);
                    $quote = Quote::find($session->quote_id);
                    $quote?->recalculateTotals();

                    $this->traceNode($session->id, 'VariationMatched', 'database', 'success',
                        ['combination_key' => $key, 'combination' => $combination],
                        ['variation_id' => $variation->id, 'price' => $variation->price, 'new_total' => $quote?->grand_total]);
                }
            }
        }
    }

    private function getProductDisplayName(Product $product): string
    {
        if (!$this->uniqueColumnLoaded) {
            $this->uniqueColumn = CatalogueCustomColumn::where('company_id', $this->companyId)
                ->where('is_unique', true)
                ->first();
            $this->uniqueColumnLoaded = true;
        }

        if ($this->uniqueColumn) {
            $cv = $product->customValues->firstWhere('column_id', $this->uniqueColumn->id);
            $uniqueVal = $cv ? (json_decode($cv->value, true) ?: $cv->value) : null;
            if (is_array($uniqueVal)) $uniqueVal = implode(', ', $uniqueVal);

            if ($product->category && !empty($product->category->name)) {
                $base = $product->category->name;
                if ($uniqueVal) {
                    return trim($base) . ' (' . trim($uniqueVal) . ')';
                }
                return trim($base);
            }

            return $uniqueVal ?: $product->name;
        }

        return $product->name;
    }

    private function getAiVisibleColumns()
    {
        if ($this->aiVisibleColumns === null) {
            $this->aiVisibleColumns = CatalogueCustomColumn::where('company_id', $this->companyId)
                ->where('show_in_ai', true)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        }
        return $this->aiVisibleColumns;
    }

    /**
     * Group wrongly-created categories by their text prefix.
     */
    private function groupCategoriesByPrefix($categories): ?\Illuminate\Support\Collection
    {
        $groups = [];
        $catIdMap = [];

        foreach ($categories as $cat) {
            $baseName = preg_replace('/[\s\-_]*[\d]+[\s\-_]*$/', '', trim($cat->name));
            $baseName = trim($baseName);

            if (empty($baseName)) {
                $baseName = $cat->name;
            }

            if (!isset($groups[$baseName])) {
                $groups[$baseName] = 0;
                $catIdMap[$baseName] = [];
            }
            $groups[$baseName] += $cat->products_count ?? 0;
            $catIdMap[$baseName][] = $cat->id;
        }

        if (count($groups) >= count($categories) * 0.8) {
            return null;
        }

        $result = collect();
        foreach ($groups as $name => $count) {
            $firstCatId = $catIdMap[$name][0] ?? 0;
            $virtual = new \stdClass();
            $virtual->id = $firstCatId;
            $virtual->name = $name;
            $virtual->company_id = $this->companyId;
            $virtual->products_count = $count;
            $virtual->_grouped_cat_ids = $catIdMap[$name];
            $result->push($virtual);
        }

        return $result->sortBy('name')->values();
    }

    private function getCategoryFieldLabel(): string
    {
        $catCol = CatalogueCustomColumn::where('company_id', $this->companyId)
            ->where('is_category', true)
            ->where('is_active', true)
            ->first();
        return $catCol ? $catCol->name : 'Category';
    }

    /**
     * Append reset footer to bot messages.
     */
    private function resetFooter(): string
    {
        return "\n\n↩️ Type *reset* to start over";
    }

    /**
     * Mark a chatflow step as skipped (no data for this product).
     */
    private function markStepSkipped(AiChatSession $session, ChatflowStep $step): void
    {
        $answers = $session->collected_answers ?? [];
        $skipped = $answers['_skipped_step_ids'] ?? [];
        if (!in_array($step->id, $skipped)) {
            $skipped[] = $step->id;
        }
        $answers['_skipped_step_ids'] = $skipped;
        $session->collected_answers = $answers;
        Log::info('ListBot: Step marked as skipped', ['step_id' => $step->id, 'step' => $step->name ?? $step->step_type]);
    }

    /**
     * Fuzzy match user text against rowMap option titles.
     */
    private function fuzzyMatchFromRowMap(string $userInput, array $rowMap): ?array
    {
        $input = mb_strtolower(trim($userInput));
        if (empty($input) || mb_strlen($input) < 2) {
            return null;
        }

        $bestMatch = null;
        $bestScore = 0;
        $threshold = 60;

        foreach ($rowMap as $num => $entry) {
            if (is_array($entry)) {
                $rowId = $entry['rowId'] ?? '';
                $title = $entry['title'] ?? '';
            } else {
                $rowId = $entry;
                $title = '';
            }

            if (empty($title)) continue;

            $optionLower = mb_strtolower(trim($title));
            $score = 0;

            // Exact match
            if ($input === $optionLower) {
                return ['rowId' => $rowId, 'title' => $title, 'score' => 100];
            }

            // Substring match
            if (str_contains($optionLower, $input) || str_contains($input, $optionLower)) {
                $score = 85;
            }

            // similar_text
            if ($score < $threshold) {
                similar_text($input, $optionLower, $similarPercent);
                $score = max($score, $similarPercent);
            }

            // Levenshtein distance
            if ($score < $threshold && mb_strlen($input) <= 50 && mb_strlen($optionLower) <= 50) {
                $distance = levenshtein($input, $optionLower);
                $maxLen = max(mb_strlen($input), mb_strlen($optionLower));
                if ($maxLen > 0) {
                    $levenScore = (1 - ($distance / $maxLen)) * 100;
                    $score = max($score, $levenScore);
                }
            }

            // Word-level matching
            if ($score < $threshold) {
                $inputWords = preg_split('/[\s_\-]+/', $input);
                $optionWords = preg_split('/[\s_\-]+/', $optionLower);
                $matchedWords = 0;
                foreach ($inputWords as $word) {
                    if (mb_strlen($word) < 2) continue;
                    foreach ($optionWords as $optWord) {
                        if (str_contains($optWord, $word) || str_contains($word, $optWord)) {
                            $matchedWords++;
                            break;
                        }
                    }
                }
                if (count($inputWords) > 0) {
                    $wordScore = ($matchedWords / count($inputWords)) * 90;
                    $score = max($score, $wordScore);
                }
            }

            if ($score > $bestScore && $score >= $threshold) {
                $bestScore = $score;
                $bestMatch = ['rowId' => $rowId, 'title' => $title, 'score' => round($score, 1)];
            }
        }

        return $bestMatch;
    }

    private function sendMediaToWhatsApp(AiChatSession $session, string $mediaPath, string $instanceName): void
    {
        try {
            $config = Setting::getValue('whatsapp', 'api_config', [], $this->companyId);
            if (empty($config['api_url']) || empty($config['api_key'])) return;

            $fullUrl = rtrim(config('app.url'), '/') . $mediaPath;
            $ext = strtolower(pathinfo($mediaPath, PATHINFO_EXTENSION));
            $isVideo = in_array($ext, ['mp4', 'mov', 'avi', 'webm']);
            $endpoint = $isVideo ? 'sendVideo' : 'sendImage';

            \Illuminate\Support\Facades\Http::withHeaders([
                'apikey' => $config['api_key'],
                'Content-Type' => 'application/json',
            ])->post("{$config['api_url']}/message/{$endpoint}/{$instanceName}", [
                'number' => WhatsAppService::formatPhone($session->phone_number),
                'mediaUrl' => $fullUrl,
            ]);
        } catch (\Exception $e) {
            Log::warning('ListBot: Media send failed - ' . $e->getMessage());
        }
    }
}
