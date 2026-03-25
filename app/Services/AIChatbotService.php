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
use Illuminate\Support\Facades\Log;

class AIChatbotService
{
    private int $companyId;
    private int $userId;
    private VertexAIService $vertexAI;
    private string $replyLanguage;

    public function __construct(int $companyId, int $userId)
    {
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->vertexAI = new VertexAIService($companyId);
        $this->replyLanguage = Setting::getValue('ai_bot', 'reply_language', 'auto', $companyId);
    }

    private ?CatalogueCustomColumn $uniqueColumn = null;
    private bool $uniqueColumnLoaded = false;

    private function getProductDisplayName(Product $product): string
    {
        if (!$this->uniqueColumnLoaded) {
            $this->uniqueColumn = CatalogueCustomColumn::where('company_id', $this->companyId)
                ->where('is_unique', true)
                ->first();
            $this->uniqueColumnLoaded = true;
        }

        if ($this->uniqueColumn) {
            if ($this->uniqueColumn->is_system) {
                return $product->{$this->uniqueColumn->slug} ?: $product->name;
            } else {
                if ($product->relationLoaded('customValues')) {
                    $customVal = $product->customValues->where('column_id', $this->uniqueColumn->id)->first();
                } else {
                    $customVal = $product->customValues()->where('column_id', $this->uniqueColumn->id)->first();
                }
                
                if ($customVal && !empty($customVal->value)) {
                    $val = json_decode($customVal->value, true);
                    return is_array($val) ? implode(', ', $val) : $customVal->value;
                }
            }
        }

        return $product->name;
    }

    // ═══════════════════════════════════════════════════════
    // MAIN ENTRY POINT
    // ═══════════════════════════════════════════════════════

    public function processMessage(
        string $instanceName,
        string $phone,
        string $messageText,
        ?array $replyContext = null,
        ?string $imageUrl = null
    ): array {
        return DB::transaction(function () use ($instanceName, $phone, $messageText, $replyContext, $imageUrl) {

            $session = AiChatSession::findOrCreateForPhone($this->companyId, $phone, $instanceName);
            $session->update(['last_message_at' => now()]);

            // Save incoming user message
            AiChatMessage::create([
                'session_id' => $session->id,
                'role' => 'user',
                'message' => $messageText,
                'message_type' => $imageUrl ? 'image' : 'text',
                'image_url' => $imageUrl,
                'reply_context' => $replyContext,
            ]);

            // Get quoted text if reply
            $quotedText = $replyContext['quoted_text'] ?? null;
            $fullMessage = $quotedText ? "[Replying to: \"{$quotedText}\"]\n{$messageText}" : $messageText;

            // ═══ SMART ROUTER — decide Tier 1, Tier 2, or PHP Direct ═══
            $responseText = $this->routeMessage($session, $fullMessage, $messageText, $imageUrl);

            // Save bot response
            AiChatMessage::create([
                'session_id' => $session->id,
                'role' => 'bot',
                'message' => $responseText,
                'message_type' => 'text',
            ]);

            // Send via WhatsApp
            $sendResult = $this->sendWhatsAppMessage($instanceName, $phone, $responseText);

            return [
                'status' => $sendResult ? 'sent' : 'send_failed',
                'response' => $responseText,
                'session_id' => $session->id,
                'lead_id' => $session->lead_id,
                'quote_id' => $session->quote_id,
            ];
        });
    }

    // ═══════════════════════════════════════════════════════
    // MESSAGE ROUTER — Core logic
    // ═══════════════════════════════════════════════════════

    private function routeMessage(AiChatSession $session, string $fullMessage, string $rawMessage, ?string $imageUrl): string
    {
        $steps = ChatflowStep::where('company_id', $this->companyId)->orderBy('sort_order')->get();
        $currentStep = $session->current_step_id ? $steps->firstWhere('id', $session->current_step_id) : null;
        $answers = $session->collected_answers ?? [];

        // Removed empty chatflow steps bypass to allow catalog and pre-product phase matching

        // ══ CASE 1: No product selected yet ══
        if (!isset($answers['product_id'])) {
            return $this->handlePreProductPhase($session, $steps, $fullMessage, $rawMessage, $imageUrl);
        }

        // ══ CASE 2: Product selected, in chatflow ══
        if ($currentStep) {
            switch ($currentStep->step_type) {
                case 'ask_combo':
                    return $this->handleComboStep($session, $currentStep, $rawMessage, $steps);

                case 'ask_optional':
                case 'ask_custom':
                    return $this->handleCustomStep($session, $currentStep, $rawMessage, $steps);

                case 'send_summary':
                    return $this->handleSummaryStep($session);
            }
        }

        // ══ CASE 3: Fallback — Tier 2 ══
        return $this->handleTier2($session, $fullMessage, $imageUrl);
    }

    // ═══════════════════════════════════════════════════════
    // PRE-PRODUCT PHASE (Catalogue)
    // ═══════════════════════════════════════════════════════

