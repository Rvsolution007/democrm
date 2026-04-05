<?php

namespace App\Services;

use App\Models\AiChatSession;
use App\Models\AiChatMessage;
use App\Models\AiChatTrace;
use App\Models\AiTokenLog;
use App\Models\AiProductSession;
use App\Models\ChatflowStep;
use App\Models\ChatFollowupSchedule;
use App\Models\CatalogueCustomColumn;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Lead;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
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
    private string $tier3Prompt;
    private int $matchMinConfidence;

    public function __construct(int $companyId, int $userId)
    {
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->vertexAI = new VertexAIService($companyId);
        $this->replyLanguage = Setting::getValue('ai_bot', 'reply_language', 'auto', $companyId);
        $this->greetingPrompt = Setting::getValue('ai_bot', 'greeting_prompt', '', $companyId);
        $this->businessPrompt = Setting::getValue('ai_bot', 'business_prompt', '', $companyId);
        $this->spellCorrectionPrompt = Setting::getValue('ai_bot', 'spell_correction_prompt', '', $companyId);
        $this->tier3Prompt = Setting::getValue('ai_bot', 'tier3_prompt', '', $companyId);
        $this->matchMinConfidence = (int) Setting::getValue('ai_bot', 'match_min_confidence', 60, $companyId);
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

    /**
     * Get a summary of ALL show_in_ai catalogue column values for a product.
     * Used to enrich Tier 1 AI input with full context (like combo step).
     * Returns: "Category: Door Handle | Size: 225mm | Finish: Chrome"
     */
    private function getProductColumnSummary(Product $product): string
    {
        // Ensure aiVisibleColumns is loaded
        if ($this->aiVisibleColumns === null) {
            $this->aiVisibleColumns = CatalogueCustomColumn::where('company_id', $this->companyId)
                ->where('show_in_ai', true)
                ->where('is_active', true)
                ->orderBy('sort_order', 'asc')
                ->get();
        }

        $parts = [];
        foreach ($this->aiVisibleColumns as $col) {
            $val = '';
            if ($col->is_category && $product->category_id) {
                $cat = $product->relationLoaded('category') ? $product->category : $product->category()->first();
                $val = $cat?->name ?? '';
            } elseif ($col->is_system) {
                $slug = $col->slug === 'product_name' ? 'name' : $col->slug;
                $val = $product->{$slug} ?? '';
            } else {
                $cv = $product->relationLoaded('customValues')
                    ? $product->customValues->where('column_id', $col->id)->first()
                    : $product->customValues()->where('column_id', $col->id)->first();
                if ($cv && !empty($cv->value)) {
                    $decoded = json_decode($cv->value, true);
                    $val = is_array($decoded) ? implode(', ', $decoded) : $cv->value;
                }
            }
            if (!empty(trim((string)$val))) {
                $parts[] = $col->name . ': ' . trim((string)$val);
            }
        }
        return implode(' | ', $parts);
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

                // Save incoming user message FIRST (so traceNode has message context)
                $userMsg = AiChatMessage::create([
                    'session_id' => $session->id,
                    'role' => 'user',
                    'message' => $messageText,
                    'message_type' => $imageUrl ? 'image' : 'text',
                    'image_url' => $imageUrl,
                    'reply_context' => $replyContext,
                ]);
                $this->currentMessageId = $userMsg->id;

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
        $steps = ChatflowStep::with('linkedColumn')->where('company_id', $this->companyId)->orderBy('sort_order')->get();
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
                $this->logTokens($session, 0, $greetResult);
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

                case 'ask_column':
                    $this->routeTrace[] = 'handleColumnFilterStep';
                    return $this->handleColumnFilterStep($session, $currentStep, $rawMessage, $steps);

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

        // ═══ STAGE 2: PHP PRODUCT GROUP MATCH — Runs BEFORE any AI/intent checks ═══
        $isPureNumber = preg_match('/^\d+$/', trim($rawMessage));
        if (!$isPureNumber && mb_strlen(trim($rawMessage)) >= 2) {
            $pgmStart = microtime(true);
            $pgmMatches = $this->phpProductGroupMatch($rawMessage);
            $pgmMs = (int)((microtime(true) - $pgmStart) * 1000);

            if (!empty($pgmMatches)) {
                $this->traceNode($session->id, 'PHPProductGroupMatch', 'routing', 'success',
                    ['message' => $rawMessage, 'min_confidence' => $this->matchMinConfidence, 'engine' => '5-Layer (Exact/Contains/Word/Fuzzy/Phonetic)'],
                    ['match_count' => count($pgmMatches), 'time_ms' => $pgmMs,
                     'top_matches' => array_map(fn($m) => [
                         'col' => $m['column_name'], 'val' => $m['matched_value'],
                         'type' => $m['match_type'], 'conf' => $m['confidence'] . '%',
                         'is_cat' => $m['is_category'], 'is_uniq' => $m['is_unique'],
                     ], array_slice($pgmMatches, 0, 8))], null, $pgmMs);

                // Classify matches
                $catPGM = array_values(array_filter($pgmMatches, fn($m) => $m['is_category']));
                $uniPGM = array_values(array_filter($pgmMatches, fn($m) => $m['is_unique']));

                // SCENARIO 1a: Category matches + category step pending
                if (!empty($catPGM) && $hasCategoryStep && !isset($answers['category_id'])) {
                    $this->routeTrace[] = 'PGM→CategoryMatch';
                    return $this->handlePGMCategoryMatch($session, $rawMessage, $catPGM, $steps);
                }

                // SCENARIO 1b: Unique column matches + past category step
                if (!empty($uniPGM) && (!$hasCategoryStep || isset($answers['category_id']))) {
                    $this->routeTrace[] = 'PGM→ProductMatch';
                    return $this->matchProductFromMessage($session, $fullMessage, $rawMessage, $imageUrl, $steps);
                }

                // SCENARIO 2: Other column matches → Tier 3
                $this->routeTrace[] = 'PGM→Tier3';
                return $this->handleTier3ColumnAnalytics($session, $rawMessage, $pgmMatches, $imageUrl);
            } else {
                $this->traceNode($session->id, 'PHPProductGroupMatch', 'routing', 'success',
                    ['message' => $rawMessage, 'min_confidence' => $this->matchMinConfidence],
                    ['match_count' => 0, 'time_ms' => $pgmMs, 'fallback' => 'legacy_intent_flow'], null, $pgmMs);
            }
        }

        // ═══ EXISTING FLOW (PGM found no matches or skipped) ═══
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
     * Handle PGM-resolved category matches.
     * Single match → auto-select. Multiple → queue with AiProductSession.
     */
    private function handlePGMCategoryMatch(AiChatSession $session, string $rawMessage, array $catPGM, $steps): string
    {
        $categories = \App\Models\Category::where('company_id', $this->companyId)
            ->where('status', 'active')
            ->whereHas('products', fn($q) => $q->where('status', 'active'))
            ->orderBy('name')
            ->get();

        // Resolve PGM matched values → actual Category models
        $resolved = [];
        foreach ($catPGM as $pm) {
            $val = mb_strtolower(trim($pm['matched_value']));
            foreach ($categories as $cat) {
                if (isset($resolved[$cat->id])) continue;
                $catLow = mb_strtolower($cat->name);
                if ($catLow === $val || str_contains($catLow, $val) || str_contains($val, $catLow)) {
                    $resolved[$cat->id] = [
                        'category' => $cat,
                        'pgm_value' => $pm['matched_value'],
                        'confidence' => $pm['confidence'],
                        'match_type' => $pm['match_type'],
                    ];
                }
            }
        }

        if (empty($resolved)) {
            $this->traceNode($session->id, 'PGMCategoryResolve', 'routing', 'warning',
                ['pgm_values' => array_column($catPGM, 'matched_value')],
                ['resolved_count' => 0, 'fallback' => 'sendCategoryList']);
            return $this->sendCategoryList($session);
        }

        $resolvedArr = array_values($resolved);

        if (count($resolvedArr) === 1) {
            // ── SINGLE CATEGORY → Auto-select ──
            $cat = $resolvedArr[0]['category'];
            $this->traceNode($session->id, 'PGMCategoryAutoSelect', 'routing', 'success',
                ['pgm_input' => $rawMessage, 'pgm_value' => $resolvedArr[0]['pgm_value'],
                 'confidence' => $resolvedArr[0]['confidence'] . '%', 'match_type' => $resolvedArr[0]['match_type']],
                ['category_id' => $cat->id, 'category_name' => $cat->name, 'action' => 'auto_select']);

            $session->setAnswer('category_id', $cat->id);
            $session->setAnswer('category_name', $cat->name);
            $session->conversation_state = 'awaiting_product';
            $session->catalogue_sent = true;
            $this->advanceChatflow($session, $steps);
            $session->save();

            Log::info("AIChatbot: PGM category auto-select", ['session' => $session->id, 'category' => $cat->name]);

            $msg = "✅ *{$cat->name}* selected!\n\n";
            $msg .= $this->sendCatalogue($session);
            return $msg;
        }

        // ── MULTIPLE CATEGORIES → Process first, queue rest ──
        $first = $resolvedArr[0]['category'];
        $queuedNames = [];

        for ($i = 1; $i < count($resolvedArr); $i++) {
            $qCat = $resolvedArr[$i]['category'];
            AiProductSession::create([
                'session_id' => $session->id,
                'product_ids' => [],
                'status' => 'queued',
                'metadata' => [
                    'type' => 'category_queue',
                    'category_id' => $qCat->id,
                    'category_name' => $qCat->name,
                ],
            ]);
            $queuedNames[] = $qCat->name;
        }

        $this->traceNode($session->id, 'PGMCategoryMultiMatch', 'routing', 'success',
            ['pgm_input' => $rawMessage, 'total_matched' => count($resolvedArr),
             'all_categories' => array_map(fn($r) => $r['category']->name, $resolvedArr)],
            ['processing_first' => $first->name, 'queued' => $queuedNames,
             'sessions_created' => count($queuedNames), 'action' => 'multi_queue']);

        $session->setAnswer('category_id', $first->id);
        $session->setAnswer('category_name', $first->name);
        $session->conversation_state = 'awaiting_product';
        $session->catalogue_sent = true;
        $this->advanceChatflow($session, $steps);
        $session->save();

        Log::info("AIChatbot: PGM multi-category", ['session' => $session->id, 'first' => $first->name, 'queued' => $queuedNames]);

        $msg = "✅ *{$first->name}* selected!";
        if (!empty($queuedNames)) {
            $msg .= "\n📋 _" . implode(', ', $queuedNames) . " ke liye baad me puchenge._";
        }
        $msg .= "\n\n";
        $msg .= $this->sendCatalogue($session);
        return $msg;
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

        // 2. Try partial/substring match — collect ALL matches to detect ambiguity
        if (!$selectedCategory) {
            $phpMatches = [];
            foreach ($categories as $cat) {
                $cNameLower = strtolower($cat->name);
                $matched = false;
                
                // Bidirectional substring match
                if (str_contains($msg, $cNameLower) || (strlen($msg) >= 3 && str_contains($cNameLower, $msg))) {
                    $matched = true;
                }
                
                // Word-by-word exact match
                if (!$matched) {
                    $userWords = preg_split('/[\s,]+/', $msg);
                    foreach ($userWords as $word) {
                        if (strlen($word) >= 3 && str_contains($cNameLower, $word)) {
                            $matched = true;
                            break;
                        }
                    }
                }

                // Fuzzy word match (handles typos like 'cabinat' → 'cabinet')
                if (!$matched) {
                    $userWords = preg_split('/[\s,]+/', $msg);
                    $catWords = preg_split('/[\s,]+/', $cNameLower);
                    foreach ($userWords as $uWord) {
                        if (strlen($uWord) < 3) continue;
                        foreach ($catWords as $cWord) {
                            if (strlen($cWord) < 3) continue;
                            $maxDist = strlen($uWord) <= 4 ? 1 : 2;
                            if (levenshtein($uWord, $cWord) <= $maxDist) {
                                $matched = true;
                                break 2;
                            }
                        }
                    }
                }
                
                if ($matched) {
                    $phpMatches[] = $cat;
                }
            }
            
            // If exactly 1 match → use it. If multiple → let AI disambiguate.
            if (count($phpMatches) === 1) {
                $selectedCategory = $phpMatches[0];
            } elseif (count($phpMatches) > 1) {
                $this->traceNode($session->id, 'CategoryPHPMultiMatch', 'routing', 'info',
                    ['message' => $rawMessage, 'match_count' => count($phpMatches)],
                    ['matched_names' => collect($phpMatches)->pluck('name')->toArray()],
                    'Multiple PHP matches found, forwarding to AI for disambiguation.');
                // Don't set $selectedCategory — let AI contextual matcher handle it
            }
        }

        // ── AI Contextual Fallback if PHP matching fails ──
        if (!$selectedCategory) {
            $aiResponse = $this->matchContextuallyUsingAI($session, $rawMessage, $catList, $categoryLabel, $categories->count());
            
            // Handle QUEUE_MATCHES (user asked for multiple categories) — pick the first one
            if (preg_match('/QUEUE_MATCHES:\s*([\d,\s]+)/i', $aiResponse, $matches)) {
                $ids = array_filter(array_map('trim', explode(',', $matches[1])));
                if (!empty($ids)) {
                    $matchedId = $ids[0]; // Pick first category
                    $selectedCategory = $categories->firstWhere('id', (int)$matchedId);
                    if (!$selectedCategory && (int)$matchedId <= $categories->count()) {
                        $selectedCategory = $categories->values()[(int)$matchedId - 1] ?? null;
                    }
                }
            } elseif (preg_match('/MATCH_ID:\s*(\d+)/i', $aiResponse, $matches)) {
                $matchedId = $matches[1] ?? null;
                if ($matchedId) {
                    $selectedCategory = $categories->firstWhere('id', (int)$matchedId);
                    if (!$selectedCategory && (int)$matchedId <= $categories->count()) {
                        $selectedCategory = $categories->values()[(int)$matchedId - 1] ?? null;
                    }
                }
            }
            
            if (!$selectedCategory && strtoupper(trim($aiResponse)) !== 'NONE') {
                $cleanResponse = $this->sanitizeAiResponseForUser($aiResponse);
                if ($cleanResponse) {
                    return $this->translateIfNeeded($session, $cleanResponse);
                }
                // Sanitization emptied the response (was technical gibberish) — fall through to re-send list
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

        // Apply any column filters
        // Identify which prefix 'column_filter_' exists in answers
        foreach ($answers as $key => $val) {
            if (str_starts_with($key, 'column_filter_')) {
                $colId = str_replace('column_filter_', '', $key);
                $query->whereHas('customValues', function($q) use ($colId, $val) {
                    $q->where('column_id', $colId)
                      ->where('value', $val);
                });
            }
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            return "Sorry, we don't have any {$terms['plural_base']} available right now.";
        }

        // AUTO-SELECT: If only 1 product exists in this filtered set, auto-select it
        if ($products->count() === 1) {
            $onlyProduct = $products->first();
            $this->traceNode($session->id, 'AutoSelectSingleProduct', 'routing', 'success',
                ['trigger' => 'single_product_in_category'],
                ['product_id' => $onlyProduct->id, 'product_name' => $this->getProductDisplayName($onlyProduct)]);
            $steps = ChatflowStep::where('company_id', $this->companyId)->orderBy('sort_order')->get();
            return $this->selectProduct($session, $onlyProduct->id, $steps);
        }

        // Get AI-visible columns for price/desc mapping if needed
        $visibleColumns = $this->getAiVisibleColumns();
        $showPrice = $visibleColumns->contains('slug', 'sale_price') || $visibleColumns->contains('slug', 'mrp');

        // ── Send category image BEFORE product listing ──
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

        // Standard flat listing logic
        // Check for unique column step question text as AI reference
        $uniqueStep = ChatflowStep::where('company_id', $this->companyId)
            ->whereIn('step_type', ['ask_product', 'ask_unique_column'])
            ->orderBy('sort_order')
            ->first();

        $msg = "🛍️ *Our {$terms['plural_base']}:*\n\n";

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
            ['trigger' => 'product_intent'],
            ['products_count' => $products->count(), 'product_names' => array_slice($productNames, 0, 10), 'terminology' => $terms]);
        Log::info("AIChatbot: Catalogue sent (PHP Direct)", ['session' => $session->id]);
        return $this->translateIfNeeded($session, $msg);
    }

    /**
     * Handle ask_column step (e.g., Ask Material, Ask Brand)
     */
    private function handleColumnFilterStep(AiChatSession $session, $currentStep, string $rawMessage, $steps): string
    {
        $answers = $session->collected_answers ?? [];
        $colId = $currentStep->linked_column_id;
        
        if (!$colId) {
            $this->advanceChatflow($session, $steps); // Skip if invalid setup
            return $this->translateIfNeeded($session, $this->buildNextStepPrompt($session, $steps) ?: "Continuing...");
        }

        $column = $currentStep->linkedColumn;
        if (!$column) {
            $this->advanceChatflow($session, $steps);
            return $this->translateIfNeeded($session, $this->buildNextStepPrompt($session, $steps) ?: "Continuing...");
        }

        // 1. Get filtered products for context
        $query = Product::where('company_id', $this->companyId)->where('status', 'active');
        if (isset($answers['category_id'])) {
            $query->where('category_id', $answers['category_id']);
        }
        
        // Apply existing filters before this one
        foreach ($answers as $key => $val) {
            if (str_starts_with($key, 'column_filter_') && $key !== "column_filter_{$colId}") {
                $extColId = str_replace('column_filter_', '', $key);
                $query->whereHas('customValues', function($q) use ($extColId, $val) {
                    $q->where('column_id', $extColId)->where('value', $val);
                });
            }
        }
        
        $productSet = $query->with('customValues')->get();

        // 2. Extract distinct values for this column from the active product set
        $availableValues = [];
        foreach ($productSet as $p) {
            $val = $p->customValues->firstWhere('column_id', $colId)?->value;
            if (!empty($val)) {
                $availableValues[$val] = true; // Use array keys for uniqueness
            }
        }
        
        $valuesList = array_keys($availableValues);
        sort($valuesList); // Alphabetical sort for now

        if (empty($valuesList)) {
            // Nothing to choose, advance flow
            $this->advanceChatflow($session, $steps);
            $session->save();
            return $this->translateIfNeeded($session, $this->buildNextStepPrompt($session, $steps) ?: "Continuing...");
        }

        // If bot just asked the question, or user answered out of context
        $msg = strtolower(trim($rawMessage));
        $selectedValue = null;

        // Try strict number extraction
        if (preg_match('/^\s*(?:#|no\.?\s*|number\s*)?\s*(\d+)\s*$/i', $msg, $numMatch)) {
            $num = (int)$numMatch[1];
            if ($num >= 1 && $num <= count($valuesList)) {
                $selectedValue = $valuesList[$num - 1];
            }
        }

        // If not number, check for affirmative response on single option
        if (!$selectedValue && count($valuesList) === 1 && $this->isAffirmativeResponse($rawMessage)) {
            $selectedValue = $valuesList[0];
        }

        // If not affirmative, try substring match via PHP
        if (!$selectedValue) {
            $matches = [];
            foreach ($valuesList as $val) {
                if (str_contains(strtolower($val), $msg) || str_contains($msg, strtolower($val))) {
                    $matches[] = $val;
                }
            }
            if (count($matches) === 1) {
                $selectedValue = $matches[0];
            }
        }

        // Fallback to AI matching
        if (!$selectedValue) {
            $listStr = collect($valuesList)->map(fn($v, $i) => ($i + 1) . ". {$v}")->implode("\n");
            $aiResponse = $this->matchContextuallyUsingAI($session, $rawMessage, $listStr, $column->name, count($valuesList));
            
            if (preg_match('/MATCH_ID:\s*(\d+)/i', $aiResponse, $aiMatch)) {
                $num = (int)$aiMatch[1];
                if ($num >= 1 && $num <= count($valuesList)) {
                    $selectedValue = $valuesList[$num - 1];
                }
            }
        }

        if ($selectedValue) {
            $this->traceNode($session->id, 'ColumnFilterSelected', 'routing', 'success', ['column' => $column->name, 'message' => $rawMessage], ['selected' => $selectedValue]);
            $session->setAnswer("column_filter_{$colId}", $selectedValue);
            // $session->setAnswer("{$column->slug}", $selectedValue); // optional if you want natural keys too
            $session->save();
            
            // Advance chatflow
            $this->advanceChatflow($session, $steps);
            $session->save();
            
            $msg = "✅ *{$selectedValue}* selected!";
            $nextPrompt = $this->buildNextStepPrompt($session, $steps);
            if ($nextPrompt) {
                $msg .= "\n\n" . $nextPrompt;
            }
            return $this->translateIfNeeded($session, $msg);
        }

        // If we get here, user gave an invalid selection. Ask them nicely.
        // Or if this is the first time the step is being executed (from advanceChatflow)
        $qText = $currentStep->question_text ?: "Please select {$column->name}:";
        
        $output = "{$qText}\n\n";
        foreach ($valuesList as $i => $val) {
            $output .= ($i + 1) . "️⃣ *{$val}*\n";
        }
        $output .= "\nReply with number or name! 👆";
        
        return $this->translateIfNeeded($session, $output);
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
            // Then try bidirectional substring/partial match — collect ALL matches
            if (!$selectedProduct) {
                $phpProductMatches = [];
                foreach ($products as $product) {
                    $displayName = strtolower($this->getProductDisplayName($product));
                    $productName = strtolower($product->name);
                    
                    // Flatten all custom values (attributes) for robust matching
                    $attributes = $product->customValues->map(function($cv) {
                        return strtolower(is_string($cv->value) ? $cv->value : json_encode($cv->value));
                    })->implode(' ');

                    $matched = false;
                    // User message contains product name, or product name contains user message
                    if (str_contains($msg, $displayName) || str_contains($msg, $productName) || (strlen($msg) >= 3 && str_contains($attributes, $msg))
                        || (strlen($msg) >= 3 && (str_contains($displayName, $msg) || str_contains($productName, $msg)))) {
                        $matched = true;
                    }
                    // Check individual words from user message against product name and attributes
                    if (!$matched) {
                        $userWords = preg_split('/[\s,]+/', $msg);
                        foreach ($userWords as $word) {
                            if (strlen($word) >= 3 && (str_contains($displayName, $word) || str_contains($productName, $word) || str_contains($attributes, $word))) {
                                $matched = true;
                                break;
                            }
                        }
                    }
                    // Fuzzy word match for typos
                    if (!$matched) {
                        $userWords = preg_split('/[\s,]+/', $msg);
                        $nameWords = preg_split('/[\s,]+/', $displayName . ' ' . $productName);
                        foreach ($userWords as $uWord) {
                            if (strlen($uWord) < 3) continue;
                            foreach ($nameWords as $nWord) {
                                if (strlen($nWord) < 3) continue;
                                $maxDist = strlen($uWord) <= 4 ? 1 : 2;
                                if (levenshtein($uWord, $nWord) <= $maxDist) {
                                    $matched = true;
                                    break 2;
                                }
                            }
                        }
                    }
                    
                    if ($matched) {
                        $phpProductMatches[] = $product;
                    }
                }
                
                if (count($phpProductMatches) === 1) {
                    $selectedProduct = $phpProductMatches[0];
                } elseif (count($phpProductMatches) > 1) {
                    $this->traceNode($session->id, 'ProductPHPMultiMatch', 'routing', 'info',
                        ['message' => $rawMessage, 'match_count' => count($phpProductMatches)],
                        ['matched_names' => collect($phpProductMatches)->map(fn($p) => $this->getProductDisplayName($p))->toArray()],
                        'Multiple PHP matches found, forwarding to AI for disambiguation.');
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
        $queuePrefixMessage = '';
        if (!$selectedProduct) {
            $productList = $products->map(function($p, $i) {
                $line = ($i + 1) . ". " . $this->getProductDisplayName($p) . " (ID:{$p->id})";
                $colSummary = $this->getProductColumnSummary($p);
                if ($colSummary) {
                    $line .= " | " . $colSummary;
                }
                return $line;
            })->implode("\n");
            $aiResponse = $this->matchContextuallyUsingAI($session, $rawMessage, $productList, 'Product/Item', $products->count());
            
            if (preg_match('/QUEUE_MATCHES:\s*([\d,\s]+)/i', $aiResponse, $matches)) {
                $ids = array_filter(array_map('trim', explode(',', $matches[1])));
                if (!empty($ids)) {
                    $matchedId = $ids[0];
                    $selectedProduct = $products->firstWhere('id', (int)$matchedId);
                    if (!$selectedProduct && (int)$matchedId <= $products->count()) {
                        $selectedProduct = $products->values()[(int)$matchedId - 1] ?? null;
                    }
                    
                    if ($selectedProduct && count($ids) > 1) {
                        $pendingQueue = [];
                        for ($i = 1; $i < count($ids); $i++) {
                            $qId = $ids[$i];
                            $qProd = $products->firstWhere('id', (int)$qId);
                            if (!$qProd && (int)$qId <= $products->count()) {
                                $qProd = $products->values()[(int)$qId - 1] ?? null;
                            }
                            if ($qProd) {
                                $pendingQueue[] = $qProd->id;
                            }
                        }
                        if (!empty($pendingQueue)) {
                            // Fetch existing queue if they somehow add to it? No, just overwrite to start fresh
                            $session->setAnswer('pending_product_queue', $pendingQueue);
                            $session->save();
                            $queuePrefixMessage = "Behtareen! Main aapke sabhi items order mein add kar dunga. Chaliye abhi ke liye iski details fill karna shuru karte hain...";
                        }
                    }
                }
            } elseif (preg_match('/MATCH_ID:\s*(\d+)/i', $aiResponse, $matches)) {
                $matchedId = $matches[1] ?? null;
                if ($matchedId) {
                    $selectedProduct = $products->firstWhere('id', (int)$matchedId);
                    if (!$selectedProduct && (int)$matchedId <= $products->count()) {
                        $selectedProduct = $products->values()[(int)$matchedId - 1] ?? null;
                    }
                }
            }
            
            if (!$selectedProduct && strtoupper(trim($aiResponse)) !== 'NONE') {
                $cleanResponse = $this->sanitizeAiResponseForUser($aiResponse);
                if ($cleanResponse) {
                    return $this->translateIfNeeded($session, $cleanResponse);
                }
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

        return $this->selectProduct($session, $selectedProduct->id, $steps, $queuePrefixMessage);
    }

    /**
     * Contextual AI Default Matcher for Categories, Groups, and Products
     * Handles ambiguity dynamically by returning clarification questions.
     * All examples are generated from actual option names — zero hardcoding.
     */
    private function matchContextuallyUsingAI(AiChatSession $session, string $rawMessage, string $optionsList, string $entityType, int $optionsCount): string
    {
        // ── Build dynamic examples from actual options ──
        $optionLines = array_filter(explode("\n", $optionsList));
        $optionNames = [];
        $optionIds = [];
        foreach ($optionLines as $line) {
            if (preg_match('/^\d+\.\s*(.+?)\s*\(ID:(\d+)\)/', $line, $m)) {
                $optionNames[] = preg_replace('/\s*\(.*$/', '', trim($m[1])); // Clean name without parenthetical
                $optionIds[] = $m[2];
            }
        }

        // Pick first 2 option names for dynamic examples (or fallback to generic)
        $ex1Name = $optionNames[0] ?? 'Option A';
        $ex2Name = $optionNames[1] ?? 'Option B';
        $ex1Id = $optionIds[0] ?? '1';
        $ex2Id = $optionIds[1] ?? '2';
        // Extract short keyword from name (first word) for realistic user example
        $ex1Short = explode(' ', $ex1Name)[0];
        $ex2Short = explode(' ', $ex2Name)[0];

        $prompt  = "You are a strict product-matching engine. Your job is to map the user's message to option IDs.\n\n";
        $prompt .= "AVAILABLE OPTIONS:\n{$optionsList}\n\n";
        $prompt .= "USER MESSAGE: \"{$rawMessage}\"\n\n";
        $prompt .= "═══ RULES (follow in order, stop at first match) ═══\n\n";

        // Rule 1: Single match
        $prompt .= "RULE 1 — SINGLE MATCH:\n";
        $prompt .= "If the user clearly wants EXACTLY ONE option, output:\n";
        $prompt .= "MATCH_ID: <ID>\n";
        $prompt .= "Example: User says \"{$ex1Short}\" → MATCH_ID: {$ex1Id}\n\n";

        // Rule 2: Multiple matches — ALWAYS use QUEUE_MATCHES for deterministic output
        $prompt .= "RULE 2 — MULTIPLE MATCHES:\n";
        $prompt .= "If the user names MULTIPLE DISTINCT options (e.g. \"{$ex1Short} and {$ex2Short}\", \"1 and 2\", \"dono chahiye\"), output:\n";
        $prompt .= "QUEUE_MATCHES: <ID1>,<ID2>\n";
        $prompt .= "Example: User says \"{$ex1Short} and {$ex2Short}\" → QUEUE_MATCHES: {$ex1Id},{$ex2Id}\n";
        $prompt .= "STRICT: Only include IDs the user EXPLICITLY named. Do NOT add extra options the user did not ask for.\n\n";

        // Rule 3: Ambiguous
        $prompt .= "RULE 3 — AMBIGUOUS:\n";
        $prompt .= "If the user's message partially matches multiple options and you cannot determine which one, ask a SHORT clarifying question in Hindi/Hinglish listing only the relevant options.\n\n";

        // Rule 4: No match
        $prompt .= "RULE 4 — NO MATCH:\n";
        $prompt .= "If the message has NO relation to any option, output exactly: NONE\n\n";

        // Output format enforcement
        $prompt .= "═══ OUTPUT FORMAT ═══\n";
        $prompt .= "- For Rule 1: MATCH_ID: <number>\n";
        $prompt .= "- For Rule 2: QUEUE_MATCHES: <number>,<number>\n";
        $prompt .= "- For Rule 3: Plain Hindi/Hinglish text (NO markdown, NO asterisks, NO JSON)\n";
        $prompt .= "- For Rule 4: NONE\n";
        $prompt .= "Output ONLY one of the above. Nothing else.\n";

        $t = microtime(true);
        $matchResult = $this->vertexAI->classifyContent($prompt);
        $ms = (int)((microtime(true) - $t) * 1000);
        $this->logTokens($session, 1, $matchResult);

        $matchText = trim($matchResult['text']);
        $this->traceNode($session->id, 'ContextualMatchAI', 'ai_call', 'success',
            ['message' => $rawMessage, 'entity' => $entityType, 'options_available' => $optionsCount, 'prompt_sent' => $prompt],
            ['raw_response' => $matchText, 'tokens_used' => $matchResult['total_tokens'] ?? 0, 'model' => 'gemini-2.0-flash'], null, $ms);

        return $matchText;
    }

    /**
     * Sanitize AI matcher response before sending to WhatsApp user.
     * Removes all technical artifacts (MATCH_ID, MATCH, ID:, markdown, JSON)
     * and returns clean conversational text, or null if nothing usable remains.
     */
    private function sanitizeAiResponseForUser(string $aiResponse): ?string
    {
        $clean = $aiResponse;

        // Remove any line containing MATCH keyword (MATCH_ID, MATCH_, MATCH etc.)
        $clean = preg_replace('/.*\bMATCH\b.*/i', '', $clean);

        // Remove ID: references (e.g. "ID:15", "ID: 3")
        $clean = preg_replace('/\bID:\s*\d+/i', '', $clean);

        // Remove NONE keyword if it snuck in partially
        $clean = preg_replace('/\bNONE\b/i', '', $clean);

        // Remove markdown/JSON formatting symbols: { } [ ] * _ ` # :
        $clean = preg_replace('/[{}\[\]*_`#]/', '', $clean);

        // Remove excessive colons at start of lines
        $clean = preg_replace('/^\s*:\s*/m', '', $clean);

        // Collapse multiple blank lines into one
        $clean = preg_replace('/\n{2,}/', "\n", $clean);
        $clean = trim($clean);

        // Only return if we have a meaningful conversational message (>= 15 chars)
        if (empty($clean) || mb_strlen($clean) < 15) {
            return null;
        }

        return $clean;
    }

    /**
     * Update the QuoteItem description dynamically using collected answers
     */
    private function updateQuoteDescription(AiChatSession $session): void
    {
        if (!$session->quote_id || !$session->getAnswer('product_id')) {
            return;
        }

        $productId = $session->getAnswer('product_id');
        $product = Product::with('combos.column')->find($productId);
        if (!$product) return;

        // Pass true to onlyShowAnsweredCombos to exclude unopened combo questions
        $newDesc = $product->getDynamicDescription($session->collected_answers ?? [], true);
        
        // Add any non-product custom chatflow answers
        $descLines = [];
        if ($newDesc) $descLines[] = $newDesc;
        
        foreach ($session->collected_answers as $key => $val) {
            if (str_starts_with($key, 'column_filter_')) continue;

            if (!in_array($key, ['product_id', 'product_name', 'category_id', 'category_name', 'selected_product_group']) && !$product->combos->pluck('column.slug')->contains($key)) {
                $descLines[] = ucfirst(str_replace('_', ' ', $key)) . ": {$val}";
            }
        }
        
        $fullDesc = implode("\n", $descLines);

        // Update QuoteItem description
        $quoteItem = QuoteItem::where('quote_id', $session->quote_id)
            ->where('product_id', $productId)
            ->first();
        if ($quoteItem) {
            $quoteItem->update(['description' => $fullDesc]);
        }

        // ALSO update Lead→Product pivot description so it shows in Lead edit modal
        if ($session->lead_id) {
            $lead = Lead::find($session->lead_id);
            if ($lead && $lead->products()->where('product_id', $productId)->exists()) {
                $lead->products()->updateExistingPivot($productId, [
                    'description' => $fullDesc,
                ]);
            }
        }

        $this->traceNode($session->id, 'DescriptionSynced', 'database', 'success',
            ['product_id' => $productId],
            ['quote_updated' => (bool)$quoteItem, 'lead_updated' => (bool)($session->lead_id), 'desc_preview' => mb_substr($fullDesc, 0, 100)]);
    }

    /**
     * Select product — create Lead, Quote, send details (PHP built)
     */
    private function selectProduct(AiChatSession $session, int $productId, $steps, string $prefixMsg = ''): string
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

        // Create or update Quote
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
            
            $quoteSeqParts = explode('-', $quote->quote_no);
            $quoteSequence = (int) end($quoteSeqParts);

            $this->traceNode($session->id, 'QuoteCreated', 'database', 'success',
                ['lead_id' => $session->lead_id, 'product_id' => $productId, 'product_name' => $displayName],
                ['database_id' => $quote->id, 'quote_sequence_id' => $quoteSequence, 'quote_no' => $quote->quote_no, 'grand_total' => '₹' . number_format($quote->grand_total / 100, 2)]);
        } else {
            // Add new item to existing quote
            $quote = Quote::find($session->quote_id);
            if ($quote) {
                $maxSortOrder = QuoteItem::where('quote_id', $quote->id)->max('sort_order') ?? 0;
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
                    'sort_order' => $maxSortOrder + 1,
                ]);

                // Recalculate quote totals
                $allItems = QuoteItem::where('quote_id', $quote->id)->get();
                $subtotal = $allItems->sum(fn($item) => $item->rate * $item->qty);
                $gstTotal = $allItems->sum(fn($item) => ($item->rate * $item->qty * ($item->gst_percent ?? 0)) / 100);
                $quote->update([
                    'subtotal' => $subtotal,
                    'gst_total' => $gstTotal,
                    'grand_total' => $subtotal + $gstTotal,
                ]);

                $this->traceNode($session->id, 'QuoteItemAdded', 'database', 'success',
                    ['product_id' => $productId, 'product_name' => $displayName],
                    ['quote_id' => $quote->id, 'item_count' => $allItems->count(), 'new_grand_total' => '₹' . number_format(($subtotal + $gstTotal) / 100, 2)]);
            }
        }

        // Send unique model image if available
        $this->sendProductMedia($session, $product);

        // Simple confirmation — NO detail dump
        // The chatflow steps will ask questions one-by-one
        $msg = $prefixMsg ? $prefixMsg . "\n\n✅ *{$displayName}* selected! 🛍️" : "✅ *{$displayName}* selected! 🛍️";

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

        // If there is only 1 option available, accept positive confirmation (yes, ok, etc.)
        if (count($comboValues) === 1 && $this->isAffirmativeResponse($rawMessage)) {
            $matchedOption = $comboValues[0];
        }

        if (!$matchedOption) {
            foreach ($comboValues as $opt) {
                if (trim(strtolower($rawMessage)) === strtolower(trim($opt))) {
                    $matchedOption = $opt;
                    break;
                }
            }
        }

        // Tier 1b (Relaxed PHP Check): Handle partial numeric matches like "166" → "166mm"
        if (!$matchedOption) {
            $normalizedMessage = preg_replace('/[^a-z0-9]/', '', strtolower($rawMessage));
            foreach ($comboValues as $opt) {
                $normalizedOpt = preg_replace('/[^a-z0-9]/', '', strtolower($opt));
                if (empty($normalizedOpt)) continue;
                // Exact normalized match
                if ($normalizedMessage === $normalizedOpt) {
                    $matchedOption = $opt;
                    break;
                }
                // Partial: option starts with user input (e.g. user "166" → option "166mm")
                if (strlen($normalizedMessage) >= 2 && str_starts_with($normalizedOpt, $normalizedMessage)) {
                    $matchedOption = $opt;
                    break;
                }
                // Partial: user input starts with option (e.g. user "166mm black" → option "166mm")
                if (strlen($normalizedOpt) >= 2 && str_starts_with($normalizedMessage, $normalizedOpt)) {
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

        // Append next step
        $nextPrompt = $this->buildNextStepPrompt($session, $steps);
        if ($nextPrompt) {
            // Progress summary is removed so it doesn't interrupt flow before send_summary
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

    private function handleSummaryStep(AiChatSession $session, string $prefix = ''): string
    {
        $answers = $session->collected_answers ?? [];
        $visibleColumns = $this->getAiVisibleColumns();

        $msg = $prefix . "📋 *Order Summary:*\n\n";

        // Show each AI-visible catalogue column with its value from the selected product
        $productId = $answers['product_id'] ?? null;
        $product = $productId ? Product::with(['combos.column', 'customValues', 'category'])->find($productId) : null;

        // Internal keys that should NEVER show in customer-facing summary
        $internalKeys = ['product_id', 'product_name', 'category_id', 'category_name', 'selected_product_group', 'product_price'];

        if ($product && $visibleColumns->isNotEmpty()) {
            foreach ($visibleColumns as $col) {
                // Skip combo columns — they are shown separately below
                if ($product->combos->pluck('column_id')->contains($col->id)) {
                    continue;
                }

                $value = null;

                // System columns
                if ($col->is_system) {
                    $slug = $col->slug;
                    if ($slug === 'product_name') $slug = 'name';
                    $value = $product->{$slug} ?? null;
                } elseif ($col->is_category) {
                    // Category column → show category name
                    $cat = $product->category;
                    $value = $cat ? $cat->name : null;
                } else {
                    // Custom column
                    $customVal = $product->customValues->where('column_id', $col->id)->first();
                    if ($customVal && !empty($customVal->value)) {
                        $decoded = json_decode($customVal->value, true);
                        $value = is_array($decoded) ? implode(', ', $decoded) : $customVal->value;
                    }
                }

                if (!empty($value)) {
                    $msg .= "{$col->name}: *{$value}*\n";
                }
            }
        } else {
            // Fallback: just show product_name if no visible columns configured
            $msg .= "Product: *{$answers['product_name']}*\n";
        }

        // Combo selections (Finish, Size, etc.)
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

        // Custom chatflow answers (ask_custom/ask_optional fields only)
        $comboSlugs = $product ? $product->combos->pluck('column.slug')->filter()->toArray() : [];
        $visibleSlugs = $visibleColumns->pluck('slug')->toArray();
        foreach ($answers as $key => $val) {
            if (str_starts_with($key, 'column_filter_')) continue;
            // Skip internal keys, combo keys, and catalogue column keys
            if (in_array($key, $internalKeys) || in_array($key, $comboSlugs) || in_array($key, $visibleSlugs)) {
                continue;
            }
            $msg .= ucfirst(str_replace('_', ' ', $key)) . ": {$val}\n";
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
            $productName = $session->getAnswer('product_name') ?? 'your selected product';
            
            // Move Lead to Target Stage
            $targetStage = Setting::getValue('ai_bot', 'target_stage', '', $this->companyId);
            if (!empty($targetStage) && $session->lead_id) {
                $lead = \App\Models\Lead::find($session->lead_id);
                if ($lead) {
                    $lead->update(['stage' => $targetStage]);
                }
            }
            
            $this->traceNode($session->id, 'OrderConfirmed', 'application', 'success', 
                ['message' => $rawMessage, 'product' => $productName], 
                ['status' => 'confirmed', 'new_lead_stage' => $targetStage, 'lead_id' => $session->lead_id, 'quote_id' => $session->quote_id]);

            // Reset chatflow for next product — keep lead_id and quote_id intact
            $answers = $session->collected_answers ?? [];
            $keysToKeep = []; // Lead and quote are on session model, not in answers
            $keysToRemove = ['product_id', 'product_name', 'product_price', 'category_id', 'category_name', 'selected_product_group'];
            
            // Also remove combo answer keys (finish, size, etc.)
            $productId = $answers['product_id'] ?? null;
            if ($productId) {
                $product = Product::with('combos.column')->find($productId);
                if ($product) {
                    foreach ($product->combos as $combo) {
                        if ($combo->column) {
                            $keysToRemove[] = $combo->column->slug;
                        }
                    }
                }
            }
            
            // Clear product-specific answers
            $newAnswers = [];
            foreach ($answers as $key => $val) {
                if (!in_array($key, $keysToRemove)) {
                    $newAnswers[$key] = $val;
                }
            }
            $session->collected_answers = $newAnswers;
            
            // Reset chatflow state — session stays ACTIVE, lead_id and quote_id stay
            $session->current_step_id = null;
            $session->current_step_retries = 0;
            $session->conversation_state = 'new';
            $session->catalogue_sent = false;
            $session->status = 'active';
            $session->media_sent_keys = []; // Allow fresh media for next product
            $session->save();

            $reply = "✅ *{$productName} Confirmed!*\n\n";
            $reply .= "Thank you! 🙏\n\n";
            $reply .= "Would you like to add another product? Just tell me what you're looking for!\n\n";
            $reply .= "Or if you're done, our team will reach out to you for delivery & payment details. 😊";

        } elseif (in_array($msg, ['no', 'n', 'nahi', 'cancel'])) {
            // Check if this is cancelling the LAST product or the entire order
            // If quote has items already confirmed, just remove the current uncommitted product
            $session->update(['conversation_state' => 'new']);
            
            // Reset product-specific answers
            $answers = $session->collected_answers ?? [];
            $keysToRemove = ['product_id', 'product_name', 'product_price', 'category_id', 'category_name', 'selected_product_group'];
            $productId = $answers['product_id'] ?? null;
            if ($productId) {
                $product = Product::with('combos.column')->find($productId);
                if ($product) {
                    foreach ($product->combos as $combo) {
                        if ($combo->column) {
                            $keysToRemove[] = $combo->column->slug;
                        }
                    }
                }
            }
            $newAnswers = [];
            foreach ($answers as $key => $val) {
                if (!in_array($key, $keysToRemove)) {
                    $newAnswers[$key] = $val;
                }
            }
            $session->collected_answers = $newAnswers;
            $session->current_step_id = null;
            $session->current_step_retries = 0;
            $session->catalogue_sent = false;
            $session->media_sent_keys = [];
            $session->save();

            $reply = "No worries! 🙏\n\n";
            $reply .= "Would you like to explore other products? Just tell me what you need!\n\n";
            $reply .= "I'm here to help! 😊";
            $this->traceNode($session->id, 'ProductCancelled', 'application', 'success', ['message' => $rawMessage], ['status' => 'cancelled']);
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

    // ═══════════════════════════════════════════════════════
    // BASE GREETINGS — 200+ multi-language universal set
    // ═══════════════════════════════════════════════════════

    private const BASE_GREETINGS = [
        // English
        'hi', 'hello', 'hey', 'hola', 'howdy', 'sup', 'yo', 'heya', 'hiya',
        'good morning', 'good afternoon', 'good evening', 'good night',
        'gm', 'gn', 'ge', 'ga', 'morning', 'evening',
        'how are you', 'how r u', 'hw r u', 'whats up', 'what\'s up',
        'hows it going', 'how\'s it going', 'greetings',
        // Common typos
        'hii', 'hiii', 'hiiii', 'hiiiii', 'hiiiiii',
        'helo', 'hllo', 'hlw', 'hellow', 'helloo', 'hellloo', 'helllo',
        'heloo', 'helo ji', 'hello ji', 'hi ji', 'hey ji',
        // Hindi / Hinglish
        'namaste', 'namaskar', 'namasté', 'namashkar',
        'kaise ho', 'kya hal hai', 'kya haal hai', 'kya hal he',
        'kaise hain', 'kaise hain aap', 'aap kaise hain',
        'kya haal', 'sab theek', 'sab badhiya', 'kya chal raha',
        'pranam', 'pranaam', 'namskar', 'namshte',
        'shubh prabhat', 'suprabhat', 'shubh sandhya', 'shubh ratri',
        'suprabhaat', 'subh prabhat',
        // Religious
        'jai shri ram', 'jai shree ram', 'ram ram', 'jai siya ram',
        'jai jinendra', 'jai hind', 'jai ho',
        'radhe radhe', 'radha radha', 'hare krishna', 'hare rama',
        'jai mata di', 'jai ambe', 'har har mahadev',
        'om namah shivaya', 'jai ganesh', 'jai hanuman',
        // Punjabi
        'sat sri akal', 'sat shri akal', 'sasriyakal', 'sasriakaal',
        'wahe guru', 'waheguru', 'waheguruji',
        'pairi pauna', 'sat shri akaal',
        // Urdu / Arabic
        'assalamu alaikum', 'salam', 'salaam', 'walaikum assalam',
        'salam alaikum', 'aadab', 'aadaab', 'adab',
        'khuda hafiz', 'allah hafiz',
        // Gujarati
        'kem cho', 'kem chho', 'majama', 'jai shri krishna',
        'jai swaminarayan', 'jay swaminarayan',
        // Rajasthani / Marwadi
        'khamma ghani', 'khamma', 'padharo mhare des',
        'ram ram sa', 'ram ram ji',
        // Tamil
        'vanakkam', 'vanakam', 'vaṇakkam',
        // Telugu
        'namaskaram', 'namaskaaralu', 'baagunnara',
        // Kannada
        'namaskara', 'hegiddira', 'hegiddiri',
        // Malayalam
        'namaskkaram', 'namaskkaaram', 'sughamano',
        // Bengali
        'nomoskar', 'nomoshkar', 'kemon acho', 'ki khobor',
        // Marathi
        'namaskar', 'kasa ahat', 'kase aahat',
        // Odia
        'namaskar', 'kemiti achhi',
        // Assamese
        'namaskar', 'apuni kene', 'bhaal ase ne',
        // Short forms
        'hlo', 'hii there', 'hi there', 'hey there', 'hello there',
        'ji', 'jee', 'bhai', 'bro', 'dost',
        'arey', 'are', 'oye', 'oyee',
        // Emojis and symbols (single emoji greetings)
        '🙏', '👋', '🙏🏻', '👋🏻', 'namaste 🙏', 'hi 👋',
        // Thanks (sometimes used as greeting)
        'dhanyavaad', 'dhanyawad', 'shukriya', 'thanks', 'thank you',
    ];

    /**
     * Load the complete greeting word set (system base + admin custom).
     * Cached per-request for performance.
     */
    private ?array $greetingSetCache = null;

    private function loadGreetingSet(): array
    {
        if ($this->greetingSetCache !== null) {
            return $this->greetingSetCache;
        }

        $base = self::BASE_GREETINGS;

        // Merge admin custom greetings from settings
        $adminGreetings = Setting::getValue('ai_bot', 'greeting_words', '', $this->companyId);
        if (!empty($adminGreetings)) {
            $custom = array_filter(array_map('trim', explode("\n", strtolower($adminGreetings))));
            $base = array_merge($base, $custom);
        }

        $this->greetingSetCache = array_unique($base);
        return $this->greetingSetCache;
    }

    /**
     * Fast PHP-level greeting detection with fuzzy matching.
     * 3-layer check: exact → fuzzy/levenshtein → no match.
     */
    private function isGreeting(string $message): bool
    {
        $msg = strtolower(trim($message));
        if (empty($msg)) return false;

        $greetings = $this->loadGreetingSet();

        // Layer 1: Exact match
        if (in_array($msg, $greetings, true)) return true;

        // Layer 2: Fuzzy match (for typos in greetings > 3 chars)
        if (mb_strlen($msg) >= 3 && mb_strlen($msg) <= 25) {
            foreach ($greetings as $g) {
                if (mb_strlen($g) >= 3) {
                    $maxDist = mb_strlen($msg) <= 4 ? 1 : 2;
                    if (levenshtein($msg, $g) <= $maxDist) return true;
                }
            }
        }

        return false;
    }

    // ═══════════════════════════════════════════════════════
    // PHP PRODUCT GROUP MATCH ENGINE (99.99% Accuracy)
    // ═══════════════════════════════════════════════════════

    /**
     * Build a column-wise index of unique product values.
     * Uses cache to avoid redundant DB queries (auto-cleared on product CRUD).
     */
    private function buildProductGroupIndex(): array
    {
        $cacheKey = "pgm_index_company_{$this->companyId}";

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $index = $this->buildProductGroupIndexFromDB();

        // Cache for 5 minutes
        Cache::put($cacheKey, $index, 300);

        return $index;
    }

    /**
     * Build product group index from database.
     */
    private function buildProductGroupIndexFromDB(): array
    {
        $columns = CatalogueCustomColumn::where('company_id', $this->companyId)
            ->where('is_active', true)
            ->where('show_in_ai', true)
            ->orderBy('sort_order')
            ->get();

        $products = Product::with('customValues', 'category')
            ->where('company_id', $this->companyId)
            ->where('status', 'active')
            ->get();

        $index = [];

        foreach ($columns as $col) {
            $uniqueValues = [];

            foreach ($products as $product) {
                $value = '';

                if ($col->is_category && $product->category) {
                    $value = $product->category->name;
                } elseif ($col->slug === 'product_name' || $col->slug === 'name') {
                    $value = $product->name ?? '';
                } elseif ($col->is_system) {
                    $value = $product->{$col->slug} ?? '';
                } else {
                    $cv = $product->customValues->firstWhere('column_id', $col->id);
                    if ($cv && !empty($cv->value)) {
                        $decoded = json_decode($cv->value, true);
                        $value = is_array($decoded) ? implode(', ', $decoded) : $cv->value;
                    }
                }

                $trimmed = trim($value);
                if (!empty($trimmed)) {
                    $uniqueValues[$trimmed] = true;
                }
            }

            $index[$col->id] = [
                'column_name' => $col->name,
                'column_slug' => $col->slug,
                'is_category' => (bool) $col->is_category,
                'is_unique'   => (bool) $col->is_unique,
                'is_combo'    => (bool) $col->is_combo,
                'unique_values' => array_keys($uniqueValues),
            ];
        }

        return $index;
    }

    /**
     * PHP Product Group Match — match user message against the product index.
     * Returns array of matches sorted by confidence DESC.
     */
    public function phpProductGroupMatch(string $userMessage): array
    {
        $msg = mb_strtolower(trim($userMessage));
        if (empty($msg)) return [];

        $index = $this->buildProductGroupIndex();
        $matches = [];

        foreach ($index as $colId => $colData) {
            foreach ($colData['unique_values'] as $value) {
                $valueLower = mb_strtolower(trim($value));
                $matchResult = $this->advancedMatch($msg, $valueLower);

                if ($matchResult['matched'] && $matchResult['confidence'] >= $this->matchMinConfidence) {
                    $matches[] = [
                        'column_id'     => $colId,
                        'column_name'   => $colData['column_name'],
                        'column_slug'   => $colData['column_slug'],
                        'is_category'   => $colData['is_category'],
                        'is_unique'     => $colData['is_unique'],
                        'is_combo'      => $colData['is_combo'],
                        'matched_value' => $value,
                        'match_type'    => $matchResult['type'],
                        'confidence'    => $matchResult['confidence'],
                    ];
                }
            }
        }

        // Sort by confidence DESC
        usort($matches, fn($a, $b) => $b['confidence'] <=> $a['confidence']);

        return $matches;
    }

    /**
     * 5-Level Advanced Matching Chain.
     * Returns: ['matched' => bool, 'type' => string, 'confidence' => int]
     */
    private function advancedMatch(string $userMsg, string $dbValue): array
    {
        // ═══ LEVEL 1: Exact Match (100% confidence) ═══
        if ($userMsg === $dbValue) {
            return ['matched' => true, 'type' => 'exact', 'confidence' => 100];
        }

        // ═══ LEVEL 2: Contains Match (90-95% confidence) ═══
        if (mb_strlen($dbValue) >= 3 && str_contains($userMsg, $dbValue)) {
            return ['matched' => true, 'type' => 'contains_in_msg', 'confidence' => 95];
        }
        if (mb_strlen($userMsg) >= 3 && str_contains($dbValue, $userMsg)) {
            return ['matched' => true, 'type' => 'msg_in_value', 'confidence' => 90];
        }

        // ═══ LEVEL 2.5: Space-stripped match (92% confidence) ═══
        $msgNoSpace = str_replace(' ', '', $userMsg);
        $valNoSpace = str_replace(' ', '', $dbValue);
        if (mb_strlen($valNoSpace) >= 4 && (str_contains($msgNoSpace, $valNoSpace) || str_contains($valNoSpace, $msgNoSpace))) {
            return ['matched' => true, 'type' => 'space_stripped', 'confidence' => 92];
        }

        // ═══ LEVEL 3: Word-by-Word Match (85% confidence) ═══
        $userWords = $this->pgmTokenize($userMsg);
        $dbWords = $this->pgmTokenize($dbValue);

        foreach ($userWords as $uw) {
            if (mb_strlen($uw) < 3) continue;
            foreach ($dbWords as $dw) {
                if (mb_strlen($dw) < 3) continue;
                if ($uw === $dw) {
                    return ['matched' => true, 'type' => 'word_exact', 'confidence' => 85];
                }
            }
        }

        // ═══ LEVEL 4: Fuzzy/Levenshtein Match (75% confidence) ═══
        foreach ($userWords as $uw) {
            if (mb_strlen($uw) < 3) continue;
            foreach ($dbWords as $dw) {
                if (mb_strlen($dw) < 3) continue;
                $maxDist = mb_strlen($uw) <= 4 ? 1 : 2;
                if (levenshtein($uw, $dw) <= $maxDist) {
                    return ['matched' => true, 'type' => 'fuzzy_levenshtein', 'confidence' => 75];
                }
            }
        }

        // ═══ LEVEL 5: Phonetic + N-Gram Match (60-65% confidence) ═══
        foreach ($userWords as $uw) {
            if (mb_strlen($uw) < 3) continue;
            foreach ($dbWords as $dw) {
                if (mb_strlen($dw) < 3) continue;

                // Soundex/Metaphone matching
                if (soundex($uw) === soundex($dw) || metaphone($uw) === metaphone($dw)) {
                    return ['matched' => true, 'type' => 'phonetic', 'confidence' => 65];
                }

                // N-gram (bigram overlap ratio)
                $overlap = $this->bigramSimilarity($uw, $dw);
                if ($overlap >= 0.6) {
                    return ['matched' => true, 'type' => 'ngram', 'confidence' => (int) (60 + ($overlap * 10))];
                }
            }
        }

        return ['matched' => false, 'type' => 'none', 'confidence' => 0];
    }

    /**
     * Tokenize text into words for matching.
     */
    private function pgmTokenize(string $text): array
    {
        return array_filter(
            preg_split('/[\s,;|\/\-_]+/u', $text),
            fn($w) => mb_strlen($w) > 0
        );
    }

    /**
     * Calculate bigram (2-char overlap) similarity between two strings.
     */
    private function bigramSimilarity(string $a, string $b): float
    {
        $aBigrams = [];
        $bBigrams = [];
        for ($i = 0; $i < mb_strlen($a) - 1; $i++) {
            $aBigrams[] = mb_substr($a, $i, 2);
        }
        for ($i = 0; $i < mb_strlen($b) - 1; $i++) {
            $bBigrams[] = mb_substr($b, $i, 2);
        }
        if (empty($aBigrams) || empty($bBigrams)) return 0;

        $intersection = count(array_intersect($aBigrams, $bBigrams));
        $union = count(array_unique(array_merge($aBigrams, $bBigrams)));

        return $union > 0 ? $intersection / $union : 0;
    }

    /**
     * Clear the Product Group Match cache for this company.
     * Called on product/category/column CRUD operations.
     */
    public static function clearProductGroupCache(int $companyId): void
    {
        Cache::forget("pgm_index_company_{$companyId}");
    }

    // ═══════════════════════════════════════════════════════
    // TIER 3 — Column Analytics AI (Sales Executive Persona)
    // ═══════════════════════════════════════════════════════

    /**
     * Handle Tier 3: Column Analytics AI response.
     * Triggered when PHP Product Group Match finds column matches
     * but the matched column ≠ current chatflow question column.
     */
    private function handleTier3ColumnAnalytics(AiChatSession $session, string $rawMessage, array $pgMatches, ?string $imageUrl = null): string
    {
        $this->routeTrace[] = 'handleTier3ColumnAnalytics';

        // Get matched column data
        $matchedColNames = array_unique(array_column($pgMatches, 'column_name'));
        $matchedValues = array_unique(array_column($pgMatches, 'matched_value'));

        // Retrieve data about matched columns for AI context
        $columnDataStr = '';
        foreach ($pgMatches as $m) {
            $columnDataStr .= "{$m['column_name']}: {$m['matched_value']} (Confidence: {$m['confidence']}%)\n";
        }

        // Build Tier 3 prompt
        $adminPrompt = $this->tier3Prompt;
        if (empty($adminPrompt)) {
            $adminPrompt = "You are a senior sales executive with deep product knowledge.\n"
                . "A customer asked about something related to our products.\n"
                . "Provide a helpful, conversational response that guides them to the right product.\n"
                . "Keep response concise (2-4 sentences max).\n"
                . "Use the customer's language (Hindi/Hinglish/English).\n"
                . "Mention specific product values from the data below.\n"
                . "Do NOT use markdown formatting.\n"
                . "Do NOT invent data not in MATCHED DATA below.";
        }

        $fullPrompt = $adminPrompt
            . "\n\n## MATCHED PRODUCT DATA:\n" . $columnDataStr
            . "\n\n## LANGUAGE\nReply in the same language the customer is using.";

        $t = microtime(true);
        $result = $this->vertexAI->generateContent(
            $fullPrompt,
            [['role' => 'user', 'text' => $rawMessage]],
            $imageUrl
        );
        $ms = (int)((microtime(true) - $t) * 1000);

        $this->logTokens($session, 3, $result);

        $responseText = trim($result['text'] ?? '');

        $this->traceNode($session->id, 'Tier3ColumnAnalytics', 'ai_call',
            !empty($responseText) ? 'success' : 'error',
            ['message' => $rawMessage, 'matched_columns' => $matchedColNames, 'matched_values' => $matchedValues, 'prompt_type' => 'tier3_analytics'],
            ['response' => mb_substr($responseText, 0, 200), 'tokens_used' => $result['total_tokens'] ?? 0, 'model' => 'gemini-2.0-flash'],
            null, $ms);

        if (empty($responseText)) {
            return $this->handleTier2($session, $rawMessage, $imageUrl);
        }

        return $responseText;
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
            } elseif ($step->step_type === 'ask_column' && $step->linkedColumn) {
                if (isset($answers['column_filter_' . $step->linked_column_id])) {
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
            $this->traceNode($session->id, 'ChatflowAdvanced', 'routing', 'info',
                ['next_step' => $nextStep->name, 'next_step_type' => $nextStep->step_type],
                ['next_step_id' => $nextStep->id, 'answers_count' => count($answers)]);
        } else {
            $session->current_step_id = null;
            $session->conversation_state = 'completed';
            $session->update(['status' => 'completed']);
            $this->traceNode($session->id, 'ChatflowCompleted', 'routing', 'success',
                [],
                ['answers_count' => count($answers), 'final_state' => 'completed']);
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

    private function buildNextStepPrompt(AiChatSession $session, $steps, array $autoMessages = []): string
    {
        $nextStep = $session->current_step_id ? $steps->firstWhere('id', $session->current_step_id) : null;
        if (!$nextStep) {
            // No next step — if chatflow is completed and we have product answers, show summary + confirm
            $prefix = !empty($autoMessages) ? implode("\n", $autoMessages) . "\n\n" : "";
            if ($session->getAnswer('product_id') && $session->conversation_state === 'completed') {
                return $this->handleSummaryStep($session, $prefix);
            }
            return $this->translateIfNeeded($session, $prefix . "✅ All done! Our team will contact you. 🙏");
        }

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
            
            if (empty($comboValues)) {
                $this->advanceChatflow($session, $steps);
                $session->save();
                return $this->buildNextStepPrompt($session, $steps, $autoMessages);
            }
            
            $question = $nextStep->question_text ?: "Which {$nextStep->linkedColumn->name}?";
            $prefix = !empty($autoMessages) ? implode("\n", $autoMessages) . "\n\n" : "";
            return $this->translateIfNeeded($session, $prefix . "{$question} 👇\n" . implode(' | ', $comboValues));
        }

        if ($nextStep->step_type === 'ask_column' && $nextStep->linkedColumn) {
            $answers = $session->collected_answers ?? [];
            $colId = $nextStep->linked_column_id;
            
            $query = Product::where('company_id', $this->companyId)->where('status', 'active');
            if (isset($answers['product_id'])) {
                $query->where('id', $answers['product_id']);
            } else {
                if (isset($answers['category_id'])) {
                    $query->where('category_id', $answers['category_id']);
                }
                foreach ($answers as $key => $val) {
                    if (str_starts_with($key, 'column_filter_') && $key !== "column_filter_{$colId}") {
                        $extColId = str_replace('column_filter_', '', $key);
                        $query->whereHas('customValues', function($q) use ($extColId, $val) {
                            $q->where('column_id', $extColId)->where('value', $val);
                        });
                    }
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
            
            if (count($valuesList) >= 1) {
                 $question = $nextStep->question_text ?: "Please select {$nextStep->linkedColumn->name}:";
                 $optionsStr = implode(' | ', $valuesList);
                 $prefix = !empty($autoMessages) ? implode("\n", $autoMessages) . "\n\n" : "";
                 return $this->translateIfNeeded($session, $prefix . "{$question} 👇\n" . $optionsStr);
            } else {
                 $this->advanceChatflow($session, $steps);
                 $session->save();
                 return $this->buildNextStepPrompt($session, $steps, $autoMessages);
            }
        }

        if ($nextStep->step_type === 'send_summary') {
            $prefix = !empty($autoMessages) ? implode("\n", $autoMessages) . "\n\n" : "";
            
            // Cart Loop Check: Are there pending products to configure?
            $queue = $session->getAnswer('pending_product_queue');
            if (!empty($queue) && is_array($queue)) {
                $nextProductId = array_shift($queue);
                // Resave the remaining queue
                $session->setAnswer('pending_product_queue', array_values($queue));
                
                // Soft reset session: remove product-specific answers to provide clean slate for next item
                $answers = $session->collected_answers ?? [];
                foreach ($answers as $key => $val) {
                    if (str_starts_with($key, 'column_filter_') || $key === 'product_id' || $key === 'product_name' || (!in_array($key, ['category_id', 'selected_product_group', 'pending_product_queue', 'name', 'phone', 'email', 'company_name']))) {
                        unset($answers[$key]);
                    }
                }
                $session->collected_answers = $answers;
                $session->current_step_id = null;
                $session->save();
                
                $loopPrefix = "✅ Picchla item order mein add ho gaya! Ab chaliye aapke agle product ke options dekhte hain...";
                if ($prefix) {
                    $loopPrefix = $prefix . $loopPrefix;
                }
                
                // Jump to the next product in the queue. selectProduct will reset the chatflow and build the next prompt automatically.
                return $this->selectProduct($session, $nextProductId, $steps, $loopPrefix);
            }

            return $this->handleSummaryStep($session, $prefix);
        }

        $prefix = !empty($autoMessages) ? implode("\n", $autoMessages) . "\n\n" : "";
        return $this->translateIfNeeded($session, $prefix . ($nextStep->question_text ?: "Please provide your {$nextStep->field_key}:"));
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
        // ── Resolve relative URLs to full public URLs ──
        // Evolution API needs a fully accessible http(s):// URL to fetch the media.
        // Use APP_URL from .env to build the absolute URL.
        $originalMediaUrl = $mediaUrl; // keep for tracing
        if (!empty($mediaUrl) && !preg_match('#^https?://#i', $mediaUrl)) {
            // Build public URL using APP_URL (e.g., https://bot.rvallsolutions.com)
            $appUrl = rtrim(config('app.url', ''), '/');
            $mediaUrl = $appUrl . '/' . ltrim($mediaUrl, '/');
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
                    ['original_url' => $originalMediaUrl, 'resolved_url' => $mediaUrl, 'media_type' => $mediaType],
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


}
