<?php

namespace App\Services;

use App\Models\AiChatSession;
use App\Models\AiChatMessage;
use App\Models\AiTokenLog;
use App\Models\AiBotTestQuestion;
use App\Models\AiProductSession;
use App\Models\Lead;
use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Support\Facades\Log;

class AiConversationTestService
{
    private int $companyId;
    private int $userId;
    private AIChatbotService $chatbotService;
    private string $simPhone = '919999999991';
    private array $lastBotListItems = [];
    private string $lastBotResponse = '';

    public function __construct(int $companyId, int $userId)
    {
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->chatbotService = new AIChatbotService($companyId, $userId);
    }

    /**
     * Run conversation test with user-defined questions (streamed output).
     * Uses the EXACT same processMessage() as WhatsApp webhook.
     *
     * Dynamic placeholders supported:
     *   {{pick:N}}  — pick N random items from bot's last list
     *   {{pick:1}}  — pick 1 random item
     *   {{first}}   — first item from bot's last list
     *   {{last}}    — last item from bot's last list
     *   {{all}}     — all items combined ("X and Y and Z")
     *   {{yes}}     — sends "yes"
     *   {{no}}      — sends "no"
     *   {{number:N}} — sends the Nth number (e.g. {{number:2}} → "2")
     */
    public function run(callable $log): void
    {
        try {
            $questions = AiBotTestQuestion::where('company_id', $this->companyId)
                ->orderBy('sort_order')
                ->get();

            if ($questions->isEmpty()) {
                $log('error', 'No test questions found! Add questions first, then run.');
                return;
            }

            // Cleanup old test data
            $this->cleanup();

            $log('info', '🧪 Test started — ' . $questions->count() . ' questions');

            $errorCount = 0;

            foreach ($questions as $i => $question) {
                $turnNum = $i + 1;
                $userMsg = trim($question->question);

                // ═══ RESOLVE DYNAMIC PLACEHOLDERS ═══
                $originalMsg = $userMsg;
                $userMsg = $this->resolvePlaceholders($userMsg);

                $log('info', "── Turn {$turnNum}/{$questions->count()} ──");
                if ($originalMsg !== $userMsg) {
                    $log('info', "🔄 {$originalMsg} → {$userMsg}");
                }
                $log('user', $userMsg);

                try {
                    $botResult = $this->chatbotService->processMessage(
                        'conversation_tester_1',
                        $this->simPhone,
                        $userMsg
                    );

                    $botMsg = $botResult['response'] ?? 'No response generated.';
                    $this->lastBotResponse = $botMsg;

                    // Extract list items from bot response for next dynamic question
                    $this->lastBotListItems = $this->extractListItems($botMsg);

                    $log('bot', $botMsg);

                    // Show route trace
                    $routeTrace = $this->chatbotService->getRouteTrace();
                    if (!empty($routeTrace)) {
                        $log('info', '🛤️ Route: ' . implode(' → ', $routeTrace));
                    }

                    // Check for error responses
                    $botErrorPhrases = [
                        'sorry, i could not generate',
                        'sorry, an error occurred',
                        'sorry, i am unable to process',
                        'ai bot is not configured',
                    ];
                    $botLower = strtolower($botMsg);
                    foreach ($botErrorPhrases as $errPhrase) {
                        if (str_contains($botLower, $errPhrase)) {
                            $log('error', "⚠️ Bot returned error response at Turn {$turnNum}!");
                            $errorCount++;
                            break;
                        }
                    }

                    // Log session state
                    $session = AiChatSession::where('phone_number', $this->simPhone)
                        ->where('status', 'active')
                        ->first();
                    if ($session) {
                        $stateInfo = "State: {$session->conversation_state}";
                        if ($session->lead_id) $stateInfo .= " | Lead: #{$session->lead_id}";
                        if ($session->quote_id) $stateInfo .= " | Quote: #{$session->quote_id}";
                        $log('info', "📊 {$stateInfo}");
                    }

                } catch (\Exception $e) {
                    $log('error', "❌ Bot crashed at Turn {$turnNum}: " . $e->getMessage());
                    $errorCount++;
                }

                // Small delay between turns
                if ($i < $questions->count() - 1) {
                    sleep(2);
                }
            }

            // Final summary
            if ($errorCount === 0) {
                $log('success', "🎉 All {$questions->count()} turns completed!");
            } else {
                $log('error', "⚠️ {$errorCount} error(s) in {$questions->count()} turns");
            }

            // Cleanup test data
            $this->cleanup();
            $log('success', '🧹 Test data cleaned.');

        } catch (\Exception $e) {
            $log('error', "Fatal error: " . $e->getMessage() . " on line " . $e->getLine());
        }
    }