    private function handlePreProductPhase(AiChatSession $session, $steps, string $fullMessage, string $rawMessage, ?string $imageUrl): string
    {
        $answers = $session->collected_answers ?? [];

        // ══ Check if ask_category step exists and category not yet selected ══
        $hasCategoryStep = $steps->contains('step_type', 'ask_category');

        if ($hasCategoryStep && !isset($answers['category_id'])) {
            // Category flow active — check if categories already sent
            if ($session->conversation_state === 'awaiting_category') {
                return $this->matchCategoryFromMessage($session, $rawMessage, $steps);
            }

            // Check if catalogue has been sent (meaning categories were sent)
            if ($session->catalogue_sent) {
                return $this->matchCategoryFromMessage($session, $rawMessage, $steps);
            }

            // Not sent yet — check intent then send category list
            $intentPrompt = "User said: \"{$rawMessage}\"\n\nIs the user EXPLICITLY asking about products, catalogue, prices, or what you sell? Simple greetings like 'hi', 'hello', 'hey', 'namaste', 'good morning', general casual conversation, or vague messages are NOT product queries — reply NO for those. Only reply YES if the user is clearly and specifically asking about products or showing direct buying intent. Reply with ONLY 'YES' or 'NO'.";
            $intentResult = $this->vertexAI->classifyContent($intentPrompt);
            $this->logTokens($session, 1, $intentResult);
            $isProductIntent = str_contains(strtoupper($intentResult['text']), 'YES');

            if ($isProductIntent) {
                return $this->sendCategoryList($session);
            }

            return $this->handleTier2($session, $fullMessage, $imageUrl);
        }

        // ══ Standard product flow (no category step or category already selected) ══
        if ($session->catalogue_sent) {
            return $this->matchProductFromMessage($session, $rawMessage, $steps);
        }

        // Catalogue not sent yet — check if user is asking about products (Tier 1)
        $intentPrompt = "User said: \"{$rawMessage}\"\n\nIs the user EXPLICITLY asking about products, catalogue, prices, or what you sell? Simple greetings like 'hi', 'hello', 'hey', 'namaste', 'good morning', general casual conversation, or vague messages are NOT product queries — reply NO for those. Only reply YES if the user is clearly and specifically asking about products or showing direct buying intent. Reply with ONLY 'YES' or 'NO'.";

        $intentResult = $this->vertexAI->classifyContent($intentPrompt);
        $this->logTokens($session, 1, $intentResult);

        $isProductIntent = str_contains(strtoupper($intentResult['text']), 'YES');

        if ($isProductIntent) {
            // Send catalogue
            return $this->sendCatalogue($session);
        }

        // Not product-related — use Tier 2 for general conversation
        return $this->handleTier2($session, $fullMessage, $imageUrl);
    }

    /**
     * Send category list message (PHP built — no AI)
     */
    private function sendCategoryList(AiChatSession $session): string
    {
        $categories = \App\Models\Category::where('company_id', $this->companyId)
            ->where('status', 'active')
            ->whereHas('products', function ($q) {
                $q->where('status', 'active');
            })
            ->orderBy('name')
            ->get();

        if ($categories->isEmpty()) {
            // No categories with products — fall back to direct product list
            return $this->sendCatalogue($session);
        }

        $msg = "📂 *Our Categories:*\n\n";
        foreach ($categories as $i => $cat) {
            $num = $i + 1;
            $productCount = Product::where('company_id', $this->companyId)
                ->where('category_id', $cat->id)
                ->where('status', 'active')
                ->count();
            $msg .= "{$num}️⃣ *{$cat->name}* ({$productCount} products)\n";
        }
        $msg .= "\nReply with category number or name! 👆";

        // Update session state
        $session->catalogue_sent = true;
        $session->conversation_state = 'awaiting_category';

        // Set current step to category step
        $categoryStep = ChatflowStep::where('company_id', $this->companyId)
            ->where('step_type', 'ask_category')
            ->orderBy('sort_order')
            ->first();
        if ($categoryStep) {
            $session->current_step_id = $categoryStep->id;
        }

        $session->save();

        Log::info("AIChatbot: Category list sent (PHP Direct)", ['session' => $session->id]);
        return $msg;
    }

