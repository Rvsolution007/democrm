<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AiTokenLog;
use App\Models\AiChatSession;
use App\Models\AiChatMessage;
use App\Models\AiBotTestQuestion;
use App\Models\Setting;
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
}
