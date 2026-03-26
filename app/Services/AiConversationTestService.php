<?php

namespace App\Services;

use App\Models\AiChatSession;
use App\Models\AiChatMessage;
use App\Models\AiTokenLog;
use App\Models\AiBotTestQuestion;
use App\Models\Lead;
use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Support\Facades\Log;

class AiConversationTestService
{
    private int $companyId;
    private int $userId;
    private AIChatbotService $chatbotService;
    private string $simPhone = '919999999991'; // Different from old simulator

    public function __construct(int $companyId, int $userId)
    {
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->chatbotService = new AIChatbotService($companyId, $userId);
    }

    /**
     * Run conversation test with user-defined questions (streamed output).
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

            $log('info', '═══════════════════════════════════════');
            $log('info', '🗣️ AI Bot Conversation Test');
            $log('info', "📋 {$questions->count()} questions to test");
            $log('info', '═══════════════════════════════════════');

            $errorCount = 0;

            foreach ($questions as $i => $question) {
                $turnNum = $i + 1;
                $userMsg = trim($question->question);

                $log('info', '');
                $log('info', "── Turn {$turnNum}/{$questions->count()} ──");
                $log('user', $userMsg);

                try {
                    $botResult = $this->chatbotService->processMessage(
                        'conversation_tester_1',
                        $this->simPhone,
                        $userMsg
                    );

                    $botMsg = $botResult['response'] ?? 'No response generated.';
                    $log('bot', $botMsg);

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
                    $session = AiChatSession::where('phone_number', $this->simPhone)->first();
                    if ($session) {
                        $stateInfo = "State: {$session->conversation_state}";
                        if ($session->lead_id) $stateInfo .= " | Lead: #{$session->lead_id}";
                        if ($session->quote_id) $stateInfo .= " | Quote: #{$session->quote_id}";
                        $answers = $session->collected_answers ?? [];
                        if (!empty($answers)) {
                            $stateInfo .= " | Answers: " . count($answers);
                        }
                        $log('info', "   📊 {$stateInfo}");
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
            $log('info', '');
            $log('info', '═══════════════════════════════════════');
            if ($errorCount === 0) {
                $log('success', "🎉 All {$questions->count()} turns completed successfully!");
            } else {
                $log('error', "⚠️ {$errorCount} error(s) in {$questions->count()} turns");
            }

            // Show final session state
            $session = AiChatSession::where('phone_number', $this->simPhone)->first();
            if ($session) {
                $log('info', "Final State: {$session->conversation_state}");
                $answers = $session->collected_answers ?? [];
                if (!empty($answers)) {
                    $log('info', 'Collected Answers:');
                    foreach ($answers as $k => $v) {
                        $log('info', "   • {$k}: {$v}");
                    }
                }
                if ($session->quote_id) {
                    $quote = Quote::with('items')->find($session->quote_id);
                    if ($quote) {
                        $log('info', "Quote #{$quote->id}: {$quote->items->count()} item(s), Total: ₹" . number_format($quote->grand_total / 100, 2));
                    }
                }
            }

            // Cleanup test data
            $log('info', '');
            $log('info', 'Cleaning up test data...');
            $this->cleanup();
            $log('success', '✅ Test data cleaned up.');

        } catch (\Exception $e) {
            $log('error', "Fatal error: " . $e->getMessage() . " on line " . $e->getLine());
        }
    }

    /**
     * Cleanup simulation data
     */
    private function cleanup(): void
    {
        $session = AiChatSession::where('phone_number', $this->simPhone)->first();
        if ($session) {
            AiChatMessage::where('session_id', $session->id)->delete();
            if ($session->lead_id) {
                Lead::where('id', $session->lead_id)->delete();
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
