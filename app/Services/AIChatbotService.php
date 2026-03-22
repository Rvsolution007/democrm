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

class AIChatbotService
{
    private int $companyId;
    private int $userId;
    private VertexAIService $vertexAI;

    public function __construct(int $companyId, int $userId)
    {
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->vertexAI = new VertexAIService($companyId);
    }

    /**
     * Main entry point: process an incoming WhatsApp message
     *
     * @param string      $instanceName  WhatsApp instance
     * @param string      $phone         Sender phone number (normalized)
     * @param string      $messageText   Message text content
     * @param array|null  $replyContext   Quoted message data (if reply)
     * @param string|null $imageUrl       Image URL (if image sent)
     * @return array  Result with 'status', 'response', etc.
     */
    public function processMessage(
        string $instanceName,
        string $phone,
        string $messageText,
        ?array $replyContext = null,
        ?string $imageUrl = null
    ): array {
        // Use DB lock to prevent race conditions from rapid messages
        return DB::transaction(function () use ($instanceName, $phone, $messageText, $replyContext, $imageUrl) {

            // 1. Find or create session
            $session = AiChatSession::findOrCreateForPhone($this->companyId, $phone, $instanceName);
            $session->update(['last_message_at' => now()]);

            // 2. Save incoming user message
            AiChatMessage::create([
                'session_id' => $session->id,
                'role' => 'user',
                'message' => $messageText,
                'message_type' => $imageUrl ? 'image' : 'text',
                'image_url' => $imageUrl,
                'reply_context' => $replyContext,
            ]);

            // 3. Build AI prompt with full context
            $systemPrompt = $this->buildSystemPrompt($session);
            $chatHistory = $this->buildChatHistory($session, $messageText, $replyContext, $imageUrl);

            // 4. Call Vertex AI
            $aiResponse = $this->vertexAI->generateContent($systemPrompt, $chatHistory, $imageUrl);

            // 5. Parse structured data from AI response
            $parsedData = $this->parseAIResponse($aiResponse, $session);

            // 6. Update session state, create/update lead & quote
            $this->updateSessionState($session, $parsedData);

            // 7. Get the clean response text (without JSON markers)
            $responseText = $parsedData['response_text'] ?? $aiResponse;

            // 8. Save bot response message
            AiChatMessage::create([
                'session_id' => $session->id,
                'role' => 'bot',
                'message' => $responseText,
                'message_type' => 'text',
            ]);

            // 9. Send via Evolution API
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

    /**
     * Build the system prompt with chatflow context, memory, and catalogue data
     */
    private function buildSystemPrompt(AiChatSession $session): string
    {
        // Get admin-defined system prompt
        $basePrompt = Setting::getValue('ai_bot', 'system_prompt', '', $this->companyId);

        // Get chatflow steps
        $chatflowContext = $this->buildChatflowContext($session);

        // Get memory context (existing lead + quote data)
        $memoryContext = $this->buildMemoryContext($session);

        // Get catalogue data relevant to current state
        $catalogueContext = $this->buildCatalogueContext($session);

        $fullPrompt = $basePrompt . "\n\n";
        $fullPrompt .= "---\n## CURRENT SESSION CONTEXT\n\n";
        $fullPrompt .= $chatflowContext . "\n\n";

        if (!empty($memoryContext)) {
            $fullPrompt .= "## MEMORY (Previous data from this customer)\n";
            $fullPrompt .= $memoryContext . "\n\n";
        }

        if (!empty($catalogueContext)) {
            $fullPrompt .= "## CATALOGUE DATA\n";
            $fullPrompt .= $catalogueContext . "\n\n";
        }

        // Add structured response instruction
        $fullPrompt .= "## RESPONSE FORMAT INSTRUCTION\n";
        $fullPrompt .= "You MUST respond with a JSON block followed by your message text.\n";
        $fullPrompt .= "JSON format: ```json\n{\"action\":\"<action>\",\"data\":{...}}\n```\n";
        $fullPrompt .= "Then on a new line, write the message to send to the customer.\n\n";
        $fullPrompt .= "Possible actions:\n";
        $fullPrompt .= "- `greeting` — casual/greeting response, no data needed\n";
        $fullPrompt .= "- `select_product` — user selected a product. data: {\"product_id\": <id>}\n";
        $fullPrompt .= "- `select_combo` — user selected a combo value. data: {\"column_slug\": \"<slug>\", \"value\": \"<selected_value>\"}\n";
        $fullPrompt .= "- `answer_optional` — user answered optional question. data: {\"field_key\": \"<key>\", \"value\": \"<answer>\"}\n";
        $fullPrompt .= "- `skip_optional` — user didn't answer optional, skip it. data: {}\n";
        $fullPrompt .= "- `complete` — all steps done. data: {}\n";
        $fullPrompt .= "- `escalate` — user needs human support. data: {\"reason\": \"<why>\"}\n";
        $fullPrompt .= "- `continue` — general conversation, no state change. data: {}\n\n";
        $fullPrompt .= "IMPORTANT: Always include the JSON block even for casual greetings.\n";

        return $fullPrompt;
    }

    /**
     * Build chatflow context for the AI prompt
     */
    private function buildChatflowContext(AiChatSession $session): string
    {
        $steps = ChatflowStep::where('company_id', $this->companyId)
            ->orderBy('sort_order')
            ->get();

        if ($steps->isEmpty()) {
            return "No chatflow defined. Have a natural conversation and help the customer.";
        }

        $context = "### Chatflow Steps (follow this sequence):\n";

        foreach ($steps as $step) {
            $status = '⬜'; // Not started
            $currentStepId = $session->current_step_id;

            // Check if this step is completed (earlier in order than current)
            if ($currentStepId) {
                $currentStep = $steps->firstWhere('id', $currentStepId);
                if ($currentStep && $step->sort_order < $currentStep->sort_order) {
                    $status = '✅'; // Completed
                } elseif ($step->id == $currentStepId) {
                    $status = '🔄'; // Current step (retries: {$session->current_step_retries})
                }
            }

            $optional = $step->is_optional ? ' (OPTIONAL - ask only once)' : '';
            $context .= "{$status} Step {$step->sort_order}: [{$step->step_type}] {$step->name}{$optional}\n";

            if ($step->question_text) {
                $context .= "   Question: \"{$step->question_text}\"\n";
            }
            if ($step->step_type === 'ask_combo' && $step->linkedColumn) {
                $context .= "   Column: {$step->linkedColumn->name}\n";
            }
            if ($step->field_key) {
                $context .= "   Save answer to: {$step->field_key}\n";
            }
        }

        // Collected answers so far
        $answers = $session->collected_answers ?? [];
        if (!empty($answers)) {
            $context .= "\n### Already collected answers:\n";
            foreach ($answers as $key => $value) {
                $context .= "- {$key}: {$value}\n";
            }
        }

        // Optional questions already asked
        $optionalAsked = $session->optional_asked ?? [];
        if (!empty($optionalAsked)) {
            $context .= "\n### Optional questions already asked (DO NOT ask again):\n";
            foreach ($optionalAsked as $field) {
                $context .= "- {$field}\n";
            }
        }

        // Current step retries
        if ($session->current_step_retries > 0) {
            $currentStep = $steps->firstWhere('id', $session->current_step_id);
            $maxRetries = $currentStep->max_retries ?? 2;
            $context .= "\n⚠️ Current step has been retried {$session->current_step_retries}/{$maxRetries} times.";
            if ($session->current_step_retries >= $maxRetries) {
                if ($currentStep && $currentStep->isOptionalStep()) {
                    $context .= " SKIP this step and move to next.";
                } else {
                    $context .= " ESCALATE to human support.";
                }
            }
        }

        return $context;
    }

    /**
     * Build memory context from existing lead and quote data
     */
    private function buildMemoryContext(AiChatSession $session): string
    {
        $context = '';

        if ($session->lead_id) {
            $lead = Lead::with('products')->find($session->lead_id);
            if ($lead) {
                $context .= "### Existing Lead:\n";
                $context .= "- Name: " . ($lead->name ?: 'Not provided') . "\n";
                $context .= "- Phone: {$lead->phone}\n";
                if ($lead->city) $context .= "- City: {$lead->city}\n";
                if ($lead->ai_custom_data) {
                    foreach ($lead->ai_custom_data as $key => $val) {
                        $context .= "- {$key}: {$val}\n";
                    }
                }
            }
        }

        if ($session->quote_id) {
            $quote = Quote::with('items.product')->find($session->quote_id);
            if ($quote) {
                $context .= "\n### Existing Quote (#{$quote->quote_no}):\n";
                foreach ($quote->items as $item) {
                    $context .= "- Product: {$item->product_name}";
                    if ($item->selected_combination) {
                        $combos = collect($item->selected_combination)->map(fn($v, $k) => "{$k}: {$v}")->implode(', ');
                        $context .= " ({$combos})";
                    }
                    if ($item->rate > 0) {
                        $context .= " — ₹" . number_format($item->rate / 100, 2);
                    }
                    $context .= "\n";
                }
            }
        }

        return $context;
    }

    /**
     * Build catalogue context relevant to current session state
     */
    private function buildCatalogueContext(AiChatSession $session): string
    {
        $answers = $session->collected_answers ?? [];
        $context = '';

        // If no product selected yet, list available products
        if (!isset($answers['product_id'])) {
            $products = Product::where('company_id', $this->companyId)
                ->where('status', 'active')
                ->get(['id', 'name', 'description', 'sale_price']);

            if ($products->isNotEmpty()) {
                $context .= "### Available Products:\n";
                foreach ($products as $product) {
                    $price = $product->sale_price > 0 ? " — ₹" . number_format($product->sale_price / 100, 2) : '';
                    $context .= "- ID:{$product->id} | {$product->name}{$price}\n";
                    if ($product->description) {
                        $context .= "  Description: {$product->description}\n";
                    }
                }
            }
            return $context;
        }

        // Product is selected — show combo options for current step
        $productId = $answers['product_id'];
        $product = Product::with(['combos.column', 'activeVariations'])->find($productId);

        if (!$product) return $context;

        $context .= "### Selected Product: {$product->name}\n";
        if ($product->description) {
            $context .= "Description: {$product->description}\n";
        }

        // Show combo options
        foreach ($product->combos as $combo) {
            $column = $combo->column;
            $selectedValue = $answers[$column->slug] ?? null;
            $values = $combo->selected_values ?? [];

            if ($selectedValue) {
                $context .= "- {$column->name}: {$selectedValue} ✅\n";
            } else {
                $context .= "- {$column->name}: [" . implode(', ', $values) . "] ⬜ (pending)\n";
            }
        }

        // If all combos are selected, show variation price
        $allCombosSelected = true;
        $combination = [];
        foreach ($product->combos as $combo) {
            $slug = $combo->column->slug;
            if (!isset($answers[$slug])) {
                $allCombosSelected = false;
                break;
            }
            $combination[$slug] = $answers[$slug];
        }

        if ($allCombosSelected && !empty($combination)) {
            $key = ProductVariation::generateKey($combination);
            $variation = $product->activeVariations->firstWhere('combination_key', $key);
            if ($variation) {
                $context .= "\n### Matched Variation:\n";
                $context .= "Price: ₹" . number_format($variation->price / 100, 2) . "\n";
                if ($variation->description) {
                    $context .= "Details: {$variation->description}\n";
                }
            }
        }

        return $context;
    }

    /**
     * Build chat history in Vertex AI format
     */
    private function buildChatHistory(
        AiChatSession $session,
        string $currentMessage,
        ?array $replyContext,
        ?string $imageUrl
    ): array {
        $history = [];

        // Get recent messages from DB
        $messages = $session->getRecentMessages(10);

        foreach ($messages as $msg) {
            $role = $msg->role === 'user' ? 'user' : 'model';
            $text = $msg->message;

            // Add reply context info if present
            if ($msg->reply_context) {
                $quotedText = $msg->reply_context['quoted_text'] ?? '';
                if ($quotedText) {
                    $text = "[Replying to: \"{$quotedText}\"]\n{$text}";
                }
            }

            $history[] = ['role' => $role, 'text' => $text];
        }

        // Add current message (if not already in the history — it was just saved)
        $currentText = $currentMessage;
        if ($replyContext) {
            $quotedText = $replyContext['quoted_text'] ?? '';
            if ($quotedText) {
                $currentText = "[Replying to: \"{$quotedText}\"]\n{$currentMessage}";
            }
        }

        // The last message in history should be the current one we just saved
        // If not (edge case), add it
        if (empty($history) || end($history)['text'] !== $currentText) {
            $history[] = ['role' => 'user', 'text' => $currentText];
        }

        return $history;
    }

    /**
     * Parse the AI response for structured action data
     * Expected format: ```json\n{...}\n```\nMessage text
     */
    private function parseAIResponse(string $response, AiChatSession $session): array
    {
        $result = [
            'action' => 'continue',
            'data' => [],
            'response_text' => $response,
        ];

        // Try to extract JSON block from response
        if (preg_match('/```json\s*(.*?)\s*```/s', $response, $matches)) {
            $jsonStr = $matches[1];
            $parsed = json_decode($jsonStr, true);

            if ($parsed && isset($parsed['action'])) {
                $result['action'] = $parsed['action'];
                $result['data'] = $parsed['data'] ?? [];
            }

            // Remove JSON block from response text (send only human-readable part)
            $result['response_text'] = trim(preg_replace('/```json\s*.*?\s*```/s', '', $response));
        }

        return $result;
    }

    /**
     * Update session state based on parsed AI response
     */
    private function updateSessionState(AiChatSession $session, array $parsedData): void
    {
        $action = $parsedData['action'];
        $data = $parsedData['data'];

        switch ($action) {
            case 'select_product':
                $this->handleProductSelection($session, $data);
                break;

            case 'select_combo':
                $this->handleComboSelection($session, $data);
                break;

            case 'answer_optional':
                $this->handleOptionalAnswer($session, $data);
                break;

            case 'skip_optional':
                $this->handleSkipOptional($session);
                break;

            case 'complete':
                $session->update(['status' => 'completed']);
                break;

            case 'escalate':
                $this->handleEscalation($session, $data);
                break;

            case 'greeting':
            case 'continue':
            default:
                // Check if we need to advance the chatflow
                $this->advanceChatflowIfNeeded($session);
                break;
        }

        $session->save();
    }

    /**
     * Handle product selection — create lead and quote
     */
    private function handleProductSelection(AiChatSession $session, array $data): void
    {
        $productId = $data['product_id'] ?? null;
        if (!$productId) return;

        $product = Product::find($productId);
        if (!$product || $product->company_id !== $this->companyId) return;

        // Save to session
        $session->setAnswer('product_id', $productId);
        $session->setAnswer('product_name', $product->name);

        // Create Lead if not exists
        if (!$session->lead_id) {
            $lead = Lead::create([
                'company_id' => $this->companyId,
                'created_by_user_id' => $this->userId,
                'source' => 'whatsapp',
                'name' => $session->phone_number,
                'phone' => $session->phone_number,
                'stage' => 'new',
                'product_name' => $product->name,
            ]);
            $session->lead_id = $lead->id;

            // Attach product to lead
            $lead->products()->attach($productId, [
                'quantity' => 1,
                'price' => $product->sale_price,
            ]);
        }

        // Create Quote if not exists
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
                'product_name' => $product->name,
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

        // Move to next chatflow step
        $this->advanceChatflow($session);
    }

    /**
     * Handle combo value selection — update quote variation
     */
    private function handleComboSelection(AiChatSession $session, array $data): void
    {
        $columnSlug = $data['column_slug'] ?? null;
        $value = $data['value'] ?? null;
        if (!$columnSlug || !$value) return;

        // Save to session answers
        $session->setAnswer($columnSlug, $value);
        $session->current_step_retries = 0;

        // Check if all combos are now selected
        $productId = $session->getAnswer('product_id');
        if (!$productId) return;

        $product = Product::with('combos.column')->find($productId);
        if (!$product) return;

        $allSelected = true;
        $combination = [];
        foreach ($product->combos as $combo) {
            $slug = $combo->column->slug;
            $val = $session->getAnswer($slug);
            if (!$val) {
                $allSelected = false;
                break;
            }
            $combination[$slug] = $val;
        }

        // If all combos selected, update quote with variation price
        if ($allSelected && !empty($combination) && $session->quote_id) {
            $key = ProductVariation::generateKey($combination);
            $variation = ProductVariation::where('product_id', $productId)
                ->where('combination_key', $key)
                ->where('status', 'active')
                ->first();

            if ($variation) {
                $quoteItem = QuoteItem::where('quote_id', $session->quote_id)
                    ->where('product_id', $productId)
                    ->first();

                if ($quoteItem) {
                    $quoteItem->update([
                        'variation_id' => $variation->id,
                        'selected_combination' => $combination,
                        'rate' => $variation->price,
                        'unit_price' => $variation->price,
                        'description' => $variation->description ?? $quoteItem->description,
                    ]);

                    // Recalculate quote totals
                    $quote = Quote::find($session->quote_id);
                    if ($quote) {
                        $quote->recalculateTotals();
                    }
                }
            }
        }

        // Move to next chatflow step
        $this->advanceChatflow($session);
    }

    /**
     * Handle optional question answer — save to lead
     */
    private function handleOptionalAnswer(AiChatSession $session, array $data): void
    {
        $fieldKey = $data['field_key'] ?? null;
        $value = $data['value'] ?? null;
        if (!$fieldKey || !$value) return;

        // Mark as asked
        $session->markOptionalAsked($fieldKey);
        $session->setAnswer($fieldKey, $value);
        $session->current_step_retries = 0;

        // Update lead with custom data
        if ($session->lead_id) {
            $lead = Lead::find($session->lead_id);
            if ($lead) {
                // Some fields go directly on the lead model
                $directFields = ['name', 'email', 'city', 'state'];
                if (in_array($fieldKey, $directFields)) {
                    $lead->update([$fieldKey => $value]);
                } else {
                    // Other fields go to ai_custom_data
                    $customData = $lead->ai_custom_data ?? [];
                    $customData[$fieldKey] = $value;
                    $lead->update(['ai_custom_data' => $customData]);
                }
            }
        }

        // Move to next chatflow step
        $this->advanceChatflow($session);
    }

    /**
     * Handle skip optional — mark as asked and move on
     */
    private function handleSkipOptional(AiChatSession $session): void
    {
        $currentStep = ChatflowStep::find($session->current_step_id);
        if ($currentStep && $currentStep->field_key) {
            $session->markOptionalAsked($currentStep->field_key);
        }
        $session->current_step_retries = 0;
        $this->advanceChatflow($session);
    }

    /**
     * Handle escalation — mark lead for manual follow-up
     */
    private function handleEscalation(AiChatSession $session, array $data): void
    {
        if ($session->lead_id) {
            $lead = Lead::find($session->lead_id);
            if ($lead) {
                $reason = $data['reason'] ?? 'AI bot escalated';
                $lead->update([
                    'notes' => ($lead->notes ? $lead->notes . "\n" : '') . "[AI Bot Escalated] {$reason}",
                    'stage' => 'contacted',
                ]);
            }
        }
    }

    /**
     * Advance chatflow to the next step
     */
    private function advanceChatflow(AiChatSession $session): void
    {
        $currentStep = ChatflowStep::find($session->current_step_id);

        if ($currentStep) {
            $nextStep = $currentStep->getNextStep();
        } else {
            // Start from the first step
            $nextStep = ChatflowStep::getFirstStep($this->companyId);
        }

        if ($nextStep) {
            $session->current_step_id = $nextStep->id;
            $session->current_step_retries = 0;
        } else {
            // No more steps — chatflow complete
            $session->update(['status' => 'completed']);
        }
    }

    /**
     * Advance chatflow if the current step hasn't been set yet
     * (For greeting/continue actions where we need to start the flow)
     */
    private function advanceChatflowIfNeeded(AiChatSession $session): void
    {
        if (!$session->current_step_id) {
            $firstStep = ChatflowStep::getFirstStep($this->companyId);
            if ($firstStep) {
                $session->current_step_id = $firstStep->id;
            }
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
            // Format phone for Evolution API
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
                Log::error('AIChatbot: Failed to send message', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('AIChatbot: Send exception - ' . $e->getMessage());
            return false;
        }
    }
}
