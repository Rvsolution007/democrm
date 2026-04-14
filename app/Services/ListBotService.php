<?php

namespace App\Services;

use App\Models\AiChatSession;
use App\Models\AiChatMessage;
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

/**
 * List Bot Service — Zero AI, Pure Interactive List Menus
 *
 * Uses the SAME chatflow steps, session model, lead/quote creation as AIChatbotService
 * but with ZERO Gemini/AI calls. All selections are via WhatsApp Interactive Lists.
 *
 * Flow:
 * 1. User sends any message → Welcome + Category Menu
 * 2. User taps Category → Product Menu
 * 3. User taps Product → Create Lead/Quote → Chatflow Step Menu
 * 4. User taps Column/Combo option → Save → Next Step
 * 5. User types free text (ask_custom) → Save as-is → Next Step
 * 6. All steps done → Order Summary
 */
class ListBotService
{
    private int $companyId;
    private int $userId;
    private WhatsAppService $whatsApp;
    private ?CatalogueCustomColumn $uniqueColumn = null;
    private bool $uniqueColumnLoaded = false;
    private $aiVisibleColumns = null;

    public function __construct(int $companyId, int $userId)
    {
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->whatsApp = new WhatsAppService($companyId);

        Log::info('ListBot: Initialized', [
            'company_id' => $companyId,
            'user_id' => $userId,
            'active_products' => Product::where('company_id', $companyId)->where('status', 'active')->count(),
            'total_products' => Product::where('company_id', $companyId)->count(),
        ]);
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
        if (!$this->whatsApp->isConfigured()) {
            Log::error('ListBot: WhatsApp API not configured');
            return;
        }

        try {
            $session = AiChatSession::findOrCreateForPhone($this->companyId, $phone, $instanceName);
            $session->update(['last_message_at' => now()]);

            // Session expiry check
            $validDays = (int) Setting::getValue('ai_bot', 'session_valid_days', 10, $this->companyId);
            if (!$session->wasRecentlyCreated && $session->last_message_at) {
                $daysSinceLastMessage = $session->last_message_at->diffInDays(now());
                if ($daysSinceLastMessage >= $validDays) {
                    Log::info('ListBot: Session expired', ['session' => $session->id, 'days' => $daysSinceLastMessage]);
                    $session->update(['status' => 'expired']);
                    $session = AiChatSession::create([
                        'company_id' => $this->companyId,
                        'phone_number' => $phone,
                        'instance_name' => $instanceName,
                        'status' => 'active',
                        'last_message_at' => now(),
                    ]);
                }
            }

            // Save user message
            AiChatMessage::create([
                'session_id' => $session->id,
                'role' => 'user',
                'message' => $messageText,
                'message_type' => 'text',
            ]);

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
                Log::info('ListBot: Lead created', ['session' => $session->id, 'lead_id' => $lead->id]);
            }

            // Reset session if user types "hi", "hello", "menu", or "reset" to start over
            if (in_array(trim(strtolower($messageText)), ['hi', 'hello', 'menu', 'reset'])) {
                // Mark old session expired and create a new one
                $session->update(['status' => 'expired', 'is_completed' => true]);
                
                $session = AiChatSession::create([
                    'company_id' => $this->companyId,
                    'phone_number' => $phone,
                    'instance_name' => $instanceName,
                    'status' => 'active',
                    'conversation_state' => 'started',
                    'last_message_at' => now(),
                ]);
                
                Log::info('ListBot: Session reset by user', ['session' => $session->id]);
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
        }
    }

    // ═══════════════════════════════════════════════════════
    // MESSAGE ROUTING — based on session state + rowId
    // ═══════════════════════════════════════════════════════