    /**
     * Match user's message to a category (Tier 1)
     */
    private function matchCategoryFromMessage(AiChatSession $session, string $rawMessage, $steps): string
    {
        $categories = \App\Models\Category::where('company_id', $this->companyId)
            ->where('status', 'active')
            ->whereHas('products', function ($q) {
                $q->where('status', 'active');
            })
            ->orderBy('name')
            ->get();

        if ($categories->isEmpty()) {
            return $this->sendCatalogue($session);
        }

        // Build category list for prompt
        $catList = $categories->map(fn($c, $i) => ($i + 1) . ". {$c->name} (ID:{$c->id})")->implode("\n");

        $prompt = "User said: \"{$rawMessage}\"\n\nAvailable categories:\n{$catList}\n\nWhich category number did user select or which category name matches? Reply with ONLY the ID number. If unclear or no match, reply NONE.";

        $matchResult = $this->vertexAI->classifyContent($prompt);
        $this->logTokens($session, 1, $matchResult);

        $matchText = trim($matchResult['text']);

        // Extract number from response
        preg_match('/(\d+)/', $matchText, $matches);
        $matchedId = $matches[1] ?? null;

        // Check if it's a valid category ID or list number
        $selectedCategory = null;
        if ($matchedId) {
            $selectedCategory = $categories->firstWhere('id', (int)$matchedId);
            if (!$selectedCategory && (int)$matchedId <= $categories->count()) {
                $selectedCategory = $categories->values()[(int)$matchedId - 1] ?? null;
            }
        }

        if (!$selectedCategory || strtoupper($matchText) === 'NONE') {
            return "Sorry, I couldn't match that to a category. Please reply with the category number or name from the list above. 🙏";
        }

        // Category matched — save to session and send filtered product list
        $session->setAnswer('category_id', $selectedCategory->id);
        $session->setAnswer('category_name', $selectedCategory->name);
        $session->conversation_state = 'awaiting_product';

        // Advance chatflow past ask_category step
        $this->advanceChatflow($session, $steps);
        $session->save();

        Log::info("AIChatbot: Category selected (Tier 1)", ['session' => $session->id, 'category' => $selectedCategory->name]);

        // Now send filtered product catalogue
        $msg = "✅ *{$selectedCategory->name}* selected!\n\n";
        $msg .= $this->sendCatalogue($session);
        return $msg;
    }

    /**
     * Send product catalogue message (PHP built — no AI)
     */
    private function sendCatalogue(AiChatSession $session): string
    {
        $answers = $session->collected_answers ?? [];
        $query = Product::with('customValues')->where('company_id', $this->companyId)
            ->where('status', 'active');

        // Filter by category if category was selected
        if (isset($answers['category_id'])) {
            $query->where('category_id', $answers['category_id']);
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            return "Sorry, we don't have any products available right now.";
        }

        // Get AI-visible columns
        $visibleColumns = $this->getAiVisibleColumns();
        $showPrice = $visibleColumns->contains('slug', 'sale_price') || $visibleColumns->contains('slug', 'mrp');

        $msg = "🛍️ *Our Products:*\n\n";
        foreach ($products as $i => $product) {
            $num = $i + 1;
            $displayName = $this->getProductDisplayName($product);
            $msg .= "{$num}️⃣ *{$displayName}*";
            if ($showPrice && $product->sale_price > 0) {
                $msg .= " — ₹" . number_format($product->sale_price / 100, 2);
            }
            $msg .= "\n";
            if ($product->description && $visibleColumns->contains('slug', 'description')) {
                $msg .= "   {$product->description}\n";
            }
        }
        $msg .= "\nReply with product number or name! 👆";

        // Update session state
        $session->catalogue_sent = true;
        $session->conversation_state = 'awaiting_product';

        // Set current step to first product step (if exists)
        $productStep = ChatflowStep::where('company_id', $this->companyId)
            ->where('step_type', 'ask_product')
            ->orderBy('sort_order')
            ->first();
        if ($productStep) {
            $session->current_step_id = $productStep->id;
        }

        $session->save();

        Log::info("AIChatbot: Catalogue sent (PHP Direct)", ['session' => $session->id]);
        return $msg;
    }

    /**
     * Match user's message to a product (Tier 1)
     */
    private function matchProductFromMessage(AiChatSession $session, string $rawMessage, $steps): string
    {
        $answers = $session->collected_answers ?? [];
        $query = Product::with('customValues')->where('company_id', $this->companyId)
            ->where('status', 'active');

        // Filter by category if category was selected
        if (isset($answers['category_id'])) {
            $query->where('category_id', $answers['category_id']);
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            return "Sorry, no products available right now.";
        }

        // Build product list for prompt
        $productList = $products->map(fn($p, $i) => ($i + 1) . ". " . $this->getProductDisplayName($p) . " (ID:{$p->id})")->implode("\n");

        $prompt = "User said: \"{$rawMessage}\"\n\nAvailable products:\n{$productList}\n\nWhich product number did user select or which product name matches? Reply with ONLY the ID number. If unclear or no match, reply NONE.";

        $matchResult = $this->vertexAI->classifyContent($prompt);
        $this->logTokens($session, 1, $matchResult);

        $matchText = trim($matchResult['text']);

        // Extract number from response
        preg_match('/(\d+)/', $matchText, $matches);
        $matchedId = $matches[1] ?? null;

        // Check if it's a valid product ID or list number
        $selectedProduct = null;
        if ($matchedId) {
            // First try as product ID
            $selectedProduct = $products->firstWhere('id', (int)$matchedId);
            // Then try as list number
            if (!$selectedProduct && (int)$matchedId <= $products->count()) {
                $selectedProduct = $products->values()[(int)$matchedId - 1] ?? null;
            }
        }

        if (!$selectedProduct || strtoupper($matchText) === 'NONE') {
            return "Sorry, I couldn't match that to a product. Please reply with the product number or name from the list above. 🙏";
        }

        // Product matched — proceed
        return $this->selectProduct($session, $selectedProduct->id, $steps);
    }