    /**
     * Resolve dynamic placeholders in a question using the last bot response.
     */
    private function resolvePlaceholders(string $question): string
    {
        // {{yes}} / {{no}}
        if (trim(strtolower($question)) === '{{yes}}') return 'yes';
        if (trim(strtolower($question)) === '{{no}}') return 'no';

        // {{number:N}} — just return the number
        if (preg_match('/\{\{number:(\d+)\}\}/i', $question, $m)) {
            return $m[1];
        }

        // {{first}} — first item from last list
        if (str_contains(strtolower($question), '{{first}}')) {
            $first = $this->lastBotListItems[0] ?? '1';
            return str_ireplace('{{first}}', $first, $question);
        }

        // {{last}} — last item from last list
        if (str_contains(strtolower($question), '{{last}}')) {
            $last = end($this->lastBotListItems) ?: '1';
            return str_ireplace('{{last}}', $last, $question);
        }

        // {{all}} — all items combined
        if (str_contains(strtolower($question), '{{all}}')) {
            $all = !empty($this->lastBotListItems) ? implode(' and ', $this->lastBotListItems) : '1';
            return str_ireplace('{{all}}', $all, $question);
        }

        // {{pick:N}} — pick N random items from last list
        if (preg_match('/\{\{pick:(\d+)\}\}/i', $question, $m)) {
            $count = (int) $m[1];
            $items = $this->lastBotListItems;
            if (empty($items)) return '1';

            shuffle($items);
            $picked = array_slice($items, 0, min($count, count($items)));

            if (count($picked) === 1) {
                return $picked[0];
            }
            return implode(' and ', $picked);
        }

        // {{random}} — alias for {{pick:1}}
        if (str_contains(strtolower($question), '{{random}}')) {
            $items = $this->lastBotListItems;
            if (empty($items)) return '1';
            return $items[array_rand($items)];
        }

        return $question;
    }

    /**
     * Extract list items from bot response.
     * Handles: "1️⃣ Cabinet Handle (1 products)" → "Cabinet Handle"
     * Handles: "1. Cabinet Handle" → "Cabinet Handle"
     * Handles: "Black | Grey" (combo options) → ["Black", "Grey"]
     */
    private function extractListItems(string $botResponse): array
    {
        $items = [];

        // Pattern 1: Numbered emoji list "1️⃣ *Cabinet Handle* (1 products)"
        if (preg_match_all('/\d+️⃣\s*\*?([^*(]+?)\*?\s*\(?\d*\s*products?\)?/iu', $botResponse, $matches)) {
            foreach ($matches[1] as $m) {
                $clean = trim($m, " *\t\n\r");
                if (!empty($clean)) $items[] = $clean;
            }
        }

        // Pattern 2: Standard numbered "1. Cabinet Handle"
        if (empty($items) && preg_match_all('/^\s*\d+[\.\)]\s*\*?(.+?)\*?\s*$/mu', $botResponse, $matches)) {
            foreach ($matches[1] as $m) {
                $clean = trim(preg_replace('/\(.*\)/', '', $m));
                $clean = trim($clean, " *\t\n\r");
                if (!empty($clean)) $items[] = $clean;
            }
        }

        // Pattern 3: Pipe-separated options "Black | Grey | White"
        if (empty($items) && preg_match('/([A-Za-z0-9]+(?:\s*\|\s*[A-Za-z0-9]+)+)/', $botResponse, $m)) {
            $items = array_map('trim', explode('|', $m[1]));
        }

        // Pattern 4: Combo items "166mm | 266mm"
        if (empty($items) && preg_match_all('/(\d+\s*(?:mm|cm|kg|gm|ml|ltr|pcs?|pieces?|peace))/iu', $botResponse, $matches)) {
            $items = array_unique($matches[1]);
        }

        return array_values(array_filter($items));
    }

    /**
     * Public cleanup for external callers (step-by-step mode)
     */
    public function cleanupPublic(): void
    {
        $this->cleanup();
    }

    /**
     * Cleanup simulation data
     */
    private function cleanup(): void
    {
        $sessions = AiChatSession::where('phone_number', $this->simPhone)->get();
        foreach ($sessions as $session) {
            AiChatMessage::where('session_id', $session->id)->delete();
            AiProductSession::where('chat_session_id', $session->id)->delete();
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