    private function routeMessage(AiChatSession $session, string $instanceName, string $messageText, ?string $listRowId): ?string
    {
        $steps = ChatflowStep::with('linkedColumn')->where('company_id', $this->companyId)->orderBy('sort_order')->get();
        $currentStep = $session->current_step_id ? $steps->firstWhere('id', $session->current_step_id) : null;
        $answers = $session->collected_answers ?? [];

        // Parse rowId if present
        $parsed = $listRowId ? WhatsAppService::parseRowId($listRowId) : null;

        // ── CASE 0a: User tapped a list selection (rowId present) ──
        if ($parsed) {
            return $this->handleListSelection($session, $instanceName, $parsed, $steps);
        }

        // ── CASE 0b: Text fallback — user sent a number or typed a name to select ──
        $trimmedText = trim($messageText);
        if (isset($answers['_text_menu_rowMap'])) {
            $rowMap = $answers['_text_menu_rowMap'];

            // --- 0b-i: Exact number match ---
            if (ctype_digit($trimmedText) && isset($rowMap[$trimmedText])) {
                $entry = $rowMap[$trimmedText];
                $rowId = is_array($entry) ? ($entry['rowId'] ?? '') : $entry;
                $parsed = WhatsAppService::parseRowId($rowId);
                if ($parsed) {
                    Log::info('ListBot: Number mapped to rowId', ['number' => $trimmedText, 'rowId' => $rowId]);
                    unset($answers['_text_menu_rowMap']);
                    $session->collected_answers = $answers;
                    $session->save();
                    return $this->handleListSelection($session, $instanceName, $parsed, $steps);
                }
            }

            // --- 0b-ii: Fuzzy text matching on option titles ---
            $fuzzyMatch = $this->fuzzyMatchFromRowMap($trimmedText, $rowMap);
            if ($fuzzyMatch) {
                $rowId = $fuzzyMatch['rowId'];
                $parsed = WhatsAppService::parseRowId($rowId);
                if ($parsed) {
                    Log::info('ListBot: Fuzzy text matched', [
                        'input' => $trimmedText,
                        'matched' => $fuzzyMatch['title'],
                        'score' => $fuzzyMatch['score'],
                        'rowId' => $rowId,
                    ]);
                    unset($answers['_text_menu_rowMap']);
                    $session->collected_answers = $answers;
                    $session->save();
                    
                    return $this->handleListSelection($session, $instanceName, $parsed, $steps);
                }
            }

            // --- 0b-iii: Invalid input — resend with hint ---
            if (ctype_digit($trimmedText)) {
                $this->whatsApp->sendText($instanceName, $session->phone_number, "❌ Invalid option. Please reply with a valid number from the menu.");
            } else {
                $this->whatsApp->sendText($instanceName, $session->phone_number, "❌ Could not match \"{$trimmedText}\". Please reply with the *number* or type the *exact name*.");
            }
            return null;
        }

        // ── CASE 0c: Awaiting "add more product?" decision ──
        if ($session->conversation_state === 'awaiting_add_more') {
            $choice = trim(strtolower($trimmedText));
            if (in_array($choice, ['1', 'yes', 'haan', 'ha', 'y'])) {
                // User wants to add another product — clear product-specific answers, keep lead/quote
                $keepKeys = ['_text_menu_rowMap']; // internal keys to clear
                $productKeys = ['product_id', 'product_name', 'category_id', 'category_ids', 'category_name', 'selected_product_group'];
                $newAnswers = [];
                foreach ($answers as $k => $v) {
                    // Keep only custom answers (name, email etc.) — remove product/combo/column answers
                    if (in_array($k, $productKeys)) continue;
                    if (str_starts_with($k, 'column_filter_')) continue;
                    if (str_starts_with($k, '_')) continue;
                    // Check if key is a combo slug
                    $isComboSlug = ChatflowStep::where('company_id', $this->companyId)
                        ->where('step_type', 'ask_combo')
                        ->whereHas('linkedColumn', fn($q) => $q->where('slug', $k))
                        ->exists();
                    if ($isComboSlug) continue;
                    $newAnswers[$k] = $v;
                }
                $session->collected_answers = $newAnswers;
                $session->current_step_id = null;
                $session->conversation_state = 'started';
                $session->save();
                Log::info('ListBot: User wants to add more products', ['session' => $session->id]);
                return $this->sendWelcomeWithCategories($session, $instanceName);
            } else {
                // User is done — finalize
                $session->conversation_state = 'completed';
                $session->update(['status' => 'completed']);
                $finalMsg = "✅ Order complete! Our team will contact you shortly. 🙏";
                $this->whatsApp->sendText($instanceName, $session->phone_number, $finalMsg);
                return $finalMsg;
            }
        }

        // ── CASE 1: No product selected yet → send welcome/categories ──
        if (!isset($answers['product_id']) && !isset($answers['category_id'])) {
            return $this->sendWelcomeWithCategories($session, $instanceName);
        }

        // ── CASE 2: Category selected but no product → re-send product list ──
        if (isset($answers['category_id']) && !isset($answers['product_id'])) {
            // User typed text instead of tapping menu — re-send product list
            return $this->resendCurrentMenu($session, $instanceName, $steps);
        }


        // ── CASE 3: Product selected, in chatflow ──
        if ($currentStep) {
            // For ask_custom/ask_optional steps, accept text as-is
            if (in_array($currentStep->step_type, ['ask_custom', 'ask_optional'])) {
                return $this->handleCustomStep($session, $currentStep, $messageText, $steps, $instanceName);
            }

            // For other step types (ask_combo, ask_column), user should use menu
            return $this->resendCurrentMenu($session, $instanceName, $steps);
        }

        // ── CASE 4: Completed or no active step → restart ──
        return $this->sendWelcomeWithCategories($session, $instanceName);
    }

