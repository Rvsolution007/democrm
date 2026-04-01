<?php

namespace App\Services;

use App\Models\AiChatSession;
use App\Models\AiChatMessage;
use App\Models\AiChatTrace;
use App\Models\AiTokenLog;
use App\Models\ChatflowStep;
use App\Models\ChatFollowupSchedule;
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
    private array $routeTrace = [];
    private ?int $currentMessageId = null;
    private array $pendingTraces = [];
    private string $greetingPrompt;
    private string $businessPrompt;
    private string $spellCorrectionPrompt;

    public function __construct(int $companyId, int $userId)
    {
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->vertexAI = new VertexAIService($companyId);
        $this->replyLanguage = Setting::getValue('ai_bot', 'reply_language', 'auto', $companyId);
        $this->greetingPrompt = Setting::getValue('ai_bot', 'greeting_prompt', '', $companyId);
        $this->businessPrompt = Setting::getValue('ai_bot', 'business_prompt', '', $companyId);
        $this->spellCorrectionPrompt = Setting::getValue('ai_bot', 'spell_correction_prompt', '', $companyId);
    }

    /**
     * Get route trace — shows which code path was taken during message processing.
     * Used by diagnostic/test services to display exact routing decisions.
     */
    public function getRouteTrace(): array
    {
        return $this->routeTrace;
    }

    /**
     * Log a node execution trace for n8n-style diagnostic UI.
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
     * Called AFTER the DB::transaction commits so traces are never lost.
     */
    private function flushTraces(): void
    {
        foreach ($this->pendingTraces as $trace) {
            try {
                AiChatTrace::create($trace);
            } catch (\Exception $e) {
                Log::warning('AIChatbot: Failed to save trace - ' . $e->getMessage());
            }
        }
        $this->pendingTraces = [];
    }

    private ?CatalogueCustomColumn $uniqueColumn = null;
    private bool $uniqueColumnLoaded = false;
    private $aiVisibleColumns = null;

    private function getProductDisplayName(Product $product): string
    {
        if (!$this->uniqueColumnLoaded) {
            $this->uniqueColumn = CatalogueCustomColumn::where('company_id', $this->companyId)
                ->where('is_unique', true)
                ->first();
            $this->uniqueColumnLoaded = true;
        }

        if ($this->uniqueColumn) {
            // Load visible columns if not loaded
            if ($this->aiVisibleColumns === null) {
                $this->aiVisibleColumns = CatalogueCustomColumn::where('company_id', $this->companyId)
                    ->where('show_in_ai', true)
                    ->where('is_active', true)
                    ->orderBy('sort_order', 'asc')
                    ->get();
            }

            // Find columns above the unique column
            $uniqueSortOrder = $this->uniqueColumn->sort_order;
            $columnsAbove = [];
            foreach ($this->aiVisibleColumns as $col) {
                if ($col->sort_order < $uniqueSortOrder) {
                    $columnsAbove[] = $col;
                }
            }

            // Get unique value
            $uniqueValue = '';
            if ($this->uniqueColumn->is_system) {
                $slug = $this->uniqueColumn->slug;
                if ($slug === 'product_name') $slug = 'name';
                $uniqueValue = $product->{$slug};
            } else {
                if ($product->relationLoaded('customValues')) {
                    $customVal = $product->customValues->where('column_id', $this->uniqueColumn->id)->first();
                } else {
                    $customVal = $product->customValues()->where('column_id', $this->uniqueColumn->id)->first();
                }
                if ($customVal && !empty($customVal->value)) {
                    $val = json_decode($customVal->value, true);
                    $uniqueValue = is_array($val) ? implode(', ', $val) : $customVal->value;
                }
            }

            // If unique column is at the very top (no columns above it)
            if (empty($columnsAbove)) {
                return $uniqueValue ?: $product->name;
            }

            // There are columns above it. Combine them into a base name.
            $baseNameParts = [];
            foreach ($columnsAbove as $col) {
                if ($col->is_system) {
                    $slug = $col->slug;
                    if ($slug === 'product_name') $slug = 'name';
                    $val = $product->{$slug};
                    if (!empty($val)) {
                        $baseNameParts[] = $val;
                    }
                } else {
                    // For is_category columns, get value from product's category relationship
                    if ($col->is_category && $product->category_id) {
                        $catModel = $product->relationLoaded('category')
                            ? $product->category
                            : $product->category()->first();
                        if ($catModel && !empty($catModel->name)) {
                            $baseNameParts[] = $catModel->name;
                        }
                    } else {
                        if ($product->relationLoaded('customValues')) {
                            $customVal = $product->customValues->where('column_id', $col->id)->first();
                        } else {
                            $customVal = $product->customValues()->where('column_id', $col->id)->first();
                        }
                        if ($customVal && !empty($customVal->value)) {
                            $valStr = json_decode($customVal->value, true);
                            $valStr = is_array($valStr) ? implode(', ', $valStr) : $customVal->value;
                            if (!empty($valStr)) {
                                $baseNameParts[] = $valStr;
                            }
                        }
                    }
                }
            }

            $baseName = implode(' ', $baseNameParts);
            
            // Fallback in case columns above had no data
            if (empty(trim($baseName))) {
                return $uniqueValue ?: $product->name;
            }
            
            // If the unique value is empty, just return base name
            if (empty(trim((string)$uniqueValue))) {
                return trim($baseName);
            }

            // If the unique value is already basically the same as base name, don't duplicate
            if (trim(strtolower((string)$baseName)) === trim(strtolower((string)$uniqueValue))) {
                return trim($baseName);
            }

            // Combine base name and unique value
            return trim($baseName) . ' (' . trim((string)$uniqueValue) . ')';
        }

        return $product->name;
    }

    /**
     * Get the product's "base" name (group/line name) WITHOUT the unique variant identifier.
     * Used for grouping products into product lines (e.g., "Cabinet Handle").
     * Falls back to the native `name` column only if no custom columns exist.
     */
    private function getProductBaseName(Product $product): string
    {
        if (!$this->uniqueColumnLoaded) {
            $this->uniqueColumn = CatalogueCustomColumn::where('company_id', $this->companyId)
                ->where('is_unique', true)
                ->first();
            $this->uniqueColumnLoaded = true;
        }

        if ($this->uniqueColumn) {
            if ($this->aiVisibleColumns === null) {
                $this->aiVisibleColumns = CatalogueCustomColumn::where('company_id', $this->companyId)
                    ->where('show_in_ai', true)
                    ->where('is_active', true)
                    ->orderBy('sort_order', 'asc')
                    ->get();
            }

            $uniqueSortOrder = $this->uniqueColumn->sort_order;
            $columnsAbove = [];
            foreach ($this->aiVisibleColumns as $col) {
                if ($col->sort_order < $uniqueSortOrder) {
                    $columnsAbove[] = $col;
                }
            }

            // If there are columns above the unique column, build the base name from them
            if (!empty($columnsAbove)) {
                $baseNameParts = [];
                foreach ($columnsAbove as $col) {
                    if ($col->is_system) {
                        $slug = $col->slug;
                        if ($slug === 'product_name') $slug = 'name';
                        $val = $product->{$slug};
                        if (!empty($val)) {
                            $baseNameParts[] = $val;
                        }
                    } else {
                        // For is_category columns, get value from product's category relationship
                        if ($col->is_category && $product->category_id) {
                            $catModel = $product->relationLoaded('category')
                                ? $product->category
                                : $product->category()->first();
                            if ($catModel && !empty($catModel->name)) {
                                $baseNameParts[] = $catModel->name;
                            }
                        } else {
                            if ($product->relationLoaded('customValues')) {
                                $customVal = $product->customValues->where('column_id', $col->id)->first();
                            } else {
                                $customVal = $product->customValues()->where('column_id', $col->id)->first();
                            }
                            if ($customVal && !empty($customVal->value)) {
                                $valStr = json_decode($customVal->value, true);
                                $valStr = is_array($valStr) ? implode(', ', $valStr) : $customVal->value;
                                if (!empty($valStr)) {
                                    $baseNameParts[] = $valStr;
                                }
                            }
                        }
                    }
                }

                $baseName = implode(' ', $baseNameParts);
                if (!empty(trim($baseName))) {
                    return trim($baseName);
                }
            }

            // No columns above unique, or they were empty — use the unique value itself as base
            if ($this->uniqueColumn->is_system) {
                $slug = $this->uniqueColumn->slug;
                if ($slug === 'product_name') $slug = 'name';
                $val = $product->{$slug};
                if (!empty($val)) return $val;
            } else {
                if ($product->relationLoaded('customValues')) {
                    $customVal = $product->customValues->where('column_id', $this->uniqueColumn->id)->first();
                } else {
                    $customVal = $product->customValues()->where('column_id', $this->uniqueColumn->id)->first();
                }
                if ($customVal && !empty($customVal->value)) {
                    $val = json_decode($customVal->value, true);
                    $val = is_array($val) ? implode(', ', $val) : $customVal->value;
                    if (!empty($val)) return $val;
                }
            }
        }

        // Ultimate fallback: native name column
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
        $this->pendingTraces = [];

        try {
            $result = DB::transaction(function () use ($instanceName, $phone, $messageText, $replyContext, $imageUrl) {

                $session = AiChatSession::findOrCreateForPhone($this->companyId, $phone, $instanceName);
                
                // ── Session Time Validity Check ──
                $validDays = (int) Setting::getValue('ai_bot', 'session_valid_days', 10, $this->companyId);
                
                if (!$session->wasRecentlyCreated && $session->last_message_at) {
                    $daysSinceLastMessage = $session->last_message_at->diffInDays(now());
                    
                    if ($daysSinceLastMessage >= $validDays) {
                        Log::info('AIChatbot: Session expired due to valid days limit', [
                            'session_id' => $session->id,
                            'phone' => $phone,
                            'days_elapsed' => $daysSinceLastMessage,
                            'valid_days' => $validDays,
                        ]);
                        
                        // Trace the timeout before replacing the session
                        $this->traceNode($session->id, 'SessionTimeout', 'routing', 'warning', 
                            ['reason' => 'Validity period elapsed', 'days_elapsed' => $daysSinceLastMessage, 'limit_days' => $validDays], 
                            ['action' => 'expired_old_session', 'started_new_session' => true]
                        );
                        
                        $session->update(['status' => 'expired']);
                        
                        // Create a fresh session
                        $session = AiChatSession::create([
                            'company_id' => $this->companyId,
                            'phone_number' => $phone,
                            'instance_name' => $instanceName,
                            'status' => 'active',
                            'last_message_at' => now(),
                        ]);
                    }
                }

                $session->update(['last_message_at' => now()]);

                // ── Early Lead Creation (on first message) ──
                if (!$session->lead_id) {
                    // Calculate first follow-up time if schedules exist
                    $firstSchedule = ChatFollowupSchedule::where('company_id', $this->companyId)
                        ->where('is_active', true)
                        ->orderBy('delay_minutes', 'asc')
                        ->first();
                    $nextFollowUpAt = $firstSchedule ? now()->addMinutes($firstSchedule->delay_minutes) : null;

                    $lead = Lead::create([
                        'company_id' => $this->companyId,
                        'created_by_user_id' => $this->userId,
                        'source' => 'whatsapp',
                        'name' => $phone,
                        'phone' => $phone,
                        'stage' => 'new',
                        'next_follow_up_at' => $nextFollowUpAt,
                    ]);
                    $session->lead_id = $lead->id;
                    $session->save();

                    $this->traceNode($session->id, 'EarlyLeadCreated', 'database', 'success',
                        ['phone' => $phone, 'trigger' => 'first_message'],
                        ['lead_id' => $lead->id, 'lead_source' => 'whatsapp', 'next_follow_up_at' => $nextFollowUpAt ? $nextFollowUpAt->toDateTimeString() : 'none']);

                    Log::info('AIChatbot: Early lead created on first message', ['session' => $session->id, 'lead_id' => $lead->id]);
                }

                // Save incoming user message
                $userMsg = AiChatMessage::create([
                    'session_id' => $session->id,
                    'role' => 'user',
                    'message' => $messageText,
                    'message_type' => $imageUrl ? 'image' : 'text',
                    'image_url' => $imageUrl,
                    'reply_context' => $replyContext,
                ]);
                $this->currentMessageId = $userMsg->id;

                // ── Session Health Check (Deleted Quotes/Leads or Closed Leads) ──
                $needsReset = false;
                $resetReason = '';

                if ($session->lead_id) {
                    $lead = \App\Models\Lead::find($session->lead_id);
                    if (!$lead) {
                        $needsReset = true;
                        $resetReason = "Admin deleted the Lead";
                    } elseif (!$lead->isOpen()) {
                        $needsReset = true;
                        $resetReason = "Lead stage is closed ({$lead->stage})";
                    }
                }

                if (!$needsReset && $session->quote_id) {
                    $quote = \App\Models\Quote::find($session->quote_id);
                    if (!$quote) {
                        $needsReset = true;
                        $resetReason = "Admin deleted the Quote";
                    }
                }

                if ($needsReset) {
                    Log::warning("AIChatbot: Resetting session for {$phone}. Reason: {$resetReason}");

                    $session->update([
                        'lead_id' => null,
                        'quote_id' => null,
                        'current_step_id' => null,
                        'collected_answers' => [],
                        'optional_asked' => [],
                        'current_step_retries' => 0,
                        'catalogue_sent' => false,
                        'conversation_state' => 'reset_due_to_closure',
                    ]);

                    $this->traceNode($session->id, 'SessionReset', 'routing', 'warning', 
                        ['reason' => $resetReason], 
                        ['action' => 'cleared_session_data', 'restarted_as_new' => true]
                    );
                }

                // ── TRACE: Receive Message
                $messageType = 'text';
                if ($imageUrl) $messageType = 'image';
                elseif ($messageText === '[audio]') $messageType = 'audio';
                elseif ($messageText === '[sticker]') $messageType = 'sticker';
                elseif ($messageText === '[media]') $messageType = 'media';
                elseif (str_starts_with($messageText, '[contact:')) $messageType = 'contact';
                elseif ($messageText === '[location]' || $messageText === '[live_location]') $messageType = 'location';

                $receiveInput = [
                    'phone' => $phone,
                    'message' => $messageText,
                    'message_type' => $messageType,
                    'has_image' => (bool)$imageUrl,
                    'has_video' => str_contains($messageText, '[video]'),
                    'has_document' => str_contains($messageText, '[document]'),
                ];
                if ($imageUrl) $receiveInput['image_url'] = $imageUrl;
                if ($replyContext) {
                    $receiveInput['reply_id'] = $replyContext['stanza_id'] ?? null;
                    $receiveInput['reply_message'] = $replyContext['quoted_text'] ?? null;
                }

                $this->traceNode($session->id, 'ReceiveMessage', 'routing', 'success',
                    $receiveInput,
                    ['message_id' => $userMsg->id, 'session_id' => $session->id, 'is_new_session' => $session->wasRecentlyCreated]
                );

                // Get quoted text if reply
                $quotedText = $replyContext['quoted_text'] ?? null;
                $fullMessage = $quotedText ? "[Replying to: \"{$quotedText}\"]\n{$messageText}" : $messageText;

                // ── TRACE: Reply Detector (only when reply context exists)
                if ($replyContext && $quotedText) {
                    $this->traceNode($session->id, 'ReplyDetector', 'routing', 'success',
                        [
                            'reply_id' => $replyContext['stanza_id'] ?? null,
                            'reply_message' => $quotedText,
                            'original_message' => $messageText,
                        ],
                        [
                            'has_reply' => true,
                            'combined_message' => mb_substr($fullMessage, 0, 200),
                        ]
                    );
                }

                // ═══ Detect language ONCE per session ═══
                if (empty($session->detected_language) && !empty($messageText)) {
                    $session->detected_language = $this->detectLanguage($messageText);
                    $session->save();
                }

                // ═══ Build conversation context from last 5 messages ═══
                $recentMessages = AiChatMessage::where('session_id', $session->id)
                    ->where('id', '!=', $userMsg->id) // exclude current message
                    ->orderBy('id', 'desc')
                    ->take(5)
                    ->get()
                    ->reverse();

                $contextMessage = $fullMessage;
                if ($recentMessages->isNotEmpty()) {
                    $contextParts = [];
                    foreach ($recentMessages as $rm) {
                        $prefix = $rm->role === 'bot' ? 'Bot' : 'User';
                        $contextParts[] = "{$prefix}: {$rm->message}";
                    }
                    $contextParts[] = "User: {$messageText}";
                    $contextMessage = implode("\n", $contextParts);
                }

                // ═══ SMART ROUTER — decide Tier 1, Tier 2, or PHP Direct ═══
                $responseText = $this->routeMessage($session, $contextMessage, $messageText, $imageUrl);

                // Save bot response
                AiChatMessage::create([
                    'session_id' => $session->id,
                    'role' => 'bot',
                    'message' => $responseText,
                    'message_type' => 'text',
                ]);

                // Send via WhatsApp
                $sendStart = microtime(true);
                $sendResult = $this->sendWhatsAppMessage($instanceName, $phone, $responseText);
                $sendMs = (int)((microtime(true) - $sendStart) * 1000);

                // ── Update last_bot_message_at for follow-up timing ──
                $session->update(['last_bot_message_at' => now()]);

                // ── TRACE: Send Reply
                $this->traceNode($session->id, 'SendWhatsAppReply', 'delivery',
                    $sendResult ? 'success' : 'error',
                    ['phone' => $phone, 'response_length' => strlen($responseText), 'response_preview' => mb_substr($responseText, 0, 100)],
                    ['sent' => $sendResult],
                    $sendResult ? null : 'WhatsApp send failed',
                    $sendMs
                );

                return [
                    'status' => $sendResult ? 'sent' : 'send_failed',
                    'response' => $responseText,
                    'session_id' => $session->id,
                    'lead_id' => $session->lead_id,
                    'quote_id' => $session->quote_id,
                ];
            });

            return $result;
        } finally {
            // Flush traces ALWAYS — even on exception, so error diagnostics are preserved
            $this->flushTraces();
        }
    }

    // ═══════════════════════════════════════════════════════
    // MESSAGE ROUTER — Core logic
    // ═══════════════════════════════════════════════════════

    private function routeMessage(AiChatSession $session, string $fullMessage, string $rawMessage, ?string $imageUrl): string
    {
        $this->routeTrace = [];
        $steps = ChatflowStep::where('company_id', $this->companyId)->orderBy('sort_order')->get();
        $currentStep = $session->current_step_id ? $steps->firstWhere('id', $session->current_step_id) : null;
        $answers = $session->collected_answers ?? [];

        // ══ GREETING INTERCEPT ══
        if ($this->isGreeting($rawMessage)) {
            $this->routeTrace[] = 'greeting_intercept';
            $this->traceNode($session->id, 'GreetingDetector', 'routing', 'success',
                ['message' => $rawMessage, 'detection_method' => 'PHP'],
                ['detected' => true, 'matched_word' => strtolower(trim($rawMessage)), 'greeting_prompt_configured' => !empty($this->greetingPrompt), 'greeting_prompt_preview' => mb_substr($this->greetingPrompt, 0, 100)]);

            // Use dedicated greeting prompt if configured
            if (!empty($this->greetingPrompt)) {
                $this->routeTrace[] = 'handleGreetingPrompt';
                $t = microtime(true);
                $greetResult = $this->vertexAI->generateContent(
                    $this->greetingPrompt . "\n\n## LANGUAGE\nReply in the same language the customer is using.",
                    [['role' => 'user', 'text' => $rawMessage]],
                    null
                );
                $ms = (int)((microtime(true) - $t) * 1000);
                $this->logTokens($session, 2, $greetResult);
                $responseText = trim($greetResult['text'] ?? '');
                $this->traceNode($session->id, 'GreetingAIResponse', 'ai_call',
                    !empty($responseText) ? 'success' : 'error',
                    ['message' => $rawMessage, 'prompt_type' => 'greeting_prompt', 'prompt_preview' => mb_substr($this->greetingPrompt, 0, 150)],
                    ['response' => mb_substr($responseText, 0, 200), 'tokens_used' => $greetResult['total_tokens'] ?? 0, 'model' => 'gemini-2.0-flash'], null, $ms);
                if (!empty($responseText)) {
                    return $responseText;
                }
            }

            $this->routeTrace[] = 'handleTier2';
            Log::info('AIChatbot: Greeting → Tier 2', ['session' => $session->id]);
            return $this->handleTier2($session, $fullMessage, $imageUrl);
        }

        // ══ BUSINESS QUERY INTERCEPT ══
        if ($this->isBusinessQuery($rawMessage)) {
            $this->routeTrace[] = 'business_query_intercept';
            $this->traceNode($session->id, 'BusinessQueryDetector', 'routing', 'success',
                ['message' => $rawMessage, 'detection_method' => 'PHP'],
                ['detected' => true, 'business_prompt_configured' => !empty($this->businessPrompt), 'business_prompt_preview' => mb_substr($this->businessPrompt, 0, 100)]);

            if (!empty($this->businessPrompt)) {
                $this->routeTrace[] = 'handleBusinessPrompt';
                $t = microtime(true);
                $systemContext = "You are a helpful sales representative.\n" . $this->businessPrompt . "\n\nAnswer the user's business query concisely (1-2 sentences).";
                $businessResult = $this->vertexAI->generateContent(
                    $systemContext . "\n\n## LANGUAGE\nReply in the same language the customer is using.",
                    [['role' => 'user', 'text' => $rawMessage]],
                    null
                );
                $ms = (int)((microtime(true) - $t) * 1000);
                $this->logTokens($session, 2, $businessResult);
                $responseText = trim($businessResult['text'] ?? '');
                
                $this->traceNode($session->id, 'BusinessQueryAIResponse', 'ai_call',
                    !empty($responseText) ? 'success' : 'error',
                    ['message' => $rawMessage, 'prompt_type' => 'business_prompt', 'prompt_preview' => mb_substr($this->businessPrompt, 0, 150)],
                    ['response' => mb_substr($responseText, 0, 200), 'tokens_used' => $businessResult['total_tokens'] ?? 0, 'model' => 'gemini-2.0-flash'], null, $ms);
                
                if (!empty($responseText)) {
                    // Check if we should append the next chatflow question
                    $nextPrompt = "";
                    if ($session->conversation_state !== 'product_selected') {
                        // User hasn't finished product flow, don't nudge them yet or naturally fall back
                    } else {
                        // In the middle of chatflow
                        $steps = ChatflowStep::where('company_id', $this->companyId)->orderBy('sort_order')->get();
                        $nextPrompt = $this->buildNextStepPrompt($session, $steps);
                    }
                    
                    if ($nextPrompt) {
                        return $responseText . "\n\n👇\n" . $nextPrompt;
                    }
                    return $responseText;
                }
            }

            $this->routeTrace[] = 'handleTier2';
            Log::info('AIChatbot: Business Query → Tier 2', ['session' => $session->id]);
            return $this->handleTier2($session, $fullMessage, $imageUrl);
        }

        $this->traceNode($session->id, 'GreetingDetector', 'routing', 'success',
            ['message' => $rawMessage, 'detection_method' => 'PHP'], ['detected' => false]);

        // ══ CASE 0: Awaiting Confirmation ══
        if ($session->conversation_state === 'awaiting_confirmation') {
            $this->routeTrace[] = 'handleConfirmationStep';
            $this->traceNode($session->id, 'IntentRouter', 'routing', 'success',
                ['state' => 'awaiting_confirmation'],
                ['route' => 'handleConfirmationStep']);
            return $this->handleConfirmationStep($session, $rawMessage);
        }

        // ══ CASE 1: No product selected yet ══
        if (!isset($answers['product_id'])) {
            $this->routeTrace[] = 'handlePreProductPhase';
            $this->traceNode($session->id, 'IntentRouter', 'routing', 'success',
                ['state' => 'no_product', 'has_product' => false, 'catalogue_sent' => (bool)$session->catalogue_sent, 'collected_answers_count' => count($answers)],
                ['route' => 'PreProductPhase']);
            return $this->handlePreProductPhase($session, $steps, $fullMessage, $rawMessage, $imageUrl);
        }

        // ══ CASE 1.5: Product modification intent ══
        $modifyResult = $this->detectProductModifyIntent($session, $rawMessage);
        if ($modifyResult !== null) {
            $this->routeTrace[] = 'detectProductModifyIntent';
            return $modifyResult;
        }

        // ══ CASE 2: Product selected, in chatflow ══
        if ($currentStep) {
            $this->traceNode($session->id, 'IntentRouter', 'routing', 'success',
                ['state' => 'in_chatflow', 'step' => $currentStep->name, 'step_type' => $currentStep->step_type, 'collected_answers_count' => count($answers)],
                ['route' => $currentStep->step_type, 'current_step' => $currentStep->name]);
            switch ($currentStep->step_type) {
                case 'ask_combo':
                    $this->routeTrace[] = 'handleComboStep';
                    return $this->handleComboStep($session, $currentStep, $rawMessage, $steps);

                case 'ask_optional':
                case 'ask_custom':
                    $this->routeTrace[] = 'handleCustomStep';
                    return $this->handleCustomStep($session, $currentStep, $rawMessage, $steps);

                case 'send_summary':
                    $this->routeTrace[] = 'handleSummaryStep';
                    return $this->handleSummaryStep($session);

                case 'ask_base_column':
                    $this->routeTrace[] = 'handleBaseColumnStep';
                    return $this->matchProductGroupFromMessage($session, $fullMessage, $rawMessage, $imageUrl, $steps);

                case 'ask_unique_column':
                    $this->routeTrace[] = 'handleUniqueColumnStep';
                    return $this->matchProductFromMessage($session, $fullMessage, $rawMessage, $imageUrl, $steps);
            }
        }

        // ══ CASE 3: Fallback — Tier 2 ══
        $this->routeTrace[] = 'handleTier2_fallback';
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
                $this->routeTrace[] = 'matchCategoryFromMessage';
                return $this->matchCategoryFromMessage($session, $rawMessage, $steps);
            }

            // Check if catalogue has been sent (meaning categories were sent)
            if ($session->catalogue_sent) {
                $this->routeTrace[] = 'matchCategoryFromMessage';
                return $this->matchCategoryFromMessage($session, $rawMessage, $steps);
            }

            // Not sent yet — fast PHP check first, then AI fallback
            if ($this->isProductIntent($rawMessage)) {
                $this->routeTrace[] = 'isProductIntent(PHP) → sendCategoryList';
                $this->traceNode($session->id, 'ProductIntentDetector', 'routing', 'success', ['message' => $rawMessage, 'method' => 'PHP', 'match_type' => 'keyword'], ['is_product_intent' => true, 'matched_keyword' => $this->findMatchedProductKeyword($rawMessage)]);
                return $this->sendCategoryList($session);
            }

            // Check if user is giving an affirmative response to bot's product question
            // (e.g. bot asked "Aap kis tarah ke products mein interested hain?" and user says "yes")
            if ($this->isAffirmativeResponse($rawMessage) && $this->botLastAskedAboutProducts($session)) {
                $this->routeTrace[] = 'affirmativeToProductQuery → sendCategoryList';
                $this->traceNode($session->id, 'ProductIntentDetector', 'routing', 'success', ['message' => $rawMessage, 'method' => 'PHP', 'match_type' => 'affirmative_response'], ['is_product_intent' => true]);
                return $this->sendCategoryList($session);
            }

            // AI fallback for ambiguous messages
            $intentPrompt = "User said: \"{$rawMessage}\"\n\nIs the user asking about products, catalogue, prices, or what you sell? This includes messages in ANY language like Hindi, Hinglish, etc. Examples of YES: 'products dikhao', 'kya bechte ho', 'show me products', 'muje product ke bare me btao', 'catalogue', 'what do you sell'. Examples of NO: 'hi', 'hello', 'namaste', 'good morning', 'how are you', 'thanks'. Reply with ONLY 'YES' or 'NO'.";
            $t = microtime(true);
            $intentResult = $this->vertexAI->classifyContent($intentPrompt);
            $ms = (int)((microtime(true) - $t) * 1000);
            $this->logTokens($session, 1, $intentResult);
            $isProductIntent = str_contains(strtoupper($intentResult['text']), 'YES');
            
            $this->traceNode($session->id, 'ProductIntentDetector', 'ai_call', 'success', ['message' => $rawMessage, 'method' => 'AI'], ['is_product_intent' => $isProductIntent, 'raw_response' => $intentResult['text'], 'tokens_used' => $intentResult['total_tokens'] ?? 0, 'model' => 'gemini-2.0-flash'], null, $ms);

            if ($isProductIntent) {
                $this->routeTrace[] = 'isProductIntent(AI) → sendCategoryList';
                return $this->sendCategoryList($session);
            }

            $this->routeTrace[] = 'handleTier2';
            return $this->handleTier2($session, $fullMessage, $imageUrl);
        }

        // ══ Standard product flow (no category step or category already selected) ══
        if ($session->conversation_state === 'awaiting_product_group') {
            $this->routeTrace[] = 'matchProductGroupFromMessage';
            return $this->matchProductGroupFromMessage($session, $fullMessage, $rawMessage, $imageUrl, $steps);
        }

        if ($session->catalogue_sent) {
            $this->routeTrace[] = 'matchProductFromMessage';
            return $this->matchProductFromMessage($session, $fullMessage, $rawMessage, $imageUrl, $steps);
        }

        // Catalogue not sent yet — fast PHP check first, then AI fallback
        if ($this->isProductIntent($rawMessage)) {
            $this->routeTrace[] = 'isProductIntent(PHP) → sendCatalogue';
            $this->traceNode($session->id, 'ProductIntentDetector', 'routing', 'success', ['message' => $rawMessage, 'method' => 'PHP', 'match_type' => 'keyword'], ['is_product_intent' => true, 'matched_keyword' => $this->findMatchedProductKeyword($rawMessage)]);
            return $this->sendCatalogue($session);
        }

        // Affirmative response check (same as category flow)
        if ($this->isAffirmativeResponse($rawMessage) && $this->botLastAskedAboutProducts($session)) {
            $this->routeTrace[] = 'affirmativeToProductQuery → sendCatalogue';
            return $this->sendCatalogue($session);
        }

        // AI fallback for ambiguous messages
        $intentPrompt = "User said: \"{$rawMessage}\"\n\nIs the user asking about products, catalogue, prices, or what you sell? This includes messages in ANY language like Hindi, Hinglish, etc. Examples of YES: 'products dikhao', 'kya bechte ho', 'show me products', 'muje product ke bare me btao', 'catalogue', 'what do you sell'. Examples of NO: 'hi', 'hello', 'namaste', 'good morning', 'how are you', 'thanks'. Reply with ONLY 'YES' or 'NO'.";

        $t = microtime(true);
        $intentResult = $this->vertexAI->classifyContent($intentPrompt);
        $ms = (int)((microtime(true) - $t) * 1000);
        $this->logTokens($session, 1, $intentResult);

        $isProductIntent = str_contains(strtoupper($intentResult['text']), 'YES');
        $this->traceNode($session->id, 'ProductIntentDetector', 'ai_call', 'success', ['message' => $rawMessage, 'method' => 'AI'], ['is_product_intent' => $isProductIntent, 'raw_response' => $intentResult['text'], 'tokens_used' => $intentResult['total_tokens'] ?? 0, 'model' => 'gemini-2.0-flash'], null, $ms);

        if ($isProductIntent) {
            $this->routeTrace[] = 'isProductIntent(AI) → sendCatalogue';
            return $this->sendCatalogue($session);
        }

        // Not product-related — use Tier 2 for general conversation
        $this->routeTrace[] = 'handleTier2';
        return $this->handleTier2($session, $fullMessage, $imageUrl);
    }

    /**
     * Send category list message (PHP built — no AI)
     */
    private function sendCategoryList(AiChatSession $session): string
    {
        $categoryLabel = $this->getCategoryFieldLabel();
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

        $msg = "📂 *Our " . $this->simplePlural($categoryLabel) . ":*\n\n";
        foreach ($categories as $i => $cat) {
            $num = $i + 1;
            $productCount = Product::where('company_id', $this->companyId)
                ->where('category_id', $cat->id)
                ->where('status', 'active')
                ->count();
            $msg .= "{$num}️⃣ *{$cat->name}* ({$productCount} products)\n";
        }
        $msg .= "\nReply with {$categoryLabel} number or name! 👆";

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

        $this->traceNode($session->id, 'CategoryListSent', 'routing', 'success',
            ['trigger' => 'product_intent_detected'],
            ['categories_count' => $categories->count(), 'category_names' => $categories->pluck('name')->toArray()]);
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

        $categoryLabel = $this->getCategoryFieldLabel();
        $catList = $categories->map(fn($c, $i) => ($i + 1) . ". {$c->name} (ID:{$c->id})")->implode("\n");

        // ── PHP Level Matching (Number & Substring) ──
        $msg = strtolower(trim($rawMessage));
        $selectedCategory = null;
        
        // 1. Try strict number extraction
        $listNumber = null;
        if (preg_match('/^\s*(?:category|type|#|no\.?\s*|number\s*)?\s*(\d+)\s*$/i', $msg, $numMatch)) {
            $listNumber = (int)$numMatch[1];
        }
        
        if ($listNumber && $listNumber >= 1 && $listNumber <= $categories->count()) {
            $selectedCategory = $categories->values()[$listNumber - 1] ?? null;
        }

        // 2. Try partial/substring match if number wasn't found
        if (!$selectedCategory) {
            foreach ($categories as $cat) {
                $cNameLower = strtolower($cat->name);
                
                // Bidirectional substring match
                if (str_contains($msg, $cNameLower) || (strlen($msg) >= 3 && str_contains($cNameLower, $msg))) {
                    $selectedCategory = $cat;
                    break;
                }
                
                // Word-by-word match
                $userWords = preg_split('/[\s,]+/', $msg);
                foreach ($userWords as $word) {
                    if (strlen($word) >= 3 && str_contains($cNameLower, $word)) {
                        $selectedCategory = $cat;
                        break 2;
                    }
                }
            }
        }

        // ── AI Contextual Fallback if PHP matching fails ──
        if (!$selectedCategory) {
            $aiResponse = $this->matchContextuallyUsingAI($session, $rawMessage, $catList, $categoryLabel, $categories->count());
            
            if (str_starts_with($aiResponse, 'MATCH_ID:')) {
                preg_match('/MATCH_ID:\s*(\d+)/i', $aiResponse, $matches);
                $matchedId = $matches[1] ?? null;
                if ($matchedId) {
                    $selectedCategory = $categories->firstWhere('id', (int)$matchedId);
                    if (!$selectedCategory && (int)$matchedId <= $categories->count()) {
                        $selectedCategory = $categories->values()[(int)$matchedId - 1] ?? null;
                    }
                }
            } elseif (strtoupper($aiResponse) !== 'NONE') {
                // Return conversational clarification generated by AI
                return $this->translateIfNeeded($session, $aiResponse);
            }
        }

        if (!$selectedCategory) {
            if ($this->isProductIntent($rawMessage)) {
                return $this->sendCategoryList($session);
            }
            $this->traceNode($session->id, 'CategorySelected', 'routing', 'warning', ['message' => $rawMessage], ['matched' => false], 'No category matched. Falling back to Tier 2.');
            return $this->handleTier2($session, $rawMessage, null, "User is currently trying to select a {$categoryLabel}.", $rawMessage);
        }

        $this->traceNode($session->id, 'CategorySelected', 'routing', 'success', ['message' => $rawMessage], ['category_id' => $selectedCategory->id, 'category_name' => $selectedCategory->name]);

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
        $terms = $this->getDynamicTerminology();

        $query = Product::with(['customValues', 'category'])->where('company_id', $this->companyId)
            ->where('status', 'active');

        // Filter by category if category was selected
        if (isset($answers['category_id'])) {
            $query->where('category_id', $answers['category_id']);
        }

        // Apply group filter if already selected
        // Note: selectedGroup filtering is done post-query via getProductBaseName()
        // because the group name may come from custom columns, not the native `name` field
        $selectedGroup = $answers['selected_product_group'] ?? null;

        $products = $query->get();

        // Filter by selected group using computed base name (not native `name` column)
        if ($selectedGroup) {
            $products = $products->filter(function($p) use ($selectedGroup) {
                return trim(strtolower($this->getProductBaseName($p))) === trim(strtolower($selectedGroup));
            })->values();
        }

        if ($products->isEmpty()) {
            return "Sorry, we don't have any {$terms['plural_base']} available right now.";
        }

        $groupedProducts = $products->groupBy(function($p) {
            return trim(strtolower($this->getProductBaseName($p)));
        });

        // Grouping is needed if we haven't selected a group yet AND there are multiple distinct groups
        // Skip grouping if all products resolve to the same base name (single group = redundant)
        $needsGrouping = !$selectedGroup && $groupedProducts->count() > 1 && $groupedProducts->count() < $products->count();

        // Get AI-visible columns for price/desc mapping if needed
        $visibleColumns = $this->getAiVisibleColumns();
        $showPrice = $visibleColumns->contains('slug', 'sale_price') || $visibleColumns->contains('slug', 'mrp');

        // ── Send category image BEFORE product listing (works for both group and flat paths) ──
        $categoryId = $answers['category_id'] ?? ($products->first() ? $products->first()->category_id : null);
        if ($categoryId && !$session->hasMediaBeenSent("category_{$categoryId}")) {
            $category = $products->first() && $products->first()->relationLoaded('category')
                ? $products->first()->category
                : \App\Models\Category::find($categoryId);
            if ($category && !empty($category->image)) {
                $mediaUrl = '/storage/' . $category->image;
                $this->traceNode($session->id, 'CategoryMediaLookup', 'media', 'success',
                    ['category_id' => $category->id, 'company_id' => $this->companyId],
                    ['found' => true, 'media_url' => $mediaUrl, 'raw_image_path' => $category->image]);
                $this->sendMediaToWhatsApp($session, $mediaUrl, 'CategoryMediaSent');
                $session->markMediaSent("category_{$categoryId}");
                $session->save();
            } else {
                $this->traceNode($session->id, 'CategoryMediaLookup', 'media', 'info',
                    ['category_id' => $categoryId],
                    ['found' => false, 'category_exists' => (bool)$category, 'has_image' => $category ? !empty($category->image) : false]);
            }
        }

        if ($needsGrouping) {
            // Check for chatflow step question text as AI reference
            $baseStep = ChatflowStep::where('company_id', $this->companyId)
                ->whereIn('step_type', ['ask_base_column'])
                ->orderBy('sort_order')
                ->first();
            $questionRef = $baseStep && $baseStep->question_text ? $baseStep->question_text : "Kaunsa {$terms['base']} chahiye?";

            $msg = "🛍️ *Our {$terms['base']} Lines:*\n\n";
            $i = 1;
            $groupNamesForTrace = [];
            foreach ($groupedProducts as $key => $group) {
                $actualName = $this->getProductBaseName($group->first());
                $msg .= "{$i}️⃣ *{$actualName}* ({$group->count()} {$terms['plural_variant']})\n";
                $groupNamesForTrace[] = $actualName;
                $i++;
            }
            $msg .= "\nReply with {$terms['base']} number or name! 👆";

            // Send chatflow step media if attached
            if ($baseStep && $baseStep->hasMedia()) {
                $this->sendStepMedia($session, $baseStep);
            }

            $session->catalogue_sent = true;
            $session->conversation_state = 'awaiting_product_group';
            $session->save();

            $categoryFilter = isset($answers['category_id']) ? ($answers['category_name'] ?? 'ID:' . $answers['category_id']) : null;
            $this->traceNode($session->id, 'CatalogueGroupSent', 'routing', 'success',
                $categoryFilter ? ['trigger' => 'category_selected', 'category_filter' => $categoryFilter] : ['trigger' => 'product_intent'],
                ['groups_count' => $groupedProducts->count(), 'group_names' => array_slice($groupNamesForTrace, 0, 10), 'terminology' => $terms]);
            Log::info("AIChatbot: Catalogue Group sent (PHP Direct)", ['session' => $session->id]);

            return $this->translateIfNeeded($session, $msg);
        }

        // Standard flat listing logic
        // Check for unique column step question text as AI reference
        $uniqueStep = ChatflowStep::where('company_id', $this->companyId)
            ->whereIn('step_type', ['ask_product', 'ask_unique_column'])
            ->orderBy('sort_order')
            ->first();

        $msg = "🛍️ *" . ($selectedGroup ? "{$selectedGroup} {$terms['plural_variant']}" : "Our {$terms['plural_base']}") . ":*\n\n";

        // If a specific group is selected, try to send a representative image (GroupMedia) first
        if ($selectedGroup && !$session->hasMediaBeenSent("group_{$selectedGroup}")) {
            $groupMediaProduct = $products->firstWhere('cover_media_url', '!=', null);
            if ($groupMediaProduct) {
                $mediaUrl = $groupMediaProduct->cover_media_url;
                $this->traceNode($session->id, 'GroupMediaLookup', 'media', 'success',
                    ['group_name' => $selectedGroup],
                    ['found' => true, 'media_url' => $mediaUrl]);
                $this->sendMediaToWhatsApp($session, $mediaUrl, 'GroupMediaSent');
                $session->markMediaSent("group_{$selectedGroup}");
                $session->save();
            } else {
                $this->traceNode($session->id, 'GroupMediaLookup', 'media', 'info',
                    ['group_name' => $selectedGroup],
                    ['found' => false, 'message' => 'No media found for any product in this group']);
            }
        }

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
        $msg .= "\nReply with {$terms['variant']} number or name! 👆";

        // Send chatflow step media if attached
        if ($uniqueStep && $uniqueStep->hasMedia()) {
            $this->sendStepMedia($session, $uniqueStep);
        }

        // Update session state
        $session->catalogue_sent = true;
        $session->conversation_state = 'awaiting_product';

        // Set current step to first product/unique column step (if exists)
        $productStep = ChatflowStep::where('company_id', $this->companyId)
            ->whereIn('step_type', ['ask_product', 'ask_unique_column'])
            ->orderBy('sort_order')
            ->first();
        if ($productStep) {
            $session->current_step_id = $productStep->id;
        }

        $session->save();

        $productNames = $products->map(fn($p) => $this->getProductDisplayName($p))->toArray();
        $this->traceNode($session->id, 'CatalogueSent', 'routing', 'success',
            ['trigger' => $selectedGroup ? 'group_selected' : 'product_intent', 'group_filter' => $selectedGroup],
            ['products_count' => $products->count(), 'product_names' => array_slice($productNames, 0, 10), 'terminology' => $terms]);
        Log::info("AIChatbot: Catalogue sent (PHP Direct)", ['session' => $session->id]);
        return $this->translateIfNeeded($session, $msg);
    }

    /**
     * Match user's message to a product group
     */
    private function matchProductGroupFromMessage(AiChatSession $session, string $fullMessage, string $rawMessage, ?string $imageUrl, $steps): string
    {
        $answers = $session->collected_answers ?? [];
        $query = Product::with(['customValues', 'category'])->where('company_id', $this->companyId)->where('status', 'active');
        if (isset($answers['category_id'])) {
            $query->where('category_id', $answers['category_id']);
        }
        $products = $query->get();

        if ($products->isEmpty()) {
            return "Sorry, no product lines available right now.";
        }

        $groupedProducts = [];
        foreach ($products as $p) {
            $baseName = $this->getProductBaseName($p);
            $key = trim(strtolower($baseName));
            if (!isset($groupedProducts[$key])) {
                $groupedProducts[$key] = ['name' => $baseName, 'count' => 0];
            }
            $groupedProducts[$key]['count']++;
        }
        
        $groupsList = array_values($groupedProducts);

        $msg = strtolower(trim($rawMessage));
        $selectedGroupName = null;

        // Try strict number extraction
        $listNumber = null;
        if (preg_match('/^\s*(?:group|line|#|no\.?\s*|number\s*)?\s*(\d+)\s*$/i', $msg, $numMatch)) {
            $listNumber = (int)$numMatch[1];
        }

        if ($listNumber && $listNumber >= 1 && $listNumber <= count($groupsList)) {
            $selectedGroupName = $groupsList[$listNumber - 1]['name'];
        }

        if (!$selectedGroupName) {
            foreach ($groupsList as $g) {
                $gNameLower = strtolower($g['name']);
                // Bidirectional substring match: user message contains group name OR group name contains user message
                if (str_contains($msg, $gNameLower) || (strlen($msg) >= 3 && str_contains($gNameLower, $msg))) {
                    $selectedGroupName = $g['name'];
                    break;
                }
                // Also check individual words from user message against group name
                $userWords = preg_split('/[\s,]+/', $msg);
                foreach ($userWords as $word) {
                    if (strlen($word) >= 3 && str_contains($gNameLower, $word)) {
                        $selectedGroupName = $g['name'];
                        break 2;
                    }
                }
            }
        }

        // Spell correction: if PHP direct match failed, try AI micro-correction
        if (!$selectedGroupName && !empty($this->spellCorrectionPrompt)) {
            $groupNames = array_column($groupsList, 'name');
            $correctedText = $this->spellCorrect($session, $rawMessage, $groupNames);
            $correctedLower = strtolower(trim($correctedText));
            if ($correctedLower !== $msg) {
                foreach ($groupsList as $g) {
                    if (str_contains($correctedLower, strtolower($g['name']))) {
                        $selectedGroupName = $g['name'];
                        break;
                    }
                }
            }
        }

        // ── If PHP + spell correction couldn't match, fall back to AI Contextual ──
        if (!$selectedGroupName) {
            $groupListStr = collect($groupsList)->map(fn($g, $i) => ($i + 1) . ". {$g['name']} (ID: ".($i+1).")")->implode("\n");
            $aiResponse = $this->matchContextuallyUsingAI($session, $rawMessage, $groupListStr, 'Product Line/Group', count($groupsList));
            
            if (str_starts_with($aiResponse, 'MATCH_ID:')) {
                preg_match('/MATCH_ID:\s*(\d+)/i', $aiResponse, $matches);
                $matchedId = $matches[1] ?? null;
                if ($matchedId) {
                    $num = (int)$matchedId;
                    if ($num >= 1 && $num <= count($groupsList)) {
                        $selectedGroupName = $groupsList[$num - 1]['name'];
                    }
                }
            } elseif (strtoupper($aiResponse) !== 'NONE') {
                return $this->translateIfNeeded($session, $aiResponse);
            }
        }

        if ($selectedGroupName) {
            $this->traceNode($session->id, 'ProductGroupSelected', 'routing', 'success', ['message' => $rawMessage], ['group_name' => $selectedGroupName]);
            $session->setAnswer('selected_product_group', $selectedGroupName);
            $session->save();

            $replyMsg = "✅ *{$selectedGroupName}* selected!\n\n";
            $replyMsg .= $this->sendCatalogue($session);
            return $replyMsg;
        }

        if ($this->isProductIntent($rawMessage)) {
            return $this->sendCatalogue($session);
        }

        $this->traceNode($session->id, 'ProductGroupSelected', 'routing', 'warning', ['message' => $rawMessage], ['matched' => false], 'No product line matched. Falling back to OOB Handler.');
        return $this->handleOutOfContextQuestion($session, $rawMessage, $steps);
    }

    /**
     * Match user's message to a product (Tier 1)
     * First tries PHP-level number extraction, then falls back to AI matching.
     */
    private function matchProductFromMessage(AiChatSession $session, string $fullMessage, string $rawMessage, ?string $imageUrl, $steps): string
    {
        $answers = $session->collected_answers ?? [];
        $query = Product::with('customValues')->where('company_id', $this->companyId)
            ->where('status', 'active');

        // Filter by category if category was selected
        if (isset($answers['category_id'])) {
            $query->where('category_id', $answers['category_id']);
        }
        
        // Filter by group if group was selected
        $selectedGroup = $answers['selected_product_group'] ?? null;
        // Note: selectedGroup filtering is done post-query via getProductBaseName()
        // because the group name may come from custom columns, not the native `name` field

        $products = $query->get();

        // Filter by selected group using computed base name (not native `name` column)
        if ($selectedGroup) {
            $products = $products->filter(function($p) use ($selectedGroup) {
                return trim(strtolower($this->getProductBaseName($p))) === trim(strtolower($selectedGroup));
            })->values();
        }

        if ($products->isEmpty()) {
            return "Sorry, no products available right now.";
        }

        // ── PHP PRE-CHECK: Direct number extraction ──
        // Handles: "1", "product 1", "#1", "product 1 me btao", "pehla product", etc.
        $msg = strtolower(trim($rawMessage));
        $selectedProduct = null;

        // Try extracting a list number from the message
        $listNumber = null;

        // Pattern: ordinal words (Hindi/English)
        $ordinals = [
            'first' => 1, 'second' => 2, 'third' => 3, 'fourth' => 4, 'fifth' => 5,
            'pehla' => 1, 'pahla' => 1, 'pehli' => 1, 'pahli' => 1,
            'dusra' => 2, 'doosra' => 2, 'dusri' => 2, 'doosri' => 2,
            'teesra' => 3, 'tisra' => 3, 'teesri' => 3,
            'chautha' => 4, 'chauthi' => 4,
            'paanchva' => 5, 'panchva' => 5,
        ];
        foreach ($ordinals as $word => $num) {
            // Strict check for ordinal
            if (preg_match('/^\s*' . $word . '\s*(?:wala|wali|one|product)?\s*$/i', $msg)) {
                $listNumber = $num;
                break;
            }
        }

        // Pattern: strict digit match from message
        if (!$listNumber) {
            if (preg_match('/^\s*(?:product|#|no\.?\s*|number\s*)?\s*(\d+)\s*$/i', $msg, $numMatch)) {
                $listNumber = (int)$numMatch[1];
            }
        }

        // If we got a list number, try to match it
        if ($listNumber && $listNumber >= 1 && $listNumber <= $products->count()) {
            $selectedProduct = $products->values()[$listNumber - 1] ?? null;
        }

        // ── Also try direct name matching (PHP level) ──
        if (!$selectedProduct && !$listNumber) {
            // First try strict equality
            foreach ($products as $product) {
                $displayName = strtolower($this->getProductDisplayName($product));
                $productName = strtolower($product->name);
                if ($msg === $displayName || $msg === $productName) {
                    $selectedProduct = $product;
                    break;
                }
            }
            // Then try bidirectional substring/partial match
            if (!$selectedProduct) {
                foreach ($products as $product) {
                    $displayName = strtolower($this->getProductDisplayName($product));
                    $productName = strtolower($product->name);
                    
                    // Flatten all custom values (attributes) for robust matching
                    $attributes = $product->customValues->map(function($cv) {
                        return strtolower(is_string($cv->value) ? $cv->value : json_encode($cv->value));
                    })->implode(' ');

                    // User message contains product name, or product name contains user message
                    if (str_contains($msg, $displayName) || str_contains($msg, $productName) || (strlen($msg) >= 3 && str_contains($attributes, $msg))
                        || (strlen($msg) >= 3 && (str_contains($displayName, $msg) || str_contains($productName, $msg)))) {
                        $selectedProduct = $product;
                        break;
                    }
                    // Check individual words from user message against product name and attributes
                    $userWords = preg_split('/[\s,]+/', $msg);
                    foreach ($userWords as $word) {
                        if (strlen($word) >= 3 && (str_contains($displayName, $word) || str_contains($productName, $word) || str_contains($attributes, $word))) {
                            $selectedProduct = $product;
                            break 2;
                        }
                    }
                }
            }
        }

        if ($selectedProduct) {
            $matchType = $listNumber ? 'list_number' : 'name_match';
            $this->traceNode($session->id, 'ProductMatchPHP', 'routing', 'success',
                ['message' => $rawMessage, 'method' => 'PHP', 'match_type' => $matchType],
                ['product_id' => $selectedProduct->id, 'product_name' => $this->getProductDisplayName($selectedProduct)]);
        }

        // ── Spell correction: if PHP couldn't match, try fixing typos first ──
        if (!$selectedProduct && !$listNumber && !empty($this->spellCorrectionPrompt)) {
            $productNames = $products->map(fn($p) => $this->getProductDisplayName($p))->toArray();
            $correctedText = $this->spellCorrect($session, $rawMessage, $productNames);
            $correctedLower = strtolower(trim($correctedText));
            if ($correctedLower !== $msg) {
                foreach ($products as $product) {
                    $displayName = strtolower($this->getProductDisplayName($product));
                    $productName = strtolower($product->name);
                    if ($correctedLower === $displayName || $correctedLower === $productName) {
                        $selectedProduct = $product;
                        break;
                    }
                }
            }
        }

        // ── If PHP + spell correction couldn't match, fall back to AI Contextual ──
        if (!$selectedProduct) {
            $productList = $products->map(fn($p, $i) => ($i + 1) . ". " . $this->getProductDisplayName($p) . " (ID:{$p->id})")->implode("\n");
            $aiResponse = $this->matchContextuallyUsingAI($session, $rawMessage, $productList, 'Product/Item', $products->count());
            
            if (str_starts_with($aiResponse, 'MATCH_ID:')) {
                preg_match('/MATCH_ID:\s*(\d+)/i', $aiResponse, $matches);
                $matchedId = $matches[1] ?? null;
                if ($matchedId) {
                    $selectedProduct = $products->firstWhere('id', (int)$matchedId);
                    if (!$selectedProduct && (int)$matchedId <= $products->count()) {
                        $selectedProduct = $products->values()[(int)$matchedId - 1] ?? null;
                    }
                }
            } elseif (strtoupper($aiResponse) !== 'NONE') {
                return $this->translateIfNeeded($session, $aiResponse);
            }
        }

        if (!$selectedProduct) {
            if ($this->isProductIntent($rawMessage)) {
                return $this->sendCatalogue($session);
            }
            $this->traceNode($session->id, 'ProductSelected', 'routing', 'warning', ['message' => $rawMessage], ['matched' => false], 'No product matched. Falling back to OOB Handler.');
            return $this->handleOutOfContextQuestion($session, $rawMessage, $steps);
        }

        $this->traceNode($session->id, 'ProductSelected', 'routing', 'success',
            ['message' => $rawMessage],
            ['product_id' => $selectedProduct->id, 'product_name' => $this->getProductDisplayName($selectedProduct), 'product_price' => $selectedProduct->sale_price > 0 ? '₹' . number_format($selectedProduct->sale_price / 100, 2) : 'N/A']);

        return $this->selectProduct($session, $selectedProduct->id, $steps);
    }

    /**
     * Contextual AI Default Matcher for Categories, Groups, and Products
     * Handles ambiguity dynamically by returning clarification questions.
     */
    private function matchContextuallyUsingAI(AiChatSession $session, string $rawMessage, string $optionsList, string $entityType, int $optionsCount): string
    {
        $prompt = "You are a helpful e-commerce AI assistant on WhatsApp.\n";
        $prompt .= "CONTEXT: The bot is currently asking the user to select a '{$entityType}'.\n";
        $prompt .= "AVAILABLE OPTIONS:\n{$optionsList}\n\n";
        $prompt .= "USER REPLIED: \"{$rawMessage}\"\n\n";
        $prompt .= "YOUR TASK:\n";
        $prompt .= "1. Analyze the user's reply against the Exact Option Names or IDs. Does it clearly resolve to EXACTLY ONE of the options above based on semantic matching or numbering? (e.g., if user says 'cabinet me btao' and the only option with cabinet is 'Cabinet Handle', it matches exactly one).\n";
        $prompt .= "   If EXACTLY ONE matches perfectly, you MUST reply strictly with EXACTLY: MATCH_ID: <ID> (Replace <ID> with the matching option's explicitly stated ID or Number if ID is missing. Do NOT return the name text).\n";
        $prompt .= "2. If the user's reply is ambiguous and aligns with MULTIPLE options (e.g. 'cabinet' is requested but there is 'Cabinet Handle 12 inch' and 'Cabinet Handle 14 inch'), you must ask the user a polite clarifying question in conversational Hindi/English mix listing ONLY the matched options to help them narrow it down. (e.g. \"Aap 'Cabinet Handle (12 inch)' dekhna chahte hain ya 'Cabinet Handle (14 inch)'?\"). Do NOT use the MATCH_ID formatting here. Just output the question clearly.\n";
        $prompt .= "3. If the user message has absolutely NO relation to any of the options, reply strictly with: NONE.";

        $t = microtime(true);
        $matchResult = $this->vertexAI->classifyContent($prompt);
        $ms = (int)((microtime(true) - $t) * 1000);
        $this->logTokens($session, 1, $matchResult);

        $matchText = trim($matchResult['text']);
        $this->traceNode($session->id, 'ContextualMatchAI', 'ai_call', 'success',
            ['message' => $rawMessage, 'entity' => $entityType, 'options_available' => $optionsCount],
            ['raw_response' => $matchText, 'tokens_used' => $matchResult['total_tokens'] ?? 0, 'model' => 'gemini-2.0-flash'], null, $ms);

        return $matchText;
    }

    /**
     * Update the QuoteItem description dynamically using collected answers
     */
    private function updateQuoteDescription(AiChatSession $session): void
    {
        if (!$session->quote_id || !$session->getAnswer('product_id')) {
            return;
        }

        $quoteItem = QuoteItem::where('quote_id', $session->quote_id)
            ->where('product_id', $session->getAnswer('product_id'))
            ->first();
            
        if ($quoteItem) {
            $product = Product::find($session->getAnswer('product_id'));
            if ($product) {
                // Pass true to onlyShowAnsweredCombos to exclude unopened combo questions
                $newDesc = $product->getDynamicDescription($session->collected_answers ?? [], true);
                
                // Add any non-product custom chatflow answers
                $descLines = [];
                if ($newDesc) $descLines[] = $newDesc;
                
                foreach ($session->collected_answers as $key => $val) {
                    if (!in_array($key, ['product_id', 'product_name', 'category_id', 'selected_product_group']) && !$product->combos->pluck('column.slug')->contains($key)) {
                        $descLines[] = ucfirst(str_replace('_', ' ', $key)) . ": {$val}";
                    }
                }
                
                $quoteItem->update(['description' => implode("\n", $descLines)]);
            }
        }
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

        // Attach product to existing lead (lead is now created on first message)
        if ($session->lead_id) {
            $lead = Lead::find($session->lead_id);
            if ($lead) {
                // Attach product if not already attached
                if (!$lead->products()->where('product_id', $productId)->exists()) {
                    $lead->products()->attach($productId, ['quantity' => 1, 'price' => $product->sale_price]);
                }
                $lead->update(['product_name' => $displayName]);
                $this->traceNode($session->id, 'ProductAttachedToLead', 'database', 'success',
                    ['product_id' => $productId, 'product_name' => $displayName],
                    ['lead_id' => $lead->id, 'lead_phone' => $session->phone_number]);
            }
        } else {
            // Fallback: create lead if somehow it doesn't exist yet
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
            $this->traceNode($session->id, 'LeadCreated', 'database', 'success',
                ['product_id' => $productId, 'product_name' => $displayName, 'phone' => $session->phone_number],
                ['lead_id' => $lead->id, 'lead_phone' => $session->phone_number, 'lead_source' => 'whatsapp']);
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
                'description' => $product->getDynamicDescription($session->collected_answers ?? [], true),
                'hsn_code' => $product->hsn_code,
                'qty' => 1,
                'rate' => $product->sale_price,
                'unit' => $product->unit,
                'unit_price' => $product->sale_price,
                'gst_percent' => $product->gst_percent,
                'sort_order' => 1,
            ]);
            $session->quote_id = $quote->id;
            
            // Extract the sequence number from quote_no (e.g. Q-25-26-000017 -> 17) for clearer tracing
            $quoteSeqParts = explode('-', $quote->quote_no);
            $quoteSequence = (int) end($quoteSeqParts);

            $this->traceNode($session->id, 'QuoteCreated', 'database', 'success',
                ['lead_id' => $session->lead_id, 'product_id' => $productId, 'product_name' => $displayName],
                ['database_id' => $quote->id, 'quote_sequence_id' => $quoteSequence, 'quote_no' => $quote->quote_no, 'grand_total' => '₹' . number_format($quote->grand_total / 100, 2)]);
        }

        // Send unique model image if available
        $this->sendProductMedia($session, $product);

        // Simple confirmation — NO detail dump
        // The chatflow steps will ask questions one-by-one
        $msg = "✅ *{$displayName}* selected! 🛍️";

        // Advance to next step after product selection
        $this->advanceChatflow($session, $steps);
        $session->save();

        // Append first chatflow question (step-by-step)
        $nextPrompt = $this->buildNextStepPrompt($session, $steps);
        if ($nextPrompt) {
            $msg .= "\n\n" . $nextPrompt;
        }

        Log::info("AIChatbot: Product selected (PHP Direct)", ['session' => $session->id, 'product' => $product->name]);
        return $this->translateIfNeeded($session, $msg);
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

        // Tier 1 (Fast PHP Check): Exact or Case-insensitive match first to save AI token cost and avoid AI hallucinations
        $matchedOption = null;
        foreach ($comboValues as $opt) {
            if (trim(strtolower($rawMessage)) === strtolower(trim($opt))) {
                $matchedOption = $opt;
                break;
            }
        }

        // Tier 1b (Relaxed PHP Check): Handle cases like "166mm" vs "166 mm" by removing spaces/symbols
        if (!$matchedOption) {
            $normalizedMessage = preg_replace('/[^a-z0-9]/', '', strtolower($rawMessage));
            foreach ($comboValues as $opt) {
                $normalizedOpt = preg_replace('/[^a-z0-9]/', '', strtolower($opt));
                // Only consider it a match if they are completely identical when stripped of spacing/symbols
                if (!empty($normalizedOpt) && $normalizedMessage === $normalizedOpt) {
                    $matchedOption = $opt;
                    break;
                }
            }
        }

        // Tier 2: AI Match if PHP fast check fails
        if (!$matchedOption) {
            $optionsList = implode(', ', $comboValues);
            $prompt = "User said: \"{$rawMessage}\"\n\nAvailable options: [{$optionsList}]\n\nWhich option matches user's choice? Reply with ONLY the EXACT option text from the list. If the user is asking a general completely unrelated question, reply OOB. If no clear match, reply NONE.";

            $t = microtime(true);
            $matchResult = $this->vertexAI->classifyContent($prompt);
            $ms = (int)((microtime(true) - $t) * 1000);
            $this->logTokens($session, 1, $matchResult);

            $matchedText = trim($matchResult['text']);
            $this->traceNode($session->id, "ComboStepAI_{$column->slug}", 'ai_call', 'success',
                ['message' => $rawMessage, 'step_name' => $step->name, 'question' => $step->question_text ?: "Which {$column->name}?", 'available_options' => $comboValues],
                ['raw_response' => $matchedText, 'tokens_used' => $matchResult['total_tokens'] ?? 0, 'model' => 'gemini-2.0-flash'], null, $ms);

            if (strtoupper($matchedText) === 'OOB') {
                return $this->handleOutOfContextQuestion($session, $rawMessage, $steps);
            }

            // Verify match is in options (case-insensitive)
            foreach ($comboValues as $opt) {
                if (strtolower($opt) === strtolower($matchedText)) {
                    $matchedOption = $opt;
                    break;
                }
            }
        }

        if (!$matchedOption) {
            // OUT OF ORDER CHECK LOGIC
            // Check if user's input matches any OTHER unanswered combo options for this product
            $answers = $session->collected_answers ?? [];
            $unansweredComboSteps = $steps->filter(function($s) use ($session, $answers) {
                return $s->step_type === 'ask_combo' && 
                       $s->linkedColumn && 
                       $s->id !== $session->current_step_id &&
                       !isset($answers[$s->linkedColumn->slug]);
            });

            $outOfOrderMatch = null;
            $outOfOrderStep = null;

            foreach ($unansweredComboSteps as $uStep) {
                $uCol = $uStep->linkedColumn;
                $uComboValues = $product ? $this->getComboValuesForProduct($product, $uCol) : [];
                foreach ($uComboValues as $opt) {
                     // Check if exact option matches user's message (case insensitive)
                     if (trim(strtolower($rawMessage)) === strtolower(trim($opt))) {
                          $outOfOrderMatch = $opt;
                          $outOfOrderStep = $uStep;
                          break 2;
                     }
                }
            }

            $normalizedRawMessage = preg_replace('/[^a-z0-9]/', '', strtolower($rawMessage));
            
            if (!$outOfOrderMatch) {
                foreach ($unansweredComboSteps as $uStep) {
                    $uCol = $uStep->linkedColumn;
                    $uComboValues = $product ? $this->getComboValuesForProduct($product, $uCol) : [];
                    foreach ($uComboValues as $opt) {
                         // 1. Regex word boundary
                         if (preg_match('/\b' . preg_quote(trim($opt), '/') . '\b/i', $rawMessage)) {
                              $outOfOrderMatch = $opt;
                              $outOfOrderStep = $uStep;
                              break 2;
                         }
                         // 2. Stripped substring (handles "166mm" in "i want 166mm" when option is "166 mm")
                         $normalizedOpt = preg_replace('/[^a-z0-9]/', '', strtolower($opt));
                         if (!empty($normalizedOpt) && str_contains($normalizedRawMessage, $normalizedOpt)) {
                              $outOfOrderMatch = $opt;
                              $outOfOrderStep = $uStep;
                              break 2;
                         }
                    }
                }
            }

            if ($outOfOrderMatch && $outOfOrderStep) {
                 // Trace smart out-of-order match
                 $this->traceNode($session->id, "ComboStepAI_SmartMatch", 'routing', 'success',
                     ['message' => $rawMessage, 'intended_step' => $step->name, 'resolved_step' => $outOfOrderStep->name],
                     ['value' => $outOfOrderMatch, 'column' => $outOfOrderStep->linkedColumn->slug]);
                 
                 // Save the matched out-of-order option
                 $session->setAnswer($outOfOrderStep->linkedColumn->slug, $outOfOrderMatch);
                 
                 // Directly update quote item description and attempt quote variation sync
                 $this->updateQuoteItemDescription($session, $product);
                 $this->updateQuoteVariation($session, $product);
                 
                 // Advance chatflow (which will now loop back to this current step since it's still unanswered)
                 $this->advanceChatflow($session, $steps);
                 $session->save();
                 $nextPrompt = $this->buildNextStepPrompt($session, $steps);
                 return "Got it! Noted {$outOfOrderStep->linkedColumn->name}: {$outOfOrderMatch}.\n\n" . $nextPrompt;
            }

            $this->traceNode($session->id, "ComboStep_{$column->slug}", 'routing', 'error',
                ['step_name' => $step->name, 'step_type' => 'ask_combo', 'user_answer' => $rawMessage, 'matched' => false], null, 'Combo option not matched');
            // Retry
            $session->current_step_retries = ($session->current_step_retries ?? 0) + 1;

            $this->traceNode($session->id, 'ChatflowRetry', 'routing', 'warning',
                ['step_name' => $step->name, 'step_type' => 'ask_combo', 'user_answer' => $rawMessage],
                ['retries' => $session->current_step_retries, 'max_retries' => $step->max_retries ?? 2, 'action' => $session->current_step_retries >= ($step->max_retries ?? 2) ? ($step->isOptionalStep() ? 'skip_optional' : 'exhausted') : 'retry']);
            if ($session->current_step_retries >= ($step->max_retries ?? 2)) {
                if ($step->isOptionalStep()) {
                    $session->markOptionalAsked($column->slug);
                    $this->advanceChatflow($session, $steps);
                    $session->save();
                    return $this->buildNextStepPrompt($session, $steps);
                }
            }

            $session->save();
            return $this->translateIfNeeded($session, "Sorry, I didn't understand. Please choose from:\n" . implode(' | ', $comboValues));
        }

        // Matched! Save to session
        $session->setAnswer($column->slug, $matchedOption);
        $session->current_step_retries = 0;
        
        $this->traceNode($session->id, "ComboStep_{$column->slug}", 'routing', 'success',
            ['step_name' => $step->name, 'step_type' => 'ask_combo'],
            ['value' => $matchedOption, 'action' => 'saved']);

        // Update quote description and variation
        $this->updateQuoteItemDescription($session, $product);
        $this->updateQuoteVariation($session, $product);
        $this->traceNode($session->id, 'QuoteUpdated', 'database', 'success',
            ['combo' => $column->slug, 'value' => $matchedOption, 'product_name' => $session->getAnswer('product_name')],
            ['quote_id' => $session->quote_id]);

        // Combo-level media: not required per business rules
        // Media is only sent for category (image) and unique product (cover_media_url)

        // Advance chatflow
        $this->advanceChatflow($session, $steps);
        $session->save();

        // Build response
        $msg = "✅ {$column->name}: *{$matchedOption}* selected!";

        // Check if all combo steps are done — append progress summary
        $progressSummary = $this->buildProgressSummary($session, $steps);
        if (!empty($progressSummary)) {
            $msg .= $progressSummary;
        }

        // Append next step
        $nextPrompt = $this->buildNextStepPrompt($session, $steps);
        if ($nextPrompt) {
            $msg .= "\n\n" . $nextPrompt;
        }

        Log::info("AIChatbot: Combo matched (Tier 1)", ['session' => $session->id, 'column' => $column->slug, 'value' => $matchedOption]);
        return $this->translateIfNeeded($session, $msg);
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
        $prompt = "Question asked: \"{$question}\"\nUser replied: \"{$rawMessage}\"\n\nExtract the user's answer. Reply with ONLY the extracted answer text. If user seems to skip or says 'no' / 'skip', reply SKIP. If the user is asking a general completely unrelated question, reply OOB.";

        $t = microtime(true);
        $extractResult = $this->vertexAI->classifyContent($prompt);
        $ms = (int)((microtime(true) - $t) * 1000);
        $this->logTokens($session, 1, $extractResult);

        $extractedText = trim($extractResult['text']);
        $this->traceNode($session->id, "CustomStepAI_{$fieldKey}", 'ai_call', 'success',
            ['message' => $rawMessage, 'step_name' => $step->name, 'question' => $question, 'step_type' => $step->step_type],
            ['raw_response' => $extractedText, 'tokens_used' => $extractResult['total_tokens'] ?? 0, 'model' => 'gemini-2.0-flash'], null, $ms);

        if (strtoupper($extractedText) === 'OOB') {
            return $this->handleOutOfContextQuestion($session, $rawMessage, $steps);
        }

        if (strtoupper($extractedText) === 'SKIP' || empty($extractedText)) {
            if ($step->isOptionalStep()) {
                $this->traceNode($session->id, "CustomStep_{$fieldKey}", 'routing', 'skipped',
                    ['step_name' => $step->name, 'step_type' => $step->step_type], ['action' => 'skipped_optional']);
                $session->markOptionalAsked($fieldKey);
                $this->advanceChatflow($session, $steps);
                $session->save();
                return $this->buildNextStepPrompt($session, $steps);
            }
            // Not optional — ask again
            $this->traceNode($session->id, "CustomStep_{$fieldKey}", 'routing', 'error',
                ['step_name' => $step->name, 'step_type' => $step->step_type], ['action' => 'retry_required'], 'Required field skipped');
            $session->current_step_retries = ($session->current_step_retries ?? 0) + 1;
            $this->traceNode($session->id, 'ChatflowRetry', 'routing', 'warning',
                ['step_name' => $step->name, 'step_type' => $step->step_type, 'user_answer' => $rawMessage],
                ['retries' => $session->current_step_retries, 'max_retries' => $step->max_retries ?? 2, 'action' => 'retry']);
            // Auto-skip if max retries reached and step is optional
            if ($session->current_step_retries >= ($step->max_retries ?? 2)) {
                if ($step->isOptionalStep()) {
                    $session->markOptionalAsked($fieldKey);
                    $this->advanceChatflow($session, $steps);
                    $session->save();
                    return $this->buildNextStepPrompt($session, $steps);
                }
            }
            $session->save();
            return $this->translateIfNeeded($session, $question);
        }

        // Save answer
        $this->traceNode($session->id, "CustomStep_{$fieldKey}", 'routing', 'success',
            ['step_name' => $step->name, 'step_type' => $step->step_type, 'question' => $question],
            ['value' => $extractedText, 'action' => 'saved']);
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
                $updateType = in_array($fieldKey, $directFields) ? 'direct_field' : 'custom_data';
                $this->traceNode($session->id, 'LeadUpdated', 'database', 'success',
                    ['field' => $fieldKey, 'value' => $extractedText],
                    ['lead_id' => $lead->id, 'update_type' => $updateType]);
            }
        }

        // Update quote description in real time
        $this->updateQuoteDescription($session);

        // Advance
        $this->advanceChatflow($session, $steps);
        $session->save();

        $msg = "✅ Got it!";
        $nextPrompt = $this->buildNextStepPrompt($session, $steps);
        if ($nextPrompt) {
            $msg .= "\n\n" . $nextPrompt;
        }

        Log::info("AIChatbot: Custom answer (Tier 1)", ['session' => $session->id, 'field' => $fieldKey, 'value' => $extractedText]);
        return $this->translateIfNeeded($session, $msg);
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

        $msg .= "\nDo you confirm this order? Reply *Yes* or *No*. 🙏";

        $session->update(['conversation_state' => 'awaiting_confirmation']);

        Log::info("AIChatbot: Summary sent (PHP Direct)", ['session' => $session->id]);
        return $this->translateIfNeeded($session, $msg);
    }
    
    // ═══════════════════════════════════════════════════════
    // CONFIRMATION STEP
    // ═══════════════════════════════════════════════════════
    
    private function handleConfirmationStep(AiChatSession $session, string $rawMessage): string
    {
        $msg = strtolower(trim($rawMessage));
        
        if (in_array($msg, ['yes', 'y', 'ha', 'haa', 'haan', 'ji', 'sure', 'ok', 'okay', 'done', 'confirm'])) {
            $session->update(['status' => 'completed', 'conversation_state' => 'completed']);
            $reply = "✅ Order Confirmed!\n\nOur team will contact you shortly regarding the delivery and payment. Thank you for your business! 🙏";
            
            // Move Lead to Target Stage
            $targetStage = Setting::getValue('ai_bot', 'target_stage', '', $this->companyId);
            if (!empty($targetStage) && $session->lead_id) {
                $lead = \App\Models\Lead::find($session->lead_id);
                if ($lead) {
                    $lead->update(['stage' => $targetStage]);
                }
            }
            
            $this->traceNode($session->id, 'OrderConfirmed', 'application', 'success', ['message' => $rawMessage], ['status' => 'confirmed', 'new_lead_stage' => $targetStage]);
        } elseif (in_array($msg, ['no', 'n', 'nahi', 'cancel'])) {
            $session->update(['status' => 'cancelled', 'conversation_state' => 'completed']);
            if ($session->quote_id) {
                Quote::where('id', $session->quote_id)->update(['status' => 'cancelled']);
            }
            $reply = "❌ Order Cancelled.\n\nYou can start a new chat anytime!";
            $this->traceNode($session->id, 'OrderCancelled', 'application', 'success', ['message' => $rawMessage], ['status' => 'cancelled']);
        } else {
            return $this->translateIfNeeded($session, "Please reply *Yes* to confirm or *No* to cancel your order.");
        }
        
        return $this->translateIfNeeded($session, $reply);
    }

    // ═══════════════════════════════════════════════════════
    // TIER 2 — Full Conversational AI
    // ═══════════════════════════════════════════════════════

    private function handleTier2(AiChatSession $session, string $fullMessage, ?string $imageUrl, string $fallbackContext = '', string $rawMessage = ''): string
    {
        $systemPrompt = $this->buildSystemPrompt($session, $rawMessage, $fallbackContext);
        $chatHistory = $this->buildChatHistory($session, $fullMessage, $imageUrl);

        $t = microtime(true);
        $aiResult = $this->vertexAI->generateContent($systemPrompt, $chatHistory, $imageUrl);
        $ms = (int)((microtime(true) - $t) * 1000);
        $this->logTokens($session, 2, $aiResult);

        $responseText = trim($aiResult['text'] ?? '');

        // Check for empty or error responses — retry once
        $errorPhrases = ['sorry, i could not generate', 'sorry, an error occurred', 'sorry, i am unable to process'];
        $isError = empty($responseText);
        if (!$isError) {
            $lowerResp = strtolower($responseText);
            foreach ($errorPhrases as $phrase) {
                if (str_contains($lowerResp, $phrase)) {
                    $isError = true;
                    break;
                }
            }
        }

        if ($isError) {
            $this->traceNode($session->id, 'Tier2GenerativeAI', 'ai_call', 'error',
                ['prompt_length' => strlen($systemPrompt), 'history_length' => count($chatHistory), 'system_prompt_preview' => mb_substr($systemPrompt, 0, 150), 'model' => 'gemini-2.0-flash'],
                ['response' => mb_substr($responseText, 0, 200), 'tokens_used' => $aiResult['total_tokens'] ?? 0], 'First attempt failed', $ms);
            Log::warning('AIChatbot: Tier 2 returned empty/error, retrying...', ['session' => $session->id, 'original' => $responseText]);
            sleep(1);
            
            $t = microtime(true);
            $retryResult = $this->vertexAI->generateContent($systemPrompt, $chatHistory, $imageUrl);
            $retryMs = (int)((microtime(true) - $t) * 1000);
            
            $this->logTokens($session, 2, $retryResult);
            $retryText = trim($retryResult['text'] ?? '');

            // Check retry response
            $retryIsError = empty($retryText);
            if (!$retryIsError) {
                $lowerRetry = strtolower($retryText);
                foreach ($errorPhrases as $phrase) {
                    if (str_contains($lowerRetry, $phrase)) {
                        $retryIsError = true;
                        break;
                    }
                }
            }

            if ($retryIsError) {
                $this->traceNode($session->id, 'Tier2GenerativeAI_Retry', 'ai_call', 'error',
                    ['prompt_length' => strlen($systemPrompt), 'attempt' => 'retry', 'model' => 'gemini-2.0-flash'],
                    ['response' => mb_substr($retryText, 0, 200), 'tokens_used' => $retryResult['total_tokens'] ?? 0], 'Retry attempt failed', $retryMs);
                Log::error('AIChatbot: Tier 2 failed even after retry', ['session' => $session->id]);
                return "Maaf kijiye, abhi kuch technical issue aa raha hai. Kripya thodi der baad dobara try karein. 🙏";
            }

            $this->traceNode($session->id, 'Tier2GenerativeAI_Retry', 'ai_call', 'success',
                ['prompt_length' => strlen($systemPrompt), 'attempt' => 'retry', 'model' => 'gemini-2.0-flash'],
                ['response' => mb_substr($retryText, 0, 200), 'tokens_used' => $retryResult['total_tokens'] ?? 0], null, $retryMs);
            $responseText = $retryText;
        } else {
            $this->traceNode($session->id, 'Tier2GenerativeAI', 'ai_call', 'success',
                ['prompt_length' => strlen($systemPrompt), 'history_length' => count($chatHistory), 'system_prompt_preview' => mb_substr($systemPrompt, 0, 150), 'model' => 'gemini-2.0-flash'],
                ['response' => mb_substr($responseText, 0, 200), 'tokens_used' => $aiResult['total_tokens'] ?? 0], null, $ms);
        }

        // Parse structured response
        $parsed = $this->parseAIResponse($responseText, $session);
        $this->updateSessionState($session, $parsed);

        Log::info("AIChatbot: Tier 2 AI used", ['session' => $session->id, 'tokens' => $aiResult['total_tokens']]);
        return $parsed['response_text'] ?? $responseText;
    }

    /**
     * Build system prompt (optimized — only current context)
     */
    private function buildSystemPrompt(AiChatSession $session, string $rawMessage = '', string $fallbackContext = ''): string
    {
        $basePrompt = Setting::getValue('ai_bot', 'system_prompt', '', $this->companyId);

        // Apply contextual prompt layers if present
        if (!empty($rawMessage)) {
            if ($this->isGreeting($rawMessage)) {
                $greetingPrompt = Setting::getValue('ai_bot', 'greeting_prompt', '', $this->companyId);
                if (!empty($greetingPrompt)) {
                    $basePrompt .= "\n\n" . $greetingPrompt;
                }
            } else {
                $businessPrompt = Setting::getValue('ai_bot', 'business_prompt', '', $this->companyId);
                if (!empty($businessPrompt)) {
                    $basePrompt .= "\n\n" . $businessPrompt;
                }
            }
        }

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

        if (!empty($fallbackContext)) {
            $prompt .= "4. [IMPORTANT FALLBACK DIRECTIVE] {$fallbackContext} First, answer the user's current question conversationally. Then, politely but clearly remind them to reply with their choice from the list you sent them earlier to continue their inquiry.\n";
        }

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
        $terms = $this->getDynamicTerminology();

        // Get chatflow question text for current step as AI reference
        $currentStep = $session->current_step_id ? ChatflowStep::find($session->current_step_id) : null;
        $questionRef = '';
        if ($currentStep && $currentStep->question_text) {
            $questionRef = "\nIMPORTANT: When asking the user, use this question as a reference/guideline (enhance it to be friendly & engaging): \"{$currentStep->question_text}\"\n";
        }

        if (!isset($answers['product_id'])) {
            $query = Product::with('customValues')->where('company_id', $this->companyId)->where('status', 'active');

            if (isset($answers['category_id'])) {
                $query->where('category_id', $answers['category_id']);
            }

            // Apply selected group filter if available
            // Note: selectedGroup filtering is done post-query via getProductBaseName()
            $selectedGroup = $answers['selected_product_group'] ?? null;

            $products = $query->get();

            // Filter by selected group using computed base name
            if ($selectedGroup) {
                $products = $products->filter(function($p) use ($selectedGroup) {
                    return trim(strtolower($this->getProductBaseName($p))) === trim(strtolower($selectedGroup));
                })->values();
            }

            if ($products->isEmpty()) {
                return "System Note: The {$terms['base']} catalogue is currently EMPTY. We do not have any {$terms['plural_base']} to sell right now. If the user asks for {$terms['plural_base']}, inform them that we currently have no {$terms['plural_base']} available.";
            }

            $groupedProducts = $products->groupBy(function($p) {
                return trim(strtolower($this->getProductBaseName($p)));
            });

            // Grouping is needed if we haven't selected a group yet AND there are duplicates
            $needsGrouping = !$selectedGroup && $groupedProducts->count() < $products->count();

            if ($needsGrouping) {
                $context = "### {$terms['base']} Lines / Groups:\n";
                foreach ($groupedProducts as $key => $group) {
                    $actualName = $this->getProductBaseName($group->first());
                    $context .= "- {$actualName} ({$group->count()} {$terms['plural_variant']} available)\n";
                }
                $context .= "\nSystem Note: Because there are many {$terms['plural_variant']} per {$terms['base']}, DO NOT ask the user to select a final {$terms['base']}. Ask them WHICH {$terms['base']} Line from the above list they are interested in. Do NOT list the individual {$terms['plural_variant']} yet.\n";
                $context .= $questionRef;
                return $context;
            }

            $showPrice = $visibleColumns->contains('slug', 'sale_price');
            $context = "### Available " . ($selectedGroup ? "'{$selectedGroup}' {$terms['plural_variant']}" : "{$terms['plural_base']}") . ":\n";
            foreach ($products as $p) {
                $price = ($showPrice && $p->sale_price > 0) ? " — ₹" . number_format($p->sale_price / 100, 2) : '';
                $displayName = $this->getProductDisplayName($p);
                $context .= "- ID:{$p->id} | {$displayName}{$price}\n";
            }
            $context .= $questionRef;
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

        // First, try to match the entire ```json...``` code fence block
        $cleanText = $response;
        if (preg_match('/```(?:json)?\s*\n?\s*(\{[\s\S]*?"action"\s*:\s*"[^"]+"[\s\S]*?\})\s*\n?\s*```/i', $response, $fenceMatch)) {
            $parsed = json_decode($fenceMatch[1], true);
            if ($parsed && isset($parsed['action'])) {
                $result['action'] = $parsed['action'];
                $result['data'] = $parsed['data'] ?? [];
            }
            // Strip the entire code fence block (including backticks and all content between)
            $cleanText = str_replace($fenceMatch[0], '', $response);
        } elseif (preg_match('/(\{[\s\S]*?"action"\s*:\s*"[^"]+"[\s\S]*?\})/', $response, $matches)) {
            // Fallback: bare JSON without code fences
            $parsed = json_decode($matches[1], true);
            if ($parsed && isset($parsed['action'])) {
                $result['action'] = $parsed['action'];
                $result['data'] = $parsed['data'] ?? [];
            }
            $cleanText = str_replace($matches[1], '', $response);
        }

        // Clean up any remaining stray markdown artifacts
        $cleanText = preg_replace('/```(?:json)?\s*```/i', '', $cleanText);
        $cleanText = preg_replace('/```(?:json)?|```/i', '', $cleanText);
        // Remove stray lone } or { at the beginning of lines (leftover from incomplete JSON stripping)
        $cleanText = preg_replace('/^\s*[{}]\s*$/m', '', $cleanText);
        // Clean up leading/trailing whitespace and extra blank lines
        $cleanText = preg_replace('/\n{3,}/', "\n\n", trim($cleanText));
        $result['response_text'] = trim($cleanText);

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
    // PRODUCT MODIFY — Add / Edit / Delete Detection
    // ═══════════════════════════════════════════════════════

    /**
     * Detect if user wants to add another product, edit, or delete current product.
     * Returns null if no modification intent detected (normal flow continues).
     */
    private function detectProductModifyIntent(AiChatSession $session, string $rawMessage): ?string
    {
        // Only check if product is already selected
        $answers = $session->collected_answers ?? [];
        if (!isset($answers['product_id'])) return null;

        // Quick keyword pre-check to avoid unnecessary AI calls
        $lowerMsg = strtolower($rawMessage);
        $modifyKeywords = [
            'add', 'another', 'more', 'aur', 'dusra', 'ek aur', 'add karo', 'aur ek',
            'remove', 'delete', 'hatao', 'nikalo', 'nahi chahiye', 'cancel', 'hata do',
            'change', 'edit', 'badlo', 'replace', 'swap', 'modify', 'galat', 'wrong',
        ];
        $hasModifyKeyword = false;
        foreach ($modifyKeywords as $kw) {
            if (str_contains($lowerMsg, $kw)) {
                $hasModifyKeyword = true;
                break;
            }
        }

        if (!$hasModifyKeyword) return null;

        // AI classification for modification intent
        $currentProduct = $answers['product_name'] ?? 'unknown';
        $prompt = <<<PROMPT
User has selected product "{$currentProduct}" and now said: "{$rawMessage}"

Is the user trying to:
1. ADD — Add another/different product to their order
2. DELETE — Remove/cancel the current product from their order
3. EDIT — Change something about the current product (quantity, variant, etc.)
4. NONE — Not trying to modify, just answering a normal question

Reply with ONLY one word: ADD, DELETE, EDIT, or NONE.
PROMPT;

        try {
            $result = $this->vertexAI->classifyContent($prompt);
            $this->logTokens($session, 1, $result);
            $intent = strtoupper(trim($result['text'] ?? 'NONE'));
            $this->traceNode($session->id, 'ProductModifyIntentAI', 'ai_call', 'success', ['message' => $rawMessage], ['intent' => $intent]);

            switch ($intent) {
                case 'ADD':
                    return $this->handleProductAdd($session, $rawMessage);
                case 'DELETE':
                    return $this->handleProductDelete($session, $rawMessage);
                case 'EDIT':
                    return $this->handleProductEdit($session, $rawMessage);
                default:
                    return null; // Not a modify intent, continue normal flow
            }
        } catch (\Exception $e) {
            Log::warning('AIChatbot: Product modify detection failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Handle ADD — User wants to add another product to their quote
     */
    private function handleProductAdd(AiChatSession $session, string $rawMessage): string
    {
        $answers = $session->collected_answers ?? [];
        $oldProductName = $answers['product_name'] ?? 'previous product';

        // Keep lead and quote, but reset product selection to allow new product pick
        $answersToKeep = [];
        // Keep non-product answers (name, city, etc.)
        foreach ($answers as $key => $val) {
            if (!in_array($key, ['product_id', 'product_name', 'category_id', 'category_name'])) {
                // Keep only non-combo answers
                $isCombo = CatalogueCustomColumn::where('company_id', $this->companyId)
                    ->where('slug', $key)
                    ->where('is_combo', true)
                    ->exists();
                if (!$isCombo) {
                    $answersToKeep[$key] = $val;
                }
            }
        }

        $session->collected_answers = $answersToKeep;
        $session->catalogue_sent = false;
        $session->conversation_state = 'awaiting_product';
        $session->current_step_id = null;
        $session->current_step_retries = 0;
        $session->save();

        Log::info("AIChatbot: Product ADD — Reset for new product", ['session' => $session->id, 'old_product' => $oldProductName]);
        $this->traceNode($session->id, 'ProductAddFlow', 'routing', 'success',
            ['old_product' => $oldProductName, 'old_product_id' => $answers['product_id'] ?? null],
            ['action' => 'reset_for_new_product', 'quote_preserved' => true, 'lead_preserved' => true]);

        // Send catalogue again
        $msg = "✅ *{$oldProductName}* is saved in your quote! ✨\n\n";
        $msg .= "Now let's add another product:\n\n";
        $msg .= $this->sendCatalogue($session);
        return $msg;
    }

    /**
     * Handle DELETE — User wants to remove current product from quote
     */
    private function handleProductDelete(AiChatSession $session, string $rawMessage): string
    {
        $answers = $session->collected_answers ?? [];
        $productId = $answers['product_id'] ?? null;
        $productName = $answers['product_name'] ?? 'the product';

        // Remove product from quote
        if ($session->quote_id && $productId) {
            $quoteItem = QuoteItem::where('quote_id', $session->quote_id)
                ->where('product_id', $productId)
                ->first();
            if ($quoteItem) {
                $quoteItem->delete();
                // Recalculate quote totals
                $quote = Quote::find($session->quote_id);
                if ($quote) {
                    $remainingItems = QuoteItem::where('quote_id', $quote->id)->count();
                    if ($remainingItems === 0) {
                        // No items left — delete quote
                        $quote->delete();
                        $session->quote_id = null;
                    } else {
                        $quote->recalculateTotals();
                    }
                }
            }
        }

        // Remove product from lead
        if ($session->lead_id && $productId) {
            $lead = Lead::find($session->lead_id);
            if ($lead) {
                $lead->products()->detach($productId);
            }
        }

        // Reset product selection
        $answersToKeep = [];
        foreach ($answers as $key => $val) {
            if (!in_array($key, ['product_id', 'product_name', 'category_id', 'category_name'])) {
                $isCombo = CatalogueCustomColumn::where('company_id', $this->companyId)
                    ->where('slug', $key)
                    ->where('is_combo', true)
                    ->exists();
                if (!$isCombo) {
                    $answersToKeep[$key] = $val;
                }
            }
        }

        $session->collected_answers = $answersToKeep;
        $session->catalogue_sent = false;
        $session->conversation_state = 'awaiting_product';
        $session->current_step_id = null;
        $session->current_step_retries = 0;
        $session->save();

        Log::info("AIChatbot: Product DELETE", ['session' => $session->id, 'product' => $productName]);
        $this->traceNode($session->id, 'ProductRemovedFlow', 'routing', 'success',
            ['product' => $productName, 'product_id' => $productId],
            ['action' => 'product_removed', 'quote_items_remaining' => QuoteItem::where('quote_id', $session->quote_id ?? 0)->count(), 'quote_deleted' => is_null($session->quote_id)]);

        $msg = "🗑️ *{$productName}* removed from your order.\n\n";
        $msg .= "Would you like to select a different product?\n\n";
        $msg .= $this->sendCatalogue($session);
        return $msg;
    }

    /**
     * Handle EDIT — User wants to change something about the current product
     */
    private function handleProductEdit(AiChatSession $session, string $rawMessage): string
    {
        $answers = $session->collected_answers ?? [];
        $productId = $answers['product_id'] ?? null;
        $productName = $answers['product_name'] ?? 'the product';

        if (!$productId) return "No product selected to edit.";

        // Detect what the user wants to change using AI
        $product = Product::with('combos.column')->find($productId);
        if (!$product) return "Product not found.";

        $comboOptions = [];
        foreach ($product->combos as $combo) {
            $col = $combo->column;
            if ($col) {
                $currentVal = $answers[$col->slug] ?? 'not selected';
                $comboOptions[] = "{$col->name} (slug: {$col->slug}, current: {$currentVal}, options: " . implode(', ', $combo->selected_values ?? []) . ")";
            }
        }

        $comboInfo = !empty($comboOptions) ? implode("\n", $comboOptions) : 'No combo options';

        $prompt = <<<PROMPT
User wants to edit product "{$productName}" and said: "{$rawMessage}"

Available fields to edit:
{$comboInfo}

Which field does the user want to change? Also what is the new value they want?
Reply in this format ONLY:
FIELD: <slug>
VALUE: <new_value>

If you cannot determine, reply: UNCLEAR
PROMPT;

        try {
            $result = $this->vertexAI->classifyContent($prompt);
            $this->logTokens($session, 1, $result);
            $responseText = trim($result['text'] ?? '');

            if (str_contains(strtoupper($responseText), 'UNCLEAR')) {
                // Show current selections and ask what to change
                $msg = "📋 Current selections for *{$productName}*:\n\n";
                foreach ($product->combos as $combo) {
                    $col = $combo->column;
                    if ($col) {
                        $val = $answers[$col->slug] ?? '❌ Not selected';
                        $msg .= "• {$col->name}: *{$val}*\n";
                    }
                }
                $msg .= "\nWhich option would you like to change? Reply with the name and new value.";
                return $msg;
            }

            // Parse FIELD and VALUE
            preg_match('/FIELD:\s*(.+)/i', $responseText, $fieldMatch);
            preg_match('/VALUE:\s*(.+)/i', $responseText, $valueMatch);

            $fieldSlug = trim($fieldMatch[1] ?? '');
            $newValue = trim($valueMatch[1] ?? '');

            if (empty($fieldSlug) || empty($newValue)) {
                return "Sorry, I couldn't understand what to change. Please specify which option and the new value.";
            }

            // Validate the new value against available options
            $validCombo = null;
            foreach ($product->combos as $combo) {
                if ($combo->column && $combo->column->slug === $fieldSlug) {
                    $validCombo = $combo;
                    break;
                }
            }

            if (!$validCombo) {
                return "Sorry, '{$fieldSlug}' is not a valid option for this product.";
            }

            // Fuzzy match the new value against available options
            $matched = null;
            foreach ($validCombo->selected_values ?? [] as $opt) {
                if (strtolower($opt) === strtolower($newValue)) {
                    $matched = $opt;
                    break;
                }
            }

            // If no exact match, try AI matching
            if (!$matched) {
                $optionsList = implode(', ', $validCombo->selected_values ?? []);
                $matchPrompt = "User said: \"{$newValue}\"\nOptions: [{$optionsList}]\nWhich option matches? Reply with EXACT option text or NONE.";
                $matchResult = $this->vertexAI->classifyContent($matchPrompt);
                $this->logTokens($session, 1, $matchResult);
                $matchText = trim($matchResult['text'] ?? '');

                foreach ($validCombo->selected_values ?? [] as $opt) {
                    if (strtolower($opt) === strtolower($matchText)) {
                        $matched = $opt;
                        break;
                    }
                }
            }

            if (!$matched) {
                return "Sorry, '{$newValue}' is not available. Options: " . implode(' | ', $validCombo->selected_values ?? []);
            }

            // Update the answer
            $colName = $validCombo->column->name;
            $session->setAnswer($fieldSlug, $matched);
            $session->save();

            // Update quote variation
            $this->updateQuoteVariation($session, $product);
            $this->traceNode($session->id, 'ProductEditFlow', 'database', 'success',
                ['field' => $fieldSlug, 'old_value' => $answers[$fieldSlug] ?? 'none', 'new_value' => $matched],
                ['quote_id' => $session->quote_id, 'variation_updated' => true]);

            Log::info("AIChatbot: Product EDIT", [
                'session' => $session->id,
                'field' => $fieldSlug,
                'old' => $answers[$fieldSlug] ?? 'none',
                'new' => $matched,
            ]);

            return "✏️ Updated *{$colName}* to *{$matched}*! ✅";

        } catch (\Exception $e) {
            Log::error('AIChatbot: Product edit failed: ' . $e->getMessage());
            return "Sorry, couldn't process the edit. Please try again.";
        }
    }

    // ═══════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════

    /**
     * Intercept out-of-bounds questions while in a chatflow step.
     */
    private function handleOutOfContextQuestion(AiChatSession $session, string $rawMessage, $steps): string
    {
        $this->traceNode($session->id, 'OutOfContextQuery', 'ai_call', 'success', ['message' => $rawMessage], ['action' => 'answering_business_query']);

        $company = \App\Models\Company::find($this->companyId);
        $systemPrompt = "You are a highly helpful sales representative for {$company->name}.\n";
        $systemPrompt .= "The user is currently in the middle of a product selection/survey flow, but they just asked an off-topic question or made a generic remark.\n\n";

        if (!empty($this->businessPrompt)) {
            $systemPrompt .= "---\n### BUSINESS DETAILS:\n{$this->businessPrompt}\n---\n\n";
        }

        $systemPrompt .= "Your task: Answer the user's inquiry concisely and naturally (1-2 sentences maximum). If their question cannot be answered using the Business Details provided, politely formulate a general response (e.g. 'I am only equipped to help with product inquiries').\n";
        $systemPrompt .= "DO NOT append a question about the chatflow, just answer the query.";

        $t = microtime(true);
        $aiResult = $this->vertexAI->generateContent($systemPrompt, [['role' => 'user', 'text' => $rawMessage]]);
        $ms = (int)((microtime(true) - $t) * 1000);
        $this->logTokens($session, 2, $aiResult);

        $answer = trim($aiResult['text'] ?? "Sorry, I am not sure about that. Let's continue with your order!");

        // Safely strip markdown or JSON if AI hallucinates it
        $answer = preg_replace('/```.*?```/s', '', $answer);
        
        $currentPrompt = $this->buildNextStepPrompt($session, $steps) ?: "How can I further assist you?";

        return trim($answer) . "\n\n" . "👇\n" . $currentPrompt;
    }

    /**
     * Fast PHP-level greeting detection (no AI call needed).
     * Returns true if the message is a simple greeting.
     */
    private function isGreeting(string $message): bool
    {
        $msg = strtolower(trim($message));

        // Exact match greetings
        $greetings = [
            'hi', 'hello', 'hey', 'hola', 'namaste', 'namaskar', 'namasté',
            'good morning', 'good afternoon', 'good evening', 'good night',
            'gm', 'gn', 'howdy', 'sup', 'yo',
            'hii', 'hiii', 'hiiii', 'hiiiii',
            'helo', 'hllo', 'hlw', 'hellow', 'helloo',
            'hw r u', 'how are you', 'how r u',
            'kaise ho', 'kya hal hai', 'kya haal hai', 'kem cho',
            'vanakkam', 'vanakam',
            'assalamu alaikum', 'salam', 'salaam',
            'sat sri akal', 'sat shri akal',
            'jai shri ram', 'jai shree ram', 'ram ram',
            'jai jinendra', 'pranam', 'pranaam',
            'shubh prabhat', 'suprabhat',
        ];

        return in_array($msg, $greetings);
    }

    /**
     * Fast PHP-level business query detection.
     */
    private function isBusinessQuery(string $message): bool
    {
        $msg = strtolower(trim($message));
        $keywords = [
            'address', 'location', 'where are you', 'kaha ho', 'kaha par ho', 'pata',
            'contact', 'phone', 'number', 'call',
            'about', 'company', 'business', 'details', 'who are you', 'kaun ho',
            'timing', 'hours', 'open', 'close', 'kab khulte ho', 'baje',
            'website', 'link', 'url',
            'owner', 'manager', 'shop', 'dukan'
        ];

        foreach ($keywords as $kw) {
            if (str_contains($msg, $kw)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Fast PHP-level product intent detection (no AI call needed).
     * Returns true if the message clearly asks about products/catalogue.
     */
    private function isProductIntent(string $message): bool
    {
        $msg = strtolower(trim($message));

        // Keyword-based detection (substring match)
        $keywords = [
            'product', 'products', 'catalogue', 'catalog',
            'price', 'prices', 'rate', 'rates', 'cost',
            'dikhao', 'dikha do', 'btao', 'batao', 'bata do',
            'bechte', 'sell', 'selling',
            'kya hai', 'kya he', 'kya milta', 'kya milega',
            'show me', 'show products', 'list',
            'what do you', 'what you have',
            'menu', 'item', 'items',
        ];

        foreach ($keywords as $kw) {
            if (str_contains($msg, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find which product keyword matched in the message (for trace output).
     */
    private function findMatchedProductKeyword(string $message): ?string
    {
        $msg = strtolower(trim($message));
        $keywords = [
            'product', 'products', 'catalogue', 'catalog',
            'price', 'prices', 'rate', 'rates', 'cost',
            'dikhao', 'dikha do', 'btao', 'batao', 'bata do',
            'bechte', 'sell', 'selling',
            'kya hai', 'kya he', 'kya milta', 'kya milega',
            'show me', 'show products', 'list',
            'what do you', 'what you have',
            'menu', 'item', 'items',
        ];

        foreach ($keywords as $kw) {
            if (str_contains($msg, $kw)) {
                return $kw;
            }
        }
        return null;
    }

    /**
     * Check if user's message is an affirmative response (yes, ok, haan, etc.)
     */
    private function isAffirmativeResponse(string $message): bool
    {
        $msg = strtolower(trim($message));
        $affirmatives = [
            'yes', 'yeah', 'yep', 'yup', 'sure', 'ok', 'okay', 'alright',
            'haan', 'ha', 'haa', 'ji', 'ji ha', 'ha ji', 'theek hai', 'thik hai',
            'bilkul', 'zaroor', 'sahi', 'correct', 'right', 'done',
            'ho', 'han', 'hmm', 'accha', 'achha', 'acha',
        ];
        return in_array($msg, $affirmatives);
    }

    /**
     * Check if the bot's last message was asking about products/catalogue.
     * This helps handle "yes" → product list flow when bot asked "interested in products?"
     */
    private function botLastAskedAboutProducts(AiChatSession $session): bool
    {
        $lastBotMsg = AiChatMessage::where('session_id', $session->id)
            ->where('role', 'bot')
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastBotMsg) return false;

        $botText = strtolower($lastBotMsg->message);
        $productHints = [
            'product', 'catalogue', 'catalog', 'interested',
            'kya chahiye', 'kis tarah', 'madad', 'help',
            'what are you looking', 'what would you like',
        ];

        foreach ($productHints as $hint) {
            if (str_contains($botText, $hint)) {
                return true;
            }
        }
        return false;
    }

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
        $answers = $session->collected_answers ?? [];
        $nextStep = null;

        // Loop through all steps to find the FIRST unanswered step
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
            } elseif ($step->step_type === 'ask_base_column') {
                if (isset($answers['selected_product_group'])) {
                    $isAnswered = true;
                }
            } elseif ($step->step_type === 'ask_category') {
                if (isset($answers['category_id'])) {
                    $isAnswered = true;
                }
            } elseif (in_array($step->step_type, ['ask_custom', 'ask_optional'])) {
                if (isset($answers[$step->field_key]) || ($session->optional_asked ?? false)) {
                    // For simplicity, checking if key exists or optional already skipped
                    if (isset($answers[$step->field_key])) {
                        $isAnswered = true;
                    } else if ($step->isOptionalStep() && isset($session->optional_asked[$step->field_key])) {
                        $isAnswered = true;
                    }
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
            $session->conversation_state = 'completed';
            $session->update(['status' => 'completed']);
        }
    }

    private function updateQuoteItemDescription(AiChatSession $session, Product $product): void
    {
        // Build session answers map from collected answers
        $sessionAnswers = $session->collected_answers ?? [];

        // Use the product's dynamic description builder with session overlay
        $fullDesc = $product->getDynamicDescription($sessionAnswers);

        if (empty($fullDesc)) return;

        // Update QuoteItem
        if ($session->quote_id) {
            $quoteItem = QuoteItem::where('quote_id', $session->quote_id)
                ->where('product_id', $product->id)
                ->first();

            if ($quoteItem) {
                $quoteItem->update([
                    'description' => $fullDesc
                ]);
            }
        }

        // Update Lead Product pivot
        if ($session->lead_id) {
            $lead = Lead::find($session->lead_id);
            if ($lead && $lead->products->contains('id', $product->id)) {
                $lead->products()->updateExistingPivot($product->id, [
                    'description' => $fullDesc
                ]);
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

    private function buildNextStepPrompt(AiChatSession $session, $steps): string
    {
        $nextStep = $session->current_step_id ? $steps->firstWhere('id', $session->current_step_id) : null;
        if (!$nextStep) return $this->translateIfNeeded($session, "✅ All done! Our team will contact you. 🙏");

        // Send step-level media BEFORE asking the question (once per session)
        if ($nextStep->hasMedia()) {
            $mediaKey = "step_{$nextStep->id}";
            if (!$session->hasMediaBeenSent($mediaKey)) {
                $this->sendMediaToWhatsApp($session, $nextStep->media_path, 'ChatflowStepMedia');
                $session->markMediaSent($mediaKey);
                $session->save();
            }
        }

        if ($nextStep->step_type === 'ask_combo' && $nextStep->linkedColumn) {
            $productId = $session->getAnswer('product_id');
            $product = Product::with('combos.column')->find($productId);
            $comboValues = $product ? $this->getComboValuesForProduct($product, $nextStep->linkedColumn) : [];
            $question = $nextStep->question_text ?: "Which {$nextStep->linkedColumn->name}?";
            return $this->translateIfNeeded($session, "{$question} 👇\n" . implode(' | ', $comboValues));
        }

        if ($nextStep->step_type === 'send_summary') {
            return $this->handleSummaryStep($session);
        }

        return $this->translateIfNeeded($session, $nextStep->question_text ?: "Please provide your {$nextStep->field_key}:");
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

    // ═══════════════════════════════════════════════════════
    // DYNAMIC TERMINOLOGY ENGINE
    // ═══════════════════════════════════════════════════════

    /**
     * Get the label for the category field.
     */
    private function getCategoryFieldLabel(): string
    {
        $catCol = CatalogueCustomColumn::where('company_id', $this->companyId)
            ->where('is_category', true)
            ->where('is_active', true)
            ->first();

        return $catCol ? $catCol->name : 'Category';
    }

    /**
     * Get dynamic terminology labels based on CatalogueCustomColumn settings.
     * Uses sort_order hierarchy: the column ABOVE unique = Base, the unique column = Variant.
     * Results are cached per-request to avoid repeated DB queries.
     *
     * @return array{base: string, plural_base: string, variant: string, plural_variant: string}
     */
    private array $terminologyCache = [];

    private function getDynamicTerminology(): array
    {
        if (!empty($this->terminologyCache)) {
            return $this->terminologyCache;
        }

        // Find the unique column (e.g., "Model")
        $uniqueCol = CatalogueCustomColumn::where('company_id', $this->companyId)
            ->where('is_unique', true)
            ->where('is_active', true)
            ->first();

        // Find the base column: the FIRST column above the unique column (by sort_order)
        $baseCol = null;
        if ($uniqueCol) {
            $baseCol = CatalogueCustomColumn::where('company_id', $this->companyId)
                ->where('sort_order', '<', $uniqueCol->sort_order)
                ->where('is_active', true)
                ->orderBy('sort_order', 'asc')
                ->first();
        }

        // Extract clean names — strip " Name" suffix for display (e.g., "Product Name" → "Product")
        $baseName = $baseCol ? $baseCol->name : 'Product';
        $cleanBaseName = preg_replace('/\s+name$/i', '', $baseName);

        $variantName = $uniqueCol ? $uniqueCol->name : 'Model';

        // Simple English pluralization
        $pluralBase = $this->simplePlural($cleanBaseName);
        $pluralVariant = $this->simplePlural($variantName);

        $this->terminologyCache = [
            'base' => $cleanBaseName,
            'plural_base' => $pluralBase,
            'variant' => $variantName,
            'plural_variant' => $pluralVariant,
        ];

        return $this->terminologyCache;
    }

    /**
     * Basic English pluralization helper.
     * Handles common patterns: s→ses, y→ies, default +s.
     */
    private function simplePlural(string $word): string
    {
        $lower = strtolower($word);
        if (str_ends_with($lower, 's') || str_ends_with($lower, 'sh') || str_ends_with($lower, 'ch') || str_ends_with($lower, 'x')) {
            return $word . 'es';
        }
        if (str_ends_with($lower, 'y') && !in_array(substr($lower, -2, 1), ['a', 'e', 'i', 'o', 'u'])) {
            return substr($word, 0, -1) . 'ies';
        }
        return $word . 's';
    }

    // ═══════════════════════════════════════════════════════
    // MEDIA ENGINE — Step, Group & Combo Level
    // ═══════════════════════════════════════════════════════

    /**
     * Send chatflow step-level media (PDF/Image/Video) before the question.
     * Traces: ChatflowMediaCheck → ChatflowMediaSent / MediaSendFailed
     */
    private function sendStepMedia(AiChatSession $session, ChatflowStep $step): void
    {
        if (!$step->hasMedia()) {
            $this->traceNode($session->id, 'ChatflowMediaCheck', 'media', 'warning',
                ['step_id' => $step->id, 'step_type' => $step->step_type, 'has_media' => false],
                ['media_found' => false, 'message' => 'No media attached securely to this step.']);
            return;
        }

        $this->traceNode($session->id, 'ChatflowMediaCheck', 'media', 'success',
            ['step_id' => $step->id, 'step_type' => $step->step_type, 'has_media' => true],
            ['media_found' => true, 'media_url' => $step->media_path]);

        $this->sendMediaToWhatsApp($session, $step->media_path, 'ChatflowMediaSent');
    }

    /**
     * Send a media file to WhatsApp via Evolution API.
     * Handles image, document (PDF), and video types.
     * Traces the send result with the provided traceNodeName.
     */
    private function sendMediaToWhatsApp(AiChatSession $session, string $mediaUrl, string $traceNodeName): void
    {
        // ── Resolve relative URLs to absolute URLs ──
        // Evolution API needs a full http(s):// URL to fetch the media.
        // If the stored path is relative (e.g., "/storage/products/cover/abc.jpg"),
        // convert it to an absolute URL using the Laravel asset() helper.
        if (!empty($mediaUrl) && !preg_match('#^https?://#i', $mediaUrl)) {
            $mediaUrl = asset(ltrim($mediaUrl, '/'));
        }

        $config = Setting::getValue('whatsapp', 'api_config', [
            'api_url' => '',
            'api_key' => '',
        ], $this->companyId);

        if (empty($config['api_url']) || empty($config['api_key'])) {
            $this->traceNode($session->id, 'MediaSendFailed', 'media', 'error',
                ['media_url' => $mediaUrl, 'error_type' => 'config_missing'],
                ['error_message' => 'WhatsApp API not configured']);
            return;
        }

        // Detect the instance name from session
        $instanceName = $session->instance_name ?? Setting::getValue('whatsapp', 'instance_name', '', $this->companyId);
        if (empty($instanceName)) {
            $this->traceNode($session->id, 'MediaSendFailed', 'media', 'error',
                ['media_url' => $mediaUrl, 'error_type' => 'no_instance'],
                ['error_message' => 'WhatsApp instance name not found']);
            return;
        }

        // Format phone number
        $phone = preg_replace('/\D/', '', $session->phone_number);
        if (strlen($phone) == 10) {
            $phone = '91' . $phone;
        }

        // Determine media type from URL extension
        $extension = strtolower(pathinfo($mediaUrl, PATHINFO_EXTENSION));
        $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        $isVideo = in_array($extension, ['mp4', 'avi', 'mov', 'mkv']);
        $isDocument = in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']);

        // Build the API endpoint and payload
        $endpoint = $isImage ? 'sendMedia' : ($isDocument ? 'sendMedia' : ($isVideo ? 'sendMedia' : 'sendMedia'));
        $mediaType = $isImage ? 'image' : ($isDocument ? 'document' : ($isVideo ? 'video' : 'image'));

        try {
            $payload = [
                'number' => $phone,
                'mediatype' => $mediaType,
                'media' => $mediaUrl,
            ];

            if ($isDocument) {
                $payload['fileName'] = basename($mediaUrl);
            }

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'apikey' => $config['api_key'],
                'Content-Type' => 'application/json',
            ])->post("{$config['api_url']}/message/sendMedia/{$instanceName}", $payload);

            if ($response->successful()) {
                $this->traceNode($session->id, $traceNodeName, 'media', 'success',
                    ['media_url' => $mediaUrl, 'media_type' => $mediaType],
                    ['whatsapp_status' => 'sent', 'http_status' => $response->status()]);
            } else {
                $status = $response->status();
                $responseBody = $response->body();
                $isAxiosError = str_contains($responseBody, 'AxiosError') || str_contains($responseBody, '404');
                $suggestion = null;
                
                if ($isAxiosError) {
                    $suggestion = "Evolution API could not reach your network at '{$mediaUrl}'. Ensure your server's public network/DNS resolves this path, or that 'APP_URL' in your .env doesn't point to an internal-only domain inaccessible by Evolution. Also ensure the file actually exists.";
                }

                $this->traceNode($session->id, 'MediaSendFailed', 'media', 'error',
                    ['media_url' => $mediaUrl, 'error_type' => 'api_error'],
                    [
                        'error_message' => $responseBody, 
                        'http_status' => $status,
                        'is_network_routing_issue' => $isAxiosError,
                        'fix_suggestion' => $suggestion
                    ]
                );
            }
        } catch (\Exception $e) {
            $this->traceNode($session->id, 'MediaSendFailed', 'media', 'error',
                ['media_url' => $mediaUrl, 'error_type' => 'exception'],
                ['error_message' => $e->getMessage()]);
            Log::error("AIChatbot: Media send failed", ['url' => $mediaUrl, 'error' => $e->getMessage()]);
        }
    }



    /**
     * Send unique model image when a specific product is selected.
     * Traces: UniqueModelMediaLookup → UniqueModelMediaSent / not found
     */
    private function sendProductMedia(AiChatSession $session, Product $product): void
    {
        // Once-per-session guard
        $mediaKey = "product_{$product->id}";
        if ($session->hasMediaBeenSent($mediaKey)) {
            $this->traceNode($session->id, 'UniqueModelMediaLookup', 'media', 'info',
                ['product_id' => $product->id, 'model_name' => $this->getProductDisplayName($product)],
                ['found' => true, 'skipped' => 'already_sent_this_session']);
            return;
        }

        // Check for unique model image
        if (!empty($product->cover_media_url)) {
            $this->traceNode($session->id, 'UniqueModelMediaLookup', 'media', 'success',
                ['product_id' => $product->id, 'model_name' => $this->getProductDisplayName($product)],
                ['found' => true, 'media_url' => $product->cover_media_url]);
            $this->sendMediaToWhatsApp($session, $product->cover_media_url, 'UniqueModelMediaSent');
            $session->markMediaSent($mediaKey);
            $session->save();
        } else {
            $this->traceNode($session->id, 'UniqueModelMediaLookup', 'media', 'warning',
                ['product_id' => $product->id, 'model_name' => $this->getProductDisplayName($product)],
                ['found' => false, 'message' => 'No cover_media_url found for this product.']);
        }
    }

    /**
     * Send combo-level media when a specific variant (e.g., Black finish) is selected.
     * Traces: ComboMediaLookup → ComboMediaSent / not found
     */
    private function sendComboMedia(AiChatSession $session, Product $product, string $comboSlug, string $selectedValue): void
    {
        // Find the combo record for this product + column + value
        $combo = $product->combos->first(function ($c) use ($comboSlug, $selectedValue) {
            if (!$c->column || $c->column->slug !== $comboSlug) return false;
            $values = is_array($c->selected_values) ? $c->selected_values : json_decode($c->selected_values, true);
            return is_array($values) && in_array($selectedValue, $values);
        });

        if ($combo && !empty($combo->combo_media_url)) {
            $this->traceNode($session->id, 'ComboMediaLookup', 'media', 'success',
                ['product_id' => $product->id, 'combo_key' => $comboSlug, 'combo_value' => $selectedValue],
                ['found' => true, 'combo_media_url' => $combo->combo_media_url]);
            $this->sendMediaToWhatsApp($session, $combo->combo_media_url, 'ComboMediaSent');
        } else {
            $this->traceNode($session->id, 'ComboMediaLookup', 'media', 'info',
                ['product_id' => $product->id, 'combo_key' => $comboSlug, 'combo_value' => $selectedValue],
                ['found' => false]);
        }
    }


    // ═══════════════════════════════════════════════════════
    // LANGUAGE DETECTION (PHP — zero AI tokens)
    // ═══════════════════════════════════════════════════════

    /**
     * Detect language from message text using PHP regex.
     * Zero AI tokens — pure script detection + Hinglish keyword matching.
     */
    private function detectLanguage(string $message): string
    {
        // Devanagari script (Hindi)
        if (preg_match('/[\x{0900}-\x{097F}]/u', $message)) return 'hi';
        // Gujarati script
        if (preg_match('/[\x{0A80}-\x{0AFF}]/u', $message)) return 'gu';
        // Tamil script
        if (preg_match('/[\x{0B80}-\x{0BFF}]/u', $message)) return 'ta';
        // Telugu script
        if (preg_match('/[\x{0C00}-\x{0C7F}]/u', $message)) return 'te';
        // Bengali script
        if (preg_match('/[\x{0980}-\x{09FF}]/u', $message)) return 'bn';
        // Kannada script
        if (preg_match('/[\x{0C80}-\x{0CFF}]/u', $message)) return 'kn';
        // Malayalam script
        if (preg_match('/[\x{0D00}-\x{0D7F}]/u', $message)) return 'ml';
        // Punjabi/Gurmukhi script
        if (preg_match('/[\x{0A00}-\x{0A7F}]/u', $message)) return 'pa';

        // Hinglish detection (Hindi words written in Latin script)
        $hindiWords = ['kya','mujhe','chahiye','dikhao','btao','batao','bechte','hai','he','ho','haan','nahi','acha','theek','kaise','kaha','bhai','ji','dekho','bolo','karo','kru','krdo','muje','krna','dedo','bhejo','product','price','kitna','kitne','konsa','kaunsa','aur','ek','do','teen'];
        $wordCount = 0;
        $lowerMsg = strtolower($message);
        foreach ($hindiWords as $hw) {
            if (preg_match('/\b' . preg_quote($hw, '/') . '\b/i', $lowerMsg)) $wordCount++;
        }
        if ($wordCount >= 2) return 'hi';  // Hinglish → treat as Hindi

        return 'en'; // Default English
    }

    // ═══════════════════════════════════════════════════════
    // SMART TRANSLATION (AI — minimal tokens)
    // ═══════════════════════════════════════════════════════

    /**
     * Translate PHP-hardcoded response to user's detected language.
     * Skips if user language is English (zero tokens).
     * Uses micro-prompt (~30-40 tokens per call).
     */
    private function translateIfNeeded(AiChatSession $session, string $phpResponse): string
    {
        $lang = $session->detected_language ?? 'en';

        // English users → no translation needed (zero tokens)
        if ($lang === 'en') return $phpResponse;

        // Map language codes to names for the prompt
        $langNames = [
            'hi' => 'Hindi (Hinglish using Latin script)',
            'gu' => 'Gujarati',
            'ta' => 'Tamil',
            'te' => 'Telugu',
            'bn' => 'Bengali',
            'kn' => 'Kannada',
            'ml' => 'Malayalam',
            'pa' => 'Punjabi',
        ];
        $langName = $langNames[$lang] ?? 'Hindi';

        try {
            $prompt = "Translate to {$langName}. Keep emojis, *bold*, numbers, product names as-is. Only translate connecting words/phrases. Reply with translated text only:\n\n{$phpResponse}";

            $result = $this->vertexAI->classifyContent($prompt);
            $this->logTokens($session, 1, $result);

            $translated = trim($result['text'] ?? '');
            return !empty($translated) ? $translated : $phpResponse;
        } catch (\Exception $e) {
            Log::warning('AIChatbot: Translation failed, using original', ['error' => $e->getMessage()]);
            return $phpResponse;
        }
    }

    // ═══════════════════════════════════════════════════════
    // SPELL CORRECTION (AI micro-prompt — minimal tokens)
    // ═══════════════════════════════════════════════════════

    /**
     * Fix spelling mistakes in user input before product/option matching.
     * Uses configurable micro-prompt from settings.
     * Only called when PHP direct match fails to save tokens.
     */
    private function spellCorrect(AiChatSession $session, string $userText, array $availableItems): string
    {
        if (empty($availableItems)) return $userText;

        $prompt = $this->spellCorrectionPrompt;
        if (empty($prompt)) {
            $prompt = 'Extract the core product/category name from the conversational user text: "{text}". Match it to the closest item in: [{items}]. Ignore conversational filler words (like "me kya he", "dikhao", "btao"). Reply with ONLY the corrected text. If completely irrevelant, return the original text.';
        }

        $prompt = str_replace(
            ['{text}', '{items}'],
            [$userText, implode(', ', $availableItems)],
            $prompt
        );

        try {
            $t = microtime(true);
            $result = $this->vertexAI->classifyContent($prompt);
            $ms = (int)((microtime(true) - $t) * 1000);
            $this->logTokens($session, 1, $result);

            $corrected = trim($result['text'] ?? '');

            $this->traceNode($session->id, 'SpellCorrection', 'ai_call', 'success',
                ['original' => $userText, 'available_items' => array_slice($availableItems, 0, 10)],
                ['corrected' => $corrected, 'tokens_used' => $result['total_tokens'] ?? 0], null, $ms);

            return !empty($corrected) ? $corrected : $userText;
        } catch (\Exception $e) {
            Log::warning('AIChatbot: Spell correction failed', ['error' => $e->getMessage()]);
            return $userText;
        }
    }

    // ═══════════════════════════════════════════════════════
    // PROGRESS SUMMARY (after all combo steps done)
    // ═══════════════════════════════════════════════════════

    /**
     * Build progress summary showing confirmed selections.
     * Returns non-empty string ONLY when all combo steps are answered.
     */
    private function buildProgressSummary(AiChatSession $session, $steps): string
    {
        $answers = $session->collected_answers ?? [];
        $comboSteps = $steps->where('step_type', 'ask_combo');

        if ($comboSteps->isEmpty()) return '';

        $allComboDone = true;
        $confirmedItems = [];

        foreach ($comboSteps as $cs) {
            if ($cs->linkedColumn && isset($answers[$cs->linkedColumn->slug])) {
                $confirmedItems[] = "✅ {$cs->linkedColumn->name}: {$answers[$cs->linkedColumn->slug]}";
            } else if ($cs->linkedColumn) {
                $allComboDone = false;
            }
        }

        // Only show when ALL combos are done
        if (!$allComboDone || empty($confirmedItems)) return '';

        $productName = $answers['product_name'] ?? '';
        $msg = "\n\n📋 *Your Selection:*\n";
        $msg .= "✅ Product: {$productName}\n";
        foreach ($confirmedItems as $item) {
            $msg .= "{$item}\n";
        }

        return $msg;
    }


}