    /**
     * Select product — create Lead, Quote, send details (PHP built)
     */
    private function selectProduct(AiChatSession $session, int $productId, $steps): string
    {
        $product = Product::with(['combos.column', 'activeVariations', 'customValues'])->find($productId);
        if (!$product || $product->company_id !== $this->companyId) {
            return "Product not found. Please try again.";
        }

        // Save to session
        $displayName = $this->getProductDisplayName($product);
        $session->setAnswer('product_id', $productId);
        $session->setAnswer('product_name', $displayName);
        $session->conversation_state = 'product_selected';

        // Create Lead
        if (!$session->lead_id) {
            $lead = Lead::create([
                'company_id' => $this->companyId,
                'created_by_user_id' => $this->userId,
                'source' => 'whatsapp',
                'name' => $session->phone_number,
                'phone' => $session->phone_number,
                'stage' => 'new',
                'product_name' => $displayName,
            ]);
            $session->lead_id = $lead->id;
            $lead->products()->attach($productId, ['quantity' => 1, 'price' => $product->sale_price]);
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
                'description' => $product->description,
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

        // Build product details message
        $msg = $this->buildProductDetailsMessage($product);

        // Advance to next step after ask_product
        $this->advanceChatflow($session, $steps);
        $session->save();

        // Append next step question if it's a combo step
        $nextStep = $session->current_step_id ? $steps->firstWhere('id', $session->current_step_id) : null;
        if ($nextStep && $nextStep->step_type === 'ask_combo' && $nextStep->linkedColumn) {
            $comboValues = $this->getComboValuesForProduct($product, $nextStep->linkedColumn);
            if (!empty($comboValues)) {
                $msg .= "\n\n" . ($nextStep->question_text ?: "Which {$nextStep->linkedColumn->name}?") . " 👇\n";
                $msg .= implode(' | ', $comboValues);
            }
        }

        Log::info("AIChatbot: Product selected (PHP Direct)", ['session' => $session->id, 'product' => $product->name]);
        return $msg;
    }

    /**
     * Build formatted product details message
     */
    private function buildProductDetailsMessage(Product $product): string
    {
        $visibleColumns = $this->getAiVisibleColumns();
        $displayName = $this->getProductDisplayName($product);
        $msg = "✅ *{$displayName}* 🛍️\n\n";

        // Description
        if ($product->description && $visibleColumns->contains('slug', 'description')) {
            $msg .= "📋 {$product->description}\n";
        }

        // Price
        if ($visibleColumns->contains('slug', 'sale_price') && $product->sale_price > 0) {
            $msg .= "💰 Price: ₹" . number_format($product->sale_price / 100, 2) . "\n";
        }

        // HSN
        if ($visibleColumns->contains('slug', 'hsn_code') && $product->hsn_code) {
            $msg .= "🏷️ HSN: {$product->hsn_code}\n";
        }

        // GST
        if ($visibleColumns->contains('slug', 'gst_percent') && $product->gst_percent > 0) {
            $msg .= "📊 GST: {$product->gst_percent}%\n";
        }

        // Custom column values
        $customValues = \App\Models\CatalogueCustomValue::where('product_id', $product->id)->get();
        foreach ($customValues as $cv) {
            $col = $visibleColumns->firstWhere('id', $cv->column_id);
            if ($col && !$col->is_combo) {
                $msg .= "📌 {$col->name}: {$cv->value}\n";
            }
        }

        // Combo options
        foreach ($product->combos as $combo) {
            $col = $combo->column;
            if ($col && $visibleColumns->contains('id', $col->id)) {
                $values = $combo->selected_values ?? [];
                if (!empty($values)) {
                    $msg .= "📏 {$col->name}: " . implode(', ', $values) . "\n";
                }
            }
        }

        return $msg;
    }

    // ═══════════════════════════════════════════════════════
    // COMBO STEP — Tier 1 matching
    // ═══════════════════════════════════════════════════════

    private function handleComboStep(AiChatSession $session, ChatflowStep $step, string $rawMessage, $steps): string
    {
        $column = $step->linkedColumn;
        if (!$column) {
            $this->advanceChatflow($session, $steps);
            $session->save();
            return "Moving to next step...";
        }

        // Get available combo values for this product
        $productId = $session->getAnswer('product_id');
        $product = Product::with('combos.column')->find($productId);
        $comboValues = $product ? $this->getComboValuesForProduct($product, $column) : [];

        if (empty($comboValues)) {
            $this->advanceChatflow($session, $steps);
            $session->save();
            return $this->buildNextStepPrompt($session, $steps);
        }

        // Tier 1: Match user message to combo option
        $optionsList = implode(', ', $comboValues);
        $prompt = "User said: \"{$rawMessage}\"\n\nAvailable options: [{$optionsList}]\n\nWhich option matches user's choice? Reply with the EXACT option text from the list. If no clear match, reply NONE.";

        $matchResult = $this->vertexAI->classifyContent($prompt);
        $this->logTokens($session, 1, $matchResult);

        $matchedText = trim($matchResult['text']);

        // Verify match is in options (case-insensitive)
        $matchedOption = null;
        foreach ($comboValues as $opt) {
            if (strtolower($opt) === strtolower($matchedText)) {
                $matchedOption = $opt;
                break;
            }
        }

        if (!$matchedOption || strtoupper($matchedText) === 'NONE') {
            // Retry
            $session->current_step_retries = ($session->current_step_retries ?? 0) + 1;

            if ($session->current_step_retries >= ($step->max_retries ?? 2)) {
                if ($step->isOptionalStep()) {
                    $session->markOptionalAsked($column->slug);
                    $this->advanceChatflow($session, $steps);
                    $session->save();
                    return $this->buildNextStepPrompt($session, $steps);
                }
            }

            $session->save();
            return "Sorry, I didn't understand. Please choose from:\n" . implode(' | ', $comboValues);
        }

        // Matched! Save to session
        $session->setAnswer($column->slug, $matchedOption);
        $session->current_step_retries = 0;

        // Update quote variation if all combos selected
        $this->updateQuoteVariation($session, $product);

        // Advance chatflow
        $this->advanceChatflow($session, $steps);
        $session->save();

        // Build response
        $msg = "✅ {$column->name}: *{$matchedOption}* selected!";

        // Append next step
        $nextPrompt = $this->buildNextStepPrompt($session, $steps);
        if ($nextPrompt) {
            $msg .= "\n\n" . $nextPrompt;
        }

        Log::info("AIChatbot: Combo matched (Tier 1)", ['session' => $session->id, 'column' => $column->slug, 'value' => $matchedOption]);
        return $msg;
    }

    // ═══════════════════════════════════════════════════════
    // CUSTOM / OPTIONAL STEP — Tier 1 extraction
    // ═══════════════════════════════════════════════════════

    private function handleCustomStep(AiChatSession $session, ChatflowStep $step, string $rawMessage, $steps): string
    {
        $fieldKey = $step->field_key;
        if (!$fieldKey) {
            $this->advanceChatflow($session, $steps);
            $session->save();
            return $this->buildNextStepPrompt($session, $steps);
        }

        // Tier 1: Extract answer
        $question = $step->question_text ?: "What is your {$fieldKey}?";
        $prompt = "Question asked: \"{$question}\"\nUser replied: \"{$rawMessage}\"\n\nExtract the user's answer. Reply with ONLY the extracted answer text. If user seems to skip or says 'no' / 'skip', reply SKIP.";

        $extractResult = $this->vertexAI->classifyContent($prompt);
        $this->logTokens($session, 1, $extractResult);

        $extractedText = trim($extractResult['text']);

        if (strtoupper($extractedText) === 'SKIP' || empty($extractedText)) {
            if ($step->isOptionalStep()) {
                $session->markOptionalAsked($fieldKey);
                $this->advanceChatflow($session, $steps);
                $session->save();
                return $this->buildNextStepPrompt($session, $steps);
            }
            // Not optional — ask again
            $session->current_step_retries = ($session->current_step_retries ?? 0) + 1;
            $session->save();
            return $question;
        }

        // Save answer
        $session->setAnswer($fieldKey, $extractedText);
        $session->current_step_retries = 0;
        if ($step->isOptionalStep()) {
            $session->markOptionalAsked($fieldKey);
        }

        // Update lead
        if ($session->lead_id) {
            $lead = Lead::find($session->lead_id);
            if ($lead) {
                $directFields = ['name', 'email', 'city', 'state'];
                if (in_array($fieldKey, $directFields)) {
                    $lead->update([$fieldKey => $extractedText]);
                } else {
                    $customData = $lead->ai_custom_data ?? [];
                    $customData[$fieldKey] = $extractedText;
                    $lead->update(['ai_custom_data' => $customData]);
                }
            }
        }

        // Advance
        $this->advanceChatflow($session, $steps);
        $session->save();

        $msg = "✅ Got it!";
        $nextPrompt = $this->buildNextStepPrompt($session, $steps);
        if ($nextPrompt) {
            $msg .= "\n\n" . $nextPrompt;
        }

        Log::info("AIChatbot: Custom answer (Tier 1)", ['session' => $session->id, 'field' => $fieldKey, 'value' => $extractedText]);
        return $msg;
    }

    // ═══════════════════════════════════════════════════════
    // SUMMARY STEP — PHP Direct (no AI)
    // ═══════════════════════════════════════════════════════

    private function handleSummaryStep(AiChatSession $session): string
    {
        $answers = $session->collected_answers ?? [];
        $visibleColumns = $this->getAiVisibleColumns();

        $msg = "📋 *Order Summary:*\n\n";
        $msg .= "Product: *{$answers['product_name']}*\n";

        // Combo selections
        $product = Product::with('combos.column')->find($answers['product_id'] ?? null);
        if ($product) {
            foreach ($product->combos as $combo) {
                $col = $combo->column;
                if ($col && isset($answers[$col->slug])) {
                    $msg .= "{$col->name}: *{$answers[$col->slug]}*\n";
                }
            }
        }

        // Price from quote
        if ($session->quote_id && $visibleColumns->contains('slug', 'sale_price')) {
            $quote = Quote::with('items')->find($session->quote_id);
            if ($quote) {
                $msg .= "\n💰 Subtotal: ₹" . number_format($quote->subtotal / 100, 2) . "\n";
                if ($quote->gst_total > 0) {
                    $msg .= "📊 GST: ₹" . number_format($quote->gst_total / 100, 2) . "\n";
                }
                $msg .= "🏷️ *Total: ₹" . number_format($quote->grand_total / 100, 2) . "*\n";
            }
        }

        // Custom answers
        foreach ($answers as $key => $val) {
            if (!in_array($key, ['product_id', 'product_name']) && !$product?->combos->pluck('column.slug')->contains($key)) {
                $msg .= ucfirst(str_replace('_', ' ', $key)) . ": {$val}\n";
            }
        }

        $msg .= "\n✅ Our team will contact you shortly! 🙏";

        $session->update(['status' => 'completed', 'conversation_state' => 'completed']);

        Log::info("AIChatbot: Summary sent (PHP Direct)", ['session' => $session->id]);
        return $msg;
    }

    // ═══════════════════════════════════════════════════════
    // TIER 2 — Full Conversational AI
    // ═══════════════════════════════════════════════════════

    private function handleTier2(AiChatSession $session, string $fullMessage, ?string $imageUrl): string
    {
        $systemPrompt = $this->buildSystemPrompt($session);
        $chatHistory = $this->buildChatHistory($session, $fullMessage, $imageUrl);

        $aiResult = $this->vertexAI->generateContent($systemPrompt, $chatHistory, $imageUrl);
        $this->logTokens($session, 2, $aiResult);

        // Parse structured response
        $parsed = $this->parseAIResponse($aiResult['text'], $session);
        $this->updateSessionState($session, $parsed);

        Log::info("AIChatbot: Tier 2 AI used", ['session' => $session->id, 'tokens' => $aiResult['total_tokens']]);
        return $parsed['response_text'] ?? $aiResult['text'];
    }

    /**
     * Build system prompt (optimized — only current context)
     */
    private function buildSystemPrompt(AiChatSession $session): string
    {
        $basePrompt = Setting::getValue('ai_bot', 'system_prompt', '', $this->companyId);
        $chatflowContext = $this->buildChatflowContext($session);
        $memoryContext = $this->buildMemoryContext($session);
        $catalogueContext = $this->buildCatalogueContext($session);

        $prompt = $basePrompt . "\n\n---\n## SESSION CONTEXT\n\n" . $chatflowContext;

        if (!empty($memoryContext)) {
            $prompt .= "\n\n## MEMORY\n" . $memoryContext;
        }
        if (!empty($catalogueContext)) {
            $prompt .= "\n\n## CATALOGUE\n" . $catalogueContext;
        }

        // Language instruction
        $prompt .= "\n\n## LANGUAGE\n";
        switch ($this->replyLanguage) {
            case 'en':
                $prompt .= "Always reply in English only.";
                break;
            case 'hi':
                $prompt .= "Always reply in Hindi only.";
                break;
            default:
                $prompt .= "Reply in the same language the customer is using.";
        }

        // Response format
        $prompt .= "\n\n## RESPONSE FORMAT\n";
        $prompt .= "Respond with JSON block then message:\n";
        $prompt .= "```json\n{\"action\":\"<action>\",\"data\":{...}}\n```\n";
        $prompt .= "Possible actions: greeting, select_product, select_combo, answer_optional, skip_optional, complete, escalate, continue\n";
        $prompt .= "For select_product: data={\"product_id\":<id>}\n";
        $prompt .= "For select_combo: data={\"column_slug\":\"<slug>\",\"value\":\"<val>\"}\n";
        $prompt .= "For answer_optional: data={\"field_key\":\"<key>\",\"value\":\"<answer>\"}\n";
        $prompt .= "Always include JSON block.\n";

        // Critical behavioral rules
        $prompt .= "\n## CRITICAL RULES\n";
        $prompt .= "1. When greeting (action=greeting), NEVER list products, categories, or what you sell. Just greet warmly and ask how you can help.\n";
        $prompt .= "2. NEVER fabricate or invent product names. You must ONLY use product names from the ## CATALOGUE section above. If no CATALOGUE section exists, say you have no products.\n";
        $prompt .= "3. Only show product list when user EXPLICITLY asks about products/catalogue.\n";

        return $prompt;
    }

    private function buildChatflowContext(AiChatSession $session): string
    {
        $steps = ChatflowStep::where('company_id', $this->companyId)->orderBy('sort_order')->get();
        if ($steps->isEmpty()) {
            return "No chatflow defined. Have a natural conversation.";
        }

        $context = "### Chatflow Steps:\n";
        foreach ($steps as $step) {
            $status = '⬜';
            if ($session->current_step_id) {
                $currentStep = $steps->firstWhere('id', $session->current_step_id);
                if ($currentStep && $step->sort_order < $currentStep->sort_order) $status = '✅';
                elseif ($step->id == $session->current_step_id) $status = '🔄';
            }
            $context .= "{$status} [{$step->step_type}] {$step->name}\n";
        }

        $answers = $session->collected_answers ?? [];
        if (!empty($answers)) {
            $context .= "\n### Collected:\n";
            foreach ($answers as $k => $v) {
                $context .= "- {$k}: {$v}\n";
            }
        }

        return $context;
    }

    private function buildMemoryContext(AiChatSession $session): string
    {
        $context = '';
        if ($session->lead_id) {
            $lead = Lead::find($session->lead_id);
            if ($lead) {
                $context .= "Lead: {$lead->name}, Phone: {$lead->phone}";
                if ($lead->city) $context .= ", City: {$lead->city}";
                $context .= "\n";
            }
        }
        return $context;
    }

    private function buildCatalogueContext(AiChatSession $session): string
    {
        $answers = $session->collected_answers ?? [];
        $visibleColumns = $this->getAiVisibleColumns();

        if (!isset($answers['product_id'])) {
            // List products (only names + visible prices)
            $query = Product::with('customValues')->where('company_id', $this->companyId)->where('status', 'active');

            // Filter by category if selected
            if (isset($answers['category_id'])) {
                $query->where('category_id', $answers['category_id']);
            }

            $products = $query->get();
            if ($products->isEmpty()) {
                return "System Note: The product catalogue is currently EMPTY. We do not have any products to sell right now. If the user asks for products, inform them that we currently have no products available.";
            }

            $showPrice = $visibleColumns->contains('slug', 'sale_price');
            $context = "### Products:\n";
            foreach ($products as $p) {
                $price = ($showPrice && $p->sale_price > 0) ? " — ₹" . number_format($p->sale_price / 100, 2) : '';
                $displayName = $this->getProductDisplayName($p);
                $context .= "- ID:{$p->id} | {$displayName}{$price}\n";
            }
            return $context;
        }

        return "Selected: {$answers['product_name']}";
    }

    private function buildChatHistory(AiChatSession $session, string $currentMessage, ?string $imageUrl): array
    {
        $history = [];
        $messages = $session->getRecentMessages(5); // Reduced from 10

        foreach ($messages as $msg) {
            $role = $msg->role === 'user' ? 'user' : 'model';
            $history[] = ['role' => $role, 'text' => $msg->message];
        }

        if (empty($history) || end($history)['text'] !== $currentMessage) {
            $history[] = ['role' => 'user', 'text' => $currentMessage];
        }

        return $history;
    }

    private function parseAIResponse(string $response, AiChatSession $session): array
    {
        $result = ['action' => 'continue', 'data' => [], 'response_text' => $response];

        if (preg_match('/```json\s*(.*?)\s*```/s', $response, $matches)) {
            $parsed = json_decode($matches[1], true);
            if ($parsed && isset($parsed['action'])) {
                $result['action'] = $parsed['action'];
                $result['data'] = $parsed['data'] ?? [];
            }
            $result['response_text'] = trim(preg_replace('/```json\s*.*?\s*```/s', '', $response));
        }

        return $result;
    }

    private function updateSessionState(AiChatSession $session, array $parsedData): void
    {
        $action = $parsedData['action'];
        $data = $parsedData['data'];

        $steps = ChatflowStep::where('company_id', $this->companyId)->orderBy('sort_order')->get();

        switch ($action) {
            case 'select_product':
                if (isset($data['product_id'])) {
                    $this->selectProduct($session, $data['product_id'], $steps);
                }
                break;
            case 'select_combo':
                if (isset($data['column_slug'], $data['value'])) {
                    $session->setAnswer($data['column_slug'], $data['value']);
                    $session->current_step_retries = 0;
                    $productId = $session->getAnswer('product_id');
                    if ($productId) {
                        $product = Product::with('combos.column')->find($productId);
                        if ($product) $this->updateQuoteVariation($session, $product);
                    }
                    $this->advanceChatflow($session, $steps);
                }
                break;
            case 'answer_optional':
                if (isset($data['field_key'], $data['value'])) {
                    $session->setAnswer($data['field_key'], $data['value']);
                    $session->markOptionalAsked($data['field_key']);
                    $this->advanceChatflow($session, $steps);
                }
                break;
            case 'skip_optional':
                $currentStep = ChatflowStep::find($session->current_step_id);
                if ($currentStep?->field_key) $session->markOptionalAsked($currentStep->field_key);
                $this->advanceChatflow($session, $steps);
                break;
            case 'complete':
                $session->update(['status' => 'completed', 'conversation_state' => 'completed']);
                break;
            case 'escalate':
                if ($session->lead_id) {
                    $lead = Lead::find($session->lead_id);
                    $lead?->update([
                        'notes' => ($lead->notes ? $lead->notes . "\n" : '') . "[AI Escalated] " . ($data['reason'] ?? ''),
                        'stage' => 'contacted',
                    ]);
                }
                break;
            default:
                if (!$session->current_step_id) {
                    $first = ChatflowStep::getFirstStep($this->companyId);
                    if ($first) $session->current_step_id = $first->id;
                }
                break;
        }

        $session->save();
    }

    // ═══════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════

    private function getAiVisibleColumns()
    {
        return CatalogueCustomColumn::where('company_id', $this->companyId)
            ->where('show_in_ai', true)
            ->where('is_active', true)
            ->get();
    }

    private function getComboValuesForProduct(Product $product, CatalogueCustomColumn $column): array
    {
        $combo = $product->combos->firstWhere('column_id', $column->id);
        return $combo ? ($combo->selected_values ?? []) : [];
    }

    private function advanceChatflow(AiChatSession $session, $steps): void
    {
        $currentStep = $steps->firstWhere('id', $session->current_step_id);

        if ($currentStep) {
            $nextStep = $steps->first(fn($s) => $s->sort_order > $currentStep->sort_order);
        } else {
            $nextStep = $steps->first();
        }

        if ($nextStep) {
            $session->current_step_id = $nextStep->id;
            $session->current_step_retries = 0;
            $session->conversation_state = 'in_chatflow';
        } else {
            $session->conversation_state = 'completed';
            $session->update(['status' => 'completed']);
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

    private function buildNextStepPrompt(AiChatSession $session, $steps): string
    {
        $nextStep = $session->current_step_id ? $steps->firstWhere('id', $session->current_step_id) : null;
        if (!$nextStep) return "✅ All done! Our team will contact you. 🙏";

        if ($nextStep->step_type === 'ask_combo' && $nextStep->linkedColumn) {
            $productId = $session->getAnswer('product_id');
            $product = Product::with('combos.column')->find($productId);
            $comboValues = $product ? $this->getComboValuesForProduct($product, $nextStep->linkedColumn) : [];
            $question = $nextStep->question_text ?: "Which {$nextStep->linkedColumn->name}?";
            return "{$question} 👇\n" . implode(' | ', $comboValues);
        }

        if ($nextStep->step_type === 'send_summary') {
            return $this->handleSummaryStep($session);
        }

        return $nextStep->question_text ?: "Please provide your {$nextStep->field_key}:";
    }

    /**
     * Log token consumption
     */
    private function logTokens(AiChatSession $session, int $tier, array $result): void
    {
        try {
            AiTokenLog::create([
                'company_id' => $this->companyId,
                'session_id' => $session->id,
                'phone_number' => $session->phone_number,
                'tier' => $tier,
                'prompt_tokens' => $result['prompt_tokens'] ?? 0,
                'completion_tokens' => $result['completion_tokens'] ?? 0,
                'total_tokens' => $result['total_tokens'] ?? 0,
                'model_used' => 'gemini-2.0-flash',
            ]);
        } catch (\Exception $e) {
            Log::warning('AIChatbot: Failed to log tokens - ' . $e->getMessage());
        }
    }

    /**
     * Send a text message via Evolution API
     */
    private function sendWhatsAppMessage(string $instanceName, string $phone, string $text): bool
    {
        $config = Setting::getValue('whatsapp', 'api_config', [
            'api_url' => '',
            'api_key' => '',
        ], $this->companyId);

        if (empty($config['api_url']) || empty($config['api_key'])) {
            Log::error('AIChatbot: WhatsApp API not configured');
            return false;
        }

        try {
            $formattedPhone = preg_replace('/\D/', '', $phone);
            if (strlen($formattedPhone) == 10) {
                $formattedPhone = '91' . $formattedPhone;
            }

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'apikey' => $config['api_key'],
                'Content-Type' => 'application/json',
            ])->post("{$config['api_url']}/message/sendText/{$instanceName}", [
                'number' => $formattedPhone,
                'text' => $text,
            ]);

            if (!$response->successful()) {
                Log::error('AIChatbot: Failed to send', ['status' => $response->status(), 'body' => $response->body()]);
                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('AIChatbot: Send exception - ' . $e->getMessage());
            return false;
        }
    }
}