    /**
     * Fuzzy match user text against rowMap option titles.
     * Uses multiple strategies: exact match, substring, similar_text, levenshtein.
     * Returns best match if score >= 60%, null otherwise.
     */
    private function fuzzyMatchFromRowMap(string $userInput, array $rowMap): ?array
    {
        $input = mb_strtolower(trim($userInput));
        if (empty($input) || mb_strlen($input) < 2) {
            return null;
        }

        $bestMatch = null;
        $bestScore = 0;
        $threshold = 60; // minimum % match

        foreach ($rowMap as $num => $entry) {
            // Support both old format (string) and new format (array with rowId+title)
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

            // Strategy 1: Exact match (100%)
            if ($input === $optionLower) {
                return ['rowId' => $rowId, 'title' => $title, 'score' => 100];
            }

            // Strategy 2: Input is substring of option or vice versa (85%)
            if (str_contains($optionLower, $input) || str_contains($input, $optionLower)) {
                $score = 85;
            }

            // Strategy 3: similar_text percentage
            if ($score < $threshold) {
                similar_text($input, $optionLower, $similarPercent);
                $score = max($score, $similarPercent);
            }

            // Strategy 4: Levenshtein distance (for typos) — only for short strings
            if ($score < $threshold && mb_strlen($input) <= 50 && mb_strlen($optionLower) <= 50) {
                $distance = levenshtein($input, $optionLower);
                $maxLen = max(mb_strlen($input), mb_strlen($optionLower));
                if ($maxLen > 0) {
                    $levenScore = (1 - ($distance / $maxLen)) * 100;
                    $score = max($score, $levenScore);
                }
            }

            // Strategy 5: Word-level matching (check if key words match)
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

        Log::info('ListBot: Fuzzy match result', [
            'input' => $userInput,
            'bestMatch' => $bestMatch ? $bestMatch['title'] : 'none',
            'bestScore' => $bestScore,
        ]);

        return $bestMatch;
    }


    // ═══════════════════════════════════════════════════════
    // INTERACTIVE LIST SELECTION HANDLER
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
                return $this->sendWelcomeWithCategories($session, $instanceName);
        }
    }

    // ═══════════════════════════════════════════════════════
    // CATEGORY SELECTION
    // ═══════════════════════════════════════════════════════

    private function handleCategorySelection(AiChatSession $session, string $instanceName, int $categoryId, $steps): ?string
    {
        $category = \App\Models\Category::find($categoryId);
        if (!$category || $category->company_id !== $this->companyId) {
            return $this->sendWelcomeWithCategories($session, $instanceName);
        }

        // Check if this category is part of a grouped set (wrongly created 1:1 categories)
        // Find all categories that share the same prefix (e.g. "Conceal Handle 0010" → "Conceal Handle")
        $baseName = preg_replace('/[\s\-_]*[\d]+[\s\-_]*$/', '', trim($category->name));
        $baseName = trim($baseName);
        
        $relatedCatIds = [$categoryId];
        if (!empty($baseName) && $baseName !== $category->name) {
            // Find all categories with the same base name
            $relatedCats = \App\Models\Category::where('company_id', $this->companyId)
                ->where('status', 'active')
                ->where('name', 'LIKE', $baseName . '%')
                ->pluck('id')
                ->toArray();
            if (count($relatedCats) > 1) {
                $relatedCatIds = $relatedCats;
                Log::info('ListBot: Grouped category selected', [
                    'base_name' => $baseName,
                    'related_cat_count' => count($relatedCatIds),
                ]);
            }
        }

        // Save to session
        $session->setAnswer('category_id', $categoryId);
        $session->setAnswer('category_ids', $relatedCatIds);
        $session->setAnswer('category_name', $baseName ?: $category->name);
        $session->conversation_state = 'awaiting_product';
        $session->catalogue_sent = true;

        // Advance past category step
        $this->advanceChatflow($session, $steps);
        $session->save();

        Log::info('ListBot: Category selected', ['session' => $session->id, 'category' => $baseName ?: $category->name]);

        // Send product list for these categories
        return $this->sendProductList($session, $instanceName, $relatedCatIds);
    }

    // ═══════════════════════════════════════════════════════
    // PRODUCT SELECTION
    // ═══════════════════════════════════════════════════════

    private function handleProductSelection(AiChatSession $session, string $instanceName, int $productId, $steps): ?string
    {
        $product = Product::with(['combos.column', 'activeVariations', 'customValues'])->find($productId);
        if (!$product || $product->company_id !== $this->companyId) {
            return $this->sendWelcomeWithCategories($session, $instanceName);
        }

        // Save to session
        $displayName = $this->getProductDisplayName($product);
        $session->setAnswer('product_id', $productId);
        $session->setAnswer('product_name', $displayName);
        $session->conversation_state = 'product_selected';

        // Attach product to lead and create Quote (wrapped in try-catch to prevent flow termination on error)
        try {
            if ($session->lead_id) {
                $lead = Lead::find($session->lead_id);
                if ($lead) {
                    if (!$lead->products()->where('product_id', $productId)->exists()) {
                        $lead->products()->attach($productId, ['quantity' => 1, 'price' => $product->sale_price]);
                    }
                    $lead->update(['product_name' => $displayName]);
                }
            }

            // Create Quote
            if (!$session->quote_id) {
                $company = \App\Models\Company::find($this->companyId);
                $quote = Quote::create([
                    'company_id' => $this->companyId,
                    'lead_id' => $session->lead_id,
                    'created_by_user_id' => $this->userId,
                    'quote_no' => Quote::generateQuoteNumber($company),
                    'date' => now(),
                    'valid_till' => now()->addDays(30),
                    'subtotal' => $product->sale_price,
                    'discount' => 0,
                    'gst_total' => 0,
                    'grand_total' => $product->sale_price,
                    'status' => 'draft',
                ]);
                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'product_id' => $productId,
                    'product_name' => $displayName,
                    'description' => $product->getdynamicDescription($session->collected_answers ?? [], true),
                    'hsn_code' => $product->hsn_code,
                    'qty' => 1,
                    'rate' => $product->sale_price,
                    'unit' => $product->unit,
                    'unit_price' => $product->sale_price,
                    'gst_percent' => $product->gst_percent,
                    'sort_order' => 1,
                ]);
                $session->quote_id = $quote->id;
            }
        } catch (\Exception $e) {
            Log::error('ListBot: Error attaching product to lead or creating quote', [
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);
            // Flow continues even if this fails
        }

        // Advance past product step
        $this->advanceChatflow($session, $steps);
        $session->save();

        Log::info('ListBot: Product selected', ['session' => $session->id, 'product' => $displayName]);

        // Send next step (no confirmation message to user)
        return $this->sendNextStepMenu($session, $instanceName, $steps);
    }

    // ═══════════════════════════════════════════════════════
    // COLUMN FILTER SELECTION
    // ═══════════════════════════════════════════════════════

    private function handleColumnSelection(AiChatSession $session, string $instanceName, int $columnId, string $value, $steps): ?string
    {
        $session->setAnswer("column_filter_{$columnId}", $value);
        $session->current_step_retries = 0;

        // Update quote description
        $productId = $session->getAnswer('product_id');
        if ($productId) {
            $product = Product::with(['combos.column', 'customValues'])->find($productId);
            if ($product) {
                $this->updateQuoteItemDescription($session, $product);
            }
        }

        $this->advanceChatflow($session, $steps);
        $session->save();

        Log::info('ListBot: Column filter selected', ['session' => $session->id, 'column' => $columnId, 'value' => $value]);

        return $this->sendNextStepMenu($session, $instanceName, $steps);
    }

    // ═══════════════════════════════════════════════════════
    // COMBO SELECTION
    // ═══════════════════════════════════════════════════════

    private function handleComboSelection(AiChatSession $session, string $instanceName, $comboSlug, string $value, $steps): ?string
    {
        $session->setAnswer($comboSlug, $value);
        $session->current_step_retries = 0;

        // Update quote description + variation
        $productId = $session->getAnswer('product_id');
        if ($productId) {
            $product = Product::with(['combos.column', 'activeVariations', 'customValues'])->find($productId);
            if ($product) {
                $this->updateQuoteItemDescription($session, $product);
                $this->updateQuoteVariation($session, $product);
            }
        }

        $this->advanceChatflow($session, $steps);
        $session->save();

        Log::info('ListBot: Combo selected', ['session' => $session->id, 'slug' => $comboSlug, 'value' => $value]);

        return $this->sendNextStepMenu($session, $instanceName, $steps);
    }

    // ═══════════════════════════════════════════════════════
    // CUSTOM STEP — Accept text as-is (no AI)
    // ═══════════════════════════════════════════════════════

    private function handleCustomStep(AiChatSession $session, ChatflowStep $step, string $rawMessage, $steps, string $instanceName): ?string
    {
        $fieldKey = $step->field_key;
        if (!$fieldKey) {
            $this->advanceChatflow($session, $steps);
            $session->save();
            return $this->sendNextStepMenu($session, $instanceName, $steps);
        }

        // Save as-is — no AI extraction
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
                } else {
                    $customData = $lead->ai_custom_data ?? [];
                    $customData[$fieldKey] = trim($rawMessage);
                    $lead->update(['ai_custom_data' => $customData]);
                }
            }
        }

        // Update quote description
        $this->updateQuoteDescription($session);

        $this->advanceChatflow($session, $steps);
        $session->save();

        Log::info('ListBot: Custom answer saved', ['session' => $session->id, 'field' => $fieldKey, 'value' => mb_substr($rawMessage, 0, 50)]);

        return $this->sendNextStepMenu($session, $instanceName, $steps);
    }

    // ═══════════════════════════════════════════════════════
    // SEND WELCOME + CATEGORY MENU
    // ═══════════════════════════════════════════════════════

    private function sendWelcomeWithCategories(AiChatSession $session, string $instanceName): ?string
    {
        // Get admin-configured welcome message
        $welcomeMsg = Setting::getValue('list_bot', 'welcome_message', '', $this->companyId);
        if (empty($welcomeMsg)) {
            $welcomeMsg = "Welcome! 👋\nPlease select a category from the menu below.";
        }

        // Send welcome text first (with reset footer)
        $this->whatsApp->sendText($instanceName, $session->phone_number, $welcomeMsg . $this->resetFooter());

        // Get categories
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

        Log::info('ListBot: sendWelcomeWithCategories', [
            'company_id' => $this->companyId,
            'categories_found' => $categories->count(),
            'category_names' => $categories->pluck('name')->toArray(),
            'total_active_products' => $totalActive,
            'total_categories' => \App\Models\Category::where('company_id', $this->companyId)->count(),
        ]);

        // Smart category dedup: if most categories have only 1 product,
        // it means the AI wrongly created individual categories (e.g. "Conceal Handle 0010")
        // Group them by their text prefix (strip trailing numbers/codes)
        if ($categories->count() > 5 && $totalActive > 0) {
            $avgProductsPerCat = $totalActive / max($categories->count(), 1);
            if ($avgProductsPerCat <= 1.5) {
                Log::info('ListBot: Detected 1:1 category-product ratio, grouping by prefix');
                $grouped = $this->groupCategoriesByPrefix($categories);
                if ($grouped && $grouped->count() < $categories->count()) {
                    $categories = $grouped;
                    Log::info('ListBot: Grouped categories', [
                        'new_count' => $categories->count(),
                        'names' => $categories->pluck('name')->toArray(),
                    ]);
                }
            }
        }

        if ($categories->isEmpty()) {
            Log::info('ListBot: No categories with products, sending all products directly');
            // No categories → send product list directly
            return $this->sendAllProducts($session, $instanceName);
        }

        // Build category menu
        $sections = WhatsAppService::buildCategorySections($categories, $this->getCategoryFieldLabel());
        $buttonText = Setting::getValue('list_bot', 'menu_button_text', '🛍 Menu', $this->companyId);
        $menuButtonText = mb_substr($buttonText, 0, 20);

        Log::info('ListBot: Sending category menu', [
            'sections' => count($sections),
            'buttonText' => $menuButtonText,
        ]);

        $sent = $this->whatsApp->sendList(
            $instanceName,
            $session->phone_number,
            'Select Category',
            'Tap the button below to see our categories',
            $menuButtonText,
            $sections,
            'Tap an item to select'
        );

        // Store rowMap if text fallback was used
        if (is_array($sent) && !empty($sent['rowMap'])) {
            Log::info('ListBot: Text fallback used for category menu, storing rowMap');
            $session->setAnswer('_text_menu_rowMap', $sent['rowMap']);
        }

        Log::info('ListBot: Category menu sent result', ['success' => $sent !== false]);

        // Update session state
        $session->conversation_state = 'awaiting_category';
        $categoryStep = ChatflowStep::where('company_id', $this->companyId)
            ->where('step_type', 'ask_category')
            ->orderBy('sort_order')
            ->first();
        if ($categoryStep) {
            $session->current_step_id = $categoryStep->id;
        }
        $session->save();

        return null; // Message already sent via sendList
    }

    // ═══════════════════════════════════════════════════════
    // SEND PRODUCT LIST MENU
    // ═══════════════════════════════════════════════════════

    private function sendProductList(AiChatSession $session, string $instanceName, $categoryIds = null): ?string
    {
        $query = Product::where('company_id', $this->companyId)->where('status', 'active');
        if ($categoryIds) {
            if (is_array($categoryIds)) {
                $query->whereIn('category_id', $categoryIds);
            } else {
                $query->where('category_id', $categoryIds);
            }
        }

        // Apply any column filters already set
        $answers = $session->collected_answers ?? [];
        foreach ($answers as $key => $val) {
            if (str_starts_with($key, 'column_filter_')) {
                $colId = str_replace('column_filter_', '', $key);
                $query->whereHas('customValues', function ($q) use ($colId, $val) {
                    $q->where('column_id', $colId)->where('value', $val);
                });
            }
        }

        $products = $query->with(['customValues', 'category'])->orderBy('name')->get();

        Log::info('ListBot: sendProductList', [
            'company_id' => $this->companyId,
            'category_ids' => $categoryIds,
            'products_found' => $products->count(),
            'product_names' => $products->pluck('name')->toArray(),
            'product_ids' => $products->pluck('id')->toArray(),
        ]);

        if ($products->isEmpty()) {
            Log::warning('ListBot: No products found!', ['company_id' => $this->companyId, 'category_ids' => $categoryIds]);
            if ($categoryIds) {
                $msg = "Sorry, no products available in this category.";
                $this->whatsApp->sendText($instanceName, $session->phone_number, $msg);
                
                // Remove the category_id from answers so it can restart
                $answers = $session->collected_answers ?? [];
                unset($answers['category_id']);
                $session->collected_answers = $answers;
                $session->save();
                
                return $this->sendWelcomeWithCategories($session, $instanceName);
            } else {
                $msg = "Sorry, no products are currently available. Please check back later! 🙏";
                $this->whatsApp->sendText($instanceName, $session->phone_number, $msg);
                return $msg;
            }
        }

        // Auto-select if only 1 product
        if ($products->count() === 1) {
            Log::info('ListBot: Auto-selecting single product', ['product_id' => $products->first()->id]);
            $steps = ChatflowStep::with('linkedColumn')->where('company_id', $this->companyId)->orderBy('sort_order')->get();
            return $this->handleProductSelection($session, $instanceName, $products->first()->id, $steps);
        }

        $sections = WhatsAppService::buildProductSections($products, fn($p) => $this->getProductDisplayName($p));
        $buttonText = Setting::getValue('list_bot', 'menu_button_text', '🛍 Menu', $this->companyId);

        Log::info('ListBot: Sending product list', [
            'sections' => count($sections),
            'total_rows' => collect($sections)->sum(fn($s) => count($s['rows'] ?? [])),
        ]);

        $sent = $this->whatsApp->sendList(
            $instanceName,
            $session->phone_number,
            'Select Product',
            'Choose a product from our catalogue',
            mb_substr($buttonText, 0, 20),
            $sections,
            'Tap to select'
        );

        // Store rowMap if text fallback was used
        if (is_array($sent) && !empty($sent['rowMap'])) {
            Log::info('ListBot: Text fallback used for product menu, storing rowMap');
            $session->setAnswer('_text_menu_rowMap', $sent['rowMap']);
            $session->save();
        }

        Log::info('ListBot: Product list sent result', ['success' => $sent !== false]);

        // Update session
        $session->conversation_state = 'awaiting_product';
        $productStep = ChatflowStep::where('company_id', $this->companyId)
            ->whereIn('step_type', ['ask_product', 'ask_unique_column'])
            ->orderBy('sort_order')
            ->first();
        if ($productStep) {
            $session->current_step_id = $productStep->id;
        }
        $session->save();

        return null; // Message already sent via sendList
    }

    private function sendAllProducts(AiChatSession $session, string $instanceName): ?string
    {
        return $this->sendProductList($session, $instanceName, null);
    }

    // ═══════════════════════════════════════════════════════
    // SEND NEXT STEP MENU — dispatches based on step type
    // ═══════════════════════════════════════════════════════

    private function sendNextStepMenu(AiChatSession $session, string $instanceName, $steps): ?string
    {
        $nextStep = $session->current_step_id ? $steps->firstWhere('id', $session->current_step_id) : null;

        if (!$nextStep) {
            // Chatflow complete → send summary
            if ($session->getAnswer('product_id') && ($session->conversation_state === 'completed' || $session->conversation_state === 'in_chatflow')) {
                return $this->handleSummaryStep($session, $instanceName);
            }
            $msg = "✅ All done! Our team will contact you shortly. 🙏";
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
            case 'ask_combo':
                return $this->sendComboMenu($session, $instanceName, $nextStep, $steps);

            case 'ask_column':
                return $this->sendColumnMenu($session, $instanceName, $nextStep, $steps);

            case 'ask_custom':
            case 'ask_optional':
                // For free-text steps, just send the question as text
                $question = $nextStep->question_text ?: "Please provide your {$nextStep->field_key}:";
                $fullMsg = "📝 {$question}" . $this->resetFooter();
                $this->whatsApp->sendText($instanceName, $session->phone_number, $fullMsg);
                return $question;

            case 'send_summary':
                return $this->handleSummaryStep($session, $instanceName);

            default:
                // Unknown step type — advance
                $this->advanceChatflow($session, $steps);
                $session->save();
                return $this->sendNextStepMenu($session, $instanceName, $steps);
        }
    }

    // ═══════════════════════════════════════════════════════
    // COMBO MENU (finish, color, etc.)
    // ═══════════════════════════════════════════════════════

    private function sendComboMenu(AiChatSession $session, string $instanceName, ChatflowStep $step, $steps): ?string
    {
        $column = $step->linkedColumn;
        if (!$column) {
            Log::warning('ListBot: sendComboMenu — no linked column, skipping step', ['step_id' => $step->id]);
            $this->advanceChatflow($session, $steps);
            $session->save();
            return $this->sendNextStepMenu($session, $instanceName, $steps);
        }

        $productId = $session->getAnswer('product_id');
        $product = $productId ? Product::with(['combos.column', 'customValues'])->find($productId) : null;

        // ── Strategy 1: Get values from ProductCombo.selected_values ──
        $comboValues = $product ? $this->getComboValuesForProduct($product, $column) : [];

        // ── Strategy 2: Get THIS product's own custom value for this column ──
        if (empty($comboValues) && $product) {
            $customVal = $product->customValues->firstWhere('column_id', $column->id);
            if ($customVal && !empty($customVal->value)) {
                $decoded = json_decode($customVal->value, true);
                if (is_array($decoded) && count($decoded) > 0) {
                    $comboValues = array_values(array_filter($decoded));
                } elseif (!empty($customVal->value)) {
                    $comboValues = [$customVal->value];
                }
            }
        }

        Log::info('ListBot: sendComboMenu data lookup', [
            'column' => $column->name,
            'product_id' => $productId,
            'values_found' => count($comboValues),
            'values' => array_slice($comboValues, 0, 5),
        ]);

        // ── Product has NO data for this column → SKIP to next chatflow question ──
        if (empty($comboValues)) {
            Log::info('ListBot: Product has no data for this column, skipping step', [
                'step' => $step->name ?? $step->id,
                'column' => $column->name,
            ]);
            $this->advanceChatflow($session, $steps);
            $session->save();
            return $this->sendNextStepMenu($session, $instanceName, $steps);
        }

        // Auto-select if single option
        if (count($comboValues) === 1) {
            return $this->handleComboSelection($session, $instanceName, $column->slug, $comboValues[0], $steps);
        }

        $sections = WhatsAppService::buildOptionSections($comboValues, $column->name, "combo_{$column->slug}_");
        $question = $step->question_text ?: "Select {$column->name}:";
        $buttonText = Setting::getValue('list_bot', 'menu_button_text', '🛍 Menu', $this->companyId);

        Log::info('ListBot: Sending combo menu', [
            'step' => $step->name,
            'column' => $column->name,
            'values_count' => count($comboValues),
            'values' => array_slice($comboValues, 0, 5),
        ]);

        $sent = $this->whatsApp->sendList(
            $instanceName,
            $session->phone_number,
            mb_substr($question, 0, 60),
            $question,
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
    // COLUMN FILTER MENU (size, material, etc.)
    // ═══════════════════════════════════════════════════════

    private function sendColumnMenu(AiChatSession $session, string $instanceName, ChatflowStep $step, $steps): ?string
    {
        $column = $step->linkedColumn;
        $colId = $step->linked_column_id;

        if (!$column || !$colId) {
            $this->advanceChatflow($session, $steps);
            $session->save();
            return $this->sendNextStepMenu($session, $instanceName, $steps);
        }

        // Get distinct values for this column from available products
        $answers = $session->collected_answers ?? [];
        $query = Product::where('company_id', $this->companyId)->where('status', 'active');

        if (isset($answers['product_id'])) {
            $query->where('id', $answers['product_id']);
        } elseif (isset($answers['category_id'])) {
            $query->where('category_id', $answers['category_id']);
        }

        // Apply existing column filters
        foreach ($answers as $key => $val) {
            if (str_starts_with($key, 'column_filter_') && $key !== "column_filter_{$colId}") {
                $extColId = str_replace('column_filter_', '', $key);
                $query->whereHas('customValues', function ($q) use ($extColId, $val) {
                    $q->where('column_id', $extColId)->where('value', $val);
                });
            }
        }

        $productSet = $query->with('customValues')->get();
        $availableValues = [];
        foreach ($productSet as $p) {
            $rawVal = $p->customValues->firstWhere('column_id', $colId)?->value;
            if (!empty($rawVal)) {
                // Handle JSON arrays (e.g., ["6MM", "8MM"])
                $decoded = json_decode($rawVal, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $v) {
                        if (!empty($v)) $availableValues[$v] = true;
                    }
                } else {
                    $availableValues[$rawVal] = true;
                }
            }
        }
        $valuesList = array_keys($availableValues);
        sort($valuesList);

        if (empty($valuesList)) {
            Log::info('ListBot: No column values found, skipping to next step', [
                'step' => $step->name ?? $step->id,
                'column' => $column->name,
            ]);
            $this->advanceChatflow($session, $steps);
            $session->save();
            return $this->sendNextStepMenu($session, $instanceName, $steps);
        }

        // Auto-select if single option
        if (count($valuesList) === 1) {
            return $this->handleColumnSelection($session, $instanceName, $colId, $valuesList[0], $steps);
        }

        $sections = WhatsAppService::buildOptionSections($valuesList, $column->name, "col_{$colId}_");
        $question = $step->question_text ?: "Select {$column->name}:";
        $buttonText = Setting::getValue('list_bot', 'menu_button_text', '🛍 Menu', $this->companyId);

        $sent = $this->whatsApp->sendList(
            $instanceName,
            $session->phone_number,
            mb_substr($question, 0, 60),
            $question,
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
    // RE-SEND CURRENT MENU (when user types text instead of tapping)
    // ═══════════════════════════════════════════════════════

    private function resendCurrentMenu(AiChatSession $session, string $instanceName, $steps): ?string
    {
        $msg = "Please select from the menu below 👇";
        $this->whatsApp->sendText($instanceName, $session->phone_number, $msg);

        $currentStep = $session->current_step_id ? $steps->firstWhere('id', $session->current_step_id) : null;
        $answers = $session->collected_answers ?? [];

        if (!$currentStep && !isset($answers['product_id'])) {
            return $this->sendWelcomeWithCategories($session, $instanceName);
        }

        if (isset($answers['category_id']) && !isset($answers['product_id'])) {
            return $this->sendProductList($session, $instanceName, $answers['category_id']);
        }

        if ($currentStep) {
            return $this->sendNextStepMenu($session, $instanceName, $steps);
        }

        return null;
    }

    // ═══════════════════════════════════════════════════════
    // SUMMARY STEP
    // ═══════════════════════════════════════════════════════

    private function handleSummaryStep(AiChatSession $session, string $instanceName): string
    {
        $answers = $session->collected_answers ?? [];
        $msg = "📋 *Order Summary:*\n\n";

        $productId = $answers['product_id'] ?? null;
        $product = $productId ? Product::with(['combos.column', 'customValues', 'category'])->find($productId) : null;

        if ($product) {
            $msg .= "🛍️ *Product:* " . $this->getProductDisplayName($product) . "\n";

            if ($product->category) {
                $msg .= "📂 *Category:* {$product->category->name}\n";
            }

            // Show combo values
            foreach ($product->combos as $combo) {
                $slug = $combo->column->slug;
                $val = $answers[$slug] ?? null;
                if ($val) {
                    $msg .= "📌 *{$combo->column->name}:* {$val}\n";
                }
            }

            // Show column filters
            $visibleColumns = $this->getAiVisibleColumns();
            foreach ($answers as $key => $val) {
                if (str_starts_with($key, 'column_filter_')) {
                    $colId = str_replace('column_filter_', '', $key);
                    $col = $visibleColumns->firstWhere('id', (int)$colId);
                    if ($col) {
                        $msg .= "📌 *{$col->name}:* {$val}\n";
                    }
                }
            }

            // Show sale price
            if ($product->sale_price > 0) {
                $msg .= "\n💰 *Price:* ₹" . number_format($product->sale_price / 100, 2) . "\n";
            }
        }

        // Show custom answers (name, phone, address, etc.)
        $internalKeys = ['product_id', 'product_name', 'category_id', 'category_name', 'selected_product_group', 'product_price'];
        foreach ($answers as $key => $val) {
            if (in_array($key, $internalKeys) || str_starts_with($key, 'column_filter_')) continue;
            if ($product && $product->combos->pluck('column.slug')->contains($key)) continue;
            $msg .= "📝 *" . ucfirst(str_replace('_', ' ', $key)) . ":* {$val}\n";
        }

        $this->whatsApp->sendText($instanceName, $session->phone_number, $msg);

        // Ask if user wants to add another product
        $addMoreMsg = "━━━━━━━━━━━━━━━\n";
        $addMoreMsg .= "🛒 *Kya aap aur product add karna chahte hain?*\n\n";
        $addMoreMsg .= "1️⃣ ✅ Haan, aur product add karo\n";
        $addMoreMsg .= "2️⃣ ❌ Nahi, order complete karo";

        $this->whatsApp->sendText($instanceName, $session->phone_number, $addMoreMsg);

        // Set state to awaiting add-more decision
        $session->conversation_state = 'awaiting_add_more';
        $session->save();

        return $msg;
    }

    // ═══════════════════════════════════════════════════════
    // SHARED HELPERS (simplified from AIChatbotService)
    // ═══════════════════════════════════════════════════════

    private function advanceChatflow(AiChatSession $session, $steps): void
    {
        $answers = $session->collected_answers ?? [];
        $nextStep = null;

        foreach ($steps as $step) {
            $isAnswered = false;

            if ($step->step_type === 'ask_combo' && $step->linkedColumn) {
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
                ->get();
        }
        return $this->aiVisibleColumns;
    }

    /**
     * Group wrongly-created categories by their text prefix.
     * Strips trailing numbers/codes from category names to find the base group name.
     * E.g., "Conceal Handle 0010", "Conceal Handle 0011" → "Conceal Handle" (N products)
     *
     * Returns a collection of virtual category objects with merged product counts.
     */
    private function groupCategoriesByPrefix($categories): ?\Illuminate\Support\Collection
    {
        $groups = [];
        $catIdMap = []; // groupName => [cat_ids]

        foreach ($categories as $cat) {
            // Strip trailing numbers, codes, and common separators
            // "Conceal Handle 0010" → "Conceal Handle"
            // "Knob Handle 401" → "Knob Handle"
            // "Product 105" → "Product" (but this is too generic)
            $baseName = preg_replace('/[\s\-_]*[\d]+[\s\-_]*$/', '', trim($cat->name));
            $baseName = trim($baseName);

            if (empty($baseName)) {
                $baseName = $cat->name; // fallback to original
            }

            if (!isset($groups[$baseName])) {
                $groups[$baseName] = 0;
                $catIdMap[$baseName] = [];
            }
            $groups[$baseName] += $cat->products_count ?? 0;
            $catIdMap[$baseName][] = $cat->id;
        }

        // If grouping didn't reduce count meaningfully, return null
        if (count($groups) >= count($categories) * 0.8) {
            return null;
        }

        // Build virtual category collection
        $result = collect();
        foreach ($groups as $name => $count) {
            $firstCatId = $catIdMap[$name][0] ?? 0;
            // Create a virtual object that looks like a Category
            $virtual = new \stdClass();
            $virtual->id = $firstCatId; // Use first real cat ID for routing
            $virtual->name = $name;
            $virtual->company_id = $this->companyId;
            $virtual->products_count = $count;
            $virtual->_grouped_cat_ids = $catIdMap[$name]; // Store all IDs for product query
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

    private function getComboValuesForProduct(Product $product, CatalogueCustomColumn $column): array
    {
        $combo = $product->combos->firstWhere('column_id', $column->id);
        return $combo ? ($combo->selected_values ?? []) : [];
    }

    /**
     * Append reset footer to bot messages
     */
    private function resetFooter(): string
    {
        return "\n\n━━━━━━━━━━━━━━━\n↩️ Type *reset* to start over";
    }

    /**
     * Get distinct values for a column from product_custom_values.
     * Falls back across category products when product-specific values are empty.
     */
    private function getDistinctColumnValues(AiChatSession $session, CatalogueCustomColumn $column): array
    {
        $answers = $session->collected_answers ?? [];
        $colId = $column->id;

        // Build product query scoped to current selection
        $query = Product::where('company_id', $this->companyId)->where('status', 'active');

        if (isset($answers['product_id'])) {
            // First try: values from the selected product only
            $query->where('id', $answers['product_id']);
        } elseif (isset($answers['category_ids'])) {
            $query->whereIn('category_id', $answers['category_ids']);
        } elseif (isset($answers['category_id'])) {
            $query->where('category_id', $answers['category_id']);
        }

        $productIds = $query->pluck('id');

        $values = DB::table('catalogue_custom_values')
            ->whereIn('product_id', $productIds)
            ->where('column_id', $colId)
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->distinct()
            ->pluck('value')
            ->map(function ($v) {
                $decoded = json_decode($v, true);
                return is_array($decoded) ? $decoded : [$v];
            })
            ->flatten()
            ->unique()
            ->filter()
            ->sort()
            ->values()
            ->toArray();

        Log::info('ListBot: getDistinctColumnValues', [
            'column' => $column->name,
            'product_scope' => isset($answers['product_id']) ? 'single' : 'category',
            'values_found' => count($values),
        ]);

        return $values;
    }

    private function updateQuoteItemDescription(AiChatSession $session, Product $product): void
    {
        $sessionAnswers = $session->collected_answers ?? [];
        $fullDesc = $product->getDynamicDescription($sessionAnswers);
        if (empty($fullDesc)) return;

        if ($session->quote_id) {
            $quoteItem = QuoteItem::where('quote_id', $session->quote_id)
                ->where('product_id', $product->id)
                ->first();
            if ($quoteItem) {
                $quoteItem->update(['description' => $fullDesc]);
            }
        }

        if ($session->lead_id) {
            $lead = Lead::find($session->lead_id);
            if ($lead && $lead->products->contains('id', $product->id)) {
                $lead->products()->updateExistingPivot($product->id, ['description' => $fullDesc]);
            }
        }
    }

    private function updateQuoteVariation(AiChatSession $session, Product $product): void
    {
        $allSelected = true;
        $combination = [];
        foreach ($product->combos as $combo) {
            $slug = $combo->column->slug;
            $val = $session->getAnswer($slug);
            if (!$val) { $allSelected = false; break; }
            $combination[$slug] = $val;
        }

        if ($allSelected && !empty($combination) && $session->quote_id) {
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
                }
            }
        }
    }

    private function updateQuoteDescription(AiChatSession $session): void
    {
        $productId = $session->getAnswer('product_id');
        if (!$session->quote_id || !$productId) return;

        $product = Product::with('combos.column')->find($productId);
        if (!$product) return;

        $newDesc = $product->getDynamicDescription($session->collected_answers ?? [], true);
        $descLines = [];
        if ($newDesc) $descLines[] = $newDesc;

        foreach ($session->collected_answers as $key => $val) {
            if (str_starts_with($key, 'column_filter_')) continue;
            if (!in_array($key, ['product_id', 'product_name', 'category_id', 'category_name', 'selected_product_group'])
                && !$product->combos->pluck('column.slug')->contains($key)) {
                $descLines[] = ucfirst(str_replace('_', ' ', $key)) . ": {$val}";
            }
        }

        $fullDesc = implode("\n", $descLines);

        $quoteItem = QuoteItem::where('quote_id', $session->quote_id)
            ->where('product_id', $productId)
            ->first();
        if ($quoteItem) {
            $quoteItem->update(['description' => $fullDesc]);
        }

        if ($session->lead_id) {
            $lead = Lead::find($session->lead_id);
            if ($lead && $lead->products()->where('product_id', $productId)->exists()) {
                $lead->products()->updateExistingPivot($productId, ['description' => $fullDesc]);
            }
        }
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
