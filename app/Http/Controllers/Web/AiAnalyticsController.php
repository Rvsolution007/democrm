<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AiTokenLog;
use App\Models\AiChatSession;
use App\Models\AiChatMessage;
use App\Models\AiBotTestQuestion;
use App\Models\AiProductSession;
use App\Models\Setting;
use App\Services\AIChatbotService;
use App\Services\AiConversationTestService;
use App\Services\AiBotDiagnosticService;
use Illuminate\Http\Request;

class AiAnalyticsController extends Controller
{
    /**
     * Token Analytics Dashboard
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $range = $request->get('range', '7');

        $startDate = match ($range) {
            '1' => now()->startOfDay(),
            '30' => now()->subDays(30)->startOfDay(),
            default => now()->subDays(7)->startOfDay(),
        };

        $logs = AiTokenLog::where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->get();

        // Summary stats
        $totalTokens = $logs->sum('total_tokens');
        $totalCalls = $logs->count();
        $avgPerMessage = $totalCalls > 0 ? round($totalTokens / $totalCalls) : 0;

        $tier1Logs = $logs->where('tier', 1);
        $tier2Logs = $logs->where('tier', 2);
        $tier1Calls = $tier1Logs->count();
        $tier2Calls = $tier2Logs->count();
        $tier1Avg = $tier1Calls > 0 ? round($tier1Logs->avg('total_tokens')) : 0;
        $tier2Avg = $tier2Calls > 0 ? round($tier2Logs->avg('total_tokens')) : 0;

        // Client-wise breakdown
        $clientStats = $logs->groupBy('phone_number')->map(function ($group, $phone) {
            return [
                'phone' => $phone ?? 'Unknown',
                'total_calls' => $group->count(),
                'tier1_calls' => $group->where('tier', 1)->count(),
                'tier2_calls' => $group->where('tier', 2)->count(),
                'total_tokens' => $group->sum('total_tokens'),
                'last_active' => $group->max('created_at'),
            ];
        })->sortByDesc('total_tokens')->values();

        return view('admin.ai-analytics.index', compact(
            'totalTokens', 'totalCalls', 'avgPerMessage',
            'tier1Calls', 'tier2Calls', 'tier1Avg', 'tier2Avg',
            'clientStats', 'range'
        ));
    }

    /**
     * Chat Sessions List
     */
    public function chats(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $sessions = AiChatSession::where('company_id', $companyId)
            ->withCount('messages')
            ->orderByDesc('last_message_at')
            ->paginate(20);

        return view('admin.ai-analytics.chats', compact('sessions'));
    }

    /**
     * Chat Detail — view messages for a session
     */
    public function chatDetail(int $id)
    {
        $session = AiChatSession::where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        $messages = AiChatMessage::where('session_id', $session->id)
            ->orderBy('created_at')
            ->get();

        return view('admin.ai-analytics.chat-detail', compact('session', 'messages'));
    }

    /**
     * AI Bot Tester Page — Two sections
     */
    public function tester()
    {
        $companyId = auth()->user()->company_id;
        $questions = AiBotTestQuestion::where('company_id', $companyId)
            ->orderBy('sort_order')
            ->get();

        return view('admin.ai-analytics.tester', compact('questions'));
    }

    // ═══════════════════════════════════════════════════════
    // SECTION 1: AI BOT CONVERSATION TEST
    // ═══════════════════════════════════════════════════════

