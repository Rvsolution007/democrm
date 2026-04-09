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
            DB::transaction(function () use ($instanceName, $phone, $messageText, $listRowId) {
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

                // Reset session if user types "hi", "hello", or "menu" to start over
                if (in_array(trim(strtolower($messageText)), ['hi', 'hello', 'menu'])) {
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
            });
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
        $steps = ChatflowStep::where('company_id', $this->companyId)->orderBy('sort_order')->get();
        $currentStep = $session->current_step_id ? $steps->firstWhere('id', $session->current_step_id) : null;
        $answers = $session->collected_answers ?? [];

        // Parse rowId if present
        $parsed = $listRowId ? WhatsAppService::parseRowId($listRowId) : null;

        // ── CASE 0a: User tapped a list selection (rowId present) ──
        if ($parsed) {
            return $this->handleListSelection($session, $instanceName, $parsed, $steps);
        }

        // ── CASE 0b: Text fallback — user sent a number to select from menu ──
        $trimmedText = trim($messageText);
        if (ctype_digit($trimmedText) && isset($answers['_text_menu_rowMap'])) {
            $rowMap = $answers['_text_menu_rowMap'];
            if (isset($rowMap[$trimmedText])) {
                $rowId = $rowMap[$trimmedText];
                $parsed = WhatsAppService::parseRowId($rowId);
                if ($parsed) {
                    Log::info('ListBot: Number mapped to rowId', ['number' => $trimmedText, 'rowId' => $rowId]);
                    // Clear the rowMap after use
                    unset($answers['_text_menu_rowMap']);
                    $session->collected_answers = $answers;
                    $session->save();
                    return $this->handleListSelection($session, $instanceName, $parsed, $steps);
                }
            } else {
                // Invalid number — resend the menu
                $this->whatsApp->sendText($instanceName, $session->phone_number, "❌ Invalid option. Please reply with a valid number from the menu.");
                return null;
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

        // Save to session
        $session->setAnswer('category_id', $categoryId);
        $session->setAnswer('category_name', $category->name);
        $session->conversation_state = 'awaiting_product';
        $session->catalogue_sent = true;

        // Advance past category step
        $this->advanceChatflow($session, $steps);
        $session->save();

        Log::info('ListBot: Category selected', ['session' => $session->id, 'category' => $category->name]);

        // Send product list for this category
        return $this->sendProductList($session, $instanceName, $categoryId);
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

        // Attach product to lead
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

        // Advance past product step
        $this->advanceChatflow($session, $steps);
        $session->save();

        Log::info('ListBot: Product selected', ['session' => $session->id, 'product' => $displayName]);

        // Send confirmation + next step
        $confirmMsg = "✅ *{$displayName}* selected! 🛍️";
        $this->whatsApp->sendText($instanceName, $session->phone_number, $confirmMsg);

        // Build and send next step as Interactive List
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

        $confirmMsg = "✅ *{$value}* selected!";
        $this->whatsApp->sendText($instanceName, $session->phone_number, $confirmMsg);

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

        $confirmMsg = "✅ *{$value}* selected!";
        $this->whatsApp->sendText($instanceName, $session->phone_number, $confirmMsg);

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

        $confirmMsg = "✅ Got it!";
        $this->whatsApp->sendText($instanceName, $session->phone_number, $confirmMsg);

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

        // Send welcome text first
        $this->whatsApp->sendText($instanceName, $session->phone_number, $welcomeMsg);

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

        Log::info('ListBot: sendWelcomeWithCategories', [
            'company_id' => $this->companyId,
            'categories_found' => $categories->count(),
            'category_names' => $categories->pluck('name')->toArray(),
            'total_active_products' => Product::where('company_id', $this->companyId)->where('status', 'active')->count(),
            'total_categories' => \App\Models\Category::where('company_id', $this->companyId)->count(),
        ]);

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

    private function sendProductList(AiChatSession $session, string $instanceName, ?int $categoryId = null): ?string
    {
        $query = Product::where('company_id', $this->companyId)->where('status', 'active');
        if ($categoryId) {
            $query->where('category_id', $categoryId);
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
            'category_id' => $categoryId,
            'products_found' => $products->count(),
            'product_names' => $products->pluck('name')->toArray(),
            'product_ids' => $products->pluck('id')->toArray(),
        ]);

        if ($products->isEmpty()) {
            Log::warning('ListBot: No products found!', ['company_id' => $this->companyId, 'category_id' => $categoryId]);
            if ($categoryId) {
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
            $steps = ChatflowStep::where('company_id', $this->companyId)->orderBy('sort_order')->get();
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
                $this->whatsApp->sendText($instanceName, $session->phone_number, "📝 {$question}");
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
            $this->advanceChatflow($session, $steps);
            $session->save();
            return $this->sendNextStepMenu($session, $instanceName, $steps);
        }

        $productId = $session->getAnswer('product_id');
        $product = Product::with('combos.column')->find($productId);
        $comboValues = $product ? $this->getComboValuesForProduct($product, $column) : [];

        if (empty($comboValues)) {
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
            $val = $p->customValues->firstWhere('column_id', $colId)?->value;
            if (!empty($val)) {
                $availableValues[$val] = true;
            }
        }
        $valuesList = array_keys($availableValues);
        sort($valuesList);

        if (empty($valuesList)) {
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

        $msg .= "\n✅ Our team will contact you shortly! 🙏";

        $this->whatsApp->sendText($instanceName, $session->phone_number, $msg);

        // Mark session completed
        $session->conversation_state = 'completed';
        $session->update(['status' => 'completed']);

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