    /**
     * Save test questions (AJAX)
     */
    public function saveTestQuestions(Request $request)
    {
        $request->validate([
            'questions' => 'required|array|min:1',
            'questions.*' => 'required|string|max:500',
        ]);

        $companyId = auth()->user()->company_id;

        // Delete existing and re-create
        AiBotTestQuestion::where('company_id', $companyId)->delete();

        foreach ($request->questions as $i => $q) {
            AiBotTestQuestion::create([
                'company_id' => $companyId,
                'question' => trim($q),
                'sort_order' => $i + 1,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => count($request->questions) . ' questions saved.',
        ]);
    }

    /**
     * Get saved test questions (AJAX)
     */
    public function getTestQuestions()
    {
        $companyId = auth()->user()->company_id;
        $questions = AiBotTestQuestion::where('company_id', $companyId)
            ->orderBy('sort_order')
            ->pluck('question');

        return response()->json(['questions' => $questions]);
    }

    /**
     * Run Conversation Test (Streamed Response)
     */
    public function runConversationTest()
    {
        $companyId = auth()->user()->company_id;
        $userId = auth()->id();

        return response()->stream(function () use ($companyId, $userId) {
            $service = new AiConversationTestService($companyId, $userId);
            $service->run(function ($type, $message) {
                echo json_encode(['type' => $type, 'message' => $message]) . "\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            });
        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type'  => 'text/event-stream',
        ]);
    }

    // ═══════════════════════════════════════════════════════
    // SECTION 2: AI BOT DIAGNOSTIC TESTER
    // ═══════════════════════════════════════════════════════

    /**
     * Run Diagnostic Test (Streamed Response)
     */
    public function runDiagnosticTest()
    {
        $companyId = auth()->user()->company_id;
        $userId = auth()->id();

        return response()->stream(function () use ($companyId, $userId) {
            $service = new AiBotDiagnosticService($companyId, $userId);
            $service->run(function ($type, $message) {
                echo json_encode(['type' => $type, 'message' => $message]) . "\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            });
        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type'  => 'text/event-stream',
        ]);
    }

    // ═══════════════════════════════════════════════════════
    // STEP-BY-STEP TEST (AJAX driven)
    // ═══════════════════════════════════════════════════════

    /**
     * Initialize a step-by-step test session (cleanup old data)
     */
    public function testStepInit()
    {
        $companyId = auth()->user()->company_id;
        $userId = auth()->id();

        $service = new AiConversationTestService($companyId, $userId);
        $service->cleanupPublic();

        return response()->json(['status' => 'ready', 'message' => 'Test session initialized.']);
    }

    /**
     * Send ONE message in step-by-step mode, return response + queue state
     */
    public function testStepSend(Request $request)
    {
        $request->validate(['message' => 'required|string|max:1000']);

        $companyId = auth()->user()->company_id;
        $userId = auth()->id();
        $simPhone = '919999999991';

        $service = new AIChatbotService($companyId, $userId);

        try {
            $result = $service->processMessage('conversation_tester_1', $simPhone, $request->message);
            $botMsg = $result['response'] ?? '';

            // Get route trace
            $routeTrace = $service->getRouteTrace();

            // Get session state
            $session = AiChatSession::where('phone_number', $simPhone)
                ->where('status', 'active')
                ->first();

            $state = null;
            $pendingQueue = 0;
            if ($session) {
                $state = $session->conversation_state;
                $pendingQueue = \App\Models\AiProductSession::where('chat_session_id', $session->id)
                    ->where('status', 'pending')
                    ->count();
            }

            // Extract list items for frontend to show
            $listItems = $this->extractListFromResponse($botMsg);

            return response()->json([
                'status' => 'ok',
                'bot_message' => $botMsg,
                'route' => $routeTrace ? implode(' → ', $routeTrace) : null,
                'session_state' => $state,
                'lead_id' => $session->lead_id ?? null,
                'quote_id' => $session->quote_id ?? null,
                'pending_queue' => $pendingQueue,
                'list_items' => $listItems,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'bot_message' => 'Error: ' . $e->getMessage(),
                'route' => null,
                'session_state' => null,
                'pending_queue' => 0,
                'list_items' => [],
            ], 500);
        }
    }

    /**
     * Cleanup step-by-step test data
     */
    public function testStepCleanup()
    {
        $companyId = auth()->user()->company_id;
        $userId = auth()->id();

        $service = new AiConversationTestService($companyId, $userId);
        $service->cleanupPublic();

        return response()->json(['status' => 'cleaned']);
    }

    /**
     * Extract list items from bot response for frontend
     */
    private function extractListFromResponse(string $response): array
    {
        $items = [];

        // Emoji numbered: 1️⃣ Cabinet Handle (1 products)
        if (preg_match_all('/\d+️⃣\s*\*?([^*(]+?)\*?\s*\(?\d*\s*products?\)?/iu', $response, $m)) {
            foreach ($m[1] as $v) { $c = trim($v, " *\t\n\r"); if ($c) $items[] = $c; }
        }

        // Standard numbered: 1. Item or 1) Item
        if (empty($items) && preg_match_all('/^\s*\d+[\.\)]\s*\*?(.+?)\*?\s*$/mu', $response, $m)) {
            foreach ($m[1] as $v) { $c = trim(preg_replace('/\(.*\)/', '', $v)); $c = trim($c, " *\t\n\r"); if ($c) $items[] = $c; }
        }

        // Pipe separated: Black | Grey
        if (empty($items) && preg_match('/([A-Za-z0-9]+(?:\s*\|\s*[A-Za-z0-9]+)+)/', $response, $m)) {
            $items = array_map('trim', explode('|', $m[1]));
        }

        // Units: 96mm | 128mm
        if (empty($items) && preg_match_all('/(\d+\s*(?:mm|cm|kg|gm|ml|ltr|pcs?|pieces?|peace))/iu', $response, $m)) {
            $items = array_values(array_unique($m[1]));
        }

        return $items;
    }
}

